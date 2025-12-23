<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include('database/connect.php'); // Make sure this path is correct

$error_message = '';
$success_message = '';
$step = 1; // 1: Enter Email, 2: Answer Security Question, 3: Set New Password
$user_id_for_reset = null;

// Define the question map (used for initial selection/display in profile, not strictly needed for this modified forgot password logic)
$question_map = [
    'favorite_food' => 'What is your favorite food?',
    'favorite_movie' => 'What is your favorite movie?',
    'favorite_anime' => 'What is your favorite anime?',
    'favorite_sport' => 'What is your favorite sport?',
    'favorite_hobby' => 'What is your favorite hobby?',
    'maiden_name' => "What is your mother's maiden name?",
    'first_pet' => "What was the name of your first pet?",
    'city_born' => "In which city were you born?",
    'high_school' => "What is the name of your high school?",
    'dream_job' => "What was your dream job as a child?",
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'verify_email') {
        $email = $_POST['Email'] ?? '';

        if (empty($email)) {
            $error_message = 'Please enter your email address.';
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, security_question_type FROM users WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user_data) {
                    if (!empty($user_data['security_question_type'])) {
                        // Email found and security question set, proceed to step 2
                        $_SESSION['reset_email'] = $email;
                        $_SESSION['reset_user_id'] = $user_data['id'];
                        // We store the type, but user will *select* it from dropdown on next step
                        $_SESSION['security_question_type_stored'] = $user_data['security_question_type']; 
                        $step = 2; // Move to security question step
                    } else {
                        $error_message = 'No security question set for this account. Please contact support.';
                    }
                } else {
                    $error_message = 'Email address not found.';
                }
            } catch (PDOException $e) {
                error_log("Forgot password email verification error: " . $e->getMessage());
                $error_message = 'A database error occurred. Please try again later.';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'verify_answer') {
        $email = $_SESSION['reset_email'] ?? '';
        $user_id_for_reset = $_SESSION['reset_user_id'] ?? null;
        $security_question_type_selected = $_POST['security_question_type'] ?? ''; // User's selected question
        $user_answer = trim($_POST['security_answer'] ?? ''); // Trim whitespace

        if (empty($email) || is_null($user_id_for_reset) || empty($security_question_type_selected) || empty($user_answer)) {
            $error_message = 'Invalid request or session expired. Please start over.';
            $step = 1; // Go back to step 1
            session_destroy(); // Clear session if something is missing
        } else {
            try {
                // Fetch the stored security question type and answer HASH from the database for the user
                $stmt = $conn->prepare("SELECT security_question_type, security_answer FROM users WHERE id = :id AND email = :email LIMIT 1");
                $stmt->execute([
                    ':id' => $user_id_for_reset,
                    ':email' => $email
                ]);
                $user_data_db = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user_data_db) {
                    // Compare selected question type AND answer with what's in the database
                    if (
                        $user_data_db['security_question_type'] === $security_question_type_selected &&
                        password_verify(strtolower($user_answer), $user_data_db['security_answer'])
                    ) {
                        // Question type and answer are correct, proceed to step 3 (set new password)
                        $step = 3;
                    } else {
                        $error_message = 'Incorrect question selected or incorrect answer. Please try again.';
                        $step = 2; // Stay on security question step
                    }
                } else {
                    $error_message = 'User data not found for reset. Please start over.';
                    $step = 1; // Go back to step 1
                    session_destroy();
                }
            } catch (PDOException $e) {
                error_log("Forgot password answer verification error: " . $e->getMessage());
                $error_message = 'A database error occurred. Please try again later.';
                $step = 1; // Go back to step 1
                session_destroy();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $user_id_for_reset = $_SESSION['reset_user_id'] ?? null;
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (is_null($user_id_for_reset) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Invalid request or session expired. Please start over.';
            $step = 1;
            session_destroy();
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New password and confirm password do not match.';
            $step = 3; // Stay on reset password step
        } elseif (strlen($new_password) < 8) { // Recommended: minimum password length of 8 or more characters
            $error_message = 'Password must be at least 8 characters long.';
            $step = 3;
        } else {
            try {
                // HASH the new password before storing it
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the user's password with the hashed version
                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute([':password' => $hashed_password, ':id' => $user_id_for_reset]);

                $success_message = 'Your password has been successfully reset. You can now log in.';
                $step = 4; // Indicate completion
                session_destroy(); // Clear all session data after successful reset
            } catch (PDOException $e) {
                error_log("Forgot password reset error: " . $e->getMessage());
                $error_message = 'A database error occurred during password reset. Please try again later.';
                $step = 3;
            }
        }
    }
} else {
    // If it's a GET request and a reset process was in progress, continue to step 2 if security question was set
    if (isset($_SESSION['reset_email']) && isset($_SESSION['reset_user_id']) && isset($_SESSION['security_question_type_stored'])) {
        $step = 2;
        // When step 2 is displayed via GET, we still need the stored question type to potentially pre-select it
        // However, the user wants choices, so we won't pre-select but just make sure $user is available if needed for select's selected property
        // For the purpose of the select tag's 'selected' attribute, we need a $user array.
        // This is a bit of a hack as $user is not fully populated here, but it fulfills the HTML logic.
        $user = ['security_question_type' => $_SESSION['security_question_type_stored']];
    } else {
        $step = 1; // Default to step 1
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Keepkit</title>
    <link rel="stylesheet" href="auth.css"/>
    <link rel="stylesheet" href="home.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <style>
        /* Styles specific to forgot_password.php, adapting from auth.css */
        .auth-container {
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
            margin: 50px auto;
            text-align: center;
        }

        .auth-container h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .auth-container p {
            color: #777;
            margin-bottom: 25px;
        }

        .auth-container form input[type="email"],
        .auth-container form input[type="text"],
        .auth-container form input[type="password"],
        .auth-container form select { /* Added style for select element */
            width: calc(100% - 20px);
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }

        .auth-container form button {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .auth-container form button:hover {
            background-color: #2980b9;
        }

        div#errorMessage, div#successMessage {
            background: #fff;
            text-align: center;
            font-size: 20px;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            display: block; /* Always block for messages on this page */
            color: red; /* Default for error */
        }
        div#successMessage {
            color: green;
        }

        .toggle-text {
            margin-top: 20px;
            color: #555;
            font-size: 0.9em;
        }

        .toggle-text a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }

        .toggle-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">
                <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
                &nbsp;&nbsp;Keepkit
            </a>
        </div>
    </nav>

    <div class="auth-container">
        <img src="images/KeepkitLogo.png" alt="KeepkitLogo" height="180px">

        <?php if (!empty($error_message)): ?>
            <div id="errorMessage">
                <p>Error: <?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div id="successMessage">
                <p>Success: <?= htmlspecialchars($success_message) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <h2>Forgot Password</h2>
            <p>Enter your email address to begin the password reset process.</p>
            <form action="forgot_password.php" method="POST">
                <input type="email" placeholder="Enter your Email" name="Email" required />
                <input type="hidden" name="action" value="verify_email">
                <button type="submit">Continue</button>
            </form>
            <div class="toggle-text">Remembered your password? <a href="auth.php?form=signin">Sign In</a></div>
        <?php elseif ($step === 2): ?>
            <h2>Security Question</h2>
            <p>Please select your security question and provide the answer.</p>
            <form action="forgot_password.php" method="POST">
                <select id="security_question_type" name="security_question_type" required>
                    <option value="">-- Select a question --</option>
                    <option value="maiden_name" <?= (isset($user['security_question_type']) && $user['security_question_type'] == 'maiden_name') ? 'selected' : '' ?>>What is your mother's maiden name?</option>
                    <option value="first_pet" <?= (isset($user['security_question_type']) && $user['security_question_type'] == 'first_pet') ? 'selected' : '' ?>>What was the name of your first pet?</option>
                    <option value="city_born" <?= (isset($user['security_question_type']) && $user['security_question_type'] == 'city_born') ? 'selected' : '' ?>>In which city were you born?</option>
                    <option value="high_school" <?= (isset($user['security_question_type']) && $user['security_question_type'] == 'high_school') ? 'selected' : '' ?>>What is the name of your high school?</option>
                    <option value="dream_job" <?= (isset($user['security_question_type']) && $user['security_question_type'] == 'dream_job') ? 'selected' : '' ?>>What was your dream job as a child?</option>
                    <option value="favorite_food" <?= (isset($user['security_question_type']) && $user['security_question_type'] == 'favorite_food') ? 'selected' : '' ?>>What is your favorite food?</option>
                    <option value="favorite_movie" <?= (isset($user['security_question_type']) && $user['security_question_type'] == 'favorite_movie') ? 'selected' : '' ?>>What is your favorite movie?</option>
                    <option value="favorite_anime" <?= (isset($user['security_question_type']) && $user['security_question_type'] == 'favorite_anime') ? 'selected' : '' ?>>What is your favorite anime?</option>
                    <option value="favorite_sport" <?= (isset($user['security_question_type']) && $user['security_question_type'] == 'favorite_sport') ? 'selected' : '' ?>>What is your favorite sport?</option>
                    <option value="favorite_hobby" <?= (isset($user['security_question_type']) && $user['security_question_type'] == 'favorite_hobby') ? 'selected' : '' ?>>What is your favorite hobby?</option>
                </select>
                <input type="password" placeholder="Your Answer" name="security_answer" required />
                <input type="hidden" name="action" value="verify_answer">
                <button type="submit">Verify Answer</button>
            </form>
            <div class="toggle-text">Need to start over? <a href="forgot_password.php">Start Over</a></div>
        <?php elseif ($step === 3): ?>
            <h2>Reset Password</h2>
            <p>Enter your new password.</p>
            <form action="forgot_password.php" method="POST">
                <input type="password" placeholder="New Password" name="new_password" required />
                <input type="password" placeholder="Confirm New Password" name="confirm_password" required />
                <input type="hidden" name="action" value="reset_password">
                <button type="submit">Reset Password</button>
            </form>
            <div class="toggle-text">Need to start over? <a href="forgot_password.php">Start Over</a></div>
        <?php elseif ($step === 4): ?>
            <h2>Password Reset Complete</h2>
            <p><?= htmlspecialchars($success_message) ?></p>
            <div class="toggle-text"><a href="login.php?form=signin">Go to Sign In</a></div>
        <?php endif; ?>
    </div>
</body>
</html>