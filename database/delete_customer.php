<?php
session_start();
include('connect.php');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];
$redirect_url = '../customer_list.php';

// Auth Check
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $response['message'] = 'User not logged in.';
    $_SESSION['response'] = $response;
    header('Location: ' . $redirect_url);
    exit();
}

// Permission Check
$user_permissions = $_SESSION['user']['permissions'] ?? [];
if (!isset($user_permissions['customer']) || !in_array('delete', $user_permissions['customer'])) {
    $response['message'] = 'Unauthorized access.';
    $_SESSION['response'] = $response;
    header('Location: ' . $redirect_url);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $customer_id = $_GET['id'];

    try {
        // FIX: Hanapin ang user regardless of role
        $stmt_get_pic = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt_get_pic->execute([$customer_id]);
        $customer_data = $stmt_get_pic->fetch(PDO::FETCH_ASSOC);

        if ($customer_data) {
            $conn->beginTransaction();

            // FIX: Burahin ang user regardless of role
            $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt_delete->execute([$customer_id]);

            if ($stmt_delete->rowCount() > 0) {
                // Delete file kung hindi default
                $pic = $customer_data['profile_picture'];
                if ($pic && $pic !== 'iconUser.png') {
                    $file_path = '../uploads/profiles/' . $pic;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                $conn->commit();
                $response = ['success' => true, 'message' => 'Account deleted successfully!'];
            } else {
                $conn->rollBack();
                $response['message'] = 'Failed to delete record.';
            }
        } else {
            $response['message'] = 'Account record not found.';
        }

    } catch (PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

$_SESSION['response'] = $response;
header('Location: ' . $redirect_url);
exit();
?>