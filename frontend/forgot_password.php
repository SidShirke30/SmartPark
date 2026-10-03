<?php

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../backend/auth.php';
require_once '../backend/db_connect.php';


$message = '';
$error = '';

$projectRoot = dirname(__DIR__);
$envFile = $projectRoot . '/.env';
$mailerFile = $projectRoot . '/vendor/autoload.php';


function forgotEnv($key, $default = '')
{
    // First, read environment variables from Render
    $value = getenv($key);

    if ($value !== false && trim($value) !== '') {
        return trim($value);
    }

    // Fallback to the local .env file
    global $envFile;

    static $config = null;

    if ($config === null) {
        $config = [];

        if (isset($envFile) && file_exists($envFile)) {
            $lines = file(
                $envFile,
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
            );

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || strpos($line, '#') === 0) {
                    continue;
                }

                $pos = strpos($line, '=');

                if ($pos === false) {
                    continue;
                }

                $name = trim(substr($line, 0, $pos));
                $value = trim(substr($line, $pos + 1));

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

function sendForgotOTP($recipient, $otp)
{
    global $mailerFile;

    if (!file_exists($mailerFile)) {
        error_log('PHPMailer autoload file not found.');
        return false;
    }

    require_once $mailerFile;

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = forgotEnv('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = forgotEnv('SMTP_USERNAME');
        $mail->Password = forgotEnv('SMTP_PASSWORD');
        $mail->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) forgotEnv('SMTP_PORT', '587');
        $mail->CharSet = 'UTF-8';

        $senderEmail = forgotEnv('SMTP_FROM_EMAIL');
        $senderName = forgotEnv('SMTP_FROM_NAME', 'ParkSmart');

        if (
            $mail->Username === '' ||
            $mail->Password === '' ||
            $senderEmail === ''
        ) {
            error_log('SMTP configuration is incomplete.');
            return false;
        }

        $mail->setFrom($senderEmail, $senderName);
        $mail->addAddress($recipient);

        $mail->isHTML(true);
        $mail->Subject = 'ParkSmart - Password Reset OTP';

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;padding:25px;background:#f5f7fb;'>
                <div style='max-width:500px;margin:auto;background:#fff;padding:30px;border-radius:12px;text-align:center;'>
                    <h2>ParkSmart</h2>
                    <h3>Password Reset Verification</h3>
                    <p>Your password reset OTP is:</p>
                    <h1 style='letter-spacing:8px;color:#2563eb;'>$otp</h1>
                    <p>This OTP is valid for 5 minutes.</p>
                    <p>Do not share this OTP with anyone.</p>
                </div>
            </div>
        ";

        $mail->AltBody = "Your ParkSmart password reset OTP is $otp. It expires in 5 minutes.";

        $mail->send();

        return true;

    } catch (\Throwable $e) {
        error_log('ParkSmart forgot password mail error: ' . $e->getMessage());
        return false;
    }
}

if (empty($_SESSION['forgot_csrf'])) {
    $_SESSION['forgot_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['forgot_csrf'] ?? '';

    if (
        !is_string($csrf) ||
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $csrf)
    ) {
        $error = 'Invalid request. Please refresh the page.';
    } else {

        $method = $_POST['method'] ?? 'email';
        $contact = trim($_POST['contact'] ?? '');

        if ($method === 'phone') {

            $error = 'Phone OTP is not configured yet. Please choose Email for now.';

        } elseif ($method !== 'email' || !filter_var($contact, FILTER_VALIDATE_EMAIL)) {

            $error = 'Please enter a valid email address.';

        } else {

            $lastSent = $_SESSION['forgot_last_sent'] ?? 0;

            if (time() - $lastSent < 60) {

                $error = 'Please wait 60 seconds before requesting another OTP.';

            } else {

                $email = strtolower($contact);

                $s = mysqli_prepare(
                    $con,
                    'SELECT id,email FROM users WHERE LOWER(email)=? LIMIT 1'
                );

                mysqli_stmt_bind_param($s, 's', $email);
                mysqli_stmt_execute($s);

                $user = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

                if ($user) {

                    $otp = (string) random_int(100000, 999999);

                    if (sendForgotOTP($user['email'], $otp)) {

                        $_SESSION['forgot_user_id'] = (int) $user['id'];
                        $_SESSION['forgot_email'] = $user['email'];
                        $_SESSION['forgot_otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
                        $_SESSION['forgot_otp_expiry'] = time() + 300;
                        $_SESSION['forgot_otp_attempts'] = 0;
                        $_SESSION['forgot_last_sent'] = time();

                        header('Location: verify_forgot_otp.php');
                        exit;

                    } else {
                        $error = 'Unable to send OTP. Please check SMTP configuration.';
                    }
                } else {
                    // Generic response to avoid revealing whether an account exists.
                    $message = 'If an account exists for that email, an OTP will be sent.';
                }
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Forgot Password | ParkSmart</title>

<link rel="stylesheet" href="assets/css/smartpark-motion.css">
<link rel="stylesheet" href="assets/css/smartpark-theme.css">
<link rel="stylesheet" href="assets/css/smartpark-refresh.css">
<link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">
</head>

<body>

<main class="dashboard">
    <div class="container narrow">

        <div class="card form-card">

            <h1>Forgot Password?</h1>

            <p class="muted">
                Verify your registered contact to reset your password.
            </p>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['forgot_csrf']) ?>"
                >

                <div class="form-group">
                    <label>Choose verification method</label>

                    <select class="form-control" name="method" id="method" required>
                        <option value="email">Email OTP</option>
                        <option value="phone">Phone OTP</option>
                    </select>
                </div>

                <div class="form-group">
                    <label id="contactLabel">Registered Email</label>

                    <input
                        class="form-control"
                        type="email"
                        name="contact"
                        id="contact"
                        placeholder="Enter your registered email"
                        required
                    >
                </div>

                <button class="btn btn-primary" type="submit" style="width:100%">
                    SEND OTP
                </button>

            </form>

            <p style="text-align:center;margin-top:20px;">
                <a href="index.php" style="color:var(--sp-blue);">
                    Back to Login
                </a>
            </p>

        </div>
    </div>
</main>

<script>
const method = document.getElementById('method');
const contact = document.getElementById('contact');
const label = document.getElementById('contactLabel');

method.addEventListener('change', function () {
    if (this.value === 'phone') {
        label.textContent = 'Registered Phone Number';
        contact.type = 'tel';
        contact.placeholder = 'Enter your 10-digit mobile number';
        contact.pattern = '[0-9]{10}';
        contact.maxLength = 10;
    } else {
        label.textContent = 'Registered Email';
        contact.type = 'email';
        contact.placeholder = 'Enter your registered email';
        contact.removeAttribute('pattern');
        contact.removeAttribute('maxlength');
    }
});
</script>

</body>
</html>