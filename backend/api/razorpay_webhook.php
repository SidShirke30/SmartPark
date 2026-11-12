<?php

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../config.php';

http_response_code(200);
header('Content-Type: application/json');

function webhookResponse($message)
{
    echo json_encode(['received' => true, 'message' => $message]);
    exit;
}

// Get webhook secret from configuration or environment.
$webhookSecret = defined('RAZORPAY_WEBHOOK_SECRET')
    ? RAZORPAY_WEBHOOK_SECRET
    : (getenv('RAZORPAY_WEBHOOK_SECRET') ?: '');

if ($webhookSecret === '') {
    http_response_code(500);
    webhookResponse('Webhook secret is not configured.');
}

// Read the original, unmodified request body.
$rawBody = file_get_contents('php://input');

$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
$eventId = $_SERVER['HTTP_X_RAZORPAY_EVENT_ID'] ?? '';

if ($rawBody === '' || $signature === '' || $eventId === '') {
    http_response_code(400);
    webhookResponse('Missing webhook data.');
}

// Verify Razorpay webhook signature.
$expectedSignature = hash_hmac(
    'sha256',
    $rawBody,
    $webhookSecret
);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(400);
    webhookResponse('Invalid webhook signature.');
}

$data = json_decode($rawBody, true);

if (!is_array($data)) {
    http_response_code(400);
    webhookResponse('Invalid JSON payload.');
}

$eventType = $data['event'] ?? '';

// We process successful captured payments only.
if ($eventType !== 'payment.captured') {
    webhookResponse('Event received; no action required.');
}

$payment = $data['payload']['payment']['entity'] ?? [];

$orderId = $payment['order_id'] ?? '';
$paymentId = $payment['id'] ?? '';
$amount = (int)($payment['amount'] ?? 0);
$currency = $payment['currency'] ?? '';
$status = $payment['status'] ?? '';

if (
    $orderId === '' ||
    $paymentId === '' ||
    $amount < 1 ||
    $currency !== 'INR' ||
    $status !== 'captured'
) {
    http_response_code(400);
    webhookResponse('Invalid captured payment details.');
}

mysqli_begin_transaction($con);

try {

    // Register event ID. Duplicate events cannot be processed twice.
    $eventStmt = mysqli_prepare($con, "
        INSERT INTO razorpay_webhook_events (event_id, event_type)
        VALUES (?, ?)
    ");

    mysqli_stmt_bind_param($eventStmt, 'ss', $eventId, $eventType);

    if (!mysqli_stmt_execute($eventStmt)) {
        if (mysqli_errno($con) === 1062) {
            mysqli_rollback($con);
            webhookResponse('Duplicate event ignored.');
        }

        throw new Exception('Could not record webhook event.');
    }

    // Find and lock the reservation associated with this order.
    $stmt = mysqli_prepare($con, "
        SELECT id, cost, payment_status, status, receipt_no
        FROM requests
        WHERE razorpay_order_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    mysqli_stmt_bind_param($stmt, 's', $orderId);
    mysqli_stmt_execute($stmt);

    $booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$booking) {
        throw new Exception('Reservation not found for this order.');
    }

    $expectedAmount = (int)round((float)$booking['cost'] * 100);

    if ($amount !== $expectedAmount) {
        throw new Exception('Payment amount does not match reservation.');
    }

    // Do not overwrite an already completed payment.
    if (strtolower($booking['payment_status']) === 'paid') {
        mysqli_commit($con);
        webhookResponse('Payment was already recorded.');
    }

    if (strtolower($booking['status']) !== 'requested') {
        throw new Exception('Reservation is not eligible for payment.');
    }

    $paid = 'paid';
    $method = 'Online (Razorpay)';
    $receipt = $booking['receipt_no']
        ?: ('SP-' . $booking['id'] . '-' . date('YmdHis'));

    $now = date('Y-m-d H:i:s');
    $bookingId = (int)$booking['id'];

    $update = mysqli_prepare($con, "
        UPDATE requests
        SET status = 'confirmed',
            payment_status = ?,
            payment_method = ?,
            razorpay_payment_id = ?,
            receipt_no = ?,
            paid_at = ?
        WHERE id = ?
          AND razorpay_order_id = ?
          AND payment_status <> 'paid'
    ");

    mysqli_stmt_bind_param(
        $update,
        'sssssis',
        $paid,
        $method,
        $paymentId,
        $receipt,
        $now,
        $bookingId,
        $orderId
    );

    if (!mysqli_stmt_execute($update)) {
        throw new Exception('Could not update reservation.');
    }

    if (mysqli_stmt_affected_rows($update) !== 1) {
        throw new Exception('Reservation update was not completed.');
    }

    mysqli_commit($con);

    webhookResponse('Payment successfully recorded.');

} catch (Throwable $e) {

    mysqli_rollback($con);

    error_log('SmartPark Razorpay webhook: ' . $e->getMessage());

    http_response_code(500);
    webhookResponse('Webhook processing failed.');
}

?>
