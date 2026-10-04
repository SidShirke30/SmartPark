<?php

ob_start();

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db_connect.php';

require_customer();

$email = $_SESSION['driver_email'];

$msg = '';
$error = '';

/*
|--------------------------------------------------------------------------
| PROJECT PATHS
|--------------------------------------------------------------------------
*/

$projectRoot = dirname(__DIR__);

$envFile = $projectRoot . '/.env';
$mailerFile = $projectRoot . '/vendor/autoload.php';


/*
|--------------------------------------------------------------------------
| READ SMTP CONFIGURATION
|--------------------------------------------------------------------------
*/

function profileEnv($key, $default = '')
{
    global $envFile;

    static $config = null;

    if ($config === null) {

        $config = [];

        if (file_exists($envFile)) {

            $lines = file(
                $envFile,
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
            );

            foreach ($lines as $line) {

                $line = trim($line);

                // Ignore comments
                if ($line === '' || strpos($line, '#') === 0) {
                    continue;
                }

                // Find the first equals sign
                $pos = strpos($line, '=');

                if ($pos === false) {
                    continue;
                }

                $name = trim(substr($line, 0, $pos));

                $value = trim(substr($line, $pos + 1));

                // Remove surrounding quotes
                if (
                    strlen($value) >= 2 &&
                    (
                        ($value[0] === '"' && substr($value, -1) === '"') ||
                        ($value[0] === "'" && substr($value, -1) === "'")
                    )
                ) {
                    $value = substr($value, 1, -1);
                }

                $config[$name] = $value;
            }
        }
    }

    return trim($config[$key] ?? $default);
}


/*
|--------------------------------------------------------------------------
| SEND OTP EMAIL
|--------------------------------------------------------------------------
*/


function sendProfileOTP($recipient, $otp)
{
    $apiKey = profileEnv('BREVO_API_KEY');

    $senderEmail = profileEnv('SMTP_FROM_EMAIL');
    $senderName = profileEnv('SMTP_FROM_NAME', 'ParkSmart');

    if ($apiKey === '' || $senderEmail === '') {
        error_log('Brevo API configuration is incomplete.');
        return false;
    }

    $payload = [
        'sender' => [
            'name' => $senderName,
            'email' => $senderEmail
        ],

        'to' => [
            [
                'email' => $recipient
            ]
        ],

        'subject' => 'ParkSmart - Password Verification OTP',

        'htmlContent' => "
            <div style='font-family:Arial,sans-serif;
                        max-width:600px;
                        margin:auto;
                        padding:30px;
                        background:#f5f7fb;
                        border-radius:12px;'>

                <div style='background:#ffffff;
                            padding:30px;
                            border-radius:10px;
                            text-align:center;'>

                    <h2 style='color:#111827;'>ParkSmart</h2>

                    <h3>Password Change Verification</h3>

                    <p>You requested to change your ParkSmart account password.</p>

                    <p>Your verification OTP is:</p>

                    <h1 style='letter-spacing:10px;
                               color:#2563eb;
                               font-size:36px;'>
                        {$otp}
                    </h1>

                    <p>This OTP is valid for 5 minutes.</p>

                    <p>Do not share this OTP with anyone.</p>

                    <hr>

                    <p style='font-size:12px;color:#777;'>
                        If you did not request this password change,
                        please ignore this email.
                    </p>

                </div>
            </div>
        ",

        'textContent' =>
            "Your ParkSmart password verification OTP is $otp. It expires in 5 minutes."
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        error_log('Brevo cURL error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }

    error_log(
        'Brevo API error. HTTP ' . $httpCode . ': ' . $response
    );

    return false;
}



/*
|--------------------------------------------------------------------------
| CSRF PROTECTION
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['profile_csrf'])) {
    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['profile_csrf'], $csrf)
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | ACTION 1: UPDATE PROFILE DETAILS
        |--------------------------------------------------------------------------
        */

        if ($action === 'update_profile') {

            $name = trim($_POST['name'] ?? '');

            $newEmail = trim($_POST['email'] ?? '');

            $phone = trim($_POST['phone'] ?? '');

            if (
                $name === '' ||
                !filter_var($newEmail, FILTER_VALIDATE_EMAIL) ||
                !preg_match('/^[0-9]{10}$/', $phone)
            ) {
                $error = 'Enter a valid name, email, and 10-digit mobile number.';

            } else {

                $s = mysqli_prepare(
                    $con,
                    'SELECT id FROM users
                     WHERE email=? AND email<>? LIMIT 1'
                );

                mysqli_stmt_bind_param(
                    $s,
                    'ss',
                    $newEmail,
                    $email
                );

                mysqli_stmt_execute($s);

                $existing = mysqli_stmt_get_result($s);

                if (mysqli_num_rows($existing) > 0) {

                    $error = 'That email is already registered.';

                } else {

                    $s = mysqli_prepare(
                        $con,
                        'UPDATE users SET name=?, email=?, phone=? WHERE email=?'
                    );

                    mysqli_stmt_bind_param(
                        $s,
                        'ssss',
                        $name,
                        $newEmail,
                        $phone,
                        $email
                    );

                    if (mysqli_stmt_execute($s)) {

                        $_SESSION['driver_email'] = $newEmail;
                        $_SESSION['driver_name'] = $name;

                        $email = $newEmail;

                        // Email changed, so invalidate previous OTP state.
                        unset(
                            $_SESSION['password_otp_hash'],
                            $_SESSION['password_otp_expiry'],
                            $_SESSION['password_otp_attempts'],
                            $_SESSION['password_otp_verified'],
                            $_SESSION['password_otp_verified_until'],
                            $_SESSION['password_otp_email'],
                            $_SESSION['otp_last_sent']
                        );

                        $msg = 'Your profile has been updated successfully.';

                    } else {

                        $error = 'Could not update your profile.';
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | ACTION 2: SEND OTP
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'send_otp') {

            $lastSent = $_SESSION['otp_last_sent'] ?? 0;

            if (time() - $lastSent < 60) {

                $error = 'Please wait 60 seconds before requesting another OTP.';

            } else {

                // Generate a cryptographically secure six-digit OTP.
                $otp = (string) random_int(100000, 999999);

                if (sendProfileOTP($email, $otp)) {

                    $_SESSION['password_otp_hash'] =
                        password_hash($otp, PASSWORD_DEFAULT);

                    $_SESSION['password_otp_expiry'] = time() + 300;

                    $_SESSION['password_otp_attempts'] = 0;

                    $_SESSION['password_otp_verified'] = false;

                    $_SESSION['password_otp_email'] = $email;

                    $_SESSION['otp_last_sent'] = time();

                    $msg = 'OTP sent successfully to your registered email.';

                } else {

                    $error = 'Unable to send OTP. Check SMTP configuration and try again.';
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | ACTION 3: VERIFY OTP
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'verify_otp') {

            $enteredOTP = trim($_POST['otp'] ?? '');

            if (
                empty($_SESSION['password_otp_hash']) ||
                time() > ($_SESSION['password_otp_expiry'] ?? 0)
            ) {

                unset(
                    $_SESSION['password_otp_hash'],
                    $_SESSION['password_otp_expiry'],
                    $_SESSION['password_otp_verified']
                );

                $error = 'OTP expired. Please request a new OTP.';

            } elseif (
                ($_SESSION['password_otp_email'] ?? '') !== $email
            ) {

                $error = 'Email changed. Please request a new OTP.';

            } elseif (
                ($_SESSION['password_otp_attempts'] ?? 0) >= 5
            ) {

                unset(
                    $_SESSION['password_otp_hash'],
                    $_SESSION['password_otp_expiry'],
                    $_SESSION['password_otp_verified']
                );

                $error = 'Maximum OTP attempts reached. Request a new OTP.';

            } elseif (
                !preg_match('/^[0-9]{6}$/', $enteredOTP) ||
                !password_verify(
                    $enteredOTP,
                    $_SESSION['password_otp_hash']
                )
            ) {

                $_SESSION['password_otp_attempts'] =
                    ($_SESSION['password_otp_attempts'] ?? 0) + 1;

                $error = 'Incorrect OTP. Please try again.';

            } else {

                $_SESSION['password_otp_verified'] = true;

                $_SESSION['password_otp_verified_until'] = time() + 600;

                unset(
                    $_SESSION['password_otp_hash'],
                    $_SESSION['password_otp_expiry']
                );

                $msg = 'Email verified successfully. You can now change your password.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | ACTION 4: CHANGE PASSWORD AFTER OTP VERIFICATION
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'change_password') {

            $newPass = $_POST['password'] ?? '';

            $confirm = $_POST['password_confirm'] ?? '';

            $verified =
                !empty($_SESSION['password_otp_verified']) &&
                time() <= ($_SESSION['password_otp_verified_until'] ?? 0) &&
                ($_SESSION['password_otp_email'] ?? '') === $email;

            if (!$verified) {

                $error = 'Please verify your email using OTP first.';

            } elseif (strlen($newPass) < 6) {

                $error = 'Password must contain at least 6 characters.';

            } elseif ($newPass !== $confirm) {

                $error = 'New passwords must match.';

            } else {

                $hash = password_hash(
                    $newPass,
                    PASSWORD_DEFAULT
                );

                $s = mysqli_prepare(
                    $con,
                    'UPDATE users
                     SET password=?, password_confirm=?
                     WHERE email=?'
                );

                mysqli_stmt_bind_param(
                    $s,
                    'sss',
                    $hash,
                    $hash,
                    $email
                );

                if (
                    mysqli_stmt_execute($s) &&
                    mysqli_stmt_affected_rows($s) === 1
                ) {

                    // OTP becomes unusable after password change.
                    unset(
                        $_SESSION['password_otp_hash'],
                        $_SESSION['password_otp_expiry'],
                        $_SESSION['password_otp_attempts'],
                        $_SESSION['password_otp_verified'],
                        $_SESSION['password_otp_verified_until'],
                        $_SESSION['password_otp_email'],
                        $_SESSION['otp_last_sent']
                    );

                    $msg = 'Password changed successfully.';

                } else {

                    $error = 'Could not update your password.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| FETCH CURRENT USER
|--------------------------------------------------------------------------
*/

$s = mysqli_prepare(
    $con,
    'SELECT name,email,phone FROM users WHERE email=? LIMIT 1'
);

mysqli_stmt_bind_param($s, 's', $email);

mysqli_stmt_execute($s);

$u = mysqli_fetch_assoc(mysqli_stmt_get_result($s));


/*
|--------------------------------------------------------------------------
| OTP UI STATE
|--------------------------------------------------------------------------
*/

$otpVerified =
    !empty($_SESSION['password_otp_verified']) &&
    time() <= ($_SESSION['password_otp_verified_until'] ?? 0) &&
    ($_SESSION['password_otp_email'] ?? '') === $email;

$otpSent =
    !empty($_SESSION['password_otp_hash']) &&
    time() <= ($_SESSION['password_otp_expiry'] ?? 0);

?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>My Profile | ParkSmart</title>

<link rel="stylesheet" href="assets/css/smartpark-motion.css">

<link rel="stylesheet" href="assets/css/smartpark-theme.css">

<link rel="stylesheet" href="assets/css/smartpark-refresh.css">

<link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">

</head>

<body>

<header class="topbar">

<a class="brand" href="home.php">
<i class="fa fa-car"></i>ParkSmart
</a>

<nav class="navlinks">

<a href="home.php">Dashboard</a>

<a href="customer_profile.php">My Profile</a>

<a class="btn btn-light" href="home.php">Home</a>

<a class="btn btn-light" href="javascript:history.back()">Back</a>

<a class="btn btn-light" href="javascript:history.forward()">Next</a>

<a class="btn btn-light" href="logout.php">Logout</a>

</nav>

</header>

<main class="dashboard">

<div class="container narrow">

<div class="card form-card">

<div class="profile-icon">
<i class="fa fa-user"></i>
</div>

<h1>My profile</h1>

<p class="muted">
Update your account details and password.
</p>


<!-- SUCCESS MESSAGE -->

<?php if ($msg): ?>

<div class="alert alert-success">
<?= e($msg) ?>
</div>

<?php endif; ?>


<!-- ERROR MESSAGE -->

<?php if ($error): ?>

<div class="alert alert-error">
<?= e($error) ?>
</div>

<?php endif; ?>


<!-- PROFILE DETAILS FORM -->

<form method="post">

<input
type="hidden"
name="csrf_token"
value="<?= e($_SESSION['profile_csrf']) ?>"
>

<input type="hidden" name="action" value="update_profile">

<div class="form-group">

<label>Full name</label>

<input
class="form-control"
name="name"
value="<?= e($u['name'] ?? '') ?>"
required
>

</div>

<div class="form-group">

<label>Email</label>

<input
class="form-control"
type="email"
name="email"
value="<?= e($u['email'] ?? $email) ?>"
required
>

</div>

<div class="form-group">

    <label>Phone Number</label>

    <input
        class="form-control"
        type="tel"
        name="phone"
        value="<?= e($u['phone'] ?? '') ?>"
        pattern="[0-9]{10}"
        minlength="10"
        maxlength="10"
        placeholder="Enter 10-digit mobile number"
        required
    >

</div>

<button class="btn btn-primary">
Save profile
</button>

</form>


<hr>

<h3>Change password</h3>

<p class="muted">
Verify your registered email before changing your password.
</p>


<!-- SEND OTP BUTTON -->

<?php if (!$otpSent && !$otpVerified): ?>

<form method="post">

<input
type="hidden"
name="csrf_token"
value="<?= e($_SESSION['profile_csrf']) ?>"
>

<input type="hidden" name="action" value="send_otp">

<button class="btn btn-primary" type="submit">

<i class="fa fa-envelope"></i>

Send OTP to Email

</button>

</form>

<?php endif; ?>


<!-- OTP VERIFICATION FORM -->

<?php if ($otpSent && !$otpVerified): ?>

<form method="post">

<input
type="hidden"
name="csrf_token"
value="<?= e($_SESSION['profile_csrf']) ?>"
>

<input type="hidden" name="action" value="verify_otp">

<div class="form-group">

<label>Enter 6-digit OTP</label>

<input
class="form-control"
type="text"
name="otp"
inputmode="numeric"
pattern="[0-9]{6}"
maxlength="6"
placeholder="Enter OTP received on email"
required
>

</div>

<button class="btn btn-primary" type="submit">

<i class="fa fa-check-circle"></i>

Verify OTP

</button>

</form>


<!-- RESEND OTP -->

<form method="post" style="margin-top:12px;">

<input
type="hidden"
name="csrf_token"
value="<?= e($_SESSION['profile_csrf']) ?>"
>

<input type="hidden" name="action" value="send_otp">

<button class="btn btn-light" type="submit">

<i class="fa fa-refresh"></i>

Resend OTP

</button>

</form>

<?php endif; ?>


<!-- PASSWORD FIELDS AFTER VERIFICATION -->

<?php if ($otpVerified): ?>

<div class="alert alert-success">

<i class="fa fa-check-circle"></i>

Email verified successfully. You can now change your password.

</div>

<form method="post">

<input
type="hidden"
name="csrf_token"
value="<?= e($_SESSION['profile_csrf']) ?>"
>

<input type="hidden" name="action" value="change_password">

<div class="form-group">

<label>New password</label>

<input
class="form-control"
type="password"
name="password"
minlength="6"
autocomplete="new-password"
placeholder="Enter new password"
required
>

</div>

<div class="form-group">

<label>Confirm new password</label>

<input
class="form-control"
type="password"
name="password_confirm"
minlength="6"
autocomplete="new-password"
placeholder="Confirm new password"
required
>

</div>

<button class="btn btn-primary" type="submit">

<i class="fa fa-lock"></i>

Update Password

</button>

</form>

<?php endif; ?>


<a class="btn btn-light" href="home.php">
Back
</a>

</div>

</div>

</main>

<script src="assets/js/smartpark-motion.js"></script>

</body>
</html>