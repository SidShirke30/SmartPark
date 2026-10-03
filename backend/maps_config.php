<?php
// SmartPark map stack:
//   - Leaflet 1.9.4 renders the interactive map.
//   - OpenStreetMap provides map tiles.
//   - backend/api/route.php calls Google Routes API when configured,
//     and falls back to OSRM/OpenStreetMap routing if Google is unavailable.
const SMARTPARK_MAP_PROVIDER = 'Leaflet + OpenStreetMap';
