<?php require_once '../backend/db_connect.php'; require_once '../backend/auth.php'; require_customer(); if ($_SERVER['REQUEST_METHOD']!=='POST'){
    header('Location: home.php');exit;}
$id=(int)($_POST['id']??0);$email=$_SESSION['driver_email']; $s=mysqli_prepare($con,"SELECT id,parking_id,status FROM requests WHERE id=? AND customer=? LIMIT 1");mysqli_stmt_bind_param($s,'is',$id,$email);mysqli_stmt_execute($s);$r=mysqli_fetch_assoc(mysqli_stmt_get_result($s)); if ($r&&strtolower($r['status'])==='requested'){
    $s=mysqli_prepare($con,"UPDATE requests SET status='Cancelled' WHERE id=?");mysqli_stmt_bind_param($s,'i',$id);$ok=mysqli_stmt_execute($s);if ($ok){
        $u=mysqli_prepare($con,'UPDATE parkings SET remaining_slots=remaining_slots+1 WHERE id=?');mysqli_stmt_bind_param($u,'i',$r['parking_id']);mysqli_stmt_execute($u);$_SESSION['flash']='Reservation cancelled successfully.';}
}
else$_SESSION['flash']='This reservation cannot be cancelled.';header('Location: home.php');exit;
