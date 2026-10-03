CREATE DATABASE IF NOT EXISTS `smartpark_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smartpark_db`;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS parkings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admin;
DROP TABLE IF EXISTS car_showcase;
DROP TABLE IF EXISTS site_settings;

CREATE TABLE admin(
 id INT AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(100) NOT NULL,
 password VARCHAR(255) NOT NULL,
 email VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users(
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(255) NOT NULL,
 email VARCHAR(255) NOT NULL UNIQUE,
 phone VARCHAR(20) NOT NULL DEFAULT '',
 password VARCHAR(255) NOT NULL,
 password_confirm VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE parkings(
 id INT AUTO_INCREMENT PRIMARY KEY,
 location VARCHAR(200) NOT NULL,
 street VARCHAR(200) NOT NULL,
 name VARCHAR(200) NOT NULL,
 slot INT NOT NULL,
 remaining_slots INT NOT NULL DEFAULT 0,
 attendant VARCHAR(100) DEFAULT '',
 date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 price DECIMAL(10,2) NOT NULL DEFAULT 0,
 latitude DECIMAL(10,7) DEFAULT NULL,
 longitude DECIMAL(10,7) DEFAULT NULL,
 city VARCHAR(100) DEFAULT NULL,
 parking_type VARCHAR(20) NOT NULL DEFAULT 'Open Air',
 ev_charging TINYINT(1) NOT NULL DEFAULT 0,
 contact_phone VARCHAR(20) DEFAULT '',
 INDEX lat_long_idx (latitude,longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE requests(
 id INT AUTO_INCREMENT PRIMARY KEY,
 parking_id INT NOT NULL,
 slots INT NOT NULL DEFAULT 1,
 hours INT NOT NULL,
 cost DECIMAL(10,2) NOT NULL,
 customer VARCHAR(255) NOT NULL,
 time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 status VARCHAR(25) NOT NULL DEFAULT 'requested',
 payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
 payment_method VARCHAR(20) DEFAULT NULL,
 upi_id VARCHAR(255) DEFAULT NULL,
 razorpay_order_id VARCHAR(80) DEFAULT NULL,
 razorpay_payment_id VARCHAR(80) DEFAULT NULL,
 razorpay_signature VARCHAR(255) DEFAULT NULL,
 receipt_no VARCHAR(50) DEFAULT NULL,
 paid_at DATETIME DEFAULT NULL,
 qr_scan_id BIGINT DEFAULT NULL,
 qr_scanned_amount DECIMAL(10,2) DEFAULT NULL,
 qr_transaction_ref VARCHAR(255) DEFAULT NULL,
 qr_scanned_at DATETIME DEFAULT NULL,
 INDEX(customer),
 INDEX idx_requests_rzp_order (razorpay_order_id),
 INDEX idx_requests_rzp_payment (razorpay_payment_id),
 INDEX idx_requests_qr_scan (qr_scan_id),
 CONSTRAINT fk_req_parking FOREIGN KEY(parking_id) REFERENCES parkings(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE car_showcase(
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 model VARCHAR(100) NOT NULL,
 image_path VARCHAR(255) NOT NULL,
 tag VARCHAR(50) DEFAULT 'PREMIUM',
 specs VARCHAR(255) DEFAULT '',
 sort_order INT DEFAULT 0,
 active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE site_settings(
 setting_key VARCHAR(100) PRIMARY KEY,
 setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admin(username,password,email) VALUES
('admin','admin123','admin@gmail.com'),
('king','king123','king@gmail.com');

INSERT INTO users(name,email,phone,password,password_confirm) VALUES
('Demo User','demo@smartpark.local','0000000000','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaV6H9vZl3cV2x4k9H5q6K0dXQW','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaV6H9vZl3cV2x4k9H5q6K0dXQW');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone) VALUES
('Kopargaon','Station Road','SmartPark Kopargaon Central',120,48,'',30,19.8820,74.4760,'Kopargaon','Covered',1,'+91 98220 11001'),
('Pune','Shivajinagar','Pune Central Smart Parking',120,48,'',40,18.5308,73.8475,'Pune','Multi-Level',1,'+91 98220 11002'),
('Mumbai','Dadar','Dadar Smart Parking Hub',200,73,'',60,19.0178,72.8478,'Mumbai','Multi-Level',1,'+91 98220 11003'),
('Nashik','College Road','Nashik Smart Parking',150,35,'',35,20.0059,73.7797,'Nashik','Covered',0,'+91 98220 11004'),
('Chhatrapati Sambhajinagar','CIDCO','Sambhajinagar Smart Parking',100,21,'',30,19.8762,75.3433,'Chhatrapati Sambhajinagar','Open Air',0,'+91 98220 11005'),
('Nagpur','Sitabuldi','Nagpur Central Smart Parking',180,66,'',35,21.1458,79.0882,'Nagpur','Covered',1,'+91 98220 11006'),
('Kolhapur','Rajarampuri','Kolhapur Smart Parking',90,18,'',30,16.7050,74.2433,'Kolhapur','Open Air',0,'+91 98220 11007'),
('Solapur','Railway Station Road','Solapur Smart Parking',110,27,'',25,17.6599,75.9064,'Solapur','Open Air',0,'+91 98220 11008'),
('Ahmednagar','Savedi','Ahmednagar Smart Parking',80,14,'',25,19.0948,74.7480,'Ahmednagar','Covered',0,'+91 98220 11009');
INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone) VALUES
('Shirdi','Temple Road','Shirdi Sai Smart Parking',140,52,'',35,19.7669,74.4774,'Shirdi','Covered',1,'+91 98220 11010'),
('Mumbai','Andheri East','Andheri Metro Smart Parking',220,88,'',60,19.1197,72.8468,'Mumbai','Multi-Level',1,'+91 98220 11011'),
('Mumbai','Bandra West','Bandra Smart Parking Hub',190,64,'',65,19.0607,72.8362,'Mumbai','Multi-Level',1,'+91 98220 11012'),
('Mumbai','Borivali West','Borivali Smart Parking',180,71,'',50,19.2307,72.8567,'Mumbai','Covered',0,'+91 98220 11013'),
('Mumbai','Colaba','Colaba Smart Parking',120,29,'',70,18.9220,72.8347,'Mumbai','Covered',0,'+91 98220 11014'),
('Mumbai','Kurla','Kurla Smart Parking',200,83,'',55,19.0726,72.8845,'Mumbai','Multi-Level',1,'+91 98220 11015'),
('Mumbai','BKC','BKC Smart Parking Arena',300,126,'',80,19.0670,72.8697,'Mumbai','Multi-Level',1,'+91 98220 11016'),
('Thane','Naupada','Thane Naupada Smart Parking',170,61,'',50,19.1943,72.9660,'Thane','Multi-Level',1,'+91 98220 11017'),
('Thane','Majiwada','Majiwada Smart Parking',230,104,'',55,19.2183,72.9781,'Thane','Covered',1,'+91 98220 11018'),
('Thane','Ghodbunder Road','Ghodbunder Smart Parking',210,92,'',50,19.2715,72.9634,'Thane','Open Air',0,'+91 98220 11019'),
('Kalyan','Station Road','Kalyan Smart Parking',150,48,'',40,19.2437,73.1355,'Kalyan','Covered',0,'+91 98220 11020'),
('Dombivli','Phadke Road','Dombivli Smart Parking',140,42,'',40,19.2183,73.0868,'Dombivli','Open Air',0,'+91 98220 11021'),
('Navi Mumbai','Vashi','Vashi Smart Parking',250,96,'',55,19.0771,72.9986,'Navi Mumbai','Multi-Level',1,'+91 98220 11022'),
('Navi Mumbai','Nerul','Nerul Smart Parking',180,72,'',45,19.0330,73.0169,'Navi Mumbai','Covered',1,'+91 98220 11023'),
('Panvel','Old Panvel','Panvel Smart Parking',160,57,'',35,18.9894,73.1175,'Panvel','Open Air',0,'+91 98220 11024'),
('Pune','Kothrud','Kothrud Smart Parking',180,69,'',40,18.5074,73.8077,'Pune','Covered',1,'+91 98220 11025'),
('Pune','Baner','Baner Smart Parking',210,91,'',50,18.5590,73.7868,'Pune','Multi-Level',1,'+91 98220 11026'),
('Pune','Hinjewadi','Hinjewadi Smart Parking',260,118,'',45,18.5912,73.7389,'Pune','Open Air',1,'+91 98220 11027'),
('Pune','Hadapsar','Hadapsar Smart Parking',240,105,'',45,18.5089,73.9260,'Pune','Covered',1,'+91 98220 11028'),
('Pune','Viman Nagar','Viman Nagar Smart Parking',200,84,'',50,18.5679,73.9143,'Pune','Multi-Level',1,'+91 98220 11029'),
('Pune','Swargate','Swargate Smart Parking',170,58,'',45,18.5018,73.8636,'Pune','Covered',0,'+91 98220 11030'),
('Pune','Wakad','Wakad Smart Parking',220,97,'',40,18.5993,73.7636,'Pune','Open Air',1,'+91 98220 11031'),
('Nashik','Panchavati','Panchavati Smart Parking',150,47,'',30,20.0119,73.7935,'Nashik','Open Air',0,'+91 98220 11032'),
('Nashik','Gangapur Road','Gangapur Smart Parking',180,63,'',35,20.0060,73.7304,'Nashik','Covered',1,'+91 98220 11033'),
('Nashik','CBS','Nashik CBS Smart Parking',160,51,'',30,20.0056,73.7793,'Nashik','Multi-Level',0,'+91 98220 11034'),
('Nagpur','Dharampeth','Dharampeth Smart Parking',170,58,'',35,21.1360,79.0600,'Nagpur','Covered',1,'+91 98220 11035'),
('Nagpur','Wardha Road','Wardha Road Smart Parking',230,103,'',40,21.1100,79.0700,'Nagpur','Open Air',1,'+91 98220 11036'),
('Nagpur','Manish Nagar','Manish Nagar Smart Parking',150,52,'',30,21.1046,79.0752,'Nagpur','Covered',0,'+91 98220 11037'),
('Chhatrapati Sambhajinagar','Railway Station Road','Sambhajinagar Station Parking',160,49,'',30,19.8763,75.3423,'Chhatrapati Sambhajinagar','Covered',0,'+91 98220 11038'),
('Chhatrapati Sambhajinagar','Garkheda','Garkheda Smart Parking',140,38,'',30,19.8622,75.3250,'Chhatrapati Sambhajinagar','Open Air',1,'+91 98220 11039'),
('Chhatrapati Sambhajinagar','Waluj','Waluj Smart Parking',190,76,'',25,19.8340,75.2450,'Chhatrapati Sambhajinagar','Open Air',1,'+91 98220 11040'),
('Kolhapur','Tarabai Park','Tarabai Park Smart Parking',130,44,'',30,16.7113,74.2410,'Kolhapur','Covered',0,'+91 98220 11041'),
('Kolhapur','Shahupuri','Shahupuri Smart Parking',120,31,'',25,16.7045,74.2437,'Kolhapur','Open Air',0,'+91 98220 11042'),
('Solapur','Hotgi Road','Hotgi Road Smart Parking',150,45,'',25,17.6460,75.9100,'Solapur','Open Air',0,'+91 98220 11043'),
('Solapur','Saat Rasta','Saat Rasta Smart Parking',140,39,'',25,17.6683,75.9064,'Solapur','Covered',0,'+91 98220 11044'),
('Satara','Powai Naka','Satara Smart Parking',120,37,'',25,17.6805,74.0183,'Satara','Open Air',0,'+91 98220 11045'),
('Sangli','Miraj Road','Sangli Smart Parking',130,41,'',25,16.8524,74.5815,'Sangli','Covered',0,'+91 98220 11046'),
('Latur','Gandhi Chowk','Latur Smart Parking',110,34,'',25,18.4088,76.5604,'Latur','Open Air',0,'+91 98220 11047'),
('Nanded','Vazirabad','Nanded Smart Parking',120,36,'',25,19.1503,77.3197,'Nanded','Covered',0,'+91 98220 11048'),
('Jalgaon','Station Road','Jalgaon Smart Parking',150,49,'',25,21.0077,75.5626,'Jalgaon','Open Air',0,'+91 98220 11049'),
('Akola','Civil Lines','Akola Smart Parking',110,29,'',25,20.7059,77.0011,'Akola','Covered',0,'+91 98220 11050'),
('Amravati','Rajapeth','Amravati Smart Parking',130,43,'',25,20.9374,77.7796,'Amravati','Open Air',0,'+91 98220 11051'),
('Dhule','Deopur','Dhule Smart Parking',100,28,'',20,20.9042,74.7749,'Dhule','Open Air',0,'+91 98220 11052'),
('Malegaon','Camp Road','Malegaon Smart Parking',120,33,'',20,20.5579,74.5280,'Malegaon','Covered',0,'+91 98220 11053'),
('Manmad','Station Road','Manmad Smart Parking',100,27,'',20,20.2530,74.4370,'Manmad','Open Air',0,'+91 98220 11054'),
('Bhusawal','Jalgaon Road','Bhusawal Smart Parking',130,39,'',20,21.0437,75.7850,'Bhusawal','Open Air',0,'+91 98220 11055'),
('Ratnagiri','Maruti Mandir Road','Ratnagiri Smart Parking',110,32,'',30,16.9944,73.3000,'Ratnagiri','Covered',0,'+91 98220 11056'),
('Karad','Market Yard Road','Karad Smart Parking',100,31,'',25,17.2890,74.1810,'Karad','Open Air',0,'+91 98220 11057'),
('Baramati','MIDC Road','Baramati Smart Parking',120,40,'',25,18.1517,74.5777,'Baramati','Open Air',1,'+91 98220 11058'),
('Ahmednagar','Delhi Gate','Ahmednagar City Smart Parking',130,42,'',25,19.0948,74.7480,'Ahmednagar','Covered',0,'+91 98220 11059'),
('Shirdi','Pimpalwadi Road','Shirdi Temple Area Parking',180,66,'',45,19.7664,74.4774,'Shirdi','Open Air',0,'+91 98220 11060');

INSERT INTO car_showcase(name,model,image_path,tag,specs,sort_order) VALUES
('Porsche','911 GT3','assets/cars/porsche-911-race.png','TRACK','4.0L Flat-6 • 502 HP',1),
('Dodge','Challenger','assets/cars/dodge-challenger-blue.jpg','MUSCLE','6.2L V8 • 717 HP',2),
('Ford','Mustang','assets/cars/mustang-classic.jpg','CLASSIC','5.0L V8 • 450 HP',3),
('BMW','M4','assets/img/parking-lot-night.jpg','PREMIUM','3.0L Twin-Turbo • 503 HP',4),
('Audi','R8','assets/img/1.jpg','SPORT','5.2L V10 • 602 HP',5);

INSERT INTO site_settings(setting_key,setting_value) VALUES
('brand','SmartPark'),
('tagline','Park Smart. Live Easy.'),
('theme','Cinematic Red Garage'),
('map_center_lat','19.7515'),
('map_center_lng','75.7139');

-- ==============================================================
-- SmartPark Map + QR upgrade tables/fields (fresh installs)
-- Existing installations should run database/migration_map_qr.sql
-- ==============================================================
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
 INDEX idx_customer_time (customer,recorded_at),
 INDEX idx_location (latitude,longitude),
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
 INDEX idx_parking_active (parking_id,active),
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


