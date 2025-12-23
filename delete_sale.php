<?php
session_start();
include('database/connect.php'); // Ensure database connection is established

// Redirect if user is not set or empty
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$sale_id = $_GET['id'] ?? null;
$message = '';
$message_type = '';

if ($sale_id) {
    try {
        // Determine the source table to delete from.
        // This is a complex aspect because the sales data is a UNION of multiple tables.
        // A robust solution would be to store the source table in the CombinedSales CTE
        // and pass it as a hidden field from view_sales.php to delete_sale.php.
        // For this example, we'll attempt to delete from 'orders_customer' first,
        // then 'orders', as these are the primary sales tables.
        // If an order from 'orders' table has associated 'order_products' or 'order_items',
        // those would need to be deleted first due to foreign key constraints,
        // or the foreign keys need to be set to ON DELETE CASCADE.

        $delete_success = false;

        // Start a transaction for atomicity
        $conn->beginTransaction();

        // Attempt to delete from 'orders_customer'
        // First delete associated records in order_products if they exist (assuming foreign key cascade is not set)
        $stmt = $conn->prepare("DELETE FROM order_products WHERE order_id = ? AND EXISTS (SELECT 1 FROM orders_customer WHERE id = ?)");
        $stmt->execute([$sale_id, $sale_id]);

        $stmt = $conn->prepare("DELETE FROM orders_customer WHERE id = ?");
        $stmt->execute([$sale_id]);
        if ($stmt->rowCount() > 0) {
            $delete_success = true;
            $message = 'Sale record deleted successfully from orders_customer table.';
            $message_type = 'success';
        } else {
            // If not found/deleted in orders_customer, try deleting from 'orders'
            // First delete associated records in order_products and order_items if they exist
            $stmt = $conn->prepare("DELETE FROM order_products WHERE order_id = ? AND EXISTS (SELECT 1 FROM orders WHERE id = ?)");
            $stmt->execute([$sale_id, $sale_id]);

            $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ? AND EXISTS (SELECT 1 FROM orders WHERE id = ?)");
            $stmt->execute([$sale_id, $sale_id]);

            $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$sale_id]);
            if ($stmt->rowCount() > 0) {
                $delete_success = true;
                $message = 'Sale record deleted successfully from orders table.';
                $message_type = 'success';
            }
        }

        if ($delete_success) {
            $conn->commit(); // Commit the transaction
        } else {
            $conn->rollBack(); // Rollback if no record was found/deleted
            $message = 'No sale record found or deleted for the provided ID.';
            $message_type = 'error';
        }

    } catch (PDOException $e) {
        $conn->rollBack(); // Rollback on error
        error_log("Database Error in delete_sale.php: " . $e->getMessage());
        $message = 'Error deleting sale record: ' . $e->getMessage();
        $message_type = 'error';
    }
} else {
    $message = 'No sale ID provided for deletion.';
    $message_type = 'error';
}

// Redirect back to view_sales.php with a message
header('Location: view_sales.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
exit;
?>