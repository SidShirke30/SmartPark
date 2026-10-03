-- SmartPark Map + QR Payment Upgrade
-- Compatible with older MySQL/MariaDB versions that do not support
-- Does not use unsupported MySQL IF NOT EXISTS DDL.
USE `smartpark_db`;

CREATE TABLE IF NOT EXISTS user_locations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    customer VARCHAR(255) NOT NULL,
    session_id VARCHAR(128) DEFAULT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    accuracy_m DECIMAL(10,2) DEFAULT NULL,
    heading DECIMAL(7,2) DEFAULT NULL,
    speed_mps DECIMAL(10,2) DEFAULT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    INDEX idx_customer_time (customer, recorded_at),
    INDEX idx_location (latitude, longitude),
    INDEX idx_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS parking_qr_codes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    parking_id INT NOT NULL,
    qr_code_ref VARCHAR(100) NOT NULL UNIQUE,
    merchant_upi VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    INDEX idx_parking_active (parking_id, active),
    CONSTRAINT fk_qr_parking FOREIGN KEY (parking_id) REFERENCES parkings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qr_scans (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    customer VARCHAR(255) NOT NULL,
    qr_code_id BIGINT DEFAULT NULL,
    merchant_upi VARCHAR(255) DEFAULT NULL,
    scanned_amount DECIMAL(10,2) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT 'INR',
    transaction_ref VARCHAR(255) DEFAULT NULL,
    raw_payload TEXT DEFAULT NULL,
    payload_hash CHAR(64) DEFAULT NULL,
    scan_status VARCHAR(30) NOT NULL DEFAULT 'scanned',
    error_message VARCHAR(255) DEFAULT NULL,
    scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request (request_id),
    INDEX idx_customer (customer),
    INDEX idx_scan_time (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The request/payment columns are ensured first because qr_scan_id is placed
-- after paid_at. This also fixes older databases that do not yet have paid_at.
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE requests ADD COLUMN upi_id VARCHAR(255) DEFAULT NULL AFTER payment_method',
    'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'upi_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE requests ADD COLUMN razorpay_order_id VARCHAR(80) DEFAULT NULL AFTER upi_id',
    'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'razorpay_order_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE requests ADD COLUMN razorpay_payment_id VARCHAR(80) DEFAULT NULL AFTER razorpay_order_id',
    'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'razorpay_payment_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE requests ADD COLUMN razorpay_signature VARCHAR(255) DEFAULT NULL AFTER razorpay_payment_id',
    'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'razorpay_signature');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE requests ADD COLUMN receipt_no VARCHAR(50) DEFAULT NULL AFTER razorpay_signature',
    'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'receipt_no');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE requests ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER receipt_no',
    'SELECT 1')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'paid_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

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

-- Add indexes only when missing.
SET @sql = (SELECT IF(COUNT(*) = 0,
    'CREATE INDEX idx_requests_qr_scan ON requests(qr_scan_id)',
    'SELECT 1')
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND INDEX_NAME = 'idx_requests_qr_scan');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'CREATE INDEX idx_requests_rzp_order ON requests(razorpay_order_id)',
    'SELECT 1')
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND INDEX_NAME = 'idx_requests_rzp_order');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'CREATE INDEX idx_requests_rzp_payment ON requests(razorpay_payment_id)',
    'SELECT 1')
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND INDEX_NAME = 'idx_requests_rzp_payment');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
