<?php require_once '../backend/db_connect.php'; require_once '../backend/auth.php'; require_admin(); $msg='';$error=''; if ($_SERVER['REQUEST_METHOD']==='POST'){
    $current=$_POST['current_password']??''; $new=$_POST['new_password']??''; $confirm=$_POST['confirm_password']??''; $s=mysqli_prepare($con,'SELECT password FROM admin WHERE id=? LIMIT 1');$id=(int)$_SESSION['admin_id'];mysqli_stmt_bind_param($s,'i',$id);mysqli_stmt_execute($s);$a=mysqli_fetch_assoc(mysqli_stmt_get_result($s)); $valid=$a && ($current===$a['password'] || password_verify($current,$a['password'])); if (!$valid)$error='Current password is incorrect.'; elseif (strlen($new)<4)$error='New password must be at least 4 characters.'; elseif ($new!==$confirm)$error='New passwords do not match.'; elseif ($current===$new)$error='New password must be different from the current password.'; else {
        $hash=password_hash($new,PASSWORD_DEFAULT);$s=mysqli_prepare($con,'UPDATE admin SET password=? WHERE id=?');mysqli_stmt_bind_param($s,'si',$hash,$id);$msg=mysqli_stmt_execute($s)?'Password changed successfully.':'Could not change password.';}
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Change Password | ParkSmart</title>
<link rel="stylesheet" href="assets/css/smartpark-motion.css">
<link rel="stylesheet" href="assets/css/smartpark-theme.css"><link rel="stylesheet" href="assets/css/smartpark-refresh.css">
<link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">

<style>


.password-input-wrap {
    position: relative;
    width: 100%;
}

.password-input-wrap input {
    padding-right: 50px !important;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: none;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    z-index: 5;
    border-radius: 50%;
    transition: color 0.2s ease, background 0.2s ease;
}

.password-toggle svg {
    width: 20px;
    height: 20px;
    display: block;
}

.password-toggle:hover {
    color: #111827;
    background: #e9edf2;
}

.password-toggle:focus-visible {
    outline: 2px solid #64748b;
    outline-offset: 2px;
}

</style>

</head>
<body>
<header class="topbar">
<a class="brand" href="admin.php">
<i class="fa fa-car">
</i>ParkSmart Admin</a>
<nav class="navlinks">
<a href="admin.php">Dashboard</a>
<a href="admin_admins.php">Admin accounts</a>
<a href="admin_parking.php">Parking</a>
<a href="admin_customers.php">Customers</a>
<a class="btn btn-light" href="admin.php">Home</a><a class="btn btn-light" href="javascript:history.back()">Back</a><a class="btn btn-light" href="javascript:history.forward()">Next</a><a class="btn btn-light" href="logout.php">Logout</a>
</nav>
</header>
<main class="dashboard">
<div class="container">
<div class="card narrow form-card">
<div class="profile-icon">
<i class="fa fa-lock">
</i>
</div>
<h1>Change password</h1>
<p class="muted">Update the password for <strong>
<?=e($_SESSION['admin_username'])?>
</strong>.</p>
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
<form method="post">

<div class="form-group">
    <label>Current password</label>
    <div class="password-input-wrap">
        <input
            class="form-control"
            type="password"
            id="adminCurrentPassword"
            name="current_password"
            required
        >
        <button type="button" class="password-toggle"
                data-target="adminCurrentPassword"
                aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </button>
    </div>
</div>

<div class="form-group">
    <label>New password</label>
    <div class="password-input-wrap">
        <input
            class="form-control"
            type="password"
            id="adminNewPassword"
            name="new_password"
            minlength="4"
            required
        >
        <button type="button" class="password-toggle"
                data-target="adminNewPassword"
                aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </button>
    </div>
</div>

<div class="form-group">
    <label>Confirm new password</label>
    <div class="password-input-wrap">
        <input
            class="form-control"
            type="password"
            id="adminConfirmPassword"
            name="confirm_password"
            minlength="4"
            required
        >
        <button type="button" class="password-toggle"
                data-target="adminConfirmPassword"
                aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </button>
    </div>
</div>

<button class="btn btn-primary">
<i class="fa fa-key">
</i> Change password</button>
</form>
</div>
</div>
</main>
<script src="assets/js/smartpark-motion.js"></script>


<script>
document.querySelectorAll('.password-toggle').forEach(button => {

    button.addEventListener('click', function () {

        const targetId = this.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        const icon = this.querySelector('i');

        if (!passwordInput || !icon) return;

        if (passwordInput.type === 'password') {

            passwordInput.type = 'text';

            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');

            this.setAttribute('aria-label', 'Hide password');

        } else {

            passwordInput.type = 'password';

            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');

            this.setAttribute('aria-label', 'Show password');
        }
    });

});
</script>


</body>
</html>
