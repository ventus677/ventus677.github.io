<?php
// Debugging setup
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'connect.php';

error_log("--- Starting process_order.php ---");
error_log("POST Data Received: " . print_r($_POST, true));

$post_data = $_POST;
$products_selected_indices = $post_data['products'] ?? [];
$quantities_by_product_index = $post_data['quantity'] ?? [];
// These are correctly retrieved as arrays indexed by product_row_index and then supplier_id
$manufactured_at_by_index = $post_data['manufactured_at'] ?? [];
$expiration_by_index = $post_data['expiration'] ?? [];

error_log("Products Selected Indices: " . print_r($products_selected_indices, true));
error_log("Quantities by Product Index: " . print_r($quantities_by_product_index, true));
error_log("Manufactured At by Product Index (from POST): " . print_r($manufactured_at_by_index, true));
error_log("Expiration by Product Index (from POST): " . print_r($expiration_by_index, true));

$success = true;
$message = 'Order successfully created!';
$timestamp = date('Y-m-d H:i:s');
$created_by = $_SESSION['user']['id'] ?? null;

if (!$created_by) {
    error_log("❗ User session expired or created_by is null. created_by: " . print_r($created_by, true));
    $_SESSION['response'] = [
        'message' => 'User session expired. Please log in again.',
        'success' => false
    ];
    header('Location: ../order_products.php');
    exit;
}

// Generate the batch ID ONCE for the entire submission
$main_batch_id = 'BATCH-' . date('Ymd-His') . '-' . random_int(100, 999);
error_log("➤ Generated MAIN BATCH ID for this transaction: $main_batch_id");

try {
    error_log("Attempting to begin transaction...");
    $conn->beginTransaction();
    error_log("Transaction begun successfully.");

    $rows_inserted_count = 0;

    foreach ($products_selected_indices as $product_row_index => $product_id) {
        error_log("Processing product at row index: $product_row_index, Product ID: $product_id");
        if (empty($product_id)) {
            error_log("Skipping product at index $product_row_index: No product selected (empty product_id).");
            continue;
        }

        $product_id = (int)$product_id;

        if (!isset($quantities_by_product_index[$product_row_index]) || !is_array($quantities_by_product_index[$product_row_index])) {
            error_log("No supplier quantities found for product ID $product_id (row index: $product_row_index). Skipping product.");
            continue;
        }

        $hasValidSupplierQuantity = false;

        foreach ($quantities_by_product_index[$product_row_index] as $supplier_id => $qty) {
            error_log("  Processing supplier $supplier_id for product $product_id with quantity: $qty");
            $qty = (int)$qty;

            if ($qty <= 0) {
                error_log("  Invalid quantity ($qty) for product $product_id from supplier $supplier_id. Skipping this supplier quantity.");
                continue;
            }

            // --- THIS IS THE KEY CHANGE FOR manufactured_at AND expiration ---
            // Retrieve dates from the nested arrays using both product_row_index and supplier_id
            $manufactured_at = $manufactured_at_by_index[$product_row_index][$supplier_id] ?? null;
            $expiration = $expiration_by_index[$product_row_index][$supplier_id] ?? null;

            // Handle cases where dates might be empty or invalid (e.g., if 'required' was removed from HTML)
            if (empty($manufactured_at)) {
                $manufactured_at = date('Y-m-d'); // Default to current date if not provided
                error_log("  Manufactured At for product $product_id from supplier $supplier_id is empty, defaulting to $manufactured_at.");
            }
            if (empty($expiration)) {
                $expiration = '9999-12-31'; // Default to far future date if not provided
                error_log("  Expiration for product $product_id from supplier $supplier_id is empty, defaulting to $expiration.");
            }
            // --- END KEY CHANGE ---

            error_log("  ➤ Product $product_id (Supplier $supplier_id) - Manufactured At: $manufactured_at | Expiration: $expiration");

            $batch = $main_batch_id; // Use the batch ID generated once at the top
            error_log("  Using batch: $batch for this line item.");

            $hasValidSupplierQuantity = true;

            $values = [
                ':product' => $product_id,
                ':supplier' => $supplier_id,
                ':quantity_ordered' => $qty,
                ':quantity_received' => 0,
                ':remaining_quantity' => $qty,
                ':status' => 'ORDERED',
                ':batch' => $batch,
                ':created_by' => $created_by,
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp
            ];

            error_log("  Values for insertion: " . print_r($values, true));

            $sql = "INSERT INTO order_product
                                (product, supplier, quantity_ordered, quantity_received, remaining_quantity, status, batch, created_by, created_at, updated_at)
                            VALUES
                                (:product, :supplier, :quantity_ordered, :quantity_received, :remaining_quantity, :status, :batch,  :created_by, :created_at, :updated_at)";
            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute($values);
                $rows_inserted_count++;
                error_log("✔ Successfully inserted order line for Product ID: $product_id, Supplier ID: $supplier_id, Quantity: $qty");
            } catch (PDOException $stmt_e) {
                error_log("❗ PDO Error during statement execution for Product ID $product_id: " . $stmt_e->getMessage());
                throw $stmt_e;
            }
        }

        if (!$hasValidSupplierQuantity) {
            error_log("❗ Warning: Product ID $product_id (row index: $product_row_index) had no valid supplier quantities submitted.");
        }
    }

    if ($rows_inserted_count > 0) {
        error_log("Attempting to commit transaction with $rows_inserted_count rows inserted...");
        $conn->commit();
        error_log("Transaction committed successfully!");
    } else {
        error_log("No rows were processed for insertion. Rolling back empty transaction.");
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $success = false;
        $message = "No valid order items were submitted or processed.";
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        error_log("❗ PDOException caught. Rolling back transaction...");
        $conn->rollBack();
        error_log("Transaction rolled back.");
    }
    $success = false;
    $message = "Database error: " . $e->getMessage() . " (SQLSTATE: " . ($e->errorInfo[0] ?? 'N/A') . ")";
    error_log("❗ Critical Database Error: " . $message);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        error_log("❗ General Exception caught. Rolling back transaction...");
        $conn->rollBack();
        error_log("Transaction rolled back.");
    }
    $success = false;
    $message = "An unexpected error occurred: " . $e->getMessage();
    error_log("❗ Unexpected Error: " . $message);
}

$_SESSION['response'] = [
    'message' => $message,
    'success' => $success
];

error_log("Final response message: " . $message);
error_log("Final response success: " . ($success ? 'true' : 'false'));
error_log("Redirecting to ../order_products.php");

header('Location: ../order_products.php');
exit;