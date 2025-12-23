<?php
session_start();
error_reporting(E_ALL); // For debugging: displays all errors
ini_set('display_errors', 1); // For debugging: enables error display

// Ensure user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    // If it's an AJAX request, send JSON response
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
        exit;
    } else {
        // Otherwise, redirect for standard browser requests
        header('Location: index.php');
        exit;
    }
}

// Include database connection
include('database/connect.php'); // Make sure this path is correct

$user = $_SESSION['user']; // Get logged-in user data from session

// Fetch fresh user data to ensure 2FA status is accurate
try {
    $stmt_refresh = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt_refresh->execute([':id' => $user['id']]);
    $user = $stmt_refresh->fetch(PDO::FETCH_ASSOC);
    $_SESSION['user'] = $user; // Update session with fresh data
} catch (PDOException $e) {
    error_log("Error refreshing user data: " . $e->getMessage());
}

$message = '';
$message_type = '';

// Check if it's an AJAX request (specifically for file upload or generic AJAX)
$is_ajax_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
// More specific check for fetch() API with FormData for profile picture upload
$is_profile_picture_upload_request = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_picture']) && isset($_FILES['profile_picture']));


// --- Handle Profile Picture Upload/Update (Modified for AJAX/Fetch API) ---
if ($is_profile_picture_upload_request) {
    $response = ['success' => false, 'message' => ''];

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['profile_picture']['tmp_name'];
        $file_name = $_FILES['profile_picture']['name']; // This might be 'profile_pic.png' from JS
        $file_size = $_FILES['profile_picture']['size'];
        $file_type = $_FILES['profile_picture']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpeg', 'jpg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file_ext, $allowed_extensions)) {
            $response['message'] = 'Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.';
        } elseif ($file_size > $max_file_size) {
            $response['message'] = 'File size exceeds 5MB limit.';
        } else {
            // Generate a unique file name to prevent overwrites and security issues
            $new_file_name = uniqid('profile_', true) . '.' . $file_ext;
            $upload_dir = 'uploads/profiles/'; // Make sure this directory exists and is writable!
            $upload_path = $upload_dir . $new_file_name;

            // Ensure the uploads directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); // Create directory recursively with permissions
            }

            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                try {
                    // Using PDO prepared statements with named placeholders
                    // Make sure $conn is a PDO object. If it's mysqli, this will need adjustment.
                    $stmt = $conn->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :id");
                    $stmt->execute([':profile_picture' => $new_file_name, ':id' => $user['id']]);

                    // If old picture exists and is not the default icon, delete it
                    if (!empty($user['profile_picture']) && file_exists($upload_dir . $user['profile_picture']) && $user['profile_picture'] !== 'iconUser.png') {
                        unlink($upload_dir . $user['profile_picture']); // Delete the old file
                    }

                    // Update the session variable with the new picture
                    $_SESSION['user']['profile_picture'] = $new_file_name;
                    $user['profile_picture'] = $new_file_name; // Update local $user variable too

                    $response['success'] = true;
                    $response['message'] = 'Profile picture updated successfully!';
                    $response['new_image_url'] = $upload_path; // Send back the new image path
                } catch (PDOException $e) {
                    $response['message'] = 'Database error updating profile picture: ' . $e->getMessage();
                    error_log("Profile picture update DB error: " . $e->getMessage());
                    // If DB update fails, delete the uploaded file to clean up
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                }
            } else {
                $response['message'] = 'Failed to upload profile picture. Check directory permissions or file was not moved.';
                error_log("Profile picture upload move_uploaded_file error. Error: " . ($_FILES['profile_picture']['error'] ?? 'Unknown') . " Temp: " . $file_tmp_name . " Dest: " . $upload_path);
            }
        }
    } else {
        $response['message'] = 'No file uploaded or an upload error occurred: ' . ($_FILES['profile_picture']['error'] ?? 'Unknown error code');
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit; // IMPORTANT: Exit after sending JSON response for AJAX requests
}


// --- Handle Profile Information Update (Existing logic, for regular form submission) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_info'])) {
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));

    if (empty($first_name) || empty($last_name)) { 
        $message = 'First name and Last name are required.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name WHERE id = :id");
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':id' => $user['id']
            ]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['user']['first_name'] = $first_name;
                $_SESSION['user']['last_name'] = $last_name;
                $user['first_name'] = $first_name;
                $user['last_name'] = $last_name;

                $message = 'Profile information updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'No changes detected or profile update failed.';
                $message_type = 'info';
            }
        } catch (PDOException $e) {
            $message = 'Database error updating profile info: ' . $e->getMessage();
            error_log("Profile info update DB error: " . $e->getMessage());
            $message_type = 'error';
        }
    }
}

// --- Handle Toggle 2FA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_2fa'])) {
    try {
        $new_2fa_status = ($user['is_2fa_enabled'] == 1) ? 0 : 1;
        $stmt = $conn->prepare("UPDATE users SET is_2fa_enabled = :status WHERE id = :id");
        $stmt->execute([':status' => $new_2fa_status, ':id' => $user['id']]);

        $_SESSION['user']['is_2fa_enabled'] = $new_2fa_status;
        $user['is_2fa_enabled'] = $new_2fa_status;

        $message = $new_2fa_status == 1 ? 'Two-Factor Authentication enabled!' : 'Two-Factor Authentication disabled!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error toggling 2FA: ' . $e->getMessage();
        $message_type = 'error';
    }
}


// --- Handle Security Question Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_security_question'])) {
    $security_question_type = htmlspecialchars(trim($_POST['security_question_type']));
    $security_answer = htmlspecialchars(trim($_POST['security_answer']));
    $old_security_answer = htmlspecialchars(trim($_POST['old_security_answer'] ?? '')); // Old answer for verification

    // Fetch the current security answer and question type from the database for verification
    $stmt_fetch_current_sq = $conn->prepare("SELECT security_question_type, security_answer FROM users WHERE id = :id");
    $stmt_fetch_current_sq->execute([':id' => $user['id']]);
    $current_sq_data = $stmt_fetch_current_sq->fetch(PDO::FETCH_ASSOC);

    $stored_security_question_type = $current_sq_data['security_question_type'] ?? null;
    $stored_security_answer_hash = $current_sq_data['security_answer'] ?? null;


    // Logic for when a security question is ALREADY SET
    if (!empty($stored_security_question_type)) {
        // If an old security question exists, the 'old_security_answer' must be provided and correct.
        if (empty($old_security_answer)) {
            $message = 'Old security answer is required to change your security question.';
            $message_type = 'error';
        } elseif (!password_verify($old_security_answer, $stored_security_answer_hash)) {
            $message = 'Incorrect old security answer. Cannot update security question.';
            $message_type = 'error';
        }
    }

    // Only proceed if old answer is correct (or not required if no old question) and new fields are valid
    if (empty($security_question_type) || empty($security_answer)) {
        if (empty($security_question_type)) {
             $message = 'Please select a new security question.';
             $message_type = 'error';
        } elseif (empty($security_answer)) {
             $message = 'Please provide a new security answer.';
             $message_type = 'error';
        }
    }

    if ($message_type === '') { // If no error messages set so far
        try {
            // Hash the new security answer before storing it
            $hashed_security_answer = password_hash($security_answer, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET security_question_type = :type, security_answer = :answer WHERE id = :id");
            $stmt->execute([
                ':type' => $security_question_type,
                ':answer' => $hashed_security_answer, // Store hashed answer
                ':id' => $user['id']
            ]);

            if ($stmt->rowCount() > 0) {
                // Update session with new data (only the type)
                $_SESSION['user']['security_question_type'] = $security_question_type;
                $user['security_question_type'] = $security_question_type;

                $message = 'Security question updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'No changes detected or security question update failed.';
                $message_type = 'info';
            }
        } catch (PDOException $e) {
            $message = 'Database error updating security question: ' . $e->getMessage();
            error_log("Security question update DB error: " . $e->getMessage());
            $message_type = 'error';
        }
    }
}


// --- Handle Change Password ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $message = 'All password fields are required.';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_new_password) {
        $message = 'New password and confirm new password do not match.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 8) { 
        $message = 'New password must be at least 8 characters long.';
        $message_type = 'error';
    } else {
        try {
            // Fetch the stored password for the current user
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);
            $db_stored_password = $stmt->fetchColumn();

            $is_current_password_valid = false;

            // First, try verifying with password_verify
            if (password_verify($current_password, $db_stored_password)) {
                $is_current_password_valid = true;
            } else {
                // Fallback for plaintext (not recommended, but for legacy support)
                if (!str_starts_with($db_stored_password, '$2y$') &&
                    $current_password === $db_stored_password) {
                    $is_current_password_valid = true;
                }
            }

            if (!$is_current_password_valid) {
                $message = 'Incorrect current password.';
                $message_type = 'error';
            } else {
                $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update_password = $conn->prepare("UPDATE users SET password = :new_password WHERE id = :id");
                $stmt_update_password->execute([':new_password' => $new_password_hashed, ':id' => $user['id']]);

                if ($stmt_update_password->rowCount() > 0) {
                    $_SESSION['user']['password'] = $new_password_hashed;
                    $message = 'Password changed successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Password update failed. No changes were applied.';
                    $message_type = 'info';
                }
            }
        } catch (PDOException $e) {
            $message = 'Database error changing password: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Map of security question types
$questionMap = [
    'maiden_name' => "What is your mother's maiden name?",
    'first_pet' => "What was the name of your first pet?",
    'city_born' => "In which city were you born?",
    'high_school' => "What is the name of your high school?",
    'dream_job' => "What was your dream job as a child?",
    'favorite_food' => "What is your favorite food?",
    'favorite_movie' => "What is your favorite movie?",
    'favorite_anime' => "What is your favorite anime?",
    'favorite_sport' => "What is your favorite sport?",
    'favorite_hobby' => "What is your favorite hobby?",
];

// Determine profile picture path for display
$profile_pic_path = 'uploads/profiles/' . ($user['profile_picture'] ?: 'iconUser.png');
if (!file_exists($profile_pic_path)) {
    $profile_pic_path = 'images/iconUser.png'; // Fallback to images folder
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="products.css"/> 
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
    <style>
        .profile-page {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .profile-header { text-align: center; margin-bottom: 30px; width: 100%; }
        .profile-header h1 { color: #333; margin-bottom: 5px; }
        .profile-header p { color: #777; font-size: 1.1em; }
        .profile-picture-container {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #eee;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .profile-picture-container img { width: 100%; height: 100%; object-fit: cover; }
        .profile-picture-upload-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex; justify-content: center; align-items: center;
            opacity: 0; transition: opacity 0.3s ease; cursor: pointer;
        }
        .profile-picture-container:hover .profile-picture-upload-overlay { opacity: 1; }
        .profile-picture-upload-overlay i { color: #fff; font-size: 2em; }
        .profile-form-group { margin-bottom: 15px; width: 100%; }
        .profile-form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .profile-form-group input, .profile-form-group select {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; box-sizing: border-box;
        }
        .profile-buttons { display: flex; gap: 10px; margin-top: 20px; }
        .btn-primary { background-color: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-secondary { background-color: #f1f1f1; color: #555; padding: 10px 20px; border: 1px solid #ddd; border-radius: 5px; cursor: pointer; }
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.7); display: flex; justify-content: center; align-items: center; z-index: 1001;
        }
        .modal-content { background-color: #fff; padding: 20px; border-radius: 8px; max-width: 600px; width: 90%; text-align: center; }
        .image-container { max-height: 400px; overflow: hidden; margin-bottom: 20px; }
        .responseMessage {
            position: fixed; bottom: 20px; right: 20px; width: 300px; background-color: #fff;
            border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); opacity: 0; transition: opacity 0.5s; z-index: 1000;
        }
        .responseMessage p { padding: 15px 20px; margin: 0; font-weight: bold; }
        .responseMessage_success { color: #28a745; border-left: 5px solid #28a745; }
        .responseMessage_error { color: #dc3545; border-left: 5px solid #dc3545; }
        .responseMessage_info { color: #007bff; border-left: 5px solid #007bff; }
    </style>
</head>
<body>
    <header>
        <a href="index.php" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
            <h3>&nbsp;&nbsp;Keepkit</h3>
        </a>
        <div class="right-element">
            <a href="database/logout.php" ><img src= "images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>

        <main class="main">
            <section id="userProfilePage" class="active">
                <div class="profile-page">
                    <div class="profile-header">
                        <h1>Your Profile</h1>
                        <p>Manage your account information and profile picture.</p>
                    </div>

                    <form id="profilePictureForm" action="user_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="profile-picture-container">
                            <img id="profileImageDisplay" src="<?= $profile_pic_path ?>" alt="Profile Picture">
                            <label for="profilePictureUpload" class="profile-picture-upload-overlay">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profilePictureUpload" name="profile_picture" accept="image/*" style="display: none;">
                        </div>
                    </form>

                    <form action="user_profile.php" method="POST" style="width: 100%;">
                        <div class="profile-form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="profile-form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="profile-form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required readonly>
                        </div>
                        <div class="profile-buttons">
                            <button type="submit" name="update_profile_info" class="btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>

                    <hr style="width: 100%; margin: 30px 0; border-top: 1px solid #eee;">

                    <div class="profile-header" style="text-align: left;">
                        <h2>Security: Two-Factor Authentication (2FA)</h2>
                        <p>Status: <strong><?= ($user['is_2fa_enabled'] ?? 0) == 1 ? 'Enabled' : 'Disabled' ?></strong></p>
                        <form action="user_profile.php" method="POST">
                            <div class="profile-buttons">
                                <button type="submit" name="toggle_2fa" class="btn-primary" style="background-color: <?= ($user['is_2fa_enabled'] ?? 0) == 1 ? '#e74c3c' : '#2ecc71' ?>;">
                                    <i class="fas fa-shield-alt"></i> <?= ($user['is_2fa_enabled'] ?? 0) == 1 ? 'Disable 2FA' : 'Enable 2FA' ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <hr style="width: 100%; margin: 30px 0; border-top: 1px solid #eee;">

                    <div class="profile-header">
                        <h2>Change Password</h2>
                    </div>
                    <form action="user_profile.php" method="POST" style="width: 100%;">
                        <div class="profile-form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="profile-form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="profile-form-group">
                            <label for="confirm_new_password">Confirm New Password</label>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                        </div>
                        <div class="profile-buttons">
                            <button type="submit" name="change_password" class="btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <div id="cropModal" class="modal-overlay" style="display: none;">
                <div class="modal-content">
                    <h3>Crop Image</h3>
                    <div class="image-container">
                        <img id="profileImageToCrop" src="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="cropButton" class="btn-primary">Apply Crop</button>
                        <button type="button" id="cancelCropButton" class="btn-secondary">Cancel</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php if (!empty($message)): ?>
        <div class="responseMessage">
            <p class="responseMessage_<?= $message_type ?>"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const profilePictureUpload = document.getElementById('profilePictureUpload');
            const profileImageDisplay = document.getElementById('profileImageDisplay');
            const profileImageToCrop = document.getElementById('profileImageToCrop');
            const cropModal = document.getElementById('cropModal');
            const cropButton = document.getElementById('cropButton');
            const cancelCropButton = document.getElementById('cancelCropButton');
            let cropper;

            if (profilePictureUpload) {
                profilePictureUpload.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            profileImageToCrop.src = e.target.result;
                            if (cropper) cropper.destroy();
                            cropModal.style.display = 'flex';
                            cropper = new Cropper(profileImageToCrop, { aspectRatio: 1, viewMode: 1 });
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            if (cropButton) {
                cropButton.addEventListener('click', function() {
                    cropper.getCroppedCanvas({ width: 150, height: 150 }).toBlob((blob) => {
                        const formData = new FormData();
                        formData.append('profile_picture', blob, 'profile_pic.png');
                        formData.append('update_profile_picture', '1');

                        fetch('user_profile.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                profileImageDisplay.src = data.new_image_url + '?' + new Date().getTime();
                                location.reload(); // Refresh to update all instances
                            } else {
                                alert(data.message);
                            }
                        });
                    });
                });
            }

            if (cancelCropButton) {
                cancelCropButton.addEventListener('click', () => {
                    cropModal.style.display = 'none';
                    if (cropper) cropper.destroy();
                });
            }

            // Message handling
            const msg = document.querySelector('.responseMessage');
            if (msg && msg.innerText.trim() !== "") {
                msg.style.opacity = '1';
                setTimeout(() => msg.style.opacity = '0', 3000);
            }
        });
    </script>
</body>
</html>