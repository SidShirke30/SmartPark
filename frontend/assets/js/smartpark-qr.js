/* SmartPark QR Scanner + exact amount parser/validator */
(function () {
    'use strict';

    const config = window.SMARTPARK_QR_CONFIG || {};
    let scanner = null;

    const $ = id => document.getElementById(id);

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[ch]));
    }

    function setStatus(message, type) {
        const el = $('qrScanStatus');
        if (!el) return;
        el.textContent = message;
        el.className = 'qr-scan-status ' + (type || 'info');
    }

    function parseUpiPayload(raw) {
        const value = String(raw || '').trim();
        if (!/^upi:\/\/pay(?:\?|$)/i.test(value)) {
            throw new Error('This QR is not a UPI payment QR.');
        }

        let url;
        try {
            url = new URL(value);
        } catch (error) {
            throw new Error('The QR payment data is malformed.');
        }

        const pa = (url.searchParams.get('pa') || '').trim();
        const pn = (url.searchParams.get('pn') || '').trim();
        const am = (url.searchParams.get('am') || '').trim();
        const cu = (url.searchParams.get('cu') || 'INR').trim().toUpperCase();
        const tr = (url.searchParams.get('tr') || '').trim();
        const tn = (url.searchParams.get('tn') || '').trim();

        if (!pa || !/^[^\s@]+@[^\s@]+$/.test(pa)) throw new Error('QR does not contain a valid merchant UPI ID.');
        if (!am || !/^\d+(?:\.\d{1,2})?$/.test(am)) throw new Error('QR does not contain a valid parking amount.');
        if (cu !== 'INR') throw new Error('Only INR payment QR codes are supported.');

        const amount = Number(am);
        if (!Number.isFinite(amount) || amount <= 0 || amount > 1000000) throw new Error('QR contains an invalid amount.');

        return {
            format: 'upi',
            merchant_upi: pa,
            merchant_name: pn,
            amount: Number(amount.toFixed(2)),
            currency: cu,
            transaction_ref: tr,
            note: tn,
            raw: value
        };
    }

    function parseSmartParkPayload(raw) {
        const value = String(raw || '').trim();
        if (!/^SMARTPARK:\/\/PAY(?:\?|$)/i.test(value)) return null;
        let url;
        try { url = new URL(value.replace(/^SMARTPARK:/i, 'http:')); }
        catch (e) { throw new Error('SmartPark QR data is malformed.'); }

        const amountRaw = (url.searchParams.get('amount') || '').trim();
        const parkingId = (url.searchParams.get('parking_id') || '').trim();
        const requestId = (url.searchParams.get('request_id') || '').trim();
        const expires = Number(url.searchParams.get('expires') || 0);
        const sig = (url.searchParams.get('sig') || '').trim();

        if (!/^\d+(?:\.\d{1,2})?$/.test(amountRaw)) throw new Error('SmartPark QR does not contain a valid amount.');
        const amount = Number(amountRaw);
        if (!Number.isFinite(amount) || amount <= 0) throw new Error('SmartPark QR contains an invalid amount.');
        if (expires && expires < Math.floor(Date.now() / 1000)) throw new Error('This SmartPark QR has expired.');
        if (!parkingId && !requestId) throw new Error('SmartPark QR is missing its parking reference.');

        return {
            format: 'smartpark',
            amount: Number(amount.toFixed(2)),
            parking_id: parkingId,
            request_id: requestId,
            expires,
            signature: sig,
            raw: value
        };
    }

    function parseQrPayload(raw) {
        const smartPark = parseSmartParkPayload(raw);
        if (smartPark) return smartPark;
        return parseUpiPayload(raw);
    }

    function renderQrPreview() {
        const target = $('smartparkGeneratedQr');
        if (!target || typeof QRCode === 'undefined') return;
        target.innerHTML = '';
        const payload = config.generatedUpiPayload || '';
        if (!payload) return;
        new QRCode(target, {
            text: payload,
            width: 220,
            height: 220,
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    async function stopScanner() {
        if (!scanner) return;
        try { await scanner.stop(); } catch (_) {}
        try { await scanner.clear(); } catch (_) {}
        scanner = null;
        $('qrReader')?.classList.add('hidden');
    }

    async function startScanner() {
        const reader = $('qrReader');
        if (!reader) return;
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('QR scanner library could not be loaded. Check your internet connection.', 'error');
            return;
        }

        await stopScanner();
        reader.classList.remove('hidden');
        setStatus('Starting camera… allow camera permission when prompted.', 'info');

        try {
            scanner = new Html5Qrcode('qrReader');
            const cameras = await Html5Qrcode.getCameras();
            if (!cameras.length) throw new Error('No camera was found on this device.');
            const preferred = cameras.find(c => /back|rear|environment/i.test(c.label)) || cameras[0];

            await scanner.start(
                preferred.id,
                { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 },
                decodedText => handleScannedPayload(decodedText),
                () => {}
            );
            setStatus('Camera is ready. Point it at the parking QR.', 'success');
        } catch (error) {
            console.error(error);
            reader.classList.add('hidden');
            const message = /permission|notallowed|secure context|https/i.test(String(error))
                ? 'Camera access was blocked. Use HTTPS on mobile and allow camera permission.'
                : (error.message || 'Could not start the QR scanner.');
            setStatus(message, 'error');
        }
    }

    async function handleScannedPayload(raw) {
        await stopScanner();
        setStatus('QR detected. Parsing and verifying the parking amount…', 'info');

        let qr;
        try {
            qr = parseQrPayload(raw);
        } catch (error) {
            showValidationError(error.message || 'Invalid QR.');
            return;
        }

        if ($('scannedAmount')) $('scannedAmount').textContent = '₹' + qr.amount.toFixed(2);
        if ($('scannedMerchant')) $('scannedMerchant').textContent = qr.merchant_name || qr.merchant_upi || 'SmartPark QR';
        if ($('scannedUpi')) $('scannedUpi').textContent = qr.merchant_upi || 'SmartPark signed QR';
        if ($('scannedRef')) $('scannedRef').textContent = qr.transaction_ref || qr.request_id || '—';

        try {
            const fd = new FormData();
            fd.append('request_id', String(config.requestId || ''));
            fd.append('qr_payload', qr.raw);
            const response = await fetch('../backend/api/validate_qr.php', { method: 'POST', body: fd, cache: 'no-store' });
            const result = await response.json();
            if (!result.ok) throw new Error(result.message || 'QR validation failed.');

            const verifiedAmount = Number(result.amount);
            if (!Number.isFinite(verifiedAmount)) throw new Error('Server returned an invalid amount.');
            if (Math.abs(verifiedAmount - qr.amount) > 0.01) throw new Error('QR amount verification mismatch.');

            if ($('verifiedAmount')) $('verifiedAmount').textContent = '₹' + verifiedAmount.toFixed(2);
            if ($('qrVerification')) $('qrVerification').className = 'qr-verification verified';
            if ($('qrVerification')) $('qrVerification').textContent = '✓ QR verified — amount matches this reservation.';
            if ($('upiPayBtn')) $('upiPayBtn').classList.remove('disabled');
            if ($('paymentAmount')) $('paymentAmount').value = verifiedAmount.toFixed(2);
            setStatus('QR verified successfully. The amount has been pre-filled.', 'success');
        } catch (error) {
            showValidationError(error.message || 'The server could not validate this QR.');
        }
    }

    function showValidationError(message) {
        if ($('qrVerification')) {
            $('qrVerification').className = 'qr-verification rejected';
            $('qrVerification').textContent = '✕ ' + message;
        }
        if ($('paymentAmount')) $('paymentAmount').value = '';
        if ($('upiPayBtn')) $('upiPayBtn').classList.add('disabled');
        setStatus(message, 'error');
    }

    $('startQrScannerBtn')?.addEventListener('click', startScanner);
    $('stopQrScannerBtn')?.addEventListener('click', stopScanner);
    $('scanQrImage')?.addEventListener('change', async event => {
        const file = event.target.files?.[0];
        if (!file || typeof Html5Qrcode === 'undefined') return;
        try {
            const temp = new Html5Qrcode('qrReader');
            const decoded = await temp.scanFile(file, true);
            try { await temp.clear(); } catch (_) {}
            await handleScannedPayload(decoded);
        } catch (error) {
            showValidationError('Could not read a QR code from that image.');
        } finally {
            event.target.value = '';
        }
    });

    renderQrPreview();
    window.SmartParkQR = { parseQrPayload, startScanner, stopScanner };
})();
