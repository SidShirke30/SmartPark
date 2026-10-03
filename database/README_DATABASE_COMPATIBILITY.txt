SmartPark Database Compatibility

If your XAMPP MySQL/MariaDB reports a syntax error for:
  ALTER TABLE requests ADD COLUMN IF NOT EXISTS qr_scan_id ...

Do NOT run that statement directly. Use:
  database/migration_map_qr.sql

The migration in this package checks information_schema and adds each
required column/index only when it is missing. It also creates paid_at
and other payment columns before qr_scan_id, which avoids the
"Unknown column paid_at in ALTER TABLE" error on older installations.

For a new database, import database/smartpark_db.sql. The fresh schema
now defines the payment and QR columns directly inside CREATE TABLE requests.
