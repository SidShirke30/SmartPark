<?php

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

require_customer();

header('Content-Type: application/json');

function paymentResponse($ok, $message, $extra = [])
{
    echo json_encode(array_merge([
        'ok' => $ok,
        'message' => $message
    ], $extra));
    exit;
}

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    paymentResponse(false, 'Invalid request.');
}

// Check Razorpay configuration.
if (!RAZORPAY_KEY_ID || !RAZORPAY_KEY_SECRET) {
    paymentResponse(false, 'Payment gateway is not configured.');
}

// Get submitted payment details.
$id = (int)($_POST['request_id'] ?? 0);
$orderId = trim($_POST['razorpay_order_id'] ?? '');
$paymentId = trim($_POST['razorpay_payment_id'] ?? '');
$signature = trim($_POST['razorpay_signature'] ?? '');
$email = $_SESSION['driver_email'] ?? '';

if ($id < 1 || $orderId === '' || $paymentId === '' ||
    $signature === '' || $email === '') {
    paymentResponse(false, 'Payment verification data is incomplete.');
}

// Find the reservation belonging to this customer.
$s = mysqli_prepare($con, "
    SELECT id, cost, customer, status, payment_status,
           razorpay_order_id, receipt_no
    FROM requests
    WHERE id = ? AND customer = ?
    LIMIT 1
");

mysqli_stmt_bind_param($s, 'is', $id, $email);
mysqli_stmt_execute($s);

$b = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

if (!$b) {
    paymentResponse(false, 'Reservation not found.');
}

// Handle repeated verification safely.
if (strtolower($b['payment_status'] ?? '') === 'paid') {
    paymentResponse(true, 'Payment was already verified.', [
        'receipt_url' => 'receipt.php?id=' . $id
    ]);
}

// Ensure the stored Razorpay order matches.
if ($orderId !== ($b['razorpay_order_id'] ?? '')) {
    paymentResponse(false, 'Razorpay order does not match this reservation.');
}

// Only requested reservations can be paid.
if (strtolower($b['status'] ?? '') !== 'requested') {
    paymentResponse(false, 'This reservation is not eligible for payment.');
}

// Verify Razorpay signature.
$expectedSignature = hash_hmac(
    'sha256',
    $orderId . '|' . $paymentId,
    RAZORPAY_KEY_SECRET
);

if (!hash_equals($expectedSignature, $signature)) {
    paymentResponse(false, 'Payment signature verification failed.');
}

// Fetch the actual payment directly from Razorpay.
$ch = curl_init(
    'https://api.razorpay.com/v1/payments/' . rawurlencode($paymentId)
);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 20
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($response === false || $httpCode < 200 || $httpCode >= 300) {
    error_log('Razorpay payment fetch failed: ' . $curlError);
    paymentResponse(false, 'Unable to confirm payment with Razorpay. Please try again.');
}

$payment = json_decode($response, true);

if (!is_array($payment)) {
    paymentResponse(false, 'Invalid payment gateway response.');
}

// Calculate the expected amount from the database.
$expectedAmount = (int)round((float)$b['cost'] * 100);

// Independently verify the payment details.
if (($payment['order_id'] ?? '') !== $orderId) {
    paymentResponse(false, 'Payment order verification failed.');
}

if ((int)($payment['amount'] ?? 0) !== $expectedAmount) {
    paymentResponse(false, 'Payment amount does not match the reservation.');
}

if (($payment['currency'] ?? '') !== 'INR') {
    paymentResponse(false, 'Payment currency verification failed.');
}

if (($payment['status'] ?? '') !== 'captured') {
    paymentResponse(false, 'Payment has not been captured yet.');
}

// Update the reservation only after all checks pass.
$paid = 'paid';
$method = 'Online (Razorpay)';
$receipt = $b['receipt_no'] ?: ('SP-' . $id . '-' . date('YmdHis'));
$now = date('Y-m-d H:i:s');

$u = mysqli_prepare($con, "
    UPDATE requests
    SET status = 'confirmed',
        payment_status = ?,
        payment_method = ?,
        razorpay_payment_id = ?,
        razorpay_signature = ?,
        receipt_no = ?,
        paid_at = ?
    WHERE id = ?
      AND customer = ?
      AND razorpay_order_id = ?
      AND payment_status = 'unpaid'
      AND status = 'requested'
");

mysqli_stmt_bind_param(
    $u,
    'ssssssiss',
    $paid,
    $method,
    $paymentId,
    $signature,
    $receipt,
    $now,
    $id,
    $email,
    $orderId
);

if (!mysqli_stmt_execute($u)) {
    error_log('SmartPark payment database update failed: ' . mysqli_stmt_error($u));
    paymentResponse(false, 'Payment was verified, but reservation updating failed. Please contact support.');
}

// Confirm that exactly one reservation was updated.
if (mysqli_stmt_affected_rows($u) !== 1) {
    paymentResponse(false, 'Reservation could not be updated. Please refresh and check its payment status.');
}

paymentResponse(true, 'Payment verified successfully.', [
    'receipt_url' => 'receipt.php?id=' . $id
]);

?>
