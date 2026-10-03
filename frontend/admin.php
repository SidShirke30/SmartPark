<?php require_once '../backend/db_connect.php'; require_once '../backend/auth.php'; require_admin(); function count_rows($con,$sql){
    $r=mysqli_query($con,$sql);return $r?mysqli_num_rows($r):0;}
$parking=mysqli_query($con,'SELECT * FROM parkings ORDER BY id DESC'); $requests=mysqli_query($con,'SELECT r.*,p.name parking_name FROM requests r LEFT JOIN parkings p ON p.id=r.parking_id ORDER BY r.id DESC'); $users=mysqli_query($con,'SELECT id,name,email FROM users ORDER BY id DESC'); $totalP=count_rows($con,'SELECT id FROM parkings'); $totalR=count_rows($con,'SELECT id FROM requests'); $totalU=count_rows($con,'SELECT id FROM users'); $pending=count_rows($con,"SELECT id FROM requests WHERE LOWER(status)='requested'"); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard | ParkSmart</title>
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
<a href="admin_parking.php">Parking</a>
<a href="admin_customers.php">Customers</a>
<a href="admin_requests.php">Requests</a>
<a href="admin_admins.php">Admins</a>
<a href="admin_password.php">Password</a>
<span class="muted">Hi,
<?=e($_SESSION['admin_username'])?>
</span>
<a class="btn btn-light" href="admin.php">Home</a><a class="btn btn-light" href="javascript:history.back()">Back</a><a class="btn btn-light" href="javascript:history.forward()">Next</a><a class="btn btn-light" href="logout.php">Logout</a>
</nav>
</header>
<main class="dashboard">
<div class="container">
<div class="dashboard-head">
<div>
<div class="muted">Administration</div>
<h1>Control center</h1>
<p class="muted">Manage parking locations, customers and reservations from one place.</p>
</div>
<div class="quick-actions">
<a class="btn btn-primary" href="admin_parking.php?action=add">
<i class="fa fa-plus">
</i> Add parking</a>
<a class="btn btn-light" href="admin_customers.php">Manage customers</a>
<a class="btn btn-dark" href="admin_admins.php">Manage admins</a>
</div>
</div>
<div class="stats">
<div class="card stat">
<strong>
<?=$totalP?>
</strong>
<span>Parking locations</span>
</div>
<div class="card stat">
<strong>
<?=$totalR?>
</strong>
<span>Reservations</span>
</div>
<div class="card stat">
<strong>
<?=$totalU?>
</strong>
<span>Customers</span>
</div>
<div class="card stat">
<strong>
<?=$pending?>
</strong>
<span>Pending requests</span>
</div>
</div>
<section class="section">
<div class="section-title">
<h2>Parking locations</h2>
<p>Update slots, prices and parking information.</p>
</div>
<div class="card table-wrap">
<table class="table">
<tr>
<th>Name</th>
<th>Location</th>
<th>Slots</th>
<th>Available</th>
<th>Price/hour</th>
<th>Action</th>
</tr>
<?php while ($p=mysqli_fetch_assoc($parking)):?>
<tr>
<td>
<?=e($p['name'])?>
</td>
<td>
<?=e($p['location'])?>
,
<?=e($p['street'])?>
</td>
<td>
<?=e($p['slot'])?>
</td>
<td>
<?=e($p['remaining_slots'])?>
</td>
<td>₹
<?=e($p['price'])?>
</td>
<td>
<a class="btn btn-light btn-sm" href="admin_parking.php?action=edit&id=
<?=$p['id']?>
">Edit</a>
</td>
</tr>
<?php endwhile;?>
</table>
</div>
</section>
<section class="section">
<div class="section-title">
<h2>Latest reservations</h2>
<p>Change reservation status when the parking team processes a booking.</p>
</div>
<div class="card table-wrap">
<table class="table">
<tr>
<th>ID</th>
<th>Customer</th>
<th>Parking</th>
<th>Hours</th>
<th>Cost</th>
<th>Status</th>
<th>Action</th>
</tr>
<?php while ($r=mysqli_fetch_assoc($requests)):?>
<tr>
<td>#
<?=e($r['id'])?>
</td>
<td>
<?=e($r['customer'])?>
</td>
<td>
<?=e($r['parking_name']??'Deleted parking')?>
</td>
<td>
<?=e($r['hours'])?>
</td>
<td>₹
<?=e($r['cost'])?>
</td>
<td>
<span class="badge
<?=strtolower($r['status'])==='requested'?'':'red'?>
">
<?=e($r['status'])?>
</span>
</td>
<td>
<a class="btn btn-light btn-sm" href="admin_requests.php?edit=
<?=$r['id']?>
">Update</a>
</td>
</tr>
<?php endwhile;?>
</table>
</div>
</section>
<section class="section">
<div class="section-title">
<h2>Administration & security</h2>
<p>Control who can access the management panel.</p>
</div>
<div class="grid">
<div class="card">
<h3>
<i class="fa fa-user-plus">
</i> Admin accounts</h3>
<p class="muted">Create additional administrators, edit account details or remove old accounts.</p>
<a class="btn btn-primary" href="admin_admins.php">Manage admins</a>
</div>
<div class="card">
<h3>
<i class="fa fa-key">
</i> Change password</h3>
<p class="muted">Update your own admin password and keep your account secure.</p>
<a class="btn btn-primary" href="admin_password.php">Change password</a>
</div>
<div class="card">
<h3>
<i class="fa fa-cog">
</i> Account profile</h3>
<p class="muted">Your current account: <strong>
<?=e($_SESSION['admin_username'])?>
</strong>
<br>
<?=e($_SESSION['email']??'')?>
</p>
<a class="btn btn-light" href="admin_admins.php?edit=
<?=e($_SESSION['admin_id'])?>
#form">Edit profile</a>
</div>
</div>
</section>
<section class="section">
<div class="section-title">
<h2>Team & customers</h2>
</div>
<div class="grid">
<div class="card">
<h3>
<i class="fa fa-users">
</i> Customers</h3>
<strong class="big-number">
<?=$totalU?>
</strong>
<p class="muted">View, edit or remove customer accounts.</p>
<a class="btn btn-primary" href="admin_customers.php">Manage customers</a>
</div>
</div>
</section>
</div>
</main>
<script src="assets/js/smartpark-motion.js"></script></body>
</html>
