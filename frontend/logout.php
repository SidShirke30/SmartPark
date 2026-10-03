<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Remove the session cookie
if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax'
    ]);
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit;

?>