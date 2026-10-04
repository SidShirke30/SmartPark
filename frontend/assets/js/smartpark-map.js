/* SmartPark Live Map + Routing
 * Frontend: Leaflet 1.9.4 + browser Geolocation API
 * Backend: backend/api/parkings.php, route.php, location_ping.php
 */
(function () {
    'use strict';

    const config = window.SMARTPARK_MAP_CONFIG || {};
    const mapEl = document.getElementById('smartpark-map');
    if (!mapEl || typeof L === 'undefined') return;

    const map = L.map(mapEl, { zoomControl: false }).setView(
        [Number(config.centerLat || 19.7515), Number(config.centerLng || 75.7139)],
        7
    );

    L.control.zoom({ position: 'bottomright' }).addTo(map);
    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }
    ).addTo(map);

    const parkingLayer = L.layerGroup().addTo(map);
    const routeLayers = L.layerGroup().addTo(map);

    let userMarker = null;
    let accuracyCircle = null;
    let locationWatchId = null;
    let currentLocation = null;
    let lastSentLocation = null;
    let routeMode = null;
    let origin = null;
    let destination = null;
    let allParkings = [];
    let selectedParking = null;

    const $ = id => document.getElementById(id);

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[ch]));
    }

    function setStatus(message, type) {
        const el = $('mapStatus');
        if (!el) return;
        el.textContent = message;
        el.className = 'map-status ' + (type || 'info');
    }

    function formatDistance(meters) {
        if (!Number.isFinite(Number(meters))) return '—';
        const m = Number(meters);
        return m >= 1000 ? (m / 1000).toFixed(1) + ' km' : Math.round(m) + ' m';
    }

    function formatDuration(value) {
        if (value == null) return '—';
        if (typeof value === 'number') {
            value = value + 's';
        }
        const match = String(value).match(/(?:(\d+)h)?\s*(?:(\d+)m)?\s*(?:(\d+)s)?/i);
        if (!match) return String(value);
        const h = Number(match[1] || 0);
        const m = Number(match[2] || 0);
        if (h) return `${h}h ${m}m`;
        if (m) return `${m} min`;
        const s = Number(match[3] || 0);
        return `${Math.max(1, Math.round(s))} sec`;
    }

    function setRoutePoint(label, point) {
        if (label === 'from') origin = point;
        else destination = point;
        updatePointLabels();
    }

    function updatePointLabels() {
        const from = $('routeFromValue');
        const to = $('routeToValue');
        if (from) from.textContent = origin ? `${origin.lat.toFixed(5)}, ${origin.lng.toFixed(5)}` : 'Not selected';
        if (to) to.textContent = destination ? `${destination.lat.toFixed(5)}, ${destination.lng.toFixed(5)}` : 'Not selected';
    }

    function markerColor(slots) {
        const n = Number(slots || 0);
        if (n > 30) return '#3b82f6';
        if (n > 10) return '#2563eb';
        if (n > 0) return '#64748b';
        return '#9ca3af';
    }

    function openNavigation(lat, lng) {
        const url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(lat + ',' + lng)}&travelmode=driving`;
        window.open(url, '_blank', 'noopener');
    }

    window.smartParkNavigate = function (lat, lng) {
        openNavigation(Number(lat), Number(lng));
    };

    function selectParking(p) {
        selectedParking = p;
        destination = { lat: Number(p.latitude), lng: Number(p.longitude) };
        const select = $('parkingDestination');
        if (select) select.value = String(p.id);
        updatePointLabels();
        setStatus(`Destination set to ${p.name}.`, 'success');
    }

    function addParkingMarker(p) {
        const lat = Number(p.latitude);
        const lng = Number(p.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

        const c = markerColor(p.remaining_slots);
        const popup = `
            <div class="sp-map-popup">
                <strong>${escapeHtml(p.name)}</strong>
                <div>${escapeHtml(p.city || p.location || '')}</div>
                <div>${escapeHtml(p.parking_type || 'Open Air')} · ${escapeHtml(p.remaining_slots)} spaces</div>
                <div>₹${escapeHtml(p.price)}/hour${Number(p.ev_charging) === 1 ? ' · ⚡ EV' : ''}</div>
                <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.smartParkSetDestinationById(${Number(p.id)})">ROUTE HERE</button>
                    <button type="button" class="btn btn-light btn-sm" onclick="window.smartParkNavigate(${lat},${lng})">OPEN NAVIGATION</button>
                </div>
            </div>`;

        L.circleMarker([lat, lng], {
            radius: 9,
            color: '#fff',
            weight: 2,
            fillColor: c,
            fillOpacity: .95
        }).bindPopup(popup).on('click', () => selectParking(p)).addTo(parkingLayer);
    }

    window.smartParkSetDestination = function (p) {
        selectParking(p);
        if (currentLocation) {
            origin = { ...currentLocation };
            updatePointLabels();
            calculateRoute();
        }
    };

    window.smartParkSetDestinationById = function (id) {
        const p = allParkings.find(item => Number(item.id) === Number(id));
        if (p) window.smartParkSetDestination(p);
    };

    window.smartParkFocusParkingById = function (id) {
        const p = allParkings.find(item => Number(item.id) === Number(id));
        if (!p) return;
        const lat = Number(p.latitude), lng = Number(p.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        map.setView([lat, lng], 16, {animate:true});
        selectParking(p);
        parkingLayer.eachLayer(layer => {
            const ll = layer.getLatLng ? layer.getLatLng() : null;
            if (ll && Math.abs(ll.lat-lat)<0.00001 && Math.abs(ll.lng-lng)<0.00001) layer.openPopup();
        });
    };

    async function loadParkings() {
        try {
            const response = await fetch('/backend/api/parkings.php', { cache: 'no-store' });
            const data = await response.json();
            if (!data.ok) throw new Error(data.message || 'Unable to load parking locations.');
            allParkings = Array.isArray(data.parkings) ? data.parkings : [];
            parkingLayer.clearLayers();
            const select = $('parkingDestination');
            if (select) {
                select.innerHTML = '<option value="">Choose a city / parking location…</option>';
            }
            const cityGroups = {};
            allParkings.forEach(p => {
                addParkingMarker(p);
                const city = (p.city || p.location || 'Other').trim() || 'Other';
                (cityGroups[city] ||= []).push(p);
            });
            if (select) {
                Object.keys(cityGroups).sort((a,b)=>a.localeCompare(b)).forEach(city => {
                    const group = document.createElement('optgroup');
                    group.label = city + ' (' + cityGroups[city].length + ')';
                    cityGroups[city].forEach(p => {
                        const option = document.createElement('option');
                        option.value = p.id;
                        option.textContent = `${p.name} · ${p.remaining_slots} spaces · ₹${p.price}/hr`;
                        group.appendChild(option);
                    });
                    select.appendChild(group);
                });
            }
            if (allParkings.length) setStatus(`${allParkings.length} parking locations loaded.`, 'success');
            setTimeout(() => map.invalidateSize(), 250);
        } catch (error) {
            console.error(error);
            setStatus(error.message || 'Could not load parking locations.', 'error');
        }
    }

    function startLiveLocation() {
        if (!navigator.geolocation) {
            setStatus('Live location is not supported by this browser.', 'error');
            return;
        }

        setStatus('Requesting live location permission…', 'info');
        if (locationWatchId !== null) navigator.geolocation.clearWatch(locationWatchId);

        locationWatchId = navigator.geolocation.watchPosition(
            position => {
                const lat = Number(position.coords.latitude);
                const lng = Number(position.coords.longitude);
                const accuracy = Number(position.coords.accuracy || 0);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                currentLocation = {
                    lat,
                    lng,
                    accuracy,
                    heading: position.coords.heading,
                    speed: position.coords.speed
                };

                if (!userMarker) {
                    userMarker = L.marker([lat, lng]).addTo(map).bindPopup('You are here');
                    accuracyCircle = L.circle([lat, lng], {
                        radius: accuracy,
                        color: '#0ea5e9',
                        fillColor: '#0ea5e9',
                        fillOpacity: 0.10
                    }).addTo(map);
                    map.setView([lat, lng], Math.max(map.getZoom(), 15));
                } else {
                    userMarker.setLatLng([lat, lng]);
                    accuracyCircle.setLatLng([lat, lng]);
                    accuracyCircle.setRadius(accuracy);
                }

                if ($('locationCoords')) $('locationCoords').textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                if ($('locationAccuracy')) $('locationAccuracy').textContent = `±${Math.round(accuracy)} m`;
                setStatus(`Live location active · accuracy ±${Math.round(accuracy)} m`, 'success');

                const movedEnough = !lastSentLocation || L.latLng(lat, lng).distanceTo(L.latLng(lastSentLocation.lat, lastSentLocation.lng)) >= 15;
                const timeEnough = !lastSentLocation || (Date.now() - lastSentLocation.sentAt) >= 10000;
                if (movedEnough || timeEnough) {
                    sendLocationToServer(currentLocation);
                }
            },
            error => {
                const messages = {
                    1: 'Location permission was denied. You can still choose route points manually.',
                    2: 'Your current location is temporarily unavailable.',
                    3: 'Location request timed out. Retrying…'
                };
                setStatus(messages[error.code] || 'Unable to determine your current location.', 'error');
                if ($('locationAccuracy')) $('locationAccuracy').textContent = 'Unavailable';
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 2000 }
        );
    }

    async function sendLocationToServer(location) {
        lastSentLocation = { ...location, sentAt: Date.now() };
        try {
            const fd = new FormData();
            fd.append('latitude', String(location.lat));
            fd.append('longitude', String(location.lng));
            fd.append('accuracy', String(location.accuracy ?? ''));
            fd.append('heading', String(location.heading ?? ''));
            fd.append('speed', String(location.speed ?? ''));
            await fetch('../backend/api/location_ping.php', { method: 'POST', body: fd, cache: 'no-store' });
        } catch (error) {
            console.warn('Location sync failed:', error);
        }
    }

    function clearRoutes() {
        routeLayers.clearLayers();
    }

    async function calculateRoute() {
        if (!origin || !destination) {
            setStatus('Select both a start point and destination first.', 'error');
            return;
        }

        clearRoutes();
        setStatus('Calculating road routes…', 'info');
        const btn = $('calculateRouteBtn');
        if (btn) btn.disabled = true;

        try {
            const response = await fetch('../backend/api/route.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ origin, destination })
            });
            const data = await response.json();
            if (!data.ok) throw new Error(data.message || 'No route was found.');

            const routes = Array.isArray(data.routes) ? data.routes : [];
            if (!routes.length) throw new Error('No road route was found.');

            let firstBounds = null;
            routes.forEach((route, index) => {
                const coords = Array.isArray(route.coordinates) ? route.coordinates : [];
                if (coords.length < 2) return;
                const layer = L.polyline(coords, {
                    weight: index === 0 ? 6 : 4,
                    opacity: index === 0 ? .92 : .45,
                    dashArray: index === 0 ? null : '8 8'
                }).addTo(routeLayers);
                if (index === 0) firstBounds = layer.getBounds();
            });

            if (firstBounds && firstBounds.isValid()) map.fitBounds(firstBounds, { padding: [35, 35] });

            const main = routes[0];
            if ($('routeDistance')) $('routeDistance').textContent = formatDistance(main.distance_m);
            if ($('routeTime')) $('routeTime').textContent = formatDuration(main.duration_seconds);
            if ($('routeProvider')) $('routeProvider').textContent = `${data.provider || 'Routing service'} · ${routes.length} route${routes.length > 1 ? 's' : ''}`;
            if ($('routeSummary')) $('routeSummary').classList.remove('hidden');
            setStatus(`Route ready: ${formatDistance(main.distance_m)} · ${formatDuration(main.duration_seconds)}.`, 'success');
        } catch (error) {
            console.error(error);
            setStatus(error.message || 'Routing failed. Check your internet connection.', 'error');
            if ($('routeSummary')) $('routeSummary').classList.add('hidden');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    map.on('click', event => {
        const point = { lat: Number(event.latlng.lat), lng: Number(event.latlng.lng) };
        if (routeMode === 'from') {
            setRoutePoint('from', point);
            routeMode = null;
            setStatus('Start point selected. Now choose the destination.', 'success');
        } else if (routeMode === 'to') {
            setRoutePoint('to', point);
            routeMode = null;
            setStatus('Destination selected. Press GET ROUTE.', 'success');
        }
    });

    $('useMyLocationBtn')?.addEventListener('click', () => {
        if (!currentLocation) {
            startLiveLocation();
            setTimeout(() => {
                if (currentLocation) {
                    origin = { lat: currentLocation.lat, lng: currentLocation.lng };
                    updatePointLabels();
                }
            }, 1500);
        } else {
            origin = { lat: currentLocation.lat, lng: currentLocation.lng };
            updatePointLabels();
            map.setView([currentLocation.lat, currentLocation.lng], 16);
            setStatus('Start point set to your live location.', 'success');
        }
    });

    $('setFromBtn')?.addEventListener('click', () => {

        if (!navigator.geolocation) {
            setStatus(
                'Your browser does not support GPS location.',
                'error'
            );
            alert('Location is not supported by your browser.');
            return;
        }

        setStatus('Checking location permission...', 'info');

        navigator.geolocation.getCurrentPosition(

            function (position) {

                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                currentLocation = { lat, lng };
                origin = { lat, lng };

                updatePointLabels();

                map.setView([lat, lng], 16, { animate: true });

                setStatus(
                    'Your current GPS location is set as the starting point.',
                    'success'
                );

            },

            function (error) {

                let message = '';

                switch (error.code) {

                    case error.PERMISSION_DENIED:
                        message = 'Location permission denied. Please allow location access in your browser settings.';
                        break;

                    case error.POSITION_UNAVAILABLE:
                        message = 'Your GPS location is currently unavailable. Please enable location services.';
                        break;

                    case error.TIMEOUT:
                        message = 'Location request timed out. Please try again.';
                        break;

                    default:
                        message = 'Unable to get your location.';
                }

                setStatus(message, 'error');
                alert(message);

            },

            {
                enableHighAccuracy: true,
                timeout: 60000,
                maximumAge: 30000
            }

        );

    });

    $('setToBtn')?.addEventListener('click', () => {
        routeMode = 'to';
        setStatus('Tap any point on the map to set the route destination.', 'info');
    });

    $('calculateRouteBtn')?.addEventListener('click', calculateRoute);
    $('clearRouteBtn')?.addEventListener('click', () => {
        clearRoutes();
        origin = null;
        destination = null;
        selectedParking = null;
        const select = $('parkingDestination');
        if (select) select.value = '';
        if ($('routeSummary')) $('routeSummary').classList.add('hidden');
        updatePointLabels();
        setStatus('Route cleared.', 'info');
    });

    $('parkingDestination')?.addEventListener('change', event => {
        const p = allParkings.find(item => String(item.id) === String(event.target.value));
        if (p) selectParking(p);
    });

    $('navigateBtn')?.addEventListener('click', () => {
        if (!destination) {
            setStatus('Choose a destination first.', 'error');
            return;
        }
        openNavigation(destination.lat, destination.lng);
    });

    updatePointLabels();
    loadParkings();
    startLiveLocation();

    window.smartParkMap = map;
    window.smartParkMapState = () => ({ currentLocation, origin, destination, selectedParking });
})();
