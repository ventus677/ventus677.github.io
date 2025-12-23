<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1); 
session_start(); 

// --- PHPMailer Includes ---
// NOTE: Make sure your 'vendor' folder and PHPMailer files are correctly set up.
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// --- COMMON PHPMailer Settings ---
function getMailerConfig(): array {
    return [
        'Host' => 'smtp.gmail.com',
        'Username' => 'markedselmorales0922@gmail.com', // Your working Gmail Address
        'Password' => 'polr hrom tgnx qjlt', // Your working App Password
        'SMTPSecure' => PHPMailer::ENCRYPTION_STARTTLS,
        'Port' => 587,
        'FromEmail' => 'markedselmorales0922@gmail.com'
    ];
}

// --- Email Sending Functions ---

/**
 * Sends the 2FA code to the user's email.
 */
function send2faCodeEmail(string $recipientEmail, string $code, string $firstName): bool {
    $mail = new PHPMailer(true);
    $config = getMailerConfig();
    try {
        // --- SSL VERIFICATION BYPASS (for local testing, remove in production if not needed) ---
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        // ---------------------------------------------------
        $mail->isSMTP();
        $mail->Host       = $config['Host']; 
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['Username']; 
        $mail->Password   = $config['Password']; 
        $mail->SMTPSecure = $config['SMTPSecure']; 
        $mail->Port       = $config['Port']; 

        $mail->setFrom($config['FromEmail'], 'Keepkit Security (2FA)');
        $mail->addAddress($recipientEmail, $firstName);

        $mail->isHTML(true);
        $mail->Subject = 'Your Two-Factor Authentication Code';
        $mail->Body    = "Hello {$firstName},<br><br>Your Two-Factor Authentication code is: <strong>{$code}</strong>. This code is valid for a short time.";
        $mail->AltBody = "Your 2FA code is: {$code}.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("2FA email failed for $recipientEmail: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends an email notification after successful login.
 */
function sendLoginNotificationEmail(array $userData): bool {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $time = date('Y-m-d H:i:s T');
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device/Browser';

    $mail = new PHPMailer(true);
    $config = getMailerConfig();
    try {
        // --- SSL VERIFICATION BYPASS (for local testing, remove in production if not needed) ---
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        // ---------------------------------------------------
        $mail->isSMTP();
        $mail->Host       = $config['Host']; 
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['Username']; 
        $mail->Password   = $config['Password']; 
        $mail->SMTPSecure = $config['SMTPSecure']; 
        $mail->Port       = $config['Port']; 

        $mail->setFrom($config['FromEmail'], 'Keepkit Security Alert');
        $mail->addAddress($userData['email'], $userData['first_name']);

        $mail->isHTML(true);
        $mail->Subject = 'New Login Detected on Your Keepkit Account';
        $mail->Body    = "<h2>Login Successful</h2>
                          <p>Hi {$userData['first_name']},</p>
                          <p>Your Keepkit account has just been logged into.</p>
                          <ul>
                              <li><strong>Time:</strong> $time</li>
                              <li><strong>Email:</strong> {$userData['email']}</li>
                              <li><strong>IP Address:</strong> $ipAddress</li>
                              <li><strong>Device/Browser:</strong> $browser</li>
                          </ul>
                          <p>If this was you, you can safely ignore this email.</p>
                          <p>If this was NOT you, please change your password immediately!</p>";
        $mail->AltBody = "New login detected on your account at $time from IP $ipAddress.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Login notification failed for {$userData['email']}: {$mail->ErrorInfo}");
        return false;
    }
}

// --- Function for Permission Definition (UNCHANGED) ---
function getPermissions(string $role): array {
    $role = strtolower(trim($role)); 
    $permissions = [];

    switch ($role) {
        case 'admin':
            $permissions = [
                'dashboard' => ['view'],
                'product' => ['view', 'create', 'edit', 'delete'],
                'supplier' => ['view', 'create', 'edit', 'delete'],
                'user' => ['view', 'create', 'edit', 'delete'],
                'customer' => ['view', 'create', 'edit', 'delete'], 
                'purchase_order' => ['view', 'create', 'edit', 'delete'],
                'point_of_sale' => ['view', 'create', 'edit', 'delete'],
                'reports' => ['view']
            ];
            break;
        case 'user':
            $permissions = [
                'dashboard' => ['view'],
                'product' => ['view'],
                'supplier' => ['view'],
                'user' => ['view'], 
                'customer' => ['view'], 
                'purchase_order' => ['view'],
                'point_of_sale' => ['view', 'create'], 
                'reports' => [] 
            ];
            break;
        default:
            $permissions = ['dashboard' => ['view']];
            break;
    }
    return $permissions;
}

// If the user is already logged in (full session), redirect them to home.php
if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    header('location: home.php');
    exit(); 
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    include('database/connect.php'); 

    // --- 2FA VERIFICATION CHECK ---
    // Check if the user is currently in the 2FA state (has temp_user) AND
    // if a 2FA code was submitted. This means we are processing the OTP step.
    if (isset($_SESSION['temp_user'], $_POST['two_factor_code'])) {
        
        $user_data = $_SESSION['temp_user'];
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

                    // 4. Send Login Notification 
                    sendLoginNotificationEmail($user_data); 

                    // 5. Redirect to home page
                    header("Location: home.php");
                    exit();

                } else {
                    $error_message = 'The entered code is incorrect or has expired.';
                }

            } catch (PDOException $e) {
                error_log("2FA verification database error: " . $e->getMessage());
                $error_message = "A system error occurred. Please check logs for details.";
            }
        }
    
    } 
    // --- STANDARD LOGIN CHECK ---
    // This runs if no temp_user session exists (initial login) or if a 2FA code was NOT submitted.
    else if (isset($_POST['Email'], $_POST['Password'])) { 
        $Email = $_POST['Email'] ?? ''; 
        $Password = $_POST['Password'] ?? ''; 

        if (empty($Email) || empty($Password)) {
            $error_message = 'Please enter both email and password.';
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, LOWER(role) AS role, profile_picture, security_question_type, security_answer, is_2fa_enabled, two_factor_code FROM users WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $Email]);

                if ($stmt->rowCount() > 0) {
                    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    $password_verified = false;

                    // Password Verification Logic (using password_verify and backwards compatibility)
                    if (password_verify($Password, $user_data['password'])) {
                        $password_verified = true;
                    } else if (strlen($user_data['password']) === 32 && ctype_xdigit($user_data['password'])) {
                        if (md5($Password) === $user_data['password']) {
                            $password_verified = true;
                        }
                    } else if ($Password === $user_data['password']) {
                        $password_verified = true;
                    }

                    if ($password_verified) {
                        // Password rehash check (optional)
                        $needs_rehash = password_needs_rehash($user_data['password'], PASSWORD_DEFAULT);
                        $is_old_hash = (strlen($user_data['password']) !== 60);

                        if ($needs_rehash || $is_old_hash) {
                            $newHashedPassword = password_hash($Password, PASSWORD_DEFAULT);
                            $updateStmt = $conn->prepare("UPDATE users SET password = :new_password WHERE id = :id");
                            $updateStmt->execute(['new_password' => $newHashedPassword, 'id' => $user_data['id']]);
                        }

                        unset($user_data['password']);

                        // --- 2FA LOGIC CHECK ---
                        // **REQUIRED FLOW:** Check if 2FA is enabled (is_2fa_enabled = 1)
                        if (isset($user_data['is_2fa_enabled']) && (int)$user_data['is_2fa_enabled'] === 1) { 
                            
                            // 1. Generate a 6-digit code
                            $twoFactorCode = strval(rand(100000, 999999));
                            
                            // 2. Save the code to the database
                            $updateStmt = $conn->prepare("UPDATE users SET two_factor_code = :code WHERE id = :id");
                            $updateStmt->execute(['code' => $twoFactorCode, 'id' => $user_data['id']]);

                            // 3. Send 2FA Code via Email
                            if (!send2faCodeEmail($user_data['email'], $twoFactorCode, $user_data['first_name'])) {
                                $error_message = 'Login failed. Could not send the 2FA code. Check your PHPMailer settings.';
                            } else {
                                // 4. Store temporary data in session for verification
                                $_SESSION['temp_user'] = $user_data; 
                                
                                // 5. Refresh the current page to display the 2FA form
                                // This is the key change to stay on login.php but enter the 2FA state
                                header("Location: login.php");
                                exit();
                            }

                        } else {
                            // STANDARD LOGIN (No 2FA enabled)
                            
                            $user_data['permissions'] = getPermissions($user_data['role']);
                            $_SESSION['user'] = $user_data;

                            // 2. Send Login Notification 
                            sendLoginNotificationEmail($user_data); 

                            header("Location: home.php");
                            exit();
                        }

                    } else {
                        $error_message = 'Email or password is incorrect.';
                    }
                } else {
                    $error_message = 'Email or password is incorrect.';
                }
            } catch (PDOException $e) {
                error_log("Login database error: " . $e->getMessage());
                $error_message = 'A system error occurred. Please check logs for details.';
            }
        }
    }
}

// --- Determine which form to display ---
$show_2fa_form = isset($_SESSION['temp_user']) && !empty($_SESSION['temp_user']);

// Get user data for the 2FA form if in 2FA state
$user_data_for_display = $show_2fa_form ? $_SESSION['temp_user'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $show_2fa_form ? '2FA Verification' : 'Sign In' ?> - Keepkit</title>
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
        
            <?php if ($show_2fa_form): // Display 2FA Form ?>
                <h2>Two-Factor Authentication</h2>
                <p>A 6-digit code has been sent to your email (<?= htmlspecialchars($user_data_for_display['email']) ?>). Please enter it below.</p>
                <?php if ($error_message): ?>
                    <div style="color: red; font-weight: bold; margin-bottom:15px; text-align:center;"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <form action="login.php" method="POST">
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
                    <p style="margin-top: 20px;"><a href="login.php?cancel=1">Cancel Login</a></p>
                    <?php 
                        // Cancel logic: Clears the temp_user session when the user clicks cancel
                        if (isset($_GET['cancel']) && $_GET['cancel'] == 1) {
                            unset($_SESSION['temp_user']);
                            header("Location: login.php");
                            exit();
                        }
                    ?>
                </div>

            <?php else: // Display Standard Login Form ?>

                <h2>Sign In</h2>
                <p>Access your inventory</p>
                <?php if ($error_message): ?>
                    <div style="color: red; font-weight: bold; margin-bottom:15px; text-align:center;"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <input type="email" placeholder="Email" name="Email" required autocomplete="username" />
                    <input type="password" placeholder="Password" name="Password" required autocomplete="current-password" />
                    <button type="submit">Login</button>
                </form>
                <div class="toggle-text">
                <a href="Ecommerce/user_auth.php">Are you Customer?</a>
                </div>
                <div class="toggle-text">
                    <a href="forgot_password.php">Forgot Password</a>
                </div>

            <?php endif; ?>

        </div>
    </div>
</body>
</html>