<?php

require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'smartpark_db');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));

try {
    $con = mysqli_init();

    mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 10);

    if (DB_PORT === 4000) {
        mysqli_ssl_set($con, null, null, null, null, null);
        mysqli_options($con, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
    }

    mysqli_real_connect(
        $con,
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        DB_PORT,
        null,
        DB_PORT === 4000 ? MYSQLI_CLIENT_SSL : 0
    );

    mysqli_set_charset($con, 'utf8mb4');

} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please check your database configuration.');
}