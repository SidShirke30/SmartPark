<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';
require_customer();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'message'=>'Invalid request']); exit; }
if (!RAZORPAY_KEY_SECRET) { echo json_encode(['ok'=>false,'message'=>'Payment gateway is not configured.']); exit; }

$id=(int)($_POST['request_id']??0);
$orderId=trim($_POST['razorpay_order_id']??'');
$paymentId=trim($_POST['razorpay_payment_id']??'');
$signature=trim($_POST['razorpay_signature']??'');
$email=$_SESSION['driver_email'];

$s=mysqli_prepare($con,'SELECT id,cost,customer,razorpay_order_id,payment_status,receipt_no FROM requests WHERE id=? AND customer=? LIMIT 1');
mysqli_stmt_bind_param($s,'is',$id,$email); mysqli_stmt_execute($s);
$b=mysqli_fetch_assoc(mysqli_stmt_get_result($s));
if(!$b){ echo json_encode(['ok'=>false,'message'=>'Reservation not found.']); exit; }
if(($b['payment_status']??'')==='paid'){ echo json_encode(['ok'=>true,'receipt_url'=>'receipt.php?id='.$id]); exit; }
if(!$orderId || !$paymentId || !$signature || $orderId!==($b['razorpay_order_id']??'')){
    echo json_encode(['ok'=>false,'message'=>'Payment verification data is incomplete.']); exit;
}
$expected=hash_hmac('sha256',$orderId.'|'.$paymentId,RAZORPAY_KEY_SECRET);
if(!hash_equals($expected,$signature)){ echo json_encode(['ok'=>false,'message'=>'Payment signature verification failed.']); exit; }

$paid='paid'; $method='Online (Razorpay)';
$receipt=$b['receipt_no'] ?: ('SP-'.$id.'-'.date('YmdHis'));
$now=date('Y-m-d H:i:s');
$u=mysqli_prepare($con,"UPDATE requests SET status='confirmed',payment_status=?,payment_method=?,razorpay_payment_id=?,razorpay_signature=?,receipt_no=?,paid_at=? WHERE id=? AND customer=?");
mysqli_stmt_bind_param($u,'ssssssis',$paid,$method,$paymentId,$signature,$receipt,$now,$id,$email);
if(!mysqli_stmt_execute($u)){ echo json_encode(['ok'=>false,'message'=>'Payment verified but reservation update failed.']); exit; }

echo json_encode(['ok'=>true,'message'=>'Payment verified successfully.','receipt_url'=>'receipt.php?id='.$id]);
