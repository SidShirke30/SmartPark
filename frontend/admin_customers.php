<?php require_once '../backend/db_connect.php'; require_once '../backend/auth.php'; require_admin(); $msg='';$error='';$edit=(int)($_GET['edit']??0); if ($_SERVER['REQUEST_METHOD']==='POST'){
    $mode=$_POST['mode']??''; if ($mode==='delete'){
        $id=(int)$_POST['id'];$s=mysqli_prepare($con,'SELECT email FROM users WHERE id=?');mysqli_stmt_bind_param($s,'i',$id);mysqli_stmt_execute($s);$u=mysqli_fetch_assoc(mysqli_stmt_get_result($s));if ($u){
            $s=mysqli_prepare($con,'DELETE FROM users WHERE id=?');mysqli_stmt_bind_param($s,'i',$id);mysqli_stmt_execute($s);$msg='Customer account deleted.';}
        else$error='Customer not found.';}
    if ($mode==='save'){
        $id=(int)$_POST['id'];$name=trim($_POST['name']);$email=trim($_POST['email']);$password=$_POST['password']??'';if ($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL))$error='Enter a valid name and email.';else{
            $s=mysqli_prepare($con,'SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');mysqli_stmt_bind_param($s,'si',$email,$id);mysqli_stmt_execute($s);if (mysqli_num_rows(mysqli_stmt_get_result($s)))$error='That email is already in use.';else if ($id){
                if ($password!==''){
                    $hash=password_hash($password,PASSWORD_DEFAULT);$s=mysqli_prepare($con,'UPDATE users SET name=?,email=?,password=?,password_confirm=? WHERE id=?');mysqli_stmt_bind_param($s,'ssssi',$name,$email,$hash,$hash,$id);}
                else{
                    $s=mysqli_prepare($con,'UPDATE users SET name=?,email=? WHERE id=?');mysqli_stmt_bind_param($s,'ssi',$name,$email,$id);}
                if (mysqli_stmt_execute($s))$msg='Customer account updated.';else$error='Could not update customer.';}
        }
    }
}
$editing=null;if ($edit){
    $s=mysqli_prepare($con,'SELECT id,name,email FROM users WHERE id=?');mysqli_stmt_bind_param($s,'i',$edit);mysqli_stmt_execute($s);$editing=mysqli_fetch_assoc(mysqli_stmt_get_result($s));if (!$editing){
        $edit=0;$error='Customer not found.';}
}
$list=mysqli_query($con,'SELECT u.id,u.name,u.email,COUNT(r.id) reservations FROM users u LEFT JOIN requests r ON r.customer=u.email GROUP BY u.id ORDER BY u.id DESC'); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Customer Management | ParkSmart</title>
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
<a href="admin_requests.php">Requests</a>
<a href="admin_admins.php">Admins</a>
<a href="admin_password.php">Password</a>
<a class="btn btn-light" href="admin.php">Home</a><a class="btn btn-light" href="javascript:history.back()">Back</a><a class="btn btn-light" href="javascript:history.forward()">Next</a><a class="btn btn-light" href="logout.php">Logout</a>
</nav>
</header>
<main class="dashboard">
<div class="container">
<div class="dashboard-head">
<div>
<h1>Customer management</h1>
<p class="muted">Review accounts, update details and manage customer access.</p>
</div>
</div>
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
<?php if ($editing):?>
<div class="card form-card">
<h2>Edit customer</h2>
<form method="post">
<input type="hidden" name="mode" value="save">
<input type="hidden" name="id" value="
<?=$editing['id']?>
">
<div class="form-grid">
<div class="form-group">
<label>Full name</label>
<input class="form-control" name="name" value="
<?=e($editing['name'])?>
" required>
</div>
<div class="form-group">
<label>Email</label>
<input class="form-control" type="email" name="email" value="
<?=e($editing['email'])?>
" required>
</div>
<div class="form-group">
<label>New password <span class="muted">(leave blank to keep current)</span>
</label>
<input class="form-control" type="password" name="password" minlength="6">
</div>
</div>
<button class="btn btn-primary">Update customer</button>
<a class="btn btn-light" href="admin_customers.php">Cancel</a>
</form>
</div>
<?php endif;?>
<div class="card table-wrap">
<table class="table">
<tr>
<th>Name</th>
<th>Email</th>
<th>Reservations</th>
<th>Actions</th>
</tr>
<?php while ($u=mysqli_fetch_assoc($list)):?>
<tr>
<td>
<?=e($u['name'])?>
</td>
<td>
<?=e($u['email'])?>
</td>
<td>
<?=e($u['reservations'])?>
</td>
<td class="actions">
<a class="btn btn-light btn-sm" href="admin_customers.php?edit=
<?=$u['id']?>
">Edit</a>
<form method="post" onsubmit="return confirm('Delete this customer account? Reservation history will remain.')">
<input type="hidden" name="mode" value="delete">
<input type="hidden" name="id" value="
<?=$u['id']?>
">
<button class="btn btn-danger btn-sm">Delete</button>
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
