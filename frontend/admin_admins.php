<?php require_once '../backend/db_connect.php'; require_once '../backend/auth.php'; require_admin(); $msg=''; $error=''; $edit=(int)($_GET['edit']??0); if ($_SERVER['REQUEST_METHOD']==='POST'){
    $mode=$_POST['mode']??''; if ($mode==='delete'){
        $id=(int)($_POST['id']??0); if ($id===(int)$_SESSION['admin_id']) $error='You cannot delete the admin account you are currently using.'; else {
            $s=mysqli_prepare($con,'DELETE FROM admin WHERE id=?'); mysqli_stmt_bind_param($s,'i',$id); $msg=mysqli_stmt_execute($s)?'Admin account deleted.':'Could not delete admin account.'; if (strpos($msg,'Could not')===false)$edit=0; }
    }
    if ($mode==='save'){
        $id=(int)($_POST['id']??0); $username=trim($_POST['username']??''); $email=trim($_POST['email']??''); $password=$_POST['password']??''; if ($username==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Enter a valid username and email.'; else {
            $s=mysqli_prepare($con,'SELECT id FROM admin WHERE (username=? OR email=?) AND id<>? LIMIT 1'); mysqli_stmt_bind_param($s,'ssi',$username,$email,$id); mysqli_stmt_execute($s); if (mysqli_num_rows(mysqli_stmt_get_result($s))) $error='That username or email is already in use.'; elseif (!$id && $password==='') $error='Password is required for a new admin.'; else {
                if ($id){
                    if ($password!=='') {
                        $hash=password_hash($password,PASSWORD_DEFAULT); $s=mysqli_prepare($con,'UPDATE admin SET username=?,email=?,password=? WHERE id=?'); mysqli_stmt_bind_param($s,'sssi',$username,$email,$hash,$id); }
                    else {
                        $s=mysqli_prepare($con,'UPDATE admin SET username=?,email=? WHERE id=?'); mysqli_stmt_bind_param($s,'ssi',$username,$email,$id); }
                    if (mysqli_stmt_execute($s)) {
                        $msg='Admin account updated.'; if ($id===(int)$_SESSION['admin_id']) {
                            $_SESSION['admin_username']=$username; $_SESSION['email']=$email; }
                    }
                    else $error='Could not update admin account.'; }
                else {
                    $hash=password_hash($password,PASSWORD_DEFAULT); $s=mysqli_prepare($con,'INSERT INTO admin(username,password,email) VALUES(?,?,?)'); mysqli_stmt_bind_param($s,'sss',$username,$hash,$email); $msg=mysqli_stmt_execute($s)?'New admin account created.':'Could not create admin account.'; }
            }
        }
    }
}
$editing=null; if ($edit){
    $s=mysqli_prepare($con,'SELECT id,username,email FROM admin WHERE id=?');mysqli_stmt_bind_param($s,'i',$edit);mysqli_stmt_execute($s);$editing=mysqli_fetch_assoc(mysqli_stmt_get_result($s));if (!$editing){
        $edit=0;$error='Admin not found.';}
}
$list=mysqli_query($con,'SELECT id,username,email FROM admin ORDER BY id DESC'); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Accounts | ParkSmart</title>
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
<a href="admin_requests.php">Requests</a>
<a class="btn btn-light" href="admin_password.php">Change password</a>
<a class="btn btn-light" href="admin.php">Home</a><a class="btn btn-light" href="javascript:history.back()">Back</a><a class="btn btn-light" href="javascript:history.forward()">Next</a><a class="btn btn-light" href="logout.php">Logout</a>
</nav>
</header>
<main class="dashboard">
<div class="container">
<div class="dashboard-head">
<div>
<div class="muted">Security & access</div>
<h1>Admin accounts</h1>
<p class="muted">Add administrators, edit access details and remove old accounts safely.</p>
</div>
<a class="btn btn-primary" href="admin_admins.php#form">
<i class="fa fa-plus">
</i> Add admin</a>
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
<div class="card form-card" id="form">
<h2>
<?=$editing?'Edit admin':'Create admin'?>
</h2>
<p class="muted">Passwords are securely hashed for new or changed accounts.</p>
<form method="post">
<input type="hidden" name="mode" value="save">
<input type="hidden" name="id" value="
<?=e($editing['id']??0)?>
">
<div class="form-grid">
<div class="form-group">
<label>Username</label>
<input class="form-control" name="username" value="
<?=e($editing['username']??'')?>
" required>
</div>
<div class="form-group">
<label>Email</label>
<input class="form-control" type="email" name="email" value="
<?=e($editing['email']??'')?>
" required>
</div>
<div class="form-group">
<label>Password
<?=$editing?'<span class="muted">(leave blank to keep current)</span>':''?>
</label>
<input class="form-control" type="password" name="password"
<?=$editing?'':'required'?>
minlength="4">
</div>
</div>
<button class="btn btn-primary">
<i class="fa fa-save">
</i> Save admin</button>
<?php if ($editing):?>
<a class="btn btn-light" href="admin_admins.php">Cancel</a>
<?php endif;?>
</form>
</div>
<div class="card table-wrap">
<table class="table">
<tr>
<th>Username</th>
<th>Email</th>
<th>Account</th>
<th>Actions</th>
</tr>
<?php while ($a=mysqli_fetch_assoc($list)):?>
<tr>
<td>
<strong>
<?=e($a['username'])?>
</strong>
<?php if ((int)$a['id']===(int)$_SESSION['admin_id']):?>
<span class="badge">You</span>
<?php endif;?>
</td>
<td>
<?=e($a['email'])?>
</td>
<td>
<span class="badge">Administrator</span>
</td>
<td class="actions">
<a class="btn btn-light btn-sm" href="admin_admins.php?edit=
<?=$a['id']?>
#form">Edit</a>
<?php if ((int)$a['id']!==(int)$_SESSION['admin_id']):?>
<form method="post" onsubmit="return confirm('Delete this admin account?')">
<input type="hidden" name="mode" value="delete">
<input type="hidden" name="id" value="
<?=$a['id']?>
">
<button class="btn btn-danger btn-sm">Delete</button>
</form>
<?php endif;?>
</td>
</tr>
<?php endwhile;?>
</table>
</div>
</div>
</main>
<script src="assets/js/smartpark-motion.js"></script></body>
</html>
