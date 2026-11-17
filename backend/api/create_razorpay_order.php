<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';
require_customer();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'message'=>'Invalid request']); exit; }
if (!RAZORPAY_KEY_ID || !RAZORPAY_KEY_SECRET || strpos(RAZORPAY_KEY_ID,'REPLACE_WITH') !== false) {
    echo json_encode(['ok'=>false,'message'=>'Online payment gateway is not configured. Add RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET to .env.']); exit;
}
$id=(int)($_POST['request_id']??0);
$email=$_SESSION['driver_email'];

$s=mysqli_prepare($con,"SELECT r.*,p.name parking_name,p.location FROM requests r LEFT JOIN parkings p ON p.id=r.parking_id WHERE r.id=? AND r.customer=? LIMIT 1");
mysqli_stmt_bind_param($s,'is',$id,$email); mysqli_stmt_execute($s);
$b=mysqli_fetch_assoc(mysqli_stmt_get_result($s));
if(!$b){ echo json_encode(['ok'=>false,'message'=>'Reservation not found.']); exit; }
if(($b['payment_status']??'unpaid')==='paid'){ echo json_encode(['ok'=>false,'message'=>'This reservation is already paid.']); exit; }
if (strtolower($b['status'] ?? '') !== 'requested') {
    echo json_encode([
        'ok' => false,
        'message' => 'This reservation is not eligible for payment.'
    ]);
    exit;
}

if (strtolower($b['payment_status'] ?? 'unpaid') !== 'unpaid') {
    echo json_encode([
        'ok' => false,
        'message' => 'This reservation payment cannot be processed.'
    ]);
    exit;
}
$amount=(int)round(((float)$b['cost'])*100);
if ($amount < 100) {
    echo json_encode([
        'ok' => false,
        'message' => 'Invalid reservation amount.'
    ]);
    exit;
}
$receipt='SP-'.$id.'-'.date('YmdHis');

$payload=json_encode([
    'amount'=>$amount,
    'currency'=>'INR',
    'receipt'=>$receipt,
    'notes'=>['request_id'=>(string)$id,'customer'=>$email]
]);
$ch=curl_init('https://api.razorpay.com/v1/orders');
curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_POST=>true,
    CURLOPT_POSTFIELDS=>$payload,
    CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
    CURLOPT_USERPWD=>RAZORPAY_KEY_ID.':'.RAZORPAY_KEY_SECRET,
    CURLOPT_TIMEOUT=>20
]);
$out=curl_exec($ch); $http=curl_getinfo($ch,CURLINFO_HTTP_CODE); $curlErr=curl_error($ch); curl_close($ch);
if($out===false || $http<200 || $http>=300){
    echo json_encode(['ok'=>false,'message'=>'Unable to create payment order. '.($curlErr?:'Check Razorpay keys/cURL/Internet.')]); exit;
}
$order=json_decode($out,true);
if(empty($order['id'])){ echo json_encode(['ok'=>false,'message'=>'Invalid payment gateway response.']); exit; }

$u = mysqli_prepare($con, "
    UPDATE requests
    SET razorpay_order_id = ?,
        receipt_no = ?,
        payment_status = 'unpaid'
    WHERE id = ?
      AND customer = ?
      AND status = 'requested'
      AND payment_status = 'unpaid'
");

mysqli_stmt_bind_param($u, 'ssis', $order['id'], $receipt, $id, $email);

if (!mysqli_stmt_execute($u) || mysqli_stmt_affected_rows($u) !== 1) {
    error_log('SmartPark: Failed to save Razorpay order ID for reservation ' . $id);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to save payment order. Please try again.'
    ]);
    exit;
}

echo json_encode([
 'ok'=>true,
 'key_id'=>RAZORPAY_KEY_ID,
 'order_id'=>$order['id'],
 'amount'=>$amount,
 'currency'=>'INR',
 'name'=>SMARTPARK_MERCHANT_NAME,
 'description'=>'SmartPark Reservation #'.$id,
 'receipt'=>$receipt,
 'prefill_name'=>$_SESSION['driver_name']??'Customer',
 'prefill_email'=>$email
]);
