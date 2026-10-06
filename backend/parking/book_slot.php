<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';
require_customer();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'message'=>'Invalid request']); exit; }
$pid=(int)($_POST['parking_id']??0); $hours=(int)($_POST['hours']??0); $customer=$_SESSION['driver_email'];
if($pid<1 || $hours<1 || $hours>24){ echo json_encode(['ok'=>false,'message'=>'Please enter between 1 and 24 hours.']); exit; }
mysqli_begin_transaction($con);
try {
  $s=mysqli_prepare($con,'SELECT id,name,remaining_slots,price FROM parkings WHERE id=? FOR UPDATE'); mysqli_stmt_bind_param($s,'i',$pid); mysqli_stmt_execute($s); $p=mysqli_fetch_assoc(mysqli_stmt_get_result($s));
  if(!$p || (int)$p['remaining_slots']<1) throw new Exception('This parking location is currently full.');
  $s=mysqli_prepare($con,"SELECT id FROM requests WHERE customer=? AND status='requested' LIMIT 1"); mysqli_stmt_bind_param($s,'s',$customer); mysqli_stmt_execute($s);
  if(mysqli_num_rows(mysqli_stmt_get_result($s))) throw new Exception('You already have a pending reservation. Please pay or cancel it first.');
  $cost=$hours*(float)$p['price']; $new=(int)$p['remaining_slots']-1; $slots=1; $status='requested';
  $s=mysqli_prepare($con,'INSERT INTO requests(parking_id,slots,hours,cost,customer,status,payment_status) VALUES(?,?,?,?,?,?,?)'); $paymentStatus='unpaid'; mysqli_stmt_bind_param($s,'iiidsss',$pid,$slots,$hours,$cost,$customer,$status,$paymentStatus);
  if(!mysqli_stmt_execute($s)) throw new Exception('Could not create reservation.'); $requestId=mysqli_insert_id($con);
  $u=mysqli_prepare($con,'UPDATE parkings SET remaining_slots=? WHERE id=?'); mysqli_stmt_bind_param($u,'ii',$new,$pid); if(!mysqli_stmt_execute($u)) throw new Exception('Could not update parking availability.');
  mysqli_commit($con);
  echo json_encode([
      'ok' => true,
      'message' => 'Reservation created. Please complete payment.',
      'payment_url' => '/payment.php?id=' . $requestId
  ]);
} catch(Throwable $e) { mysqli_rollback($con); echo json_encode(['ok'=>false,'message'=>$e->getMessage()]); }
