<?php
session_start();
include('../database/connect.php'); 

// Check user login (Crucial step)
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) { 
    header('Location: customer_auth.php'); 
    exit;
}
$user_id = $_SESSION['user']['id']; 

// --- 1. Handle RECEIVE Order Action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_order_id'])) {
    $order_id = (int)$_POST['receive_order_id'];

    try {
        // REVISED SQL: Payagan ang pag-update sa 'Completed' kung ang order status ay active (Pending, Processing, Shipped).
        $stmt = $conn->prepare("
            UPDATE orders_user 
            SET status = 'Completed', received_date = NOW() 
            WHERE id = ? AND user_id = ? AND status IN ('Pending', 'Processing', 'Shipped')
        ");
        $stmt->execute([$order_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['status_action_message'] = "Order #{$order_id} has been successfully marked as Completed.";
            $_SESSION['status_action_type'] = 'success';
        } else {
            $_SESSION['status_action_message'] = "Error: Order #{$order_id} could not be completed. It may have already been received, cancelled, or is not eligible.";
            $_SESSION['status_action_type'] = 'error';
        }

    } catch (PDOException $e) {
        // <<< FIX FOR DEBUGGING: Nagpapakita ng totoong database error >>>
        $db_error = $e->getMessage();
        error_log("Receive Order Failed: " . $db_error);
        $_SESSION['status_action_message'] = "A system error occurred while completing the order. DB Error: " . $db_error;
        $_SESSION['status_action_type'] = 'error';
    }
    
    // Redirect pabalik sa active orders page
    header('Location: customers_active_orders.php');
    exit;
}

// --- 2. Handle CANCEL Order Action (Logic preserved, allows cancellation for active statuses) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $order_id = (int)$_POST['cancel_order_id'];

    try {
        // I-update ang status sa 'Cancelled' at i-verify na ang order ay sa user na ito
        $stmt = $conn->prepare("
            UPDATE orders_user 
            SET status = 'Cancelled' 
            WHERE id = ? AND user_id = ? AND status IN ('Pending', 'Processing', 'Shipped')
        ");
        $stmt->execute([$order_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['status_action_message'] = "Order #{$order_id} has been successfully cancelled.";
            $_SESSION['status_action_type'] = 'success';
            // Pwede ring mag-dagdag ng logic dito para ibalik ang stocks kung kinakailangan
        } else {
            $_SESSION['status_action_message'] = "Error: Order #{$order_id} could not be cancelled or is already finished.";
            $_SESSION['status_action_type'] = 'error';
        }

    } catch (PDOException $e) {
        $db_error = $e->getMessage();
        error_log("Cancel Order Failed: " . $db_error);
        $_SESSION['status_action_message'] = "A system error occurred while cancelling the order. DB Error: " . $db_error;
        $_SESSION['status_action_type'] = 'error';
    }
    
    // Redirect pabalik sa active orders page
    header('Location: customers_active_orders.php');
    exit;
}

// Default redirect kung walang POST action
header('Location: customers_active_orders.php');
exit;
?>