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


/* Reset Password - Match Customer Login Input Design */
.password-input-wrap {
    position: relative;
    width: 100%;
    display: block;
}

.password-input-wrap input {
    display: block;
    width: 100% !important;
    height: 58px;
    box-sizing: border-box;
    padding: 0 50px 0 16px !important;
    border: 1px solid #d5ddeb;
    border-radius: 12px;
    background: #ffffff;
    font-size: 16px;
    color: #111827;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.password-input-wrap input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10);
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
    z-index: 10;
}

.password-toggle svg {
    width: 20px;
    height: 20px;
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
        <input type="password" name="password"
            id="resetNewPassword"
            minlength="8" required
            placeholder="New Password">

        <button type="button" class="password-toggle"
                data-target="resetNewPassword"
                aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2">
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </button>
    </div>
</div>

<div class="form-group">
    <label>Confirm New Password</label>
    <div class="password-input-wrap">
        <input type="password" name="password_confirm"
            id="resetConfirmPassword"
            minlength="8" required
            placeholder="Confirm Password">

        <button type="button" class="password-toggle"
                data-target="resetConfirmPassword"
                aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2">
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
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
document.addEventListener('click', function(event) {
    const button = event.target.closest('.password-toggle');
    if (!button) return;

    const targetId = button.getAttribute('data-target');
    const input = document.getElementById(targetId);

    if (!input) return;

    const showing = input.type === 'password';
    input.type = showing ? 'text' : 'password';

    button.innerHTML = showing
        ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M3 3l18 18"/>
             <path d="M10.6 10.6a2 2 0 002.8 2.8"/>
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

</body>
</html>