<?php
session_start();
include('../database/connect.php'); 

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'] ?? 'user';

$customer_data = null;
$address_data = null;
$response_message = '';
$response_type = ''; 

try {
    // Fetch User Data - Dinagdag ang is_2fa_enabled sa query
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, profile_picture, password, role, is_2fa_enabled FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch Current Address Data (is_current = 1)
    $stmt_addr = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND is_current = 1 LIMIT 1");
    $stmt_addr->execute([$id]);
    $address_data = $stmt_addr->fetch(PDO::FETCH_ASSOC);

    if (!$customer_data) {
        session_destroy();
        header('Location: ../login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// --- HANDLE POST REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Profile Picture Upload
    if ($action === 'upload_cropped_image') {
        $data = $_POST['cropped_image_data'];
        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);
        $file_name = 'profile_' . $id . '_' . time() . '.png';
        $upload_path = '../uploads/profiles/' . $file_name;
        if (file_put_contents($upload_path, $data)) {
            try {
                $stmt_pic = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt_pic->execute([$file_name, $id]);
                $_SESSION['user']['profile_picture'] = $file_name;
                echo json_encode(['success' => true, 'message' => 'Profile picture updated!', 'new_path' => $file_name]);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'DB Error']);
                exit;
            }
        }
        exit;
    }

    // 2. Profile Details Update
    elseif ($action === 'update_profile_details') {
        $first_name = htmlspecialchars(trim($_POST['first_name']));
        $last_name = htmlspecialchars(trim($_POST['last_name']));
        $email = htmlspecialchars(trim($_POST['email']));
        try {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check->execute([$email, $id]);
            if ($stmt_check->rowCount() > 0) {
                $response_message = "Email already taken.";
                $response_type = 'error';
            } else {
                $stmt_upd = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
                $stmt_upd->execute([$first_name, $last_name, $email, $id]);
                $_SESSION['user']['first_name'] = $first_name;
                $_SESSION['user']['last_name'] = $last_name;
                $response_message = "Profile updated successfully!";
                $response_type = 'success';
                $customer_data['first_name'] = $first_name;
                $customer_data['last_name'] = $last_name;
                $customer_data['email'] = $email;
            }
        } catch (PDOException $e) {
            $response_message = "Update failed.";
            $response_type = 'error';
        }
    }

    // 3. Address Update Logic
    elseif ($action === 'update_address') {
        $phone = htmlspecialchars(trim($_POST['phone_number']));
        $region = htmlspecialchars(trim($_POST['region']));
        $province = htmlspecialchars(trim($_POST['province']));
        $city = htmlspecialchars(trim($_POST['city']));
        $barangay = htmlspecialchars(trim($_POST['barangay']));
        $postal = htmlspecialchars(trim($_POST['postal_code']));
        $street = htmlspecialchars(trim($_POST['street_name_building_house_no']));

        try {
            $conn->beginTransaction();
            $stmt_reset = $conn->prepare("UPDATE user_addresses SET is_current = 0 WHERE user_id = ?");
            $stmt_reset->execute([$id]);

            $stmt_addr_ins = $conn->prepare("INSERT INTO user_addresses (user_id, phone_number, region, province, city, barangay, postal_code, street_name_building_house_no, is_current) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt_addr_ins->execute([$id, $phone, $region, $province, $city, $barangay, $postal, $street]);
            
            $conn->commit();
            $response_message = "Address updated successfully!";
            $response_type = 'success';
            
            $address_data = [
                'phone_number' => $phone, 'region' => $region, 'province' => $province, 
                'city' => $city, 'barangay' => $barangay, 'postal_code' => $postal, 
                'street_name_building_house_no' => $street
            ];
        } catch (PDOException $e) {
            $conn->rollBack();
            $response_message = "Address update failed.";
            $response_type = 'error';
        }
    }

    // 4. NEW: 2FA Toggle Logic
    elseif ($action === 'toggle_2fa') {
        $status = isset($_POST['2fa_status']) ? 1 : 0;
        try {
            $stmt_2fa = $conn->prepare("UPDATE users SET is_2fa_enabled = ? WHERE id = ?");
            $stmt_2fa->execute([$status, $id]);
            $customer_data['is_2fa_enabled'] = $status;
            $_SESSION['user']['is_2fa_enabled'] = $status;
            $response_message = "2FA settings updated successfully!";
            $response_type = 'success';
        } catch (PDOException $e) {
            $response_message = "Failed to update 2FA.";
            $response_type = 'error';
        }
    }
}

$page_title = "My Profile - Keepkit";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="customer_products.css">
    <link rel="stylesheet" href="customer_profile.css">
    <link rel="icon" type="image/png" href="../images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
    <style>
        /* Simple Switch Styling */
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #ffa53c; }
        input:checked + .slider:before { transform: translateX(26px); }
        .two-fa-section { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: #f9f9f9; border-radius: 8px; margin-top: 10px; border: 1px solid #ddd; }
        
        /* Dark Mode Support for 2FA Section */
        body.dark-mode .two-fa-section {
            background: #151515;
            border-color: #333;
            color: #e0e0e0;
        }
        body.dark-mode .two-fa-section p {
            color: #a0a0a0 !important;
        }
    </style>
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">
    <?php include('header_public.php'); ?>
    <?php include('customer_sidebar.php');?>

    <div class="profile-container">
        <div class="profile-header">
            <h1>My Profile</h1>
        </div>

        <div id="responseMessage" class="response-message <?php echo $response_type; ?>" style="display: <?php echo $response_message ? 'block' : 'none'; ?>; opacity: 1;">
            <?php echo $response_message; ?>
        </div>
        
        <div class="content-wrapper">
            <div class="form-inputs-area">
                <form action="customer_profile.php" method="POST" class="profile-form">
                    <input type="hidden" name="action" value="update_profile_details">
                    <div class="profile-header">
                        <h2>Account Details (Role: <?= htmlspecialchars(ucfirst($user_role)) ?>)</h2>
                    </div>

                    <div class="profile-form-group">
                        <label>First Name:</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($customer_data['first_name']) ?>" required>
                    </div>
                    <div class="profile-form-group">
                        <label>Last Name:</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($customer_data['last_name']) ?>" required>
                    </div>
                    <div class="profile-form-group">
                        <label>Email:</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($customer_data['email']) ?>" required>
                    </div>

                    <div class="profile-buttons">
                        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Details</button>
                    </div>
                </form>

                <hr class="section-separator">

                <form action="customer_profile.php" method="POST" class="profile-form">
                    <input type="hidden" name="action" value="toggle_2fa">
                    <div class="profile-header">
                        <h2>Security Settings</h2>
                    </div>
                    <div class="two-fa-section">
                        <div style="text-align: left;">
                            <strong>Two-Factor Authentication (2FA)</strong>
                            <p style="margin: 0; font-size: 0.85em; color: #666;">Require an OTP code sent to your email every login.</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="2fa_status" onchange="this.form.submit()" <?= $customer_data['is_2fa_enabled'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </form>

                <hr class="section-separator">

                <form action="customer_profile.php" method="POST" class="profile-form">
                    <input type="hidden" name="action" value="update_address">
                    <div class="profile-header">
                        <h2>Shipping Address</h2>
                    </div>

                    <div class="profile-form-group">
                        <label>Phone Number:</label>
                        <input type="tel" name="phone_number" value="<?= htmlspecialchars($address_data['phone_number'] ?? '') ?>" placeholder="09xxxxxxxxx" required>
                    </div>

                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div class="profile-form-group" style="flex: 1; min-width: 200px;">
                            <label>Region:</label>
                            <input type="text" name="region" value="<?= htmlspecialchars($address_data['region'] ?? '') ?>" required>
                        </div>
                        <div class="profile-form-group" style="flex: 1; min-width: 200px;">
                            <label>Province:</label>
                            <input type="text" name="province" value="<?= htmlspecialchars($address_data['province'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div class="profile-form-group" style="flex: 1; min-width: 200px;">
                            <label>City/Municipality:</label>
                            <input type="text" name="city" value="<?= htmlspecialchars($address_data['city'] ?? '') ?>" required>
                        </div>
                        <div class="profile-form-group" style="flex: 1; min-width: 200px;">
                            <label>Barangay:</label>
                            <input type="text" name="barangay" value="<?= htmlspecialchars($address_data['barangay'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="profile-form-group">
                        <label>Postal Code:</label>
                        <input type="text" name="postal_code" value="<?= htmlspecialchars($address_data['postal_code'] ?? '') ?>" required style="width: 200px;">
                    </div>

                    <div class="profile-form-group">
                        <label>Street Name, Building, House No.:</label>
                        <textarea name="street_name_building_house_no" rows="3" required><?= htmlspecialchars($address_data['street_name_building_house_no'] ?? '') ?></textarea>
                    </div>

                    <div class="profile-buttons">
                        <button type="submit" class="btn-primary"><i class="fas fa-map-marker-alt"></i> Update Address</button>
                    </div>
                </form>
            </div>

            <div class="profile-picture-sidebar">
                <div class="profile-picture-container">
                    <?php
                        $pic = $customer_data['profile_picture'];
                        $path = (!empty($pic) && file_exists('../uploads/profiles/' . $pic)) 
                                ? '../uploads/profiles/' . $pic 
                                : '../images/iconUser.png';
                    ?>
                    <img src="<?= $path ?>" alt="Profile Picture" class="profile-picture" id="currentProfilePic">
                </div>
                <label for="profile_picture_input" class="btn-secondary" style="cursor: pointer;">
                    <i class="fas fa-upload"></i> Select Image
                </label>
                <input type="file" id="profile_picture_input" accept="image/*" style="display: none;">
                <small>Max size: 2MB. Accepts: JPEG, PNG</small>
            </div>
        </div>

        <hr class="section-separator">
        
        <form action="customer_profile.php" method="POST" class="profile-form">
            <input type="hidden" name="action" value="change_password">
            <div class="profile-header">
                <h2>Change Password</h2>
            </div>
            <div class="profile-form-group">
                <label>Current Password:</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="profile-form-group">
                <label>New Password:</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="profile-buttons">
                <button type="submit" class="btn-primary">Change Password</button>
            </div>
        </form>
    </div>

    <div id="cropperModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Crop Profile Picture</h2>
            <div class="img-container">
                <img id="imageToCrop" src="">
            </div>
            <div class="crop-buttons">
                <button id="cropAndUploadBtn" class="btn-primary">Crop & Upload</button>
                <button id="cancelCropBtn" class="btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Existing Cropper JS Logic
        });
    </script>
</body>
</html>