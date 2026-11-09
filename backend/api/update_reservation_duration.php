<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';

require_customer();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'ok' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$id = (int)($_POST['request_id'] ?? 0);
$hours = (int)($_POST['hours'] ?? 0);
$email = $_SESSION['driver_email'];

if ($id < 1 || $hours < 1 || $hours > 24) {
    echo json_encode([
        'ok' => false,
        'message' => 'Please select between 1 and 24 hours.'
    ]);
    exit;
}

mysqli_begin_transaction($con);

try {
    // Lock the reservation and read its parking price.
    $s = mysqli_prepare($con, "
        SELECT
            r.id,
            r.parking_id,
            r.status,
            r.payment_status,
            p.price
        FROM requests r
        INNER JOIN parkings p ON p.id = r.parking_id
        WHERE r.id = ? AND r.customer = ?
        LIMIT 1
        FOR UPDATE
    ");

    mysqli_stmt_bind_param($s, 'is', $id, $email);
    mysqli_stmt_execute($s);

    $booking = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

    if (!$booking) {
        throw new Exception('Reservation not found.');
    }

    if (
        strtolower($booking['status']) !== 'requested' ||
        strtolower($booking['payment_status'] ?? 'unpaid') !== 'unpaid'
    ) {
        throw new Exception('Only unpaid, pending reservations can be edited.');
    }

    $hourlyPrice = (float)$booking['price'];
    $newCost = $hours * $hourlyPrice;

    // Update the existing reservation, not a new reservation.
    // Clear the old Razorpay order so it cannot be verified again.
    $u = mysqli_prepare($con, "
        UPDATE requests
        SET hours = ?,
            cost = ?,
            razorpay_order_id = NULL
        WHERE id = ? AND customer = ?
    ");

    mysqli_stmt_bind_param(
        $u,
        'idis',
        $hours,
        $newCost,
        $id,
        $email
    );

    if (!mysqli_stmt_execute($u)) {
        throw new Exception('Could not update reservation duration.');
    }

    mysqli_commit($con);

    echo json_encode([
        'ok' => true,
        'message' => 'Reservation duration updated successfully.',
        'request_id' => $id,
        'hours' => $hours,
        'cost' => number_format($newCost, 2, '.', '')
    ]);

} catch (Throwable $e) {
    mysqli_rollback($con);

    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
}
