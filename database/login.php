<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once('connect.php'); // Use require_once for critical files like database connection

/**
 * Defines permissions based on user role.
 * @param string $role The user's role (e.g., 'admin', 'user').
 * @return array An associative array where keys are modules and values are arrays of actions.
 */
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
                'user' => ['view'], // Regular users can view other users, but not manage them
                'purchase_order' => ['view'],
                'point_of_sale' => ['view', 'create'], // Assuming regular users can create sales
                'reports' => [] // Users might not view reports
            ];
            break;
        default:
            // Default permissions for unknown roles, or a very restricted guest role
            $permissions = ['dashboard' => ['view']];
            break;
    }
    return $permissions;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? ''; // Safely get password

    if (empty($email) || empty($password)) {
        $_SESSION['response'] = ['message' => 'Please enter both email and password.', 'success' => false];
        header('Location: ../index.php?form=signin');
        exit;
    }

    try {
        // Fetch the user data including the role and the HASHED password
        // Use a prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, LOWER(role) AS role FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password. IMPORTANT: Passwords in the database MUST be hashed using password_hash()
        if ($user_data && password_verify($password, $user_data['password'])) {
            unset($user_data['password']); // Remove password from session for security

            // Assign permissions based on the fetched role
            $user_data['permissions'] = getPermissions($user_data['role']);

            $_SESSION['user'] = $user_data;
            $_SESSION['response'] = ['message' => 'Login successful!', 'success' => true];
            header('Location: ../home.php'); // Redirect to home.php after successful login
            exit;
        } else {
            $_SESSION['response'] = ['message' => 'Invalid email or password.', 'success' => false];
            header('Location: ../index.php?form=signin'); // Redirect back to signin form
            exit;
        }

    } catch (PDOException $e) {
        // Log the error for debugging, but provide a generic message to the user
        error_log("Login database error: " . $e->getMessage());
        $_SESSION['response'] = ['message' => 'A database error occurred during login. Please try again later.', 'success' => false];
        header('Location: ../index.php?form=signin'); // Redirect back to signin form
        exit;
    }
} else {
    // If accessed directly without POST request
    $_SESSION['response'] = ['message' => 'Invalid request method.', 'success' => false];
    header('Location: ../index.php?form=signin'); // Redirect back to signin form
    exit;
}