<?php
session_start();
include('connect.php'); 

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];
$redirect_url = '../customer_list.php';

// Check login and permissions
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $response['message'] = 'User not logged in. Please log in again.';
    $_SESSION['response'] = $response;
    header('Location: ' . $redirect_url);
    exit();
}

$user_permissions = $_SESSION['user']['permissions'] ?? [];
if (!isset($user_permissions['customer']) || !in_array('edit', $user_permissions['customer'])) {
    $response['message'] = 'You do not have permission to edit customer records.';
    $_SESSION['response'] = $response;
    header('Location: ' . $redirect_url);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['id'] ?? null;
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';

    // Validation
    if (empty($customer_id) || !is_numeric($customer_id)) {
        $response['message'] = 'Invalid ID.';
        $_SESSION['response'] = $response;
        header('Location: ' . $redirect_url);
        exit();
    }
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $response['message'] = 'First name, Last name, and Email are required.';
        $_SESSION['response'] = $response;
        header('Location: ' . $redirect_url);
        exit();
    }

    try {
        // Check duplicate email
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check->execute([$email, $customer_id]);
        
        if ($stmt_check->rowCount() > 0) {
            $response['message'] = 'Email already exists.';
        } else {
            // FIX: Pinayagan ang edit para sa kahit anong role basta tugma ang ID
            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$first_name, $last_name, $email, $customer_id]);

            if ($stmt->rowCount() > 0) {
                $response = ['success' => true, 'message' => 'Account updated successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'No changes made or record not found.'];
            }
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

$_SESSION['response'] = $response;
header('Location: ' . $redirect_url);
exit();
?>