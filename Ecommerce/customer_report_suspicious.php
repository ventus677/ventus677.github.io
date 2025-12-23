<?php
session_start();

// --- LOGIN CHECK AND REDIRECTION ---
// Kung walang customer session, i-redirect sa login page (customer_auth.php)
if (!isset($_SESSION['customer']) || empty($_SESSION['customer'])) {
    header('location: customer_auth.php');
    exit();
}
// --- END LOGIN CHECK ---

// --- DEBUGGING SETTINGS (IBINALIK SA 0/OFF) ---
// Iwanang naka-off sa production environment
ini_set('display_errors', 0); // Disable display errors in production
ini_set('display_startup_errors', 0);
error_reporting(0); // Turn off error reporting
// --- END DEBUGGING SETTINGS ---

// include('../database/connect.php'); // Uncomment if needed

// --- PHPMailer Includes ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; 

// Path to PHPMailer (Adjust this if not using Composer)
require __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
// -----------------------------------------------------

$customer = $_SESSION['customer'] ?? null;
// Kunin ang email ng naka-login na customer para magamit sa Reply-To
$customerEmail = $_SESSION['customer']['email'] ?? 'N/A - Logged in User';
$customerName = $_SESSION['customer']['name'] ?? 'KeepKit Customer'; // Assuming 'name' is in the session, or use 'KeepKit Customer' if not

$total_items_in_cart = $_SESSION['total_items_in_cart'] ?? 0;

$report_success = false;
$report_error = '';

// --- COMMON PHPMailer Settings ---
function getMailerConfig(): array {
    // Iyong Gmail configuration (Ito ang AUTHENTICATING account)
    return [
        'Host' => 'smtp.gmail.com',
        'Username' => 'markedselmorales0922@gmail.com', // Iyong Fixed Gmail Address
        'Password' => 'polr hrom tgnx qjlt', // Iyong App Password
        'SMTPSecure' => PHPMailer::ENCRYPTION_STARTTLS,
        'Port' => 587,
        'FromEmail' => 'markedselmorales0922@gmail.com'
    ];
}

/**
 * Sends the suspicious activity report email to keepkit3a@gmail.com.
 */
function sendReportEmail(array $formData, array $files, string $customerEmail, string $customerName): bool {
    $mail = new PHPMailer(true);
    
    // --- PHPMailer DEBUGGING DISABLED ---
    $mail->SMTPDebug = 0; 
    // ------------------------------------
    
    $config = getMailerConfig();
    global $report_error;
    
    try {
        // --- SSL VERIFICATION BYPASS (for local testing) ---
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

        // From: Ito ay ang AUTHENTICATING account. Binago ko ang Display Name para mas malinaw
        $mail->setFrom($config['FromEmail'], 'Keepkit Report System (via ' . $customerEmail . ')');
        
        // --- CRITICAL: Idagdag ang customer email bilang Reply-To address ---
        // Kapag nag-reply ang Security Team, sa customer email ito mapupunta.
        $mail->addReplyTo($customerEmail, $customerName);
        
        // TARGET RECIPIENT
        $mail->addAddress('keepkit3a@gmail.com', 'KeepKit Security Team');

        $mail->isHTML(true);
        // Ginawang mas descriptive ang Subject
        $mail->Subject = 'NEW SECURITY REPORT from ' . $customerEmail . ': ' . htmlspecialchars($formData['report_type']);
        
        // Build the email body
        $body = "<h2>Suspicious Activity Report</h2>";
        // Gamitin ang customer info mula sa session
        $body .= "<p><strong>Report Submitted By (Customer Email):</strong> " . htmlspecialchars($customerEmail) . "</p>";
        $body .= "<hr>";
        $body .= "<p><strong>Report Type:</strong> " . htmlspecialchars($formData['report_type'] ?? 'N/A') . "</p>";
        
        if ($formData['report_type'] == 'Report a suspicious phone call, email, or SMS/text message') {
            $body .= "<p><strong>Contact Method:</strong> " . htmlspecialchars($formData['contact_method'] ?? 'N/A') . "</p>";
            $body .= "<p><strong>Suspicious Email/Number:</strong> " . htmlspecialchars($formData['suspicious_email'] ?? 'N/A') . "</p>";
            $body .= "<p><strong>Platform Received:</strong> " . htmlspecialchars($formData['platform_received'] ?? 'N/A') . "</p>";
            $body .= "<p><strong>Email Location:</strong> " . htmlspecialchars($formData['email_location'] ?? 'N/A') . "</p>";
            $body .= "<p><strong>Date Contacted:</strong> " . htmlspecialchars($formData['contact_date'] ?? 'N/A') . "</p>";
        }

        $body .= "<p><strong>Asset Loss:</strong> $" . htmlspecialchars($formData['asset_loss'] ?? '0') . "</p>";
        $body .= "<h3>Summary of Activity:</h3>";
        $body .= "<p style='padding: 15px; border: 1px dashed #ccc; background-color: #f9f9f9;'>" . nl2br(htmlspecialchars($formData['summary'] ?? 'No summary provided.')) . "</p>";
        
        $mail->Body = $body;
        $mail->AltBody = "Report Summary: " . ($formData['summary'] ?? 'No summary provided.') . "\nAsset Loss: $" . ($formData['asset_loss'] ?? '0') . "\nSubmitted by: " . $customerEmail;

        // Attach files
        if (isset($files['screenshot_files']) && is_array($files['screenshot_files'])) {
            $num_files = count($files['screenshot_files']['name']);
            for ($i = 0; $i < $num_files && $i < 6; $i++) {
                if ($files['screenshot_files']['error'][$i] == UPLOAD_ERR_OK) {
                    // Safety check on filename
                    $safe_filename = preg_replace('/[^a-zA-Z0-9\._-]/', '', basename($files['screenshot_files']['name'][$i]));
                    $mail->addAttachment($files['screenshot_files']['tmp_name'][$i], $safe_filename);
                }
            }
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Ipakita ang error message sa user
        $report_error = "Failed to send report. Please try again later or email us directly. Error: " . $mail->ErrorInfo;
        return false;
    }
}

// --- Report Submission Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    
    // Safety check for required fields
    if (empty($_POST['summary']) || empty($_POST['report_type'])) {
        $report_error = "Please fill out the required fields.";
    } else {
        $formData = [
            'report_type' => $_POST['report_type'] ?? 'General Suspicion',
            'contact_method' => $_POST['contact_method'] ?? '',
            'suspicious_email' => $_POST['suspicious_email'] ?? '',
            'platform_received' => $_POST['platform_received'] ?? '',
            'email_location' => $_POST['email_location'] ?? '',
            'contact_date' => (isset($_POST['contact_month']) && isset($_POST['contact_day']) && isset($_POST['contact_year'])) 
                                ? $_POST['contact_year'] . '-' . $_POST['contact_month'] . '-' . $_POST['contact_day'] 
                                : '',
            'asset_loss' => (float)str_replace(['$', ','], '', $_POST['asset_loss'] ?? 0),
            'summary' => $_POST['summary'] ?? ''
        ];

        $uploaded_files = $_FILES;

        // Pass the customer email and name to the send function
        if (sendReportEmail($formData, $uploaded_files, $customerEmail, $customerName)) {
            // Success: clear form data and set flag
            $report_success = true;
            $formData = []; // clear data to prevent re-display
        } else {
            // Error handling is already done inside sendReportEmail function
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Suspicious Activity | KeepKit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer_products.css">
    <link rel="stylesheet" href="customer_footer.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none; /* Hidden by default */
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #fff;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 20px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.5rem;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
        }

        .modal-header .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .modal-body label, .modal-content form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .modal-body input[type="text"],
        .modal-body select,
        .modal-body textarea,
        .modal-content form input[type="text"],
        .modal-content form select,
        .modal-content form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .modal-content form textarea {
            resize: vertical;
            min-height: 100px;
        }

        .asset-loss-group, .contact-date-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .asset-loss-group input {
            flex-grow: 1;
        }

        .contact-date-group select {
            flex-grow: 1;
        }

        .upload-box {
            border: 2px dashed #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .upload-box i {
            font-size: 2rem;
            color: #999;
            display: block;
            margin-bottom: 5px;
        }

        .upload-count {
            font-weight: bold;
            color: #007bff;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #ff8c00;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 10px;
        }

        .safeguard-note {
            text-align: center;
            color: green;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        /* Success/Error Message Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            text-align: center;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
    </style>
</head>
<body>
    
    <?php include('header_public.php'); ?>

    <main class="info-page-container">
        <h2>Report Something Suspicious</h2>
        <span class="page-subtitle">Help us keep KeepKit safe and secure.</span>

        <p>We promise to protect your privacy and data. If you come across any suspicious activity or have any concerns, please report them to us immediately through the options provided below. We will treat your report seriously. Thank you for your support and cooperation!</p>
        
        <?php if ($report_success): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                Report submitted successfully! Thank you for helping us keep KeepKit safe.
            </div>
        <?php endif; ?>

        <?php if ($report_error): ?>
            <div class="alert alert-error" style="max-width: 600px; margin: 20px auto;">
                <?php 
                    // Ipinapakita ang error message na na-set sa sendReportEmail function
                    echo htmlspecialchars($report_error); 
                ?>
            </div>
        <?php endif; ?>
        <p class="warning-non-suspicious">
            <i class="fas fa-info-circle"></i> If you have any **non-suspicious reports** (Official activities or order after-sales), please contact our <a href="customer_contact.php">customer service</a>.
        </p>

        <h3>Select a suspicious situation you encountered</h3>

        <div class="report-selection-grid">
            
            <a href="#" class="report-card" onclick="openModal('Report a suspicious phone call, email, or SMS/text message'); return false;">
                <span class="card-title">Report a suspicious phone call, email, or SMS/text message</span>
                <i class="fas fa-chevron-right"></i>
            </a>

            <a href="#" class="report-card" onclick="openModal('Report a fake website or app similar to KeepKit'); return false;">
                <span class="card-title">Report a fake website or app similar to KeepKit</span>
                <i class="fas fa-chevron-right"></i>
            </a>

            <a href="#" class="report-card" onclick="openModal('Report fake job opportunities and other activities that impersonate KeepKit'); return false;">
                <span class="card-title">Report fake job opportunities and other activities that impersonate KeepKit</span>
                <i class="fas fa-chevron-right"></i>
            </a>

            <a href="#" class="report-card" onclick="openModal('Unauthorized Account Access or Fraudulent Transactions'); return false;">
                <span class="card-title">Unauthorized Account Access or Fraudulent Transactions</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
        </div>
        
        <h3 style="margin-top: 80px;">How to Submit Your Report</h3>
        <p>Para sa agarang imbestigasyon, mangyaring i-email ang lahat ng detalye, kasama ang screenshots (kung meron) sa aming Security Team:</p>
        
        <div class="contact-social-links-container">
            <a href="mailto:markedsel.morales@cvsu.edu.ph" class="contact-social-link" style="width: 350px;">
                <i class="fas fa-envelope"></i>
                <div class="link-details">
                    <span>Security Email</span>
                    <small>markedsel.morales@cvsu.edu.ph</small>
                </div>
            </a>
            
            <a href="tel:(09XX)XXXX-XXXX" class="contact-social-link" style="width: 350px;">
                <i class="fas fa-phone"></i>
                <div class="link-details">
                    <span>Emergency Line</span>
                    <small>09331411265</small>
                </div>
            </a>
        </div>
        
    </main>
    
    <div id="reportModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Report Something Suspicious</h2>
                <button type="button" class="close-btn" onclick="closeModal()">×</button>
            </div>
            
            <div class="safeguard-note">
                <i class="fas fa-lock"></i> All data is safeguarded
            </div>
            
            <form method="POST" action="customer_report_suspicious.php" enctype="multipart/form-data">
                
                <input type="hidden" name="report_type" id="reportTypeHidden">

                <div id="contactFields" style="display: none;">
                    <label for="contact_method">* How were you contacted?</label>
                    <select id="contact_method" name="contact_method">
                        <option value="">Please choose</option>
                        <option value="Email">Email</option>
                        <option value="Phone Call">Phone Call</option>
                        <option value="SMS/Text Message">SMS/Text Message</option>
                        <option value="Other">Other</option>
                    </select>

                    <label for="suspicious_email">Enter the suspicious email address / phone number</label>
                    <input type="text" id="suspicious_email" name="suspicious_email" placeholder="Example: support@keepkit-scam.com">

                    <label for="platform_received">* What platform did you receive the scam email on?</label>
                    <select id="platform_received" name="platform_received">
                        <option value="">Please choose</option>
                        <option value="Gmail">Gmail</option>
                        <option value="Yahoo Mail">Yahoo Mail</option>
                        <option value="Outlook">Outlook</option>
                        <option value="N/A - Not Email">N/A - Not Email</option>
                    </select>

                    <label for="email_location">* Where did you find the suspicious email? In your inbox or the spam folder?</label>
                    <select id="email_location" name="email_location">
                        <option value="">Please choose</option>
                        <option value="Inbox">Inbox</option>
                        <option value="Spam/Junk Folder">Spam/Junk Folder</option>
                        <option value="N/A - Not Email">N/A - Not Email</option>
                    </select>

                    <label>* When were you first contacted? (Month, Day, Year)</label>
                    <div class="contact-date-group">
                        <select name="contact_month">
                            <?php 
                                // Iayos ang buwan para mag-start sa 01=January, 02=February, etc.
                                $months = ['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
                                // Ang iyong listahan ng buwan ay mali. Inayos ko na.
                                foreach($months as $num => $name) {
                                    echo "<option value='{$num}'>{$name}</option>";
                                }
                            ?>
                        </select>
                        <select name="contact_day">
                            <?php for($i=1; $i<=31; $i++) {
                                $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                                echo "<option value='{$day}'>{$i}</option>";
                            } ?>
                        </select>
                        <select name="contact_year">
                            <?php for($i=2020; $i<=2025; $i++) {
                                echo "<option value='{$i}' " . (date('Y') == $i ? 'selected' : '') . ">{$i}</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <label>Has there been a loss of assets? If yes, please fill in the specific amount</label>
                <div class="asset-loss-group">
                    <input type="text" id="asset_loss" name="asset_loss" placeholder="Example: $3">
                </div>

                <label for="screenshot_files">* Upload screenshot(s) of suspicious activity</label>
                <div class="upload-box" onclick="document.getElementById('screenshot_files').click()">
                    <i class="fas fa-camera"></i>
                    <small>Upload at most 6 photos. Please only upload JPEG or PNG.</small>
                    <p class="upload-count"><span id="fileCount">0</span> / 6</p>
                </div>
                <input type="file" id="screenshot_files" name="screenshot_files[]" accept="image/jpeg, image/png" multiple style="display: none;" onchange="updateFileCount(this)">


                <label for="summary">* Summarize the suspicious activity in a few sentences</label>
                <textarea id="summary" name="summary" required maxlength="1000" oninput="updateCharCount(this)" placeholder="Share details like a suspicious website URL or link that was provided. Note: Please do not include any personal information."></textarea>
                <small style="float: right; color: #666;"><span id="charCount">0</span>/1000</small>
                <div style="clear: both;"></div>
                
                <button type="submit" name="submit_report" class="submit-btn">Submit</button>
            </form>
        </div>
    </div>
    <?php include('footer_public.php'); ?>
    
    <script>
        const modal = document.getElementById('reportModal');
        const reportTypeHidden = document.getElementById('reportTypeHidden');
        const contactFields = document.getElementById('contactFields');
        const contactMethod = document.getElementById('contact_method');
        const platformReceived = document.getElementById('platform_received');
        const emailLocation = document.getElementById('email_location');
        const summaryTextarea = document.getElementById('summary');
        
        // Function to open the modal
        function openModal(reportTitle) {
            // Reset form when opening
            modal.querySelector('form').reset();
            document.getElementById('fileCount').textContent = '0';
            document.getElementById('charCount').textContent = '0';

            // Re-initialize character count after reset
            if (summaryTextarea) updateCharCount(summaryTextarea);

            reportTypeHidden.value = reportTitle;
            
            // Toggle required attributes and visibility for the first card type
            const isContactReport = reportTitle.includes('phone call, email, or SMS/text message');

            if (isContactReport) {
                contactFields.style.display = 'block';
                // Make contact details required for this specific report type
                contactMethod.setAttribute('required', 'required');
                platformReceived.setAttribute('required', 'required');
                emailLocation.setAttribute('required', 'required');
            } else {
                contactFields.style.display = 'none';
                // Remove required status for other report types
                contactMethod.removeAttribute('required');
                platformReceived.removeAttribute('required');
                emailLocation.removeAttribute('required');
            }
            
            modal.style.display = 'flex';
        }

        // Function to close the modal
        function closeModal() {
            modal.style.display = 'none';
            modal.querySelector('form').reset();
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // Update file count display
        function updateFileCount(input) {
            const fileCountSpan = document.getElementById('fileCount');
            const maxFiles = 6;
            let count = input.files.length;
            
            if (count > maxFiles) {
                alert("You can only upload a maximum of " + maxFiles + " files.");
                // Clear the input and reset count display
                input.value = ''; 
                count = 0;
            }
            fileCountSpan.textContent = count;
        }

        // Update character count for summary
        function updateCharCount(textarea) {
            const charCountSpan = document.getElementById('charCount');
            charCountSpan.textContent = textarea.value.length;
        }

        // Initialize char count on page load if summary has content (useful if form submission fails)
        document.addEventListener('DOMContentLoaded', function() {
            if (summaryTextarea && summaryTextarea.value.length > 0) {
                updateCharCount(summaryTextarea);
            }
        });
    </script>
</body>
</html>