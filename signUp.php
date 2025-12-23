<?php
// signUp.php
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

$error_message = $_SESSION['error_message'] ?? '';
$is_error = true; // Default to true for error messages, but we will adjust for success
unset($_SESSION['error_message']); // Clear message after display
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']); // Clear message after display

// Keep form data on error
$first_name = $_SESSION['form_data']['First_Name'] ?? '';
$last_name = $_SESSION['form_data']['Last_Name'] ?? '';
$email = $_SESSION['form_data']['Email'] ?? '';
unset($_SESSION['form_data']); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['First_Name'] ?? '';
    $last_name = $_POST['Last_Name'] ?? '';
    $email = $_POST['Email'] ?? '';
    $password = $_POST['Password'] ?? '';
    $confirm_password = $_POST['Confirm_Password'] ?? ''; 

    // Store current data in case of error for repopulation
    $_SESSION['form_data'] = [
        'First_Name' => $first_name,
        'Last_Name' => $last_name,
        'Email' => $email,
    ];

    // Input validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Password and Confirm Password do not match.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() > 0) {
            $error_message = "An account with this email already exists.";
        } else {
            // Generate OTP
            $otp = rand(100000, 999999);

            // Hash password before saving to session
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Save user data and OTP to session for verification
            $_SESSION['signup_data'] = [
                'First_Name' => $first_name,
                'Last_Name' => $last_name,
                'Email' => $email,
                'Password' => $hashed_password,
                'Role' => 'user' // Default role
            ];
            $_SESSION['verification_email'] = $email;
            $_SESSION['signup_otp'] = $otp;
            $_SESSION['otp_created_at'] = time(); // Store timestamp for expiration check
            
            // Send the OTP via Gmail SMTP using PHPMailer
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
                $mail->setFrom('markedselmorales0922@gmail.com', 'Keepkit Account Verification');
                $mail->addAddress($email, $first_name . ' ' . $last_name);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Keepkit Account Verification OTP';
                $mail->Body    = "Hello {$first_name},<br><br>Your One-Time Password (OTP) for Keepkit account verification is: <b>{$otp}</b>. This code will expire in 5 minutes.";
                
                $mail->send();
                
                // If successful, redirect to OTP verification page
                header('Location: verify_otp_signup.php');
                exit();

            } catch (Exception $e) {
                $error_message = "Verification email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                error_log("PHPMailer Error: " . $e->getMessage() . " | Mailer Info: " . $mail->ErrorInfo);
                $is_error = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sign Up - Keepkit</title>
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
        <div id="signupForm" class="form-card">
            <h2>Sign up</h2>
            <p>Create a new account</p>
            
            <?php if ($error_message): ?>
                <div style="color: red; font-weight: bold; margin-bottom:15px; text-align:center;">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php elseif ($success_message): ?>
                <div style="color: green; font-weight: bold; margin-bottom:15px; text-align:center;">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <form id="signupFormElement" name="signUp" action="signUp.php" method="POST">
                <input type="text" placeholder="First Name" name="First_Name" required value="<?= htmlspecialchars($first_name) ?>" />
                <input type="text" placeholder="Last Name" name="Last_Name" required value="<?= htmlspecialchars($last_name) ?>" />
                <input type="email" placeholder="Email" name="Email" required value="<?= htmlspecialchars($email) ?>" autocomplete="email" />
                <input type="password" placeholder="Password (min 8 chars)" name="Password" required autocomplete="new-password" />
                <input type="password" placeholder="Confirm Password" name="Confirm_Password" required autocomplete="new-password" />
                <button type="submit">Create Account</button>
            </form>
            
            <div class="toggle-text">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>