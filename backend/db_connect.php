<?php
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_OFF);

// ---------------------------------------------------------------------
// Database connection settings
// Edit these five values if your MySQL setup is different.
// ---------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smartpark_db');
define('DB_PORT', 3306);

$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if (!$con) {
    die('Database connection failed. Start MySQL and import smartpark_db.sql.');
}
mysqli_set_charset($con, 'utf8mb4');
