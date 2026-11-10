<?php

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db_connect.php';


function sendWelcomeEmail($recipient, $name)
{
    $payload = [
        'sender' => [
            'name' => BREVO_FROM_NAME,
            'email' => BREVO_FROM_EMAIL
        ],
        'to' => [
            ['email' => $recipient, 'name' => $name]
        ],
        'subject' => 'Welcome to ParkSmart!',
        'htmlContent' => "
            <div style='font-family:Arial,sans-serif;padding:25px;color:#222'>
                <h2>Welcome to ParkSmart, " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "!</h2>
                <p>Your account has been successfully created.</p>
                <p>You can now explore parking locations, check available spaces
                and reserve your parking spot easily.</p>
                <p>Thank you for joining ParkSmart!</p>
                <h3>Happy Parking! 🚗</h3>
                <p>Team ParkSmart</p>
            </div>
        "
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        error_log('Brevo email error: ' . curl_error($ch));
    } elseif ($httpCode < 200 || $httpCode >= 300) {
        error_log('Brevo API error: ' . $response);
    }

    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}


$error='';

if ($_SERVER['REQUEST_METHOD']==='POST') {

    $name=trim($_POST['name']??'');
    $email=strtolower(trim($_POST['email']??''));

    $countryCode=trim($_POST['country_code']??'+91');
    $phone=trim($_POST['phone']??'');

    $p=$_POST['password']??'';
    $c=$_POST['password_confirm']??'';

    // Keep only digits in the mobile number
    $phone=preg_replace('/\D/', '', $phone);

    // Keep only + and digits in country code
    $countryCode=preg_replace('/[^0-9+]/', '', $countryCode);

    // Complete international phone number
    $fullPhone=$countryCode.$phone;

    if (
        $name==='' ||
        !filter_var($email,FILTER_VALIDATE_EMAIL) ||
        !preg_match('/^\+[0-9]{1,4}$/', $countryCode) ||
        !preg_match('/^[0-9]{6,15}$/', $phone) ||
        strlen($fullPhone)>20 ||
        strlen($p) < 8 ||
        !preg_match('/[A-Z]/', $p) ||
        !preg_match('/[a-z]/', $p) ||
        !preg_match('/[0-9]/', $p) ||
        !preg_match('/[^A-Za-z0-9]/', $p) ||
        $p !== $c
    ) {
        $error = 'Password must be at least 8 characters and contain an uppercase letter, lowercase letter, number, and special character. Both passwords must match.';
    } else {
        $checkStmt=mysqli_prepare($con,'SELECT id FROM users WHERE email=? LIMIT 1');

        if ($checkStmt === false) {
            $error='Database error while checking the email. Please verify that the users table exists.';
        } else {
            mysqli_stmt_bind_param($checkStmt,'s',$email);
            mysqli_stmt_execute($checkStmt);
            $result=mysqli_stmt_get_result($checkStmt);

            if ($result && mysqli_num_rows($result)) {
                $error='Email is already registered.';
                mysqli_stmt_close($checkStmt);
            } else {
                mysqli_stmt_close($checkStmt);

                $hash=password_hash($p,PASSWORD_DEFAULT);
                $insertStmt=mysqli_prepare($con,'INSERT INTO users(name,email,phone,password,password_confirm) VALUES(?,?,?,?,?)');

                if ($insertStmt === false) {
                    $error='Registration database error. Make sure the users table contains a phone column.';
                } else {
                    mysqli_stmt_bind_param($insertStmt,'sssss',$name,$email,$fullPhone,$hash,$hash);

                    if (mysqli_stmt_execute($insertStmt)) {

                        mysqli_stmt_close($insertStmt);

                        if (headers_sent($file, $line)) {
                            error_log(
                                "SmartPark: Headers already sent in $file on line $line"
                            );

                            $error = 'Registration completed, but session initialization failed. Please contact support.';
                        } else {

                            if (session_status() !== PHP_SESSION_ACTIVE) {
                                session_start();
                            }

                            session_regenerate_id(true);

                            $_SESSION['driver_id'] = (int) mysqli_insert_id($con);
                            $_SESSION['driver_email'] = $email;
                            $_SESSION['driver_name'] = $name;

                            // Send welcome email after successful registration
                            sendWelcomeEmail($email, $name);

                            header('Location: home.php');
                            exit;
                        }

                    } else {

                        $error = 'Registration failed. Database error: ' .
                        mysqli_stmt_error($insertStmt);

                        mysqli_stmt_close($insertStmt);
                
                    }

                    $error='Registration failed. Database error: '.mysqli_stmt_error($insertStmt);
                    mysqli_stmt_close($insertStmt);
                }
            }
        }
    }
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account | ParkSmart</title>
<link rel="stylesheet" href="assets/css/smartpark-motion.css">
<link rel="stylesheet" href="assets/css/smartpark-theme.css"><link rel="stylesheet" href="assets/css/smartpark-refresh.css">
<style>
.phone-input-group {
    display: flex;
    width: 100%;
    gap: 8px;
}

.country-code {
    width: 120px;
    min-width: 120px;
    height: 58px;
    padding: 0 12px;
    border: 1px solid #d5dce5;
    border-radius: 12px;
    background: #ffffff;
    color: #111827;
    font-size: 15px;
    font-family: inherit;
    outline: none;
    cursor: pointer;
}

.country-code:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
}

.phone-number {
    flex: 1;
    min-width: 0;
}

@media (max-width: 500px) {
    .phone-input-group {
        gap: 6px;
    }

    .country-code {
        width: 105px;
        min-width: 105px;
        padding: 0 8px;
        font-size: 14px;
    }
}

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
<body>
<div class="auth-page">
<div class="auth-card">
<a class="brand" href="index.php">
<i class="fa fa-car">
</i>ParkSmart</a>
<h1 style="margin-top:25px">Create account</h1>
<p class="muted">Start booking parking spaces in a few steps.</p>
<?php if ($error):?>
<div class="alert alert-error">
<?=e($error)?>
</div>
<?php endif;?>
<form method="post">
<div class="form-group">
<label>Full name</label>
<input class="form-control" name="name" required>
</div>
<div class="form-group">
<label>Email</label>
<input class="form-control" type="email" name="email" required>
</div>
<div class="form-group">
<label>Phone Number</label>

<div class="phone-input-group">

    <select name="country_code" class="country-code" required>
        <option value="+91" selected>🇮🇳 +91</option>
        <option value="+1">🇺🇸 +1</option>
        <option value="+44">🇬🇧 +44</option>
        <option value="+61">🇦🇺 +61</option>
        <option value="+1">🇨🇦 +1</option>
        <option value="+49">🇩🇪 +49</option>
        <option value="+33">🇫🇷 +33</option>
        <option value="+81">🇯🇵 +81</option>
        <option value="+971">🇦🇪 +971</option>
        <option value="+65">🇸🇬 +65</option>
        <option value="+86">🇨🇳 +86</option>
        <option value="+7">🇷🇺 +7</option>
        <option value="+39">🇮🇹 +39</option>
        <option value="+34">🇪🇸 +34</option>
        <option value="+55">🇧🇷 +55</option>
        <option value="+27">🇿🇦 +27</option>
    </select>

    <input
        class="form-control phone-number"
        type="tel"
        name="phone"
        inputmode="numeric"
        pattern="[0-9]{6,15}"
        maxlength="15"
        placeholder="Enter mobile number"
        required
    >

</div>
</div>

<div class="form-group">
    <label>Password</label>
    <div class="password-input-wrap">
        <input
            class="form-control"
            type="password"
            id="registerPassword"
            name="password"
            minlength="8"
            required
        >
        <button type="button" class="password-toggle"
                data-target="registerPassword"
                aria-label="Show password">
            <i class="fa fa-eye"></i>
        </button>
    </div>
</div>

<div class="form-group">
    <label>Confirm password</label>
    <div class="password-input-wrap">
        <input
            class="form-control"
            type="password"
            id="registerConfirmPassword"
            name="password_confirm"
            minlength="8"
            required
        >
        <button type="button" class="password-toggle"
                data-target="registerConfirmPassword"
                aria-label="Show confirm password">
            <i class="fa fa-eye"></i>
        </button>
    </div>
</div>

<button class="btn btn-primary" style="width:100%">Create account</button>
</form>
<p class="muted" style="text-align:center">
<a href="index.php">Back to login</a>
</p>
</div>
</div>
<div style="position:fixed;left:18px;bottom:18px;z-index:20;display:flex;gap:8px"><a class="btn btn-light btn-sm" href="index.php"><i class="fa fa-home"></i> Home</a><a class="btn btn-light btn-sm" href="javascript:history.back()"><i class="fa fa-arrow-left"></i> Back</a></div>
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
