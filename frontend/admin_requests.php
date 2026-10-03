<?php require_once '../backend/db_connect.php'; require_once '../backend/auth.php'; require_admin(); $msg='';$error='';$edit=(int)($_GET['edit']??0); if ($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['mode']??'')==='status'){
    $id=(int)$_POST['id'];$status=$_POST['status'];$allowed=['requested','Approved','Completed','Cancelled'];if (!in_array($status,$allowed,true))$error='Invalid status.';else{
        $s=mysqli_prepare($con,'SELECT status,parking_id FROM requests WHERE id=?');mysqli_stmt_bind_param($s,'i',$id);mysqli_stmt_execute($s);$old=mysqli_fetch_assoc(mysqli_stmt_get_result($s));if ($old){
            mysqli_begin_transaction($con);$s=mysqli_prepare($con,'UPDATE requests SET status=? WHERE id=?');mysqli_stmt_bind_param($s,'si',$status,$id);$ok=mysqli_stmt_execute($s);if ($ok&&$old['status']!==$status){
                $oldActive=in_array(strtolower($old['status']),['requested','approved'],true);$newActive=in_array(strtolower($status),['requested','approved'],true);if ($oldActive&&!$newActive){
                    $u=mysqli_prepare($con,'UPDATE parkings SET remaining_slots=remaining_slots+1 WHERE id=?');mysqli_stmt_bind_param($u,'i',$old['parking_id']);$ok=mysqli_stmt_execute($u);}
                elseif (!$oldActive&&$newActive){
                    $u=mysqli_prepare($con,'UPDATE parkings SET remaining_slots=GREATEST(remaining_slots-1,0) WHERE id=? AND remaining_slots>0');mysqli_stmt_bind_param($u,'i',$old['parking_id']);$ok=mysqli_stmt_execute($u);}
            }
            if ($ok){
                mysqli_commit($con);$msg='Reservation status updated.';}
            else{
                mysqli_rollback($con);$error='Could not update reservation.';}
        }
        else$error='Reservation not found.';}
}
$list=mysqli_query($con,'SELECT r.*,p.name parking_name FROM requests r LEFT JOIN parkings p ON p.id=r.parking_id ORDER BY r.id DESC'); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reservation Management | ParkSmart</title>
<link rel="stylesheet" href="assets/css/smartpark-motion.css">
<link rel="stylesheet" href="assets/css/smartpark-theme.css"><link rel="stylesheet" href="assets/css/smartpark-refresh.css">
<link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">
</head>
<body>
<header class="topbar">
<a class="brand" href="admin.php">
<i class="fa fa-car">
</i>ParkSmart Admin</a>
<nav class="navlinks">
<a href="admin.php">Dashboard</a>
<a href="admin_parking.php">Parking</a>
<a href="admin_customers.php">Customers</a>
<a class="btn btn-light" href="admin.php">Home</a><a class="btn btn-light" href="javascript:history.back()">Back</a><a class="btn btn-light" href="javascript:history.forward()">Next</a><a class="btn btn-light" href="logout.php">Logout</a>
</nav>
</header>
<main class="dashboard">
<div class="container">
<h1>Reservation management</h1>
<p class="muted">Approve, complete or cancel customer reservations.</p>
<?php if ($msg):?>
<div class="alert alert-success">
<?=e($msg)?>
</div>
<?php endif;?>
<?php if ($error):?>
<div class="alert alert-error">
<?=e($error)?>
</div>
<?php endif;?>
<div class="card table-wrap">
<table class="table">
<tr>
<th>ID</th>
<th>Customer</th>
<th>Parking</th>
<th>Hours</th>
<th>Cost</th>
<th>Payment</th>
<th>Date</th>
<th>Status</th>
<th>Update</th>
</tr>
<?php while ($r=mysqli_fetch_assoc($list)):?>
<tr>
<td>#
<?=e($r['id'])?>
</td>
<td>
<?=e($r['customer'])?>
</td>
<td>
<?=e($r['parking_name']??'Deleted')?>
</td>
<td>
<?=e($r['hours'])?>
</td>
<td>₹
<?=e($r['cost'])?>
<br><small><?=e(strtoupper($r['payment_status']??'unpaid'))?><?php if(!empty($r['payment_method'])):?> · <?=e($r['payment_method'])?><?php endif;?><?php if(!empty($r['upi_id'])):?> · <?=e($r['upi_id'])?><?php endif;?></small>
</td>
<td>
<?=e($r['time'])?>
</td>
<td>
<span class="badge
<?=strtolower($r['status'])==='requested'?'':'red'?>
">
<?=e($r['status'])?>
</span>
</td>
<td>
<form method="post" class="inline-form">
<input type="hidden" name="mode" value="status">
<input type="hidden" name="id" value="
<?=$r['id']?>
">
<select class="form-control compact" name="status">
<option value="requested"
<?=$r['status']==='requested'?'selected':''?>
>Requested</option>
<option value="Approved"
<?=$r['status']==='Approved'?'selected':''?>
>Approved</option>
<option value="Completed"
<?=$r['status']==='Completed'?'selected':''?>
>Completed</option>
<option value="Cancelled"
<?=$r['status']==='Cancelled'?'selected':''?>
>Cancelled</option>
</select>
<button class="btn btn-primary btn-sm">Save</button>
</form>
</td>
</tr>
<?php endwhile;?>
</table>
</div>
</div>
</main>
<script src="assets/js/smartpark-motion.js"></script></body>
</html>
