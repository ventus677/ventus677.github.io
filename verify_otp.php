<?php
// verify_otp.php
session_start();
include('database/connect.php'); 

$message = '';
$error = false;
$otp_expiry_minutes = 5; 

// Get the email from the session (set after successful email send)
$email = $_SESSION['reset_email'] ?? $_POST['email'] ?? '';

if (empty($email)) {
    // If no email is set in session, redirect back
    $_SESSION['error_message'] = 'Please request a password reset first.';
    header('Location: forgot_password.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_entered = $_POST['otp'] ?? '';

    if (empty($otp_entered) || !is_numeric($otp_entered) || strlen($otp_entered) !== 6) {
        $message = "Please enter the 6-digit OTP correctly.";
        $error = true;
    } else {
        // 1. Find the latest OTP for this email
        $stmt = $conn->prepare("SELECT otp_code, created_at FROM password_reset_otps WHERE email = :email ORDER BY created_at DESC LIMIT 1");
        $stmt->execute(['email' => $email]);
        $otp_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($otp_data) {
            $created_at = new DateTime($otp_data['created_at']);
            $now = new DateTime();
            $interval = $now->getTimestamp() - $created_at->getTimestamp(); 

            // 2. Verify OTP and check expiration (5 minutes)
            if ($otp_entered == $otp_data['otp_code'] && $interval < ($otp_expiry_minutes * 60)) {
                
                // OTP is valid! 
                $_SESSION['otp_verified'] = true; 
                // Redirect to the final reset password page
                header('Location: reset_password.php');
                exit();

            } elseif ($interval >= ($otp_expiry_minutes * 60)) {
                $message = "OTP has expired. Please request a new one.";
                $error = true;
            } else {
                $message = "Invalid OTP entered.";
                $error = true;
            }
        } else {
            $message = "Invalid or expired OTP. Please request a new password reset.";
            $error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verify OTP - Keepkit</title>
    <link rel="stylesheet" href="auth.css"/>
    <link rel="stylesheet" href="home.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">
                <img src="images/KeepkitSubmark.png" alt="Keepkit Submark" height="50px" />
                &nbsp;&nbsp;Keepkit
            </a>
        </div>
    </nav>
    <div class="auth-container" id="authContainer">
        <img src="images/KeepkitLogo.png" alt="Keepkit Logo" height="180px" />
        <div class="form-card">
            <h2>Verify OTP</h2>
            <p>We sent a 6-digit code to: **<?= htmlspecialchars($email) ?>**</p>
            <?php if ($message): ?>
                <div style="color: <?= $error ? 'red' : 'green' ?>; font-weight: bold; margin-bottom:15px; text-align:center;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <form action="verify_otp.php" method="POST">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>" />
                <input type="text" placeholder="6-Digit OTP" name="otp" required maxlength="6" pattern="\d{6}" autocomplete="off" />
                <button type="submit">Verify Code</button>
            </form>
            <div class="toggle-text">
                <a href="forgot_password.php">Request a New Code</a>
            </div>
        </div>
    </div>
</body>
</html>