<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';
require_customer();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'message'=>'POST request required.']);
    exit;
}

$id = (int)($_POST['request_id'] ?? 0);
$rawQr = trim((string)($_POST['qr_payload'] ?? ''));
$email = $_SESSION['driver_email'];

if ($id < 1 || $rawQr === '' || strlen($rawQr) > 5000) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>'Invalid QR validation request.']);
    exit;
}

$stmt = mysqli_prepare($con, 'SELECT r.id,r.cost,r.payment_status,r.parking_id,p.name parking_name,p.city,p.location FROM requests r LEFT JOIN parkings p ON p.id=r.parking_id WHERE r.id=? AND r.customer=? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'is', $id, $email);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$booking) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'message'=>'Reservation not found.']);
    exit;
}
if (($booking['payment_status'] ?? 'unpaid') === 'paid') {
    echo json_encode(['ok'=>false,'message'=>'This reservation is already paid.']);
    exit;
}

function query_from_upi($raw) {
    if (!preg_match('/^upi:\/\/pay(?:\?|$)/i', $raw)) return null;
    $parts = parse_url($raw);
    if ($parts === false || !isset($parts['query'])) return null;
    parse_str($parts['query'], $q);
    $pa = trim((string)($q['pa'] ?? ''));
    $pn = trim((string)($q['pn'] ?? ''));
    $am = trim((string)($q['am'] ?? ''));
    $cu = strtoupper(trim((string)($q['cu'] ?? 'INR')));
    $tr = trim((string)($q['tr'] ?? ''));
    if (!$pa || !$am || !preg_match('/^[^\s@]+@[^\s@]+$/', $pa)) throw new Exception('QR does not contain a valid merchant UPI ID.');
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $am)) throw new Exception('QR does not contain a valid amount.');
    $amount = round((float)$am, 2);
    if ($amount <= 0) throw new Exception('QR amount must be greater than zero.');
    if ($cu !== 'INR') throw new Exception('Only INR payment QR codes are supported.');
    if (defined('SMARTPARK_MERCHANT_UPI') && SMARTPARK_MERCHANT_UPI !== '' && strcasecmp($pa, SMARTPARK_MERCHANT_UPI) !== 0) throw new Exception('This QR belongs to an unrecognized SmartPark merchant.');
    return ['format'=>'upi','merchant_upi'=>$pa,'merchant_name'=>$pn,'amount'=>$amount,'currency'=>$cu,'transaction_ref'=>$tr];
}

function query_from_smartpark($raw) {
    if (!preg_match('/^SMARTPARK:\/\/PAY(?:\?|$)/i', $raw)) return null;
    $normalized = preg_replace('/^SMARTPARK:/i', 'http:', $raw);
    $parts = parse_url($normalized);
    if ($parts === false || !isset($parts['query'])) throw new Exception('SmartPark QR data is malformed.');
    parse_str($parts['query'], $q);
    $parkingId = (int)($q['parking_id'] ?? 0);
    $requestId = (int)($q['request_id'] ?? 0);
    $amountRaw = trim((string)($q['amount'] ?? ''));
    $expires = (int)($q['expires'] ?? 0);
    $sig = trim((string)($q['sig'] ?? ''));
    if (!$parkingId && !$requestId) throw new Exception('SmartPark QR is missing its parking reference.');
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $amountRaw)) throw new Exception('SmartPark QR contains an invalid amount.');
    $amount = round((float)$amountRaw, 2);
    if ($amount <= 0) throw new Exception('SmartPark QR amount must be greater than zero.');
    if ($expires && $expires < time()) throw new Exception('This SmartPark QR has expired.');

    if (defined('SMARTPARK_QR_SECRET') && SMARTPARK_QR_SECRET !== '' && $sig !== '') {
        $canonical = $parkingId . '|' . $requestId . '|' . number_format($amount, 2, '.', '') . '|' . $expires;
        $expected = hash_hmac('sha256', $canonical, SMARTPARK_QR_SECRET);
        if (!hash_equals($expected, $sig)) throw new Exception('SmartPark QR signature verification failed.');
    }
    if ($requestId && $requestId !== (int)$_POST['request_id']) throw new Exception('This QR belongs to a different reservation.');
    if ($parkingId && $parkingId !== (int)$booking['parking_id']) throw new Exception('This QR belongs to a different parking location.');

    return ['format'=>'smartpark','merchant_upi'=>'','merchant_name'=>'SmartPark','amount'=>$amount,'currency'=>'INR','transaction_ref'=>(string)($q['ref'] ?? ''),'parking_id'=>$parkingId,'request_id'=>$requestId];
}

try {
    $parsed = query_from_upi($rawQr);
    if (!$parsed) $parsed = query_from_smartpark($rawQr);
    if (!$parsed) throw new Exception('Unsupported QR format. Use a UPI payment QR or SmartPark QR.');

    $expectedAmount = round((float)$booking['cost'], 2);
    if (abs($parsed['amount'] - $expectedAmount) > 0.01) {
        $status='rejected_amount';
        $message='QR amount does not match this reservation.';
    } else {
        $status='verified';
        $message='QR verified and amount matched.';
    }

    $hash = hash('sha256', $rawQr);
    $scanStmt = mysqli_prepare($con, 'INSERT INTO qr_scans(request_id,customer,merchant_upi,scanned_amount,currency,transaction_ref,raw_payload,payload_hash,scan_status,error_message) VALUES(?,?,?,?,?,?,?,?,?,?)');
    $errorMessage = $status === 'verified' ? null : $message;
    $merchantUpi = $parsed['merchant_upi'] ?? '';
    $currency = $parsed['currency'] ?? 'INR';
    $transactionRef = $parsed['transaction_ref'] ?? '';
    $scanStatus = $status;
    mysqli_stmt_bind_param($scanStmt, 'issdssssss', $id, $email, $merchantUpi, $parsed['amount'], $currency, $transactionRef, $rawQr, $hash, $scanStatus, $errorMessage);
    @mysqli_stmt_execute($scanStmt);
    $scanId = mysqli_insert_id($con);

    if ($status !== 'verified') {
        echo json_encode(['ok'=>false,'message'=>$message,'expected'=>number_format($expectedAmount,2,'.',''),'scanned'=>number_format($parsed['amount'],2,'.','')]);
        exit;
    }

    $update = mysqli_prepare($con, 'UPDATE requests SET qr_scan_id=?,qr_scanned_amount=?,qr_transaction_ref=?,qr_scanned_at=NOW() WHERE id=? AND customer=?');
    if ($update) {
        mysqli_stmt_bind_param($update, 'idiss', $scanId, $parsed['amount'], $transactionRef, $id, $email);
        @mysqli_stmt_execute($update);
    }

    echo json_encode([
        'ok'=>true,
        'message'=>'QR verified successfully.',
        'amount'=>$parsed['amount'],
        'currency'=>'INR',
        'merchant_upi'=>$merchantUpi,
        'merchant_name'=>$parsed['merchant_name'] ?? '',
        'transaction_ref'=>$transactionRef,
        'scan_id'=>$scanId,
        'parking_id'=>(int)$booking['parking_id'],
        'parking_name'=>$booking['parking_name']
    ]);
} catch (Throwable $e) {
    $hash = hash('sha256', $rawQr);
    $status='malformed';
    $scanStmt = mysqli_prepare($con, 'INSERT INTO qr_scans(request_id,customer,raw_payload,payload_hash,scan_status,error_message) VALUES(?,?,?,?,?,?)');
    if ($scanStmt) {
        $message=$e->getMessage();
        mysqli_stmt_bind_param($scanStmt, 'isssss', $id, $email, $rawQr, $hash, $status, $message);
        @mysqli_stmt_execute($scanStmt);
    }
    http_response_code(422);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
}
