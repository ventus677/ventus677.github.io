<?php
session_start();
include('connect.php'); // Siguraduhin na tama ang path papunta sa connection.php

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    try {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Optional: Add a check here if the user being deleted is not the currently logged-in user
        // Example: if ($user_id == $_SESSION['user']['id']) { /* set error message */ }

        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$user_id]);

        $_SESSION['response'] = [
            'message' => 'User deleted successfully!',
            'success' => true
        ];
    } catch (PDOException $e) {
        $_SESSION['response'] = [
            'message' => 'Error deleting user: ' . $e->getMessage(),
            'success' => false
        ];
    }
} else {
    $_SESSION['response'] = [
        'message' => 'No user ID provided for deletion.',
        'success' => false
    ];
}

header('Location: ../users.php'); // Redirect pabalik sa users page
exit;
?>