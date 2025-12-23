<?php
session_start();
include('connect.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['id'] ?? null;
    $first_name = $_POST['first_name'] ?? null;
    $last_name = $_POST['last_name'] ?? null;
    $email = $_POST['email'] ?? null;
    $role = $_POST['role'] ?? null; // Kunin ang 'role' mula sa form

    // Basic validation
    if (empty($user_id) || empty($first_name) || empty($last_name) || empty($email) || empty($role)) {
        $_SESSION['response'] = [
            'message' => 'All fields are required.',
            'success' => false
        ];
        header('Location: ../users.php'); // Balik sa users page
        exit;
    }

    try {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, role=? WHERE id=?");
        $stmt->execute([$first_name, $last_name, $email, $role, $user_id]);

        $_SESSION['response'] = [
            'message' => 'User updated successfully!',
            'success' => true
        ];
    } catch (PDOException $e) {
        $_SESSION['response'] = [
            'message' => 'Error updating user: ' . $e->getMessage(),
            'success' => false
        ];
    }
} else {
    $_SESSION['response'] = [
        'message' => 'Invalid request method.',
        'success' => false
    ];
}

header('Location: ../users.php'); // Redirect pabalik sa users page
exit;
?>