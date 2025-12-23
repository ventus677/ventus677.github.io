<?php
// verify_otp_signup.php
session_start();
include('database/connect.php'); 

$error_message = '';
$is_error = false;
$otp_expiry_minutes = 5; 

// Get data from session
$email = $_SESSION['verification_email'] ?? '';
$otp_expected = $_SESSION['signup_otp'] ?? null;
$otp_created_at = $_SESSION['otp_created_at'] ?? 0;
$signup_data = $_SESSION['signup_data'] ?? null;

// Security check
if (empty($email) || empty($otp_expected) || empty($signup_data)) {
    $_SESSION['error_message'] = 'Verification data missing. Please sign up again.';
    header('Location: signUp.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_entered = $_POST['otp'] ?? '';

    if (empty($otp_entered) || !is_numeric($otp_entered) || strlen($otp_entered) !== 6) {
        $error_message = "Please enter the 6-digit OTP correctly.";
        $is_error = true;
    } else {
        $interval = time() - $otp_created_at; 
        
        if ($otp_entered == $otp_expected && $interval < ($otp_expiry_minutes * 60)) {
            // OTP is valid! 
            
            // 1. Insert user data into the database
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (:first_name, :last_name, :email, :password, :role)");
            $success = $stmt->execute([
                'first_name' => $signup_data['First_Name'],
                'last_name' => $signup_data['Last_Name'],
                'email' => $signup_data['Email'],
                'password' => $signup_data['Password'],
                'role' => $signup_data['Role']
            ]);

            if ($success) {
                // 2. Clear the OTP and sign-up data from the session
                unset($_SESSION['signup_otp']); 
                unset($_SESSION['otp_created_at']);
                unset($_SESSION['signup_data']);
                unset($_SESSION['verification_email']);

                // 3. Redirect to login with success message
                $_SESSION['success_message'] = "Account successfully created! You may now sign in.";
                header('Location: login.php');
                exit();
            } else {
                $error_message = "Database error during account creation. Please try again.";
                $is_error = true;
            }

        } elseif ($interval >= ($otp_expiry_minutes * 60)) {
            $error_message = "OTP has expired. Please sign up again to request a new one.";
            $is_error = true;
        } else {
            $error_message = "Invalid OTP entered.";
            $is_error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verify Code - Keepkit</title>
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
            <h2>Verify Code</h2>
            <p>Enter the 6-digit code sent to **<?= htmlspecialchars($email) ?>** to complete your account creation.</p>
            
            <?php if ($error_message): ?>
                <div style="color: red; font-weight: bold; margin-bottom:15px; text-align:center;">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form action="verify_otp_signup.php" method="POST">
                <input type="text" placeholder="6-Digit OTP" name="otp" required maxlength="6" pattern="\d{6}" autocomplete="off" />
                <button type="submit">Verify Code</button>
            </form>
            
            <div class="toggle-text">
                Code didn't arrive? <a href="signUp.php">Go back and re-send</a>
            </div>
        </div>
    </div>
</body>
</html>