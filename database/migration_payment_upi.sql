-- SmartPark UPI/payment migration for existing installations.
-- Compatible with older MySQL/MariaDB versions.
USE `smartpark_db`;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE requests ADD COLUMN upi_id VARCHAR(255) DEFAULT NULL AFTER payment_method', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'upi_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @drop_ref_sql = (SELECT IF(COUNT(*) > 0, 'ALTER TABLE requests DROP COLUMN payment_reference', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = 'payment_reference');
PREPARE stmt FROM @drop_ref_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
