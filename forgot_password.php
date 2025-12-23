<?php
// forgot_password.php
session_start();

// --- CRITICAL: MANUAL INCLUDES FOR PHPMailer ---
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php'; 
// -----------------------------------------------

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include your database connection
include('database/connect.php'); 

$message = '';
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $error = true;
    } else {
        // 1. Check if email exists in the users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            
            // 2. Generate a 6-digit OTP
            $otp = rand(100000, 999999);
            $current_time = date("Y-m-d H:i:s");

            // 3. Store or update OTP in the password_reset_otps table
            $deleteStmt = $conn->prepare("DELETE FROM password_reset_otps WHERE email = :email");
            $deleteStmt->execute(['email' => $email]);

            $insertStmt = $conn->prepare("INSERT INTO password_reset_otps (email, otp_code, created_at) VALUES (:email, :otp, :created_at)");
            $insertStmt->execute(['email' => $email, 'otp' => $otp, 'created_at' => $current_time]);

            // 4. Send the OTP via Gmail SMTP using PHPMailer
            $mail = new PHPMailer(true);
            
            // --- SSL VERIFICATION BYPASS (LOCAL TESTING FIX) ---
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            // ---------------------------------------------------

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';             
                $mail->SMTPAuth = true;                     
                // --- KUMPIRMAHIN MO ITONG DALAWA ---
                $mail->Username = 'markedselmorales0922@gmail.com'; // IYONG BUONG GMAIL ADDRESS
                $mail->Password = 'polr hrom tgnx qjlt'; // IYONG 16-CHARACTER APP PASSWORD
                // ------------------------------------
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                $mail->Port = 587;                          

                // Recipients
                $mail->setFrom('markedselmorales0922@gmail.com', 'Keepkit Password Reset');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Keepkit Password Reset OTP';
                $mail->Body    = "Your One-Time Password for password reset is: <b>{$otp}</b>. This code will expire in 5 minutes.";

                $mail->send();
                
                // If successful, set session for verification page and redirect
                $_SESSION['reset_email'] = $email;
                header('Location: verify_otp.php');
                exit();

            } catch (Exception $e) {
                // Kung may error pa, ito na ang lalabas (pero hindi na authentication error)
                $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                error_log("PHPMailer Error: " . $e->getMessage() . " | Mailer Info: " . $mail->ErrorInfo);
                $error = true;
            }

        } else {
            // Maintining security by giving a generic message
            $message = 'If an account exists, a password reset email will be sent.';
            // Set error to false so it shows in green/default color
            $error = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Forgot Password - Keepkit</title>
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
            <h2>Forgot Password</h2>
            <p>Enter your email to reset your password</p>
            <?php if ($message): ?>
                <div style="color: <?= $error ? 'red' : 'green' ?>; font-weight: bold; margin-bottom:15px; text-align:center;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <form action="forgot_password.php" method="POST">
                <input type="email" placeholder="Email Address" name="email" required autocomplete="email" />
                <button type="submit">Send OTP</button>
            </form>
            <div class="toggle-text">
                <a href="login.php">Back to Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>