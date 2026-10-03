<?php
require_once '../backend/db_connect.php';
require_once '../backend/auth.php';

$error = '';
$message = '';

if (
    empty($_SESSION['forgot_user_id']) ||
    empty($_SESSION['forgot_otp_hash'])
) {
    header('Location: forgot_password.php');
    exit;
}

if (empty($_SESSION['verify_forgot_csrf'])) {
    $_SESSION['verify_forgot_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['verify_forgot_csrf'], $csrf)
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $otp = trim($_POST['otp'] ?? '');

        if (time() > ($_SESSION['forgot_otp_expiry'] ?? 0)) {

            unset(
                $_SESSION['forgot_otp_hash'],
                $_SESSION['forgot_otp_expiry'],
                $_SESSION['forgot_otp_attempts']
            );

            $error = 'OTP expired. Please request a new OTP.';

        } elseif (
            ($_SESSION['forgot_otp_attempts'] ?? 0) >= 5
        ) {

            unset(
                $_SESSION['forgot_otp_hash'],
                $_SESSION['forgot_otp_expiry'],
                $_SESSION['forgot_otp_attempts']
            );

            $error = 'Maximum attempts reached. Request a new OTP.';

        } elseif (
            !preg_match('/^[0-9]{6}$/', $otp) ||
            !password_verify($otp, $_SESSION['forgot_otp_hash'])
        ) {

            $_SESSION['forgot_otp_attempts'] =
                ($_SESSION['forgot_otp_attempts'] ?? 0) + 1;

            $error = 'Incorrect OTP. Please try again.';

        } else {

            $_SESSION['forgot_otp_verified'] = true;
            $_SESSION['forgot_otp_verified_until'] = time() + 600;

            unset(
                $_SESSION['forgot_otp_hash'],
                $_SESSION['forgot_otp_expiry'],
                $_SESSION['forgot_otp_attempts']
            );

            header('Location: reset_password.php');
            exit;
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Verify OTP | ParkSmart</title>

<link rel="stylesheet" href="assets/css/smartpark-motion.css">
<link rel="stylesheet" href="assets/css/smartpark-theme.css">
<link rel="stylesheet" href="assets/css/smartpark-refresh.css">
<link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">

</head>

<body>

<main class="dashboard">

<div class="container narrow">

<div class="card form-card">

<h1>Verify OTP</h1>

<p class="muted">
Enter the 6-digit OTP sent to your registered email.
</p>

<?php if ($error): ?>

<div class="alert alert-error">
<?= e($error) ?>
</div>

<?php endif; ?>

<form method="post">

<input
    type="hidden"
    name="csrf_token"
    value="<?= e($_SESSION['verify_forgot_csrf']) ?>"
>

<div class="form-group">

<label>6-digit OTP</label>

<input
    class="form-control"
    type="text"
    name="otp"
    inputmode="numeric"
    pattern="[0-9]{6}"
    minlength="6"
    maxlength="6"
    placeholder="Enter OTP"
    autocomplete="one-time-code"
    required
>

</div>

<button
    class="btn btn-primary"
    type="submit"
    style="width:100%"
>
    VERIFY OTP
</button>

</form>

<p style="text-align:center;margin-top:20px;">
<a href="forgot_password.php" style="color:var(--sp-blue);">
Request a new OTP
</a>
</p>

<p style="text-align:center;">
<a href="index.php" style="color:var(--sp-blue);">
Back to Login
</a>
</p>

</div>
</div>
</main>

</body>
</html>