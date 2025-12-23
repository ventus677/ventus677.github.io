<?php
session_start();
// Include the database connection file.
// Assuming this file is placed in the same directory as customers_order_history.php
include('../database/connect.php'); 

// Tiyakin na may session ang customer
if (!isset($_SESSION['customer']) || !isset($_SESSION['customer']['id'])) {
    // I-set ang error message at i-redirect sa login/history page
    $_SESSION['rr_action_message'] = 'Authentication required to cancel a request.';
    $_SESSION['rr_action_type'] = 'error';
    header('Location: customers_order_history.php'); 
    exit;
}

$customer_id = $_SESSION['customer']['id'];
$rr_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Tiyakin na may ID ang request
if (!$rr_id) {
    $_SESSION['rr_action_message'] = 'Invalid request ID provided.';
    $_SESSION['rr_action_type'] = 'error';
    header('Location: customers_order_history.php'); 
    exit;
}

try {
    // 1. I-check ang pag-aari at status ng request
    $stmt_check = $conn->prepare("
        SELECT status
        FROM returns_refunds 
        WHERE id = :rr_id AND customer_id = :customer_id
    ");
    $stmt_check->bindParam(':rr_id', $rr_id);
    $stmt_check->bindParam(':customer_id', $customer_id);
    $stmt_check->execute();
    $request_status = $stmt_check->fetchColumn();

    if ($request_status === false) {
        // Request not found or does not belong to the customer
        $_SESSION['rr_action_message'] = 'Return/Refund request not found.';
        $_SESSION['rr_action_type'] = 'error';
    } elseif ($request_status !== 'PENDING') {
        // Only PENDING requests can be cancelled
        $_SESSION['rr_action_message'] = "Cannot cancel request. Current status is **{$request_status}**.";
        $_SESSION['rr_action_type'] = 'error';
    } else {
        // 2. I-update ang status sa CANCELLED
        $stmt_update = $conn->prepare("
            UPDATE returns_refunds 
            SET status = 'CANCELLED', processed_at = NOW() 
            WHERE id = :rr_id AND customer_id = :customer_id AND status = 'PENDING'
        ");
        $stmt_update->bindParam(':rr_id', $rr_id);
        $stmt_update->bindParam(':customer_id', $customer_id);
        
        if ($stmt_update->execute()) {
            $_SESSION['rr_action_message'] = "Return/Refund request #{$rr_id} has been **CANCELLED** successfully.";
            $_SESSION['rr_action_type'] = 'success';
        } else {
            $_SESSION['rr_action_message'] = 'Failed to cancel the request. Please try again.';
            $_SESSION['rr_action_type'] = 'error';
        }
    }

} catch (PDOException $e) {
    // Handle database errors
    error_log("Cancellation Error: " . $e->getMessage());
    $_SESSION['rr_action_message'] = 'Database error: Could not process cancellation. (' . substr($e->getMessage(), 0, 50) . '...)';
    $_SESSION['rr_action_type'] = 'error';
}

// I-redirect pabalik sa order history page
header('Location: customers_order_history.php'); 
exit;
?>