<?php
// reset_password.php
session_start();
include('database/connect.php'); 

$message = '';
$error = false;

// 1. Security Check: Ensure the user has passed OTP verification
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please enter both new password and confirmation.";
        $error = true;
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirm password do not match.";
        $error = true;
    } elseif (strlen($new_password) < 8) { 
        $message = "Password must be at least 8 characters long.";
        $error = true;
    } else {
        // 2. Hash the new password securely
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // 3. Update the password in the main 'users' table
        $updateStmt = $conn->prepare("UPDATE users SET password = :password WHERE email = :email");
        $success = $updateStmt->execute(['password' => $hashed_password, 'email' => $email]);

        if ($success) {
            // 4. Cleanup: Destroy the reset session variables
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_email']);

            // Redirect to login with success message 
            $_SESSION['success_message'] = "Your password has been reset successfully. Please sign in.";
            header('Location: login.php');
            exit();
        } else {
            $message = "A database error occurred. Please try again.";
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
    <title>Reset Password - Keepkit</title>
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
            <h2>Reset Password</h2>
            <p>Set a new password for: **<?= htmlspecialchars($email) ?>**</p>
            <?php if ($message): ?>
                <div style="color: red; font-weight: bold; margin-bottom:15px; text-align:center;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <form action="reset_password.php" method="POST">
                <input type="password" placeholder="New Password (min 8 chars)" name="new_password" required autocomplete="new-password" />
                <input type="password" placeholder="Confirm New Password" name="confirm_password" required autocomplete="new-password" />
                <button type="submit">Reset Password</button>
            </form>
            <div class="toggle-text">
                <a href="login.php">Back to Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>