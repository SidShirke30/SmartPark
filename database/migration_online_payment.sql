-- SmartPark online payment upgrade
-- Compatible with older MySQL/MariaDB versions.
USE `smartpark_db`;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE requests ADD COLUMN upi_id VARCHAR(255) DEFAULT NULL AFTER payment_method', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'upi_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE requests ADD COLUMN razorpay_order_id VARCHAR(80) DEFAULT NULL AFTER upi_id', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'razorpay_order_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE requests ADD COLUMN razorpay_payment_id VARCHAR(80) DEFAULT NULL AFTER razorpay_order_id', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'razorpay_payment_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE requests ADD COLUMN razorpay_signature VARCHAR(255) DEFAULT NULL AFTER razorpay_payment_id', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'razorpay_signature');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE requests ADD COLUMN receipt_no VARCHAR(50) DEFAULT NULL AFTER razorpay_signature', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'receipt_no');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE requests ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER receipt_no', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'paid_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0, 'CREATE INDEX idx_requests_rzp_order ON requests(razorpay_order_id)', 'SELECT 1') FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND INDEX_NAME = 'idx_requests_rzp_order');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0, 'CREATE INDEX idx_requests_rzp_payment ON requests(razorpay_payment_id)', 'SELECT 1') FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND INDEX_NAME = 'idx_requests_rzp_payment');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
