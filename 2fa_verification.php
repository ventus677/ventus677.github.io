<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1); 
session_start(); 

// Ensure the user came from a successful login attempt (temp_user exists)
if (!isset($_SESSION['temp_user'])) {
    header('Location: login.php');
    exit();
}

// Para maging available ang sendLoginNotificationEmail at getPermissions functions
// I-include ang login.php (kung saan nakalagay ang functions)
include('login.php'); 

$error_message = '';
$user_data = $_SESSION['temp_user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('database/connect.php'); // Include your database connection

    $input_code = trim($_POST['two_factor_code'] ?? '');

    if (empty($input_code) || strlen($input_code) !== 6 || !ctype_digit($input_code)) {
        $error_message = 'Please enter a valid 6-digit code.';
    } else {
        try {
            // Retrieve the stored 2FA code from the database
            $stmt = $conn->prepare("SELECT two_factor_code FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $user_data['id']]);
            $db_code = $stmt->fetchColumn();

            if ($db_code && $input_code === $db_code) {
                // SUCCESSFUL 2FA VERIFICATION
                
                // 1. Clear the code from the database (security practice)
                $clearStmt = $conn->prepare("UPDATE users SET two_factor_code = NULL WHERE id = :id");
                $clearStmt->execute(['id' => $user_data['id']]);

                // 2. Set permanent user session
                $user_data['permissions'] = getPermissions($user_data['role']);
                $_SESSION['user'] = $user_data;

                // 3. Clear temporary session data
                unset($_SESSION['temp_user']);

                // 4. Send Login Notification (ITO ANG FINAL NOTIFICATION)
                sendLoginNotificationEmail($user_data); 

                // 5. Redirect to home page
                header("Location: home.php");
                exit();

            } else {
                $error_message = 'The entered code is incorrect or has expired.';
            }

        } catch (PDOException $e) {
            error_log("2FA verification database error: " . $e->getMessage());
            $error_message = "A system error occurred: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>2FA Verification - Keepkit</title>
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
            <h2>Two-Factor Authentication</h2>
            <p>A 6-digit code has been sent to your email (<?= htmlspecialchars($user_data['email']) ?>). Please enter it below.</p>
            <?php if ($error_message): ?>
                <div style="color: red; font-weight: bold; margin-bottom:15px; text-align:center;"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            <form action="2fa_verification.php" method="POST">
                <input 
                    type="text" 
                    placeholder="6-Digit Code" 
                    name="two_factor_code" 
                    required 
                    maxlength="6"
                    pattern="\d{6}"
                    title="Must be exactly 6 digits"
                    autocomplete="one-time-code" 
                    style="text-align: center; letter-spacing: 5px; font-size: 1.2em;"
                />
                <button type="submit">Verify Code</button>
            </form>
            <div class="toggle-text">
                <p style="margin-top: 20px;"><a href="login.php">Cancel Login</a></p>
                </div>
        </div>
    </div>
</body>
</html>