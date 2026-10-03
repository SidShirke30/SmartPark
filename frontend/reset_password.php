<?php
require_once '../backend/db_connect.php';
require_once '../backend/auth.php';

$error = '';

if (
    empty($_SESSION['forgot_user_id']) ||
    empty($_SESSION['forgot_otp_verified']) ||
    empty($_SESSION['forgot_otp_verified_until']) ||
    time() > $_SESSION['forgot_otp_verified_until']
) {
    unset(
        $_SESSION['forgot_user_id'],
        $_SESSION['forgot_email'],
        $_SESSION['forgot_otp_verified'],
        $_SESSION['forgot_otp_verified_until']
    );

    header('Location: forgot_password.php');
    exit;
}

if (empty($_SESSION['reset_password_csrf'])) {
    $_SESSION['reset_password_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf_token'] ?? '';

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirm'] ?? '';

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['reset_password_csrf'], $csrf)
    ) {
        $error = 'Invalid request. Please refresh the page.';

    } elseif (strlen($password) < 6) {

        $error = 'Password must contain at least 6 characters.';

    } elseif ($password !== $confirmPassword) {

        $error = 'Passwords do not match.';

    } else {

        $userId = (int) $_SESSION['forgot_user_id'];

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $s = mysqli_prepare(
            $con,
            'UPDATE users SET password=?, password_confirm=? WHERE id=?'
        );

        mysqli_stmt_bind_param(
            $s,
            'ssi',
            $hash,
            $hash,
            $userId
        );

        if (mysqli_stmt_execute($s) && mysqli_stmt_affected_rows($s) === 1) {

            unset(
                $_SESSION['forgot_user_id'],
                $_SESSION['forgot_email'],
                $_SESSION['forgot_otp_verified'],
                $_SESSION['forgot_otp_verified_until'],
                $_SESSION['forgot_otp_hash'],
                $_SESSION['forgot_otp_expiry'],
                $_SESSION['forgot_otp_attempts'],
                $_SESSION['forgot_last_sent'],
                $_SESSION['forgot_csrf'],
                $_SESSION['verify_forgot_csrf'],
                $_SESSION['reset_password_csrf']
            );

            header('Location: index.php?password_reset=success');
            exit;

        } else {

            $error = 'Unable to update password. Please try again.';
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Reset Password | ParkSmart</title>

<link rel="stylesheet" href="assets/css/smartpark-motion.css">
<link rel="stylesheet" href="assets/css/smartpark-theme.css">
<link rel="stylesheet" href="assets/css/smartpark-refresh.css">
<link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">

</head>

<body>

<main class="dashboard">

<div class="container narrow">

<div class="card form-card">

<h1>Set New Password</h1>

<p class="muted">
Your email has been verified. Create your new password below.
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
    value="<?= e($_SESSION['reset_password_csrf']) ?>"
>

<div class="form-group">

<label>New Password</label>

<input
    class="form-control"
    type="password"
    name="password"
    minlength="6"
    placeholder="Enter new password"
    autocomplete="new-password"
    required
>

</div>

<div class="form-group">

<label>Confirm New Password</label>

<input
    class="form-control"
    type="password"
    name="password_confirm"
    minlength="6"
    placeholder="Confirm new password"
    autocomplete="new-password"
    required
>

</div>

<button
    class="btn btn-primary"
    type="submit"
    style="width:100%"
>
    RESET PASSWORD
</button>

</form>

</div>
</div>
</main>

</body>
</html>