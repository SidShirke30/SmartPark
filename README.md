# SmartPark - Frontend / Backend Structure

## Folders
- `frontend/` - customer/admin pages, CSS, JavaScript, images and video.
- `backend/` - database connection, authentication, APIs, booking endpoint and Gemini AI endpoint.
- `database/` - MySQL SQL files.
- `.env` - local API/database environment settings.

## XAMPP
1. Put the `SmartPark_Perfect_Cinematic` folder inside `htdocs`.
2. Start Apache and MySQL.
3. Create/import the `smartpark_db` database using `database/smartpark_db.sql`.
4. Open `http://localhost/SmartPark_Perfect_Cinematic/frontend/`.
5. Gemini AI uses the backend endpoint at `backend/api/ai_assistant.php`; the API key is kept in `.env` and is not sent to browser JavaScript.

Do not commit `.env` to GitHub. Rotate the API key if it has been exposed publicly.


## Payment update
- Payment supports UPI/PhonePe and Cash on Payment.
- The previous transaction/reference ID field has been removed.
- UPI/PhonePe shows the supplied QR code and a configurable merchant UPI ID; customers may optionally enter their own UPI ID after paying.
- The scanner image is stored at `frontend/assets/payment/phonepe-scanner.png`.
- For an existing database, run `database/migration_payment_upi.sql`.
- Payment confirmation is demo-only; a production deployment should use a real UPI/payment gateway and server-side verification/webhooks.

## Navigation
Customer and admin inner pages now provide Home, Back and Next navigation controls.


## Merchant UPI ID
Open `frontend/payment.php` and replace `YOUR-UPI-ID@BANK` with the real UPI ID encoded/associated with your supplied QR code. Do not use a guessed UPI ID.

## Navigation
Customer payment pages use a direct `Dashboard` / `Home` link to `home.php`; admin pages use `admin.php`. Back/Next remain browser-history controls.


## Online Payment Upgrade
1. Import `database/migration_online_payment.sql` into `smartpark_db`.
2. Put Razorpay test/live credentials in `.env`:
   `RAZORPAY_KEY_ID=...`
   `RAZORPAY_KEY_SECRET=...`
3. Set `SMARTPARK_MERCHANT_UPI` to the UPI ID registered with your payment gateway.
4. Open the project through XAMPP, not by double-clicking PHP files.
5. Customer selects a parking space -> reservation cost is calculated from hours -> Payment page shows a dynamic UPI QR with the exact amount.
6. UPI/Card/Net Banking/Wallet payments open Razorpay Checkout. On success, the backend verifies the Razorpay signature, marks the reservation paid, stores transaction details and redirects to an automatic receipt.
7. Receipt can be printed or saved as PDF from the browser.
8. Cash remains available as a pending payment option.

The QR-only UPI URI is intentionally not treated as paid automatically. Real automatic confirmation requires a payment gateway/webhook; this upgrade uses Razorpay verification for that purpose.


## Custom payment scanner
The project includes the customer-provided SmartPark merchant QR at:
`frontend/assets/payment/smartpark-payment-scanner.jpg`

The payment page displays this scanner when UPI / QR is selected, plus a separate exact-amount UPI QR generated from the reservation total. A static merchant QR cannot itself be edited per reservation, so automatic payment verification remains through the configured Razorpay gateway.

## Live GPS + QR Payment Upgrade
This build keeps the corrected FIXED registration/profile/payment/admin code and adds the upgraded live map, road routing, QR camera/image scanning, server-side QR amount validation, and map/location database tables.

For an existing `smartpark_db`, run `database/migration_map_qr.sql` in phpMyAdmin. The routing service uses Google Routes when configured and falls back to OSRM/OpenStreetMap. QR scanning supports UPI payment payloads and SmartPark QR payloads; the server verifies merchant and reservation amount before enabling the UPI payment link.


### Expanded Maharashtra locations

Run `database/migration_maharashtra_locations.sql` once on an existing `smartpark_db` to add the expanded Maharashtra parking network, including Shirdi, Mumbai, Thane, Navi Mumbai, Pune, Nashik, Nagpur, Sambhajinagar and other cities/localities. Fresh installs already include these locations in `database/smartpark_db.sql`.


## Database compatibility
The database migrations are compatible with older XAMPP MySQL/MariaDB installations. Do not manually run `ADD COLUMN IF NOT EXISTS`; use the supplied migration files.
