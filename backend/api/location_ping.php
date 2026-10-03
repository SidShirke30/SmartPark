<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';
require_customer();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'message'=>'POST request required.']);
    exit;
}

$lat = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
$accuracy = ($_POST['accuracy'] ?? '') !== '' ? (float)$_POST['accuracy'] : null;
$heading = ($_POST['heading'] ?? '') !== '' ? (float)$_POST['heading'] : null;
$speed = ($_POST['speed'] ?? '') !== '' ? (float)$_POST['speed'] : null;
$customer = $_SESSION['driver_email'] ?? '';

if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || !$customer) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>'Invalid location data.']);
    exit;
}

$expires = date('Y-m-d H:i:s', time() + 900); // latest live location expires after 15 minutes
$stmt = mysqli_prepare($con, 'INSERT INTO user_locations(customer,session_id,latitude,longitude,accuracy_m,heading,speed_mps,recorded_at,expires_at) VALUES(?,?,?,?,?,?,?,NOW(),?)');
$sessionId = session_id();
mysqli_stmt_bind_param($stmt, 'ssddddds', $customer, $sessionId, $lat, $lng, $accuracy, $heading, $speed, $expires);
$ok = mysqli_stmt_execute($stmt);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'Location table is not ready. Run database/migration_map_qr.sql first.']);
    exit;
}

// Keep only the last 20 location records for this customer and remove expired records.
mysqli_query($con, "DELETE FROM user_locations WHERE expires_at < NOW()");
$cleanup = mysqli_prepare($con, 'DELETE FROM user_locations WHERE customer=? AND id NOT IN (SELECT id FROM (SELECT id FROM user_locations WHERE customer=? ORDER BY id DESC LIMIT 20) keep_ids)');
if ($cleanup) {
    mysqli_stmt_bind_param($cleanup, 'ss', $customer, $customer);
    @mysqli_stmt_execute($cleanup);
}

echo json_encode(['ok'=>true,'message'=>'Location updated.','expires_at'=>$expires]);
