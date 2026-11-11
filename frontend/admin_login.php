<?php require_once '../backend/db_connect.php'; require_once '../backend/auth.php'; if (!empty($_SESSION['admin_id'])){
    header('Location: admin.php');exit;}
$error='';if ($_SERVER['REQUEST_METHOD']==='POST'){
    $email=trim($_POST['email']??'');$password=$_POST['password']??'';$s=mysqli_prepare($con,'SELECT id,username,email,password FROM admin WHERE email=? LIMIT 1');mysqli_stmt_bind_param($s,'s',$email);mysqli_stmt_execute($s);$a=mysqli_fetch_assoc(mysqli_stmt_get_result($s));if ($a&&($password===$a['password']||password_verify($password,$a['password']))){
        session_regenerate_id(true);$_SESSION['admin_id']=$a['id'];$_SESSION['admin_username']=$a['username'];$_SESSION['email']=$a['email'];header('Location: admin.php');exit;}
    $error='Invalid admin credentials.';}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login | ParkSmart</title>
<link rel="stylesheet" href="assets/css/smartpark-motion.css">
<link rel="stylesheet" href="assets/css/smartpark-theme.css"><link rel="stylesheet" href="assets/css/smartpark-refresh.css">
<link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">


<style>
.password-input-wrap {
    position: relative;
    width: 100%;
}

.password-input-wrap input {
    padding-right: 48px !important;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: none;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    z-index: 5;
}

.password-toggle svg {
    width: 20px;
    height: 20px;
}
</style>


</head>
<body>
<div class="auth-page">
<div class="auth-card">
<a class="brand" href="index.php">
<i class="fa fa-car">
</i>ParkSmart</a>
<h1 style="margin-top:25px">Admin sign in</h1>
<p class="muted">Manage parking, customers and reservations.</p>
<?php if ($error):?>
<div class="alert alert-error">
<?=e($error)?>
</div>
<?php endif;?>
<form method="post">
<div class="form-group">
<label>Email</label>
<input class="form-control" type="email" name="email" required>
</div>

<div class="form-group">
    <label>Password</label>
    <div class="password-input-wrap">
        <input class="form-control" type="password"
               name="password" id="adminLoginPassword" required>

        <button type="button" class="password-toggle"
                data-target="adminLoginPassword"
                aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </button>
    </div>
</div>

<button class="btn btn-primary" style="width:100%">Sign in</button>
</form>
<p class="muted" style="text-align:center">
<a href="index.php">Back to customer portal</a>
</p>
</div>
</div>
<div style="position:fixed;left:18px;bottom:18px;z-index:20;display:flex;gap:8px"><a class="btn btn-light btn-sm" href="index.php"><i class="fa fa-home"></i> Home</a><a class="btn btn-light btn-sm" href="javascript:history.back()"><i class="fa fa-arrow-left"></i> Back</a></div>


<script>
document.addEventListener('click', function(event) {
    const button = event.target.closest('.password-toggle');
    if (!button) return;

    const input = document.getElementById(button.dataset.target);
    if (!input) return;

    const showing = input.type === 'password';
    input.type = showing ? 'text' : 'password';

    button.innerHTML = showing
        ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M3 3l18 18"/>
             <path d="M9.9 5.2A10.5 10.5 0 0112 5c6.4 0 10 7 10 7a14 14 0 01-3.2 3.8"/>
             <path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 004-.8"/>
           </svg>`
        : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/>
             <circle cx="12" cy="12" r="3"/>
           </svg>`;

    button.setAttribute(
        'aria-label',
        showing ? 'Hide password' : 'Show password'
    );
});
</script>


<script src="assets/js/smartpark-motion.js"></script>

</body>
</html>
