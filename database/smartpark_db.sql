USE smartpark_db;

CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL DEFAULT '',
    password VARCHAR(255) NOT NULL,
    password_confirm VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS parkings (
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
    INDEX lat_long_idx (latitude, longitude)
);

CREATE TABLE IF NOT EXISTS requests (
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
    INDEX idx_requests_qr_scan (qr_scan_id)
);

CREATE TABLE IF NOT EXISTS car_showcase (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    tag VARCHAR(50) DEFAULT 'PREMIUM',
    specs VARCHAR(255) DEFAULT '',
    sort_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL
);