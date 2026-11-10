<?php

require_once '../backend/auth.php';
require_once '../backend/db_connect.php';

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

    } elseif (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[^A-Za-z0-9]/', $password)
    ) {

        $error = 'Password must be at least 8 characters and contain an uppercase letter, lowercase letter, number, and special character.';
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

<style>


.password-input-wrap {
    position: relative;
    width: 100%;
}

.password-input-wrap input {
    padding-right: 45px !important;
}

.password-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    font-size: 17px;
    padding: 5px;
    z-index: 2;
}

.password-toggle:hover {
    color: #111827;
}


</style>

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
    <div class="password-input-wrap">
        <input
            class="form-control"
            type="password"
            id="resetNewPassword"
            name="password"
            minlength="8"
            placeholder="Enter new password"
            autocomplete="new-password"
            required
        >
        <button type="button" class="password-toggle"
                data-target="resetNewPassword"
                aria-label="Show password">
            <i class="fa fa-eye"></i>
        </button>
    </div>
</div>

<div class="form-group">
    <label>Confirm New Password</label>
    <div class="password-input-wrap">
        <input
            class="form-control"
            type="password"
            id="resetConfirmPassword"
            name="password_confirm"
            minlength="8"
            placeholder="Confirm new password"
            autocomplete="new-password"
            required
        >
        <button type="button" class="password-toggle"
                data-target="resetConfirmPassword"
                aria-label="Show confirm password">
            <i class="fa fa-eye"></i>
        </button>
    </div>
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