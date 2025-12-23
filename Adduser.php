<?php
session_start();

include('database/connect.php'); 

// Input validation (crucial for security!)
$firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
$lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW); // Use UNSAFE_RAW for password before hashing

// --- BAGONG ADDITION: Handle the role input ---
$role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
// Default to 'user' if no role is provided or if it's invalid/empty
if (empty($role) || !in_array(strtolower($role), ['admin', 'user'])) { // Add more roles if needed
    $role = 'user';
} else {
    $role = strtolower($role); // Ensure role is lowercase for consistency
}
// --- END BAGONG ADDITION ---


if (!$firstName || !$lastName || !$email || !$password) {
    $response = ['success' => false, 'message' => 'Please fill in all fields.'];
} else {
    //Check if email already exists
    $checkEmailQuery = "SELECT 1 FROM users WHERE email = ?";
    $checkEmailStmt = $conn->prepare($checkEmailQuery);
    $checkEmailStmt->execute([$email]);
    if ($checkEmailStmt->rowCount() > 0) {
        $response = ['success' => false, 'message' => 'Email already exists.'];
    } else {
        try {
            // --- BAGONG ADDITION: Hash the password ---
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // --- END BAGONG ADDITION ---

            // --- Updated SQL query to include 'role' column ---
            $sql = "INSERT INTO users (first_name, last_name, email, password, role, created_at, updated_at) 
                    VALUES (:first_name, :last_name, :email, :password, :role, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':email', $email);
            // --- Updated bindParam for hashed password and new for role ---
            $stmt->bindParam(':password', $hashedPassword); // Bind hashed password
            $stmt->bindParam(':role', $role);               // Bind the role
            // --- END Updated bindParam ---
            
            $stmt->execute();
            $response = ['success' => true, 'message' => "$firstName $lastName added successfully with role: " . $role];
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $response = ['success' => false, 'message' => "Database Error: " . $e->getMessage()];
        }
    }
}

$_SESSION['response'] = $response;
header('Location: users.php'); // Redirect to users.php after processing
exit();
?>