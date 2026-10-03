<?php
require_once __DIR__ . '/../db_connect.php'; require_once __DIR__ . '/../auth.php'; require_customer();
header('Content-Type: application/json');
$id=(int)($_POST['request_id']??0); $email=$_SESSION['driver_email'];
$s=mysqli_prepare($con,'SELECT id,payment_status FROM requests WHERE id=? AND customer=? LIMIT 1'); mysqli_stmt_bind_param($s,'is',$id,$email); mysqli_stmt_execute($s); $b=mysqli_fetch_assoc(mysqli_stmt_get_result($s));
if(!$b){echo json_encode(['ok'=>false,'message'=>'Reservation not found.']);exit;}
if(($b['payment_status']??'')==='paid'){echo json_encode(['ok'=>false,'message'=>'Already paid.']);exit;}
$status='pending';$method='Cash on Payment';$u=mysqli_prepare($con,'UPDATE requests SET payment_status=?,payment_method=?,upi_id=NULL WHERE id=? AND customer=?');mysqli_stmt_bind_param($u,'ssis',$status,$method,$id,$email);
echo json_encode(mysqli_stmt_execute($u)?['ok'=>true]:['ok'=>false,'message'=>'Could not update payment status.']);
