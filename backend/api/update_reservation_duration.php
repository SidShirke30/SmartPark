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
$newParkingId = (int)($_POST['parking_id'] ?? 0);
$hours = (int)($_POST['hours'] ?? 0);
$email = $_SESSION['driver_email'];

if ($id < 1 || $newParkingId < 1 || $hours < 1 || $hours > 24) {
    echo json_encode([
        'ok' => false,
        'message' => 'Please select a valid parking location and between 1 and 24 hours.'
    ]);
    exit;
}

mysqli_begin_transaction($con);

try {
    // Lock and verify the customer's existing reservation.
    $s = mysqli_prepare($con, "
        SELECT id, parking_id, status, payment_status
        FROM requests
        WHERE id = ? AND customer = ?
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

    $oldParkingId = (int)$booking['parking_id'];

    // Lock both parking records before changing availability.
    $lock = mysqli_prepare($con, "
        SELECT id, name, city, location, price, remaining_slots
        FROM parkings
        WHERE id IN (?, ?)
        ORDER BY id
        FOR UPDATE
    ");

    mysqli_stmt_bind_param($lock, 'ii', $oldParkingId, $newParkingId);
    mysqli_stmt_execute($lock);

    $result = mysqli_stmt_get_result($lock);
    $parkings = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $parkings[(int)$row['id']] = $row;
    }

    if (!isset($parkings[$oldParkingId])) {
        throw new Exception('Your existing parking location could not be found.');
    }

    if (!isset($parkings[$newParkingId])) {
        throw new Exception('The selected parking location could not be found.');
    }

    $newParking = $parkings[$newParkingId];

    // A different parking location must have an available space.
    if (
        $newParkingId !== $oldParkingId &&
        (int)$newParking['remaining_slots'] < 1
    ) {
        throw new Exception('The selected parking location is full.');
    }

    $hourlyPrice = (float)$newParking['price'];
    $newCost = $hours * $hourlyPrice;

    // Move the reserved space only when the parking location changes.
    if ($newParkingId !== $oldParkingId) {

        $release = mysqli_prepare($con, "
            UPDATE parkings
            SET remaining_slots = remaining_slots + 1
            WHERE id = ?
        ");

        mysqli_stmt_bind_param($release, 'i', $oldParkingId);

        if (!mysqli_stmt_execute($release)) {
            throw new Exception('Could not release the previous parking space.');
        }

        $reserve = mysqli_prepare($con, "
            UPDATE parkings
            SET remaining_slots = remaining_slots - 1
            WHERE id = ? AND remaining_slots > 0
        ");

        mysqli_stmt_bind_param($reserve, 'i', $newParkingId);

        if (!mysqli_stmt_execute($reserve) || mysqli_stmt_affected_rows($reserve) !== 1) {
            throw new Exception('The selected parking space is no longer available.');
        }
    }

    // Update the same reservation and invalidate any previous Razorpay order.
    $update = mysqli_prepare($con, "
        UPDATE requests
        SET parking_id = ?,
            hours = ?,
            cost = ?,
            razorpay_order_id = NULL
        WHERE id = ? AND customer = ?
    ");

    mysqli_stmt_bind_param(
        $update,
        'iidis',
        $newParkingId,
        $hours,
        $newCost,
        $id,
        $email
    );

    if (!mysqli_stmt_execute($update)) {
        throw new Exception('Could not update your reservation.');
    }

    mysqli_commit($con);

    echo json_encode([
        'ok' => true,
        'message' => 'Reservation updated successfully.',
        'request_id' => $id,
        'parking_id' => $newParkingId,
        'parking_name' => $newParking['name'],
        'city' => $newParking['city'] ?? $newParking['location'],
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
