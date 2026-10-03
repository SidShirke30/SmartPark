
<?php

// SmartPark backend configuration.

// Load .env from project root without requiring Composer.

function smartpark_load_env(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;

    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

    if (!is_file($file)) {
        return;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {

        $line = trim($line);

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $parts = explode('=', $line, 2);

        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if (
            $value !== '' &&
            ($value[0] === '"' || $value[0] === "'")
        ) {
            $value = trim($value, "\"'");
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

smartpark_load_env();


// Read environment variables from Render or local .env.

function smartpark_env(string $key, string $default = ''): string
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}


// Gemini configuration.

define('GEMINI_API_KEY', smartpark_env('GEMINI_API_KEY'));

define('GEMINI_MODEL', smartpark_env('GEMINI_MODEL', 'gemini-3.7-flash'));


// SMTP configuration for OTP emails.

define('SMTP_HOST', smartpark_env('SMTP_HOST', 'smtp.gmail.com'));

define('SMTP_PORT', (int) smartpark_env('SMTP_PORT', '587'));

define('SMTP_USERNAME', smartpark_env('SMTP_USERNAME'));

define('SMTP_PASSWORD', smartpark_env('SMTP_PASSWORD'));

define('SMTP_FROM_EMAIL', smartpark_env('SMTP_FROM_EMAIL'));

define('SMTP_FROM_NAME', smartpark_env('SMTP_FROM_NAME', 'ParkSmart'));


// Razorpay configuration.

define('RAZORPAY_KEY_ID', smartpark_env('RAZORPAY_KEY_ID'));

define('RAZORPAY_KEY_SECRET', smartpark_env('RAZORPAY_KEY_SECRET'));

define('SMARTPARK_MERCHANT_UPI', smartpark_env('SMARTPARK_MERCHANT_UPI', 'smartpark@upi'));

define('SMARTPARK_MERCHANT_NAME', smartpark_env('SMARTPARK_MERCHANT_NAME', 'SmartPark'));


// Map, routing and QR configuration.

define('GOOGLE_MAPS_API_KEY', smartpark_env('GOOGLE_MAPS_API_KEY'));

define('ROUTING_PROVIDER', smartpark_env('ROUTING_PROVIDER', 'google'));

define('OSRM_BASE_URL', smartpark_env('OSRM_BASE_URL', 'https://router.project-osrm.org'));

define('SMARTPARK_QR_SECRET', smartpark_env('SMARTPARK_QR_SECRET'));