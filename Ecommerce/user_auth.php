<?php
session_start(); 
error_reporting(E_ALL); 
ini_set('display_errors', 1); 

// --- PHPMailer Includes ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';

// --- COMMON PHPMailer Settings ---
function getMailerConfig(): array {
    return [
        'Host' => 'smtp.gmail.com',
        'Username' => 'markedselmorales0922@gmail.com',
        'Password' => 'polr hrom tgnx qjlt', 
        'SMTPSecure' => PHPMailer::ENCRYPTION_STARTTLS,
        'Port' => 587,
        'FromEmail' => 'markedselmorales0922@gmail.com'
    ];
}

function send2faCodeEmail(string $recipientEmail, string $code, string $firstName): bool {
    $mail = new PHPMailer(true);
    $config = getMailerConfig();
    try {
        $mail->SMTPOptions = array(
            'ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true)
        );
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
        $mail->Body    = "Hello {$firstName},<br><br>Ang iyong Two-Factor Authentication code ay: <strong>{$code}</strong>. Pakiusap na huwag itong ibigay kanino man.";
        $mail->AltBody = "Your 2FA code is: {$code}.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("2FA email failed for $recipientEmail: {$mail->ErrorInfo}");
        return false;
    }
}

function sendLoginNotificationEmail(string $recipientEmail, string $userName): bool {
    $mail = new PHPMailer(true);
    $config = getMailerConfig();
    try {
        $mail->isSMTP(); 
        $mail->Host       = $config['Host']; 
        $mail->SMTPAuth   = true; 
        $mail->Username   = $config['Username']; 
        $mail->Password   = $config['Password']; 
        $mail->SMTPSecure = $config['SMTPSecure']; 
        $mail->Port       = $config['Port']; 
        $mail->SMTPOptions = array(
            'ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true)
        );

        $mail->setFrom($config['FromEmail'], 'Keepkit Security Alert');
        $mail->addAddress($recipientEmail); 

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_IP';
        $login_time = date('Y-m-d H:i:s T');
        $browser_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device/Browser';

        $mail->isHTML(true); 
        $mail->Subject = 'Security Alert: New Login Detected';
        $mail->Body    = "Hello <b>" . htmlspecialchars($userName) . "</b>,<br><br>Napansin namin ang isang matagumpay na pag-login sa iyong Keepkit account.<br><br>
                          **Detalye ng Pag-login:**<br>
                          * **Petsa/Oras:** {$login_time}<br>
                          * **IP Address:** {$ip_address}<br>
                          * **Device/Browser:** " . htmlspecialchars($browser_info) . "<br><br>
                          Kung ito ay ikaw, maaari mo itong balewalain. Kung hindi ikaw ang nag-login, **agarang palitan ang iyong password**.<br><br>Salamat,<br>Keepkit Team";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Login Notification Email failed. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

include('../database/connect.php'); 

// Redirect if already logged in
if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'customer') {
        header('location: user_products.php');
    } else {
        header('location: dashboard.php'); // Or wherever admins/users go
    }
    exit(); 
}

$response_message = '';
$response_type = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- 2FA VERIFICATION ---
    if (isset($_SESSION['temp_user'], $_POST['two_factor_code'])) {
        $user_data = $_SESSION['temp_user'];
        $input_code = trim($_POST['two_factor_code'] ?? '');

        if (empty($input_code) || strlen($input_code) !== 6 || !ctype_digit($input_code)) {
            $response_message = 'Pakilagay ang wastong 6-digit code.';
            $response_type = 'error';
        } else {
            try {
                $stmt = $conn->prepare("SELECT two_factor_code FROM users WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $user_data['id']]);
                $db_code = $stmt->fetchColumn();

                if ($db_code && $input_code === $db_code) {
                    $clearStmt = $conn->prepare("UPDATE users SET two_factor_code = NULL WHERE id = :id");
                    $clearStmt->execute(['id' => $user_data['id']]);

                    $_SESSION['user'] = $user_data;
                    unset($_SESSION['temp_user']);

                    $full_name = $user_data['first_name'] . ' ' . $user_data['last_name'];
                    sendLoginNotificationEmail($user_data['email'], $full_name); 

                    if ($user_data['role'] === 'customer') {
                        header("Location: user_products.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $response_message = 'Ang ipinasok na code ay mali o nag-expire na.';
                    $response_type = 'error';
                }
            } catch (PDOException $e) {
                error_log("2FA verification error: " . $e->getMessage());
                $response_message = "System error. Please check logs.";
                $response_type = 'error';
            }
        }
    }

    // --- LOGIN ACTION ---
    else if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $response_message = 'Pakilagay ang iyong email at password.';
            $response_type = 'error';
        } else {
            try {
                // Now querying 'users' table which contains 'role'
                $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, role, profile_picture, is_2fa_enabled FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    if (isset($user['is_2fa_enabled']) && (int)$user['is_2fa_enabled'] === 1) { 
                        $twoFactorCode = strval(rand(100000, 999999));
                        $updateStmt = $conn->prepare("UPDATE users SET two_factor_code = :code WHERE id = :id");
                        $updateStmt->execute(['code' => $twoFactorCode, 'id' => $user['id']]);

                        unset($user['password']);
                        
                        if (!send2faCodeEmail($user['email'], $twoFactorCode, $user['first_name'])) {
                            $response_message = 'Hindi maipadala ang 2FA code.';
                            $response_type = 'error';
                        } else {
                            $_SESSION['temp_user'] = $user; 
                            header("Location: user_auth.php");
                            exit();
                        }
                    } else {
                        unset($user['password']);
                        $_SESSION['user'] = $user;
                        
                        $full_name = $user['first_name'] . ' ' . $user['last_name'];
                        sendLoginNotificationEmail($user['email'], $full_name);

                        if ($user['role'] === 'customer') {
                            header('Location: user_products.php'); 
                        } else {
                            header('Location: dashboard.php'); 
                        }
                        exit;
                    }
                } else {
                    $response_message = 'Hindi tugma ang email o password.';
                    $response_type = 'error';
                }
            } catch (PDOException $e) {
                error_log("Login Error: " . $e->getMessage());
                $response_message = 'Database error during login.';
                $response_type = 'error';
            }
        }
    }
}

$show_2fa_form = isset($_SESSION['temp_user']) && !empty($_SESSION['temp_user']);
$user_data_display = $show_2fa_form ? $_SESSION['temp_user'] : [];

if (isset($_GET['cancel']) && $_GET['cancel'] == 1) {
    if (isset($_SESSION['temp_user']['id'])) {
        $clearStmt = $conn->prepare("UPDATE users SET two_factor_code = NULL WHERE id = :id");
        $clearStmt->execute(['id' => $_SESSION['temp_user']['id']]);
    }
    unset($_SESSION['temp_user']);
    header("Location: user_auth.php");
    exit();
}

$page_title = $show_2fa_form ? '2FA Verification' : 'User Login';
include('header_public.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" type="image/png" href="../images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; color: #333; padding-top: 70px; box-sizing: border-box; }
        .auth-container { background-color: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); width: 100%; max-width: 450px; text-align: center; box-sizing: border-box; position: relative; margin-top: 20px; margin-bottom: 20px; }
        .auth-container h2 { margin-bottom: 30px; color: #2c3e50; font-size: 28px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="password"] { width: calc(100% - 20px); padding: 12px 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; color: #333; box-sizing: border-box; transition: border-color 0.3s ease; }
        .form-group input:focus { border-color: #6a0dad; outline: none; }
        .btn-submit { background-color: #6a0dad; color: white; padding: 14px 25px; border: none; border-radius: 6px; font-size: 18px; cursor: pointer; width: 100%; transition: background-color 0.3s ease, transform 0.2s ease; margin-top: 10px; }
        .btn-submit:hover { background-color: #5a0ca0; transform: translateY(-2px); }
        .toggle-form-link { display: block; margin-top: 25px; color: #6a0dad; text-decoration: none; font-size: 15px; transition: color 0.3s ease; }
        .toggle-form-link:hover { color: #5a0ca0; text-decoration: underline; }
        .response-message { padding: 12px 20px; margin-bottom: 20px; border-radius: 6px; font-size: 16px; text-align: center; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s ease; position: absolute; width: calc(100% - 80px); top: 20px; left: 50%; transform: translateX(-50%); box-sizing: border-box; z-index: 10; }
        .response-message.show { opacity: 1; visibility: visible; }
        .response-message.error { background-color: #ffe6e6; color: #dc3545; border: 1px solid #dc3545; }
        .auth-container input[name="two_factor_code"] { text-align: center; letter-spacing: 5px; font-size: 1.2em; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div id="responseMessage" class="response-message <?php echo htmlspecialchars($response_type); ?>" style="<?php echo empty($response_message) ? 'display: none;' : 'display: block;'; ?>">
            <?php echo htmlspecialchars($response_message); ?>
        </div>
        
        <?php if ($show_2fa_form): ?>
            <h2>Two-Factor Verification</h2>
            <p>The 6-digit code has been sent to your email(<?= htmlspecialchars($user_data_display['email']) ?>).</p>
            <form action="user_auth.php" method="POST">
                <div class="form-group">
                    <label for="twoFactorCode">2FA Code:</label>
                    <input type="text" id="twoFactorCode" placeholder="6-Digit Code" name="two_factor_code" required maxlength="6" pattern="\d{6}" autocomplete="off" />
                </div>
                <button type="submit" class="btn-submit">Verify Code</button>
                <a href="user_auth.php?cancel=1" class="toggle-form-link">Cancel Login</a>
            </form>
        <?php else: ?>
            <h2>Customer Login</h2>
            <form id="loginForm" action="user_auth.php" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="loginEmail">Email:</label>
                    <input type="email" id="loginEmail" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="loginPassword">Password:</label>
                    <input type="password" id="loginPassword" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-submit">Login</button>
                <a href="user_forgot_password.php" class="toggle-form-link">Forgot Password?</a>
            </form>
            <a href="user_register.php" class="toggle-form-link">Sign up here</a>
            <a href="../login.php" class="toggle-form-link">Are you Employee?</a>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const responseMessageDiv = document.getElementById('responseMessage');
            if (responseMessageDiv && responseMessageDiv.textContent.trim() !== '') {
                responseMessageDiv.classList.add('show');
                setTimeout(() => {
                    responseMessageDiv.classList.remove('show');
                    setTimeout(() => { responseMessageDiv.style.display = 'none'; }, 500);
                }, 4000);
            }
        });
    </script>
</body>
</html>