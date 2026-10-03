# SmartPark Map + QR Payment Upgrade

This upgrade adds live GPS, point-to-point road routing, parking navigation, camera QR scanning, exact amount extraction, server-side QR amount validation, and scan history to the existing PHP/XAMPP SmartPark project.

## Stack

- PHP + MySQL/MariaDB + XAMPP
- Vanilla JavaScript + HTML/CSS
- Leaflet 1.9.4 + OpenStreetMap tiles
- Google Routes API (preferred when configured) with OSRM/OpenStreetMap routing fallback
- html5-qrcode for camera/image QR scanning
- QRCode.js for rendering the reservation-specific UPI QR
- Razorpay for the existing server-verified online payment method

## 1. Database

For an existing FIXED SmartPark database, open phpMyAdmin and run:

`database/migration_map_qr.sql`

For a brand-new database, `database/smartpark_db.sql` now also contains the new map/QR tables and request fields.

## 2. Environment

Edit `.env`:

```env
GEMINI_API_KEY=YOUR_GEMINI_KEY
RAZORPAY_KEY_ID=YOUR_RAZORPAY_KEY_ID
RAZORPAY_KEY_SECRET=YOUR_RAZORPAY_KEY_SECRET
SMARTPARK_MERCHANT_UPI=yourmerchant@upi
SMARTPARK_MERCHANT_NAME=SmartPark

ROUTING_PROVIDER=google
GOOGLE_MAPS_API_KEY=YOUR_GOOGLE_ROUTES_API_KEY
OSRM_BASE_URL=https://router.project-osrm.org

SMARTPARK_QR_SECRET=YOUR_LONG_RANDOM_SECRET
```

This merged build keeps the existing FIXED project `.env` values and adds the map/QR variables. Keep `.env` private. A Google Routes API key is not required for the fallback OSRM mode; set `ROUTING_PROVIDER=osrm` when you want to use the fallback directly.

## 3. Google Routes API

The backend endpoint is:

`backend/api/route.php`

It sends an HTTP POST request to:

`https://routes.googleapis.com/directions/v2:computeRoutes`

Only the required route fields are requested: distance, duration, encoded polyline and route labels. The frontend decodes the polyline and renders it in Leaflet.

## 4. Live GPS

The map uses the browser Geolocation API and watches the device position. GPS updates are shown with a marker and accuracy circle. The newest points are optionally stored in `user_locations` and expire after 15 minutes.

The location endpoint is:

`backend/api/location_ping.php`

For privacy and performance, the frontend sends updates only after a meaningful movement or after a time interval instead of writing every GPS frame to MySQL.

## 5. Map controls

`frontend/home.php` now includes:

- My Location
- Set From on map
- Set To on map
- Parking destination dropdown
- Get Road Route
- Clear route
- Open Navigation
- Distance and ETA summary
- Alternative route rendering when available

The browser can also hand the destination to Google Maps using a standard directions URL.

## 6. QR scanning

`frontend/payment.php` now supports:

- Camera QR scan
- QR image upload
- UPI payload parsing
- Merchant validation against `SMARTPARK_MERCHANT_UPI`
- INR-only validation
- Exact amount extraction
- Reservation amount comparison
- Read-only amount field populated from the verified QR
- Scan history in `qr_scans`
- UPI deep-link generation after QR verification
- A reservation-specific exact-amount QR rendered with QRCode.js

The validation endpoint is:

`backend/api/validate_qr.php`

The backend, not the browser, remains the final authority for the reservation amount.

## 7. QR format expected from parking meters/attendants

For standard UPI, the QR should encode a URI similar to:

```text
upi://pay?pa=yourmerchant@upi&pn=SmartPark&am=60.00&cu=INR&tr=SP-123&tn=Parking
```

The critical field for automatic amount extraction is `am`.

The backend rejects a QR when:

- it is not a supported QR format
- merchant UPI does not match the configured SmartPark merchant
- currency is not INR
- amount is missing or malformed
- amount does not match the reservation
- a signed SmartPark QR is expired or has an invalid signature

## 8. Camera permissions / mobile testing

Camera and browser location features need a secure origin on real mobile devices. For local XAMPP development, use localhost on the development machine or serve the site over HTTPS when testing from another device on the network.

## 9. Payment confirmation

QR parsing and QR validation are not proof that a payment succeeded. The existing Razorpay flow still performs server-side signature verification. A production UPI deployment should additionally connect the merchant/payment provider to a webhook or payment-status API before changing the reservation to `paid`.

## 10. Important security note

Do not upload `.env` with real API keys to GitHub or share the ZIP publicly. The delivered copy replaces the previously embedded Gemini key with a placeholder. Any previously exposed API key should be rotated before production use.
