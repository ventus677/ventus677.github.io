<?php
// finalize_account.php
session_start();
include('database/connect.php'); 

// 1. Security Check: Ensure verification was successful
if (!isset($_SESSION['account_verified']) || $_SESSION['account_verified'] !== true || !isset($_SESSION['signup_data'])) {
    $_SESSION['error_message'] = 'Verification failed. Please sign up again.';
    header('Location: signup.php');
    exit();
}

$user_data = $_SESSION['signup_data'];

// --- STEP 2: SAVE USER TO DATABASE ---
try {
    $insert = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, created_at, updated_at) 
                             VALUES (:fname, :lname, :email, :pass, 'user', NOW(), NOW())"); // Default role to 'user'

    $insert->execute([
        'fname' => $user_data['first_name'],
        'lname' => $user_data['last_name'],
        'email' => $user_data['email'],
        'pass' => $user_data['password'], // Already hashed
    ]);

    // Cleanup session variables
    unset($_SESSION['signup_data']);
    unset($_SESSION['account_verified']);
    unset($_SESSION['verification_email']);

    // Redirect to login with success message
    $_SESSION['success_message'] = "Account created successfully! Please sign in.";
    header('Location: login.php');
    exit();

} catch (PDOException $e) {
    error_log("Finalize Account DB Error: " . $e->getMessage());
    $_SESSION['error_message'] = 'A critical error occurred during account finalization. Please contact support.';
    header('Location: signup.php');
    exit();
}
// This page does not need any HTML as it only processes the data and redirects.
?>