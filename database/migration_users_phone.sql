USE `smartpark_db`;

ALTER TABLE `users`
  ADD COLUMN `phone` VARCHAR(20) NOT NULL DEFAULT '' AFTER `email`;
