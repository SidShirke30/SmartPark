-- SmartPark QR migration compatibility fix
-- Use this file on an EXISTING smartpark_db when your MySQL/MariaDB
-- rejects: ALTER TABLE ... ADD COLUMN IF NOT EXISTS ...
USE `smartpark_db`;

-- Make sure the payment columns that QR fields depend on exist.
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE requests ADD COLUMN upi_id VARCHAR(255) DEFAULT NULL AFTER payment_method',
  'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'upi_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE requests ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER upi_id',
  'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'paid_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add the QR columns only when they are missing.
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE requests ADD COLUMN qr_scan_id BIGINT DEFAULT NULL AFTER paid_at',
  'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'qr_scan_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE requests ADD COLUMN qr_scanned_amount DECIMAL(10,2) DEFAULT NULL AFTER qr_scan_id',
  'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'qr_scanned_amount');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE requests ADD COLUMN qr_transaction_ref VARCHAR(255) DEFAULT NULL AFTER qr_scanned_amount',
  'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'qr_transaction_ref');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE requests ADD COLUMN qr_scanned_at DATETIME DEFAULT NULL AFTER qr_transaction_ref',
  'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'qr_scanned_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add the QR index only when it is missing.
SET @sql = (SELECT IF(COUNT(*) = 0,
  'CREATE INDEX idx_requests_qr_scan ON requests(qr_scan_id)',
  'SELECT 1')
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND INDEX_NAME = 'idx_requests_qr_scan');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'QR database migration completed successfully.' AS result;
