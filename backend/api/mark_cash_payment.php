<?php

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';

require_customer();

header('Content-Type: application/json');

function cashResponse($ok, $message)
{
    echo json_encode([
        'ok' => $ok,
        'message' => $message
    ]);
    exit;
}

// POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cashResponse(false, 'Invalid request.');
}

$id = (int)($_POST['request_id'] ?? 0);
$email = $_SESSION['driver_email'] ?? '';

if ($id < 1 || $email === '') {
    cashResponse(false, 'Invalid reservation details.');
}

// Check reservation ownership and status.
$s = mysqli_prepare($con, "
    SELECT id, status, payment_status
    FROM requests
    WHERE id = ? AND customer = ?
    LIMIT 1
");

mysqli_stmt_bind_param($s, 'is', $id, $email);
mysqli_stmt_execute($s);

$b = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

if (!$b) {
    cashResponse(false, 'Reservation not found.');
}

if (strtolower($b['payment_status'] ?? '') === 'paid') {
    cashResponse(false, 'This reservation is already paid.');
}

if (strtolower($b['status'] ?? '') !== 'requested') {
    cashResponse(false, 'This reservation is not eligible for cash payment.');
}

// Update only an unpaid requested reservation.
$status = 'pending';
$method = 'Cash on Payment';

$u = mysqli_prepare($con, "
    UPDATE requests
    SET payment_status = ?,
        payment_method = ?,
        upi_id = NULL
    WHERE id = ?
      AND customer = ?
      AND status = 'requested'
      AND payment_status = 'unpaid'
");

mysqli_stmt_bind_param($u, 'ssis', $status, $method, $id, $email);

if (!mysqli_stmt_execute($u)) {
    error_log('Cash payment update failed: ' . mysqli_stmt_error($u));
    cashResponse(false, 'Could not update payment status.');
}

if (mysqli_stmt_affected_rows($u) !== 1) {
    cashResponse(false, 'Reservation status changed. Please refresh and try again.');
}

cashResponse(true, 'Cash payment marked as pending.');
?>
