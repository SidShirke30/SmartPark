-- Optional migration for older SmartPark installations.
-- Compatible with older MySQL/MariaDB versions.
USE `smartpark_db`;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE parkings ADD COLUMN parking_type VARCHAR(20) NOT NULL DEFAULT \'Open Air\' AFTER city', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'parkings' AND COLUMN_NAME = 'parking_type');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE parkings ADD COLUMN ev_charging TINYINT(1) NOT NULL DEFAULT 0 AFTER parking_type', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'parkings' AND COLUMN_NAME = 'ev_charging');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE parkings ADD COLUMN contact_phone VARCHAR(20) DEFAULT \'\' AFTER ev_charging', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'parkings' AND COLUMN_NAME = 'contact_phone');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
