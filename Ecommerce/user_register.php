<?php
session_start(); 
include('../database/connect.php'); 

// --- PHPMailer Includes ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';

$response_message = '';
$response_type = ''; 

function generateOTP(): string {
    return str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

function sendOTPEmail(string $recipientEmail, string $otp): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); 
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true; 
        $mail->Username   = 'markedselmorales0922@gmail.com'; 
        $mail->Password   = 'polr hrom tgnx qjlt'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587; 

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('no-reply@keepkit.com', 'Keepkit Inventory System');
        $mail->addAddress($recipientEmail); 

        $mail->isHTML(true); 
        $mail->Subject = 'Keepkit: Your OTP for Customer Registration';
        $mail->Body    = "Hello,<br><br>Ang iyong One-Time Password (OTP) para sa pag-verify ng iyong account ay: <b>$otp</b><br><br>Salamat,<br>Keepkit Team";
        $mail->AltBody = "Ang iyong OTP: $otp.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email failed: {$mail->ErrorInfo}");
        return false;
    }
}

$is_otp_required = isset($_SESSION['otp_required']) && $_SESSION['otp_required'] === true && isset($_SESSION['reg_data']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'register_request':
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $phone_number = $_POST['phone_number'] ?? '';
            $region = $_POST['region'] ?? '';
            $province = $_POST['province'] ?? '';
            $city = $_POST['city'] ?? '';
            $barangay = $_POST['barangay'] ?? '';
            $postal_code = $_POST['postal_code'] ?? '';
            $street = $_POST['street'] ?? '';

            if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
                $response_message = 'Lahat ng field ay kailangan.';
                $response_type = 'error';
            } elseif ($password !== $confirm_password) {
                $response_message = 'Hindi magkatugma ang password.';
                $response_type = 'error';
            } else {
                try {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $response_message = 'May account na ang email na ito.';
                        $response_type = 'error';
                    } else {
                        $otp = generateOTP();
                        $_SESSION['reg_data'] = [
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'email' => $email,
                            'password' => password_hash($password, PASSWORD_DEFAULT),
                            'phone_number' => $phone_number,
                            'region' => $region,
                            'province' => $province,
                            'city' => $city,
                            'barangay' => $barangay,
                            'postal_code' => $postal_code,
                            'street' => $street,
                            'otp' => $otp,
                            'otp_expiry' => time() + (10 * 60)
                        ];

                        if (sendOTPEmail($email, $otp)) {
                            $_SESSION['otp_required'] = true; 
                            $is_otp_required = true;
                            $response_message = 'OTP sent to your email.';
                            $response_type = 'info';
                        }
                    }
                } catch (PDOException $e) {
                    $response_message = 'Database error.';
                    $response_type = 'error';
                }
            }
            break;

        case 'verify_otp':
            $entered_otp = $_POST['otp_code'] ?? '';
            $reg_data = $_SESSION['reg_data'] ?? null;

            if ($reg_data && $entered_otp === $reg_data['otp']) {
                try {
                    $conn->beginTransaction();
                    
                    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, Created_AT) VALUES (?, ?, ?, ?, 'customer', NOW())");
                    $stmt->execute([$reg_data['first_name'], $reg_data['last_name'], $reg_data['email'], $reg_data['password']]);
                    $user_id = $conn->lastInsertId();

                    $stmt_addr = $conn->prepare("INSERT INTO user_addresses (user_id, phone_number, region, province, city, barangay, postal_code, street_name_building_house_no, is_current, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
                    $stmt_addr->execute([$user_id, $reg_data['phone_number'], $reg_data['region'], $reg_data['province'], $reg_data['city'], $reg_data['barangay'], $reg_data['postal_code'], $reg_data['street']]);

                    $conn->commit();
                    $_SESSION['status_message'] = 'Registration successful!';
                    $_SESSION['status_type'] = 'success';
                    unset($_SESSION['reg_data'], $_SESSION['otp_required']);
                    header('Location: user_auth.php');
                    exit;
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $response_message = 'Error during registration: ' . $e->getMessage();
                    $response_type = 'error';
                }
            } else {
                $response_message = 'Mali ang OTP.';
                $response_type = 'error';
            }
            break;
    }
}

$page_title = "Customer Registration";
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
        .auth-container { background-color: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); width: 100%; max-width: 550px; text-align: center; box-sizing: border-box; position: relative; margin: 20px; }
        .auth-container h2 { margin-bottom: 30px; color: #2c3e50; font-size: 28px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; box-sizing: border-box; }
        .btn-submit { background-color: #6a0dad; color: white; padding: 14px 25px; border: none; border-radius: 6px; font-size: 18px; cursor: pointer; width: 100%; margin-top: 10px; }
        .toggle-form-link { display: block; margin-top: 25px; color: #6a0dad; text-decoration: none; font-size: 15px; }
        .response-message { padding: 12px 20px; margin-bottom: 20px; border-radius: 6px; font-size: 16px; text-align: center; opacity: 0; visibility: hidden; position: absolute; width: calc(100% - 80px); top: 20px; left: 50%; transform: translateX(-50%); z-index: 10; }
        .response-message.show { opacity: 1; visibility: visible; }
        .response-message.error { background-color: #ffe6e6; color: #dc3545; border: 1px solid #dc3545; }
        .response-message.info { background-color: #e6f7ff; color: #007bff; border: 1px solid #007bff; }
        .grid-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        body.dark-mode { background-color: #121212; color: #e0e0e0; }
        body.dark-mode .auth-container { background-color: #1e1e1e; }
        body.dark-mode .form-group input, body.dark-mode .form-group textarea, body.dark-mode .form-group select { background-color: #2c2c2c; color: #e0e0e0; border: 1px solid #444; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div id="responseMessage" class="response-message <?php echo htmlspecialchars($response_type); ?>">
            <?php echo htmlspecialchars($response_message); ?>
        </div>

        <?php if ($is_otp_required): ?>
            <form action="user_register.php" method="POST">
                <input type="hidden" name="action" value="verify_otp">
                <h2>Verify Account</h2>
                <div class="form-group">
                    <label>OTP Code:</label>
                    <input type="text" name="otp_code" required maxlength="6" pattern="\d{6}">
                </div>
                <button type="submit" class="btn-submit">Verify</button>
            </form>
        <?php else: ?>
            <form action="user_register.php" method="POST">
                <input type="hidden" name="action" value="register_request">
                <h2>Customer Registration</h2>
                <div class="grid-row">
                    <div class="form-group">
                        <label>First Name:</label>
                        <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name:</label>
                        <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="grid-row">
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password:</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Phone Number:</label>
                    <input type="text" name="phone_number" required value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>">
                </div>
                <div class="grid-row">
                    <div class="form-group">
                        <label>Region:</label>
                        <input type="text" name="region" required value="<?= htmlspecialchars($_POST['region'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Province:</label>
                        <input type="text" name="province" required value="<?= htmlspecialchars($_POST['province'] ?? '') ?>">
                    </div>
                </div>
                <div class="grid-row">
                    <div class="form-group">
                        <label>City:</label>
                        <input type="text" name="city" required value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Barangay:</label>
                        <input type="text" name="barangay" required value="<?= htmlspecialchars($_POST['barangay'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Postal Code:</label>
                    <input type="text" name="postal_code" required value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Street Name / Bldg / House No:</label>
                    <textarea name="street" rows="2" required><?= htmlspecialchars($_POST['street'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn-submit">Register</button>
            </form>
            <a href="user_auth.php" class="toggle-form-link">Already have an account? Log in</a>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const res = document.getElementById('responseMessage');
            if (res && res.textContent.trim() !== '') {
                res.classList.add('show');
                setTimeout(() => { res.classList.remove('show'); }, 4000);
            }
        });
    </script>
</body>
</html>