<?php
session_start(); // Ensure session_start() is at the very beginning
include('../database/connect.php'); // Siguraduhin na tama ang path

// Include PHPMailer for sending emails (Gamit ang manual includes base sa signUp.php)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// ⚡️ File paths ng PHPMailer (Ginamit ang __DIR__ at manual requires)
require __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
// -------------------------------------------------------------

$response_message = '';
$response_type = ''; // 'success', 'error', 'info'

// Flag para malaman kung verified na ang OTP (Default: false)
$is_otp_verified = $_SESSION['is_otp_verified'] ?? false;


// --- Utility Functions for OTP ---
/**
 * Generates a random 6-digit OTP.
 * @return string
 */
function generateOTP(): string {
    return str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Sends an email with the generated OTP.
 *
 * @param string $recipientEmail
 * @param string $otp
 * @return bool True if email was sent successfully, false otherwise.
 */
function sendOTPEmail(string $recipientEmail, string $otp): bool {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP(); // Send using SMTP
        $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true; // Enable SMTP authentication
        $mail->Username   = 'markedselmorales0922@gmail.com'; 
        $mail->Password   = 'polr hrom tgnx qjlt'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 587; // TCP port to connect to

        // SSL VERIFICATION BYPASS (Assuming this is needed based on previous context)
         $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        //Recipients
        $mail->setFrom('no-reply@keepkit.com', 'Keepkit Inventory System');
        $mail->addAddress($recipientEmail); // Add a recipient

        //Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = 'Keepkit: Your One-Time Password (OTP)';
        $mail->Body    = "Hello,<br><br>Ang iyong One-Time Password (OTP) para sa iyong password reset ay: <b>$otp</b><br><br>Ang code na ito ay mag-eexpire sa loob ng 10 minuto. Huwag ibigay ang code na ito sa iba.<br><br>Salamat,<br>Keepkit Team";
        $mail->AltBody = "Ang iyong OTP: $otp. Mag-eexpire sa loob ng 10 minuto.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
// --- End Utility Functions ---


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        // --- Step 1: Forgot Password Request (Send OTP) ---
        case 'forgot_password_request':
            unset($_SESSION['is_otp_verified']); // Reset verification flag
            $email = $_POST['email'] ?? '';

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response_message = 'Pakilagay ang valid email address.';
                $response_type = 'error';
            } else {
                try {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($customer) {
                        $otp = generateOTP();
                        $otp_expiry = time() + (10 * 60); // OTP expires in 10 minutes

                        // Store reset data and OTP in session
                        $_SESSION['reset_data'] = [
                            'user_id' => $customer['id'],
                            'email' => $email,
                            'otp' => $otp,
                            'otp_expiry' => $otp_expiry
                        ];
                        $_SESSION['reset_otp_required'] = true; // Flag to show OTP form

                        if (sendOTPEmail($email, $otp)) {
                            $response_message = 'Isang OTP ang ipinadala sa iyong email. Pakilagay ang code para magpatuloy sa pag-reset ng password.';
                            $response_type = 'info';
                        } else {
                            unset($_SESSION['reset_data']);
                            unset($_SESSION['reset_otp_required']);
                            $response_message = 'Hindi maipadala ang OTP email. Pakisuri ang iyong email address at subukan muli.';
                            $response_type = 'error';
                        }

                    } else {
                        $response_message = 'Ang email address ay hindi nakita sa aming system.';
                        $response_type = 'error';
                    }
                } catch (PDOException $e) {
                    error_log("Forgot Password Request Error: " . $e->getMessage());
                    $response_message = 'Database error during password request. Please try again later.';
                    $response_type = 'error';
                }
            }
            break;

        // --- Step 2: OTP Verification ---
        case 'verify_otp':
            $entered_otp = $_POST['otp_code'] ?? '';

            $stored_otp = $_SESSION['reset_data']['otp'] ?? '';
            $otp_expiry = $_SESSION['reset_data']['otp_expiry'] ?? 0;

            if (!isset($_SESSION['reset_data'])) {
                $response_message = 'Walang aktibong password reset request. Subukan muli.';
                $response_type = 'error';
                unset($_SESSION['reset_otp_required']);
            } elseif (time() > $otp_expiry) {
                $response_message = 'Ang OTP code ay nag-expire na. Subukan muli ang pag-request ng password reset.';
                $response_type = 'error';
                unset($_SESSION['reset_data']);
                unset($_SESSION['reset_otp_required']);
            } elseif (empty($entered_otp)) {
                $response_message = 'Pakilagay ang OTP code.';
                $response_type = 'error';
                $_SESSION['reset_otp_required'] = true; // Keep showing OTP form
            } elseif ($entered_otp === $stored_otp) {
                // OTP Verified! Move to Step 3 (New Password)
                $_SESSION['is_otp_verified'] = true;
                $is_otp_verified = true;
                $response_message = 'Matagumpay na na-verify ang OTP. Maaari ka nang maglagay ng bagong password.';
                $response_type = 'success';
            } else {
                $response_message = 'Mali o hindi tugma ang OTP code. Pakisuri muli.';
                $response_type = 'error';
                $_SESSION['reset_otp_required'] = true; // Keep showing OTP form
            }
            break;

        // --- Step 3: Reset Password (Using New Password) ---
        case 'reset_password':
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            $customer_id = $_SESSION['reset_data']['user_id'] ?? null;

            if (!$is_otp_verified || empty($customer_id)) {
                 // Should not happen if flow is correct, but good for security
                $response_message = 'Hindi na-verify ang OTP o walang aktibong reset request. Subukan muli.';
                $response_type = 'error';
                unset($_SESSION['reset_data']);
                unset($_SESSION['reset_otp_required']);
                unset($_SESSION['is_otp_verified']);
            } elseif (empty($new_password) || empty($confirm_password)) {
                $response_message = 'Lahat ng field ay kailangan.';
                $response_type = 'error';
                $_SESSION['is_otp_verified'] = true; // Keep showing form
            } elseif ($new_password !== $confirm_password) {
                $response_message = 'Hindi magkatugma ang mga bagong password.';
                $response_type = 'error';
                $_SESSION['is_otp_verified'] = true; // Keep showing form
            } elseif (strlen($new_password) < 6) {
                $response_message = 'Ang password ay dapat mayroong hindi bababa sa 6 na karakter.';
                $response_type = 'error';
                $_SESSION['is_otp_verified'] = true; // Keep showing form
            } else {
                // Reset password.
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $customer_id])) {
                        // Success! Redirect to login with a success message
                        $_SESSION['status_message'] = 'Matagumpay na na-reset ang iyong password! Maaari ka nang mag-login.';
                        $_SESSION['status_type'] = 'success';
                        header('Location: user_auth.php');
                        exit;
                    } else {
                        $response_message = 'May nangyaring error sa pag-reset ng password. Subukan muli.';
                        $response_type = 'error';
                    }
                } catch (PDOException $e) {
                    error_log("Password Reset Error: " . $e->getMessage());
                    $response_message = 'Database error during password reset. Please try again later.';
                    $response_type = 'error';
                }

                // Clear session data after final error
                unset($_SESSION['reset_data']);
                unset($_SESSION['reset_otp_required']);
                unset($_SESSION['is_otp_verified']);
            }
            break;
    }
}

// Check the current form state for display
$is_reset_otp_required = isset($_SESSION['reset_otp_required']) && $_SESSION['reset_otp_required'] === true && isset($_SESSION['reset_data']);

$page_title = "Forgot Password";

// Include the public header (RETAINED AS REQUESTED)
include('header_public.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Forgot Password'; ?></title>
    <link rel="icon" type="image/png" href="../images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* BASE STYLES */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
            padding-top: 70px;
            box-sizing: border-box;
        }

        .auth-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-sizing: border-box;
            position: relative;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .auth-container h2 {
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: calc(100% - 20px);
            padding: 12px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            color: #333;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: #6a0dad;
            outline: none;
        }

        .btn-submit {
            background-color: #6a0dad;
            color: white;
            padding: 14px 25px;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #5a0ca0;
            transform: translateY(-2px);
        }

        .toggle-form-link {
            display: block;
            margin-top: 25px;
            color: #6a0dad;
            text-decoration: none;
            font-size: 15px;
            transition: color 0.3s ease;
        }

        .toggle-form-link:hover {
            color: #5a0ca0;
            text-decoration: underline;
        }

        .response-message {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 16px;
            text-align: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            position: absolute;
            width: calc(100% - 80px);
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            box-sizing: border-box;
            z-index: 10;
        }
        .response-message.show {
            opacity: 1;
            visibility: visible;
        }
        .response-message.success {
            background-color: #e6ffe6;
            color: #28a745;
            border: 1px solid #28a745;
        }
        .response-message.error {
            background-color: #ffe6e6;
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        .response-message.info {
            background-color: #e6f7ff;
            color: #007bff;
            border: 1px solid #007bff;
        }

        .section-hidden {
            display: none !important;
        }

        /* DARK MODE STYLES */
        body.dark-mode {
            background-color: #121212;
            color: #e0e0e0;
        }

        body.dark-mode .auth-container {
            background-color: #1e1e1e;
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.05);
        }

        body.dark-mode .auth-container h2 {
            color: #bb86fc; /* Lighter purple for headings */
        }

        body.dark-mode .form-group label {
            color: #b0b0b0;
        }

        body.dark-mode .form-group input[type="text"],
        body.dark-mode .form-group input[type="email"],
        body.dark-mode .form-group input[type="password"] {
            background-color: #2c2c2c;
            color: #e0e0e0;
            border: 1px solid #444;
        }

        body.dark-mode .form-group input:focus {
            border-color: #bb86fc;
        }

        body.dark-mode .btn-submit {
            background-color: #bb86fc; /* Lighter purple for dark mode buttons */
        }

        body.dark-mode .btn-submit:hover {
            background-color: #9a67e2;
        }
        
        body.dark-mode .toggle-form-link {
            color: #bb86fc;
        }
        
        body.dark-mode .toggle-form-link:hover {
            color: #9a67e2;
        }

        /* Dark Mode Response Messages */
        body.dark-mode .response-message.success {
            background-color: #1f3d24; /* Darker green background */
            color: #69b976; /* Lighter green text */
            border: 1px solid #69b976;
        }
        body.dark-mode .response-message.error {
            background-color: #49171a;
            color: #ffbaba;
            border: 1px solid #cf6679;
        }
        body.dark-mode .response-message.info {
            background-color: #182e44; /* Darker blue background */
            color: #8ab4f8; /* Lighter blue text */
            border: 1px solid #8ab4f8;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div id="responseMessage" class="response-message <?php echo htmlspecialchars($response_type); ?>" style="<?php echo empty($response_message) ? 'display: none;' : 'display: block;'; ?>">
            <?php echo htmlspecialchars($response_message); ?>
        </div>
        
        <?php if ($is_otp_verified): ?>
            <form id="newPasswordForm" action="user_forgot_password.php" method="POST">
                <input type="hidden" name="action" value="reset_password">
                <h2 style="margin-bottom: 20px; font-size: 24px;">Set New Password</h2>
                <p style="margin-bottom: 30px;">Enter your new password to complete the reset..</p>
                <div class="form-group">
                    <label for="newPassword">New Password:</label>
                    <input type="password" id="newPassword" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirmNewPassword">Confirm New Password:</label>
                    <input type="password" id="confirmNewPassword" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-submit">Reset Password</button>
            </form>

        <?php elseif ($is_reset_otp_required): ?>
            <form id="verifyOtpForm" action="user_forgot_password.php" method="POST">
                <input type="hidden" name="action" value="verify_otp">
                <h2 style="margin-bottom: 20px; font-size: 24px;">Verify OTP</h2>
                <p style="margin-bottom: 30px;">Enter the 6-digit code sent to <b><?= htmlspecialchars($_SESSION['reset_data']['email'] ?? 'iyong email') ?></b>.</p>
                <div class="form-group">
                    <label for="resetOtpCode">OTP Code:</label>
                    <input type="text" id="resetOtpCode" name="otp_code" required maxlength="6" pattern="\d{6}" title="Pakilagay ang 6-digit OTP code">
                </div>
                <button type="submit" class="btn-submit">Verify OTP</button>
                <a href="user_forgot_password.php" id="resendResetOtpLink" class="toggle-form-link" style="font-size: 14px;">Resend OTP</a>
            </form>

        <?php else: ?>
            <form id="forgotRequestForm" action="user_forgot_password.php" method="POST">
                <input type="hidden" name="action" value="forgot_password_request">
                <h2 style="margin-bottom: 20px; font-size: 24px;">Forgot Password</h2>
                <p style="margin-bottom: 30px;">Enter your email address to send the OTP..</p>
                <div class="form-group">
                    <label for="forgotEmail">Email:</label>
                    <input type="email" id="forgotEmail" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-submit">Send OTP</button>
            </form>
        <?php endif; ?>

        <a href="user_auth.php" id="backToLoginLink" class="toggle-form-link">Return to Login</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // PHP-provided initial response message handling
            const responseMessageDiv = document.getElementById('responseMessage');
            if (responseMessageDiv && responseMessageDiv.textContent.trim() !== '') {
                // Initial load: Show message for 4 seconds
                responseMessageDiv.classList.add('show');
                setTimeout(() => {
                    responseMessageDiv.classList.remove('show');
                    setTimeout(() => {
                        responseMessageDiv.style.display = 'none';
                    }, 500);
                }, 4000);
            }
        });
    </script>
</body>
</html>