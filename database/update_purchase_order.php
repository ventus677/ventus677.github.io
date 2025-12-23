<?php
session_start();
include('connect.php'); // Make sure this path is correct relative to this file

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

// Get the raw POST data
$input = file_get_contents('php://input');
$updatedData = json_decode($input, true); // Decode the JSON into a PHP associative array

if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON input: ' . json_last_error_msg();
    echo json_encode($response);
    exit;
}

if (empty($updatedData)) {
    $response['message'] = 'No data provided for update.';
    echo json_encode($response);
    exit;
}

try {
    $conn->beginTransaction(); // Start a transaction for atomicity

    $success_count = 0;
    foreach ($updatedData as $item) {
        $id = $item['id'] ?? null;
        $qtyReceived_input_from_modal = $item['qtyReceived'] ?? null; // The quantity received from user input in modal
        $manufacturedAt = !empty($item['manufacturedAt']) ? $item['manufacturedAt'] : null;
        $expiration = !empty($item['expiration']) ? $item['expiration'] : null;

        if (!$id) {
            error_log("Skipping item due to missing ID in update_purchase_order.php.");
            continue;
        }

        // Fetch current quantity_ordered, current quantity_received, current status, and product_id from the database
        // Also fetch product_id to link to the products table
        $stmt_fetch_current_data = $conn->prepare("SELECT quantity_ordered, quantity_received, status, product FROM order_product WHERE id = :id");
        $stmt_fetch_current_data->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_fetch_current_data->execute();
        $current_order_data = $stmt_fetch_current_data->fetch(PDO::FETCH_ASSOC);

        if (!$current_order_data) {
            error_log("No existing order found for ID: " . $id . " in update_purchase_order.php.");
            continue;
        }

        $quantity_ordered = $current_order_data['quantity_ordered'];
        $current_quantity_received_db = $current_order_data['quantity_received'];
        $current_status_db = $current_order_data['status'];
        $product_id = $current_order_data['product']; // Get the product ID

        // Ensure that qtyReceived_input_from_modal is a number
        $new_qtyReceived_entered = is_numeric($qtyReceived_input_from_modal) ? (int)$qtyReceived_input_from_modal : 0;

        $final_quantity_received = $current_quantity_received_db; // Default to current DB value
        $quantity_added_to_stock = 0; // Initialize quantity to be added to stock

        // Only update quantity_received if the status is NOT 'Complete'
        if ($current_status_db !== 'Complete') {
            // Calculate the potential new total received quantity based on user input
            $potential_final_quantity_received = min($new_qtyReceived_entered, $quantity_ordered);
            $potential_final_quantity_received = max(0, $potential_final_quantity_received);

            // Calculate how much new quantity is actually being received now
            // This is the difference between the potential new total and what was already received before this update
            $quantity_added_to_stock = $potential_final_quantity_received - $current_quantity_received_db;

            // Ensure we don't add negative stock (e.g., if user reduced input, which shouldn't happen if complete)
            if ($quantity_added_to_stock < 0) {
                $quantity_added_to_stock = 0; // Don't reduce stock
            }
            
            $final_quantity_received = $potential_final_quantity_received;

        }
        // If status is 'Complete', $final_quantity_received remains $current_quantity_received_db (no change)


        // Calculate remaining_quantity based on ordered and the final adjusted received quantity
        $remaining_quantity = $quantity_ordered - $final_quantity_received;

        // Determine the status automatically
        $calculated_status = 'ORDERED'; // Default status
        if ($final_quantity_received >= $quantity_ordered) {
            $calculated_status = 'Complete';
        }

        // If the status was already 'Complete' in the DB, it should remain 'Complete'
        // even if someone tries to reduce the received quantity in the modal.
        if ($current_status_db === 'Complete') {
            $calculated_status = 'Complete';
            $final_quantity_received = $current_quantity_received_db; // Lock quantity if already complete
            $remaining_quantity = $quantity_ordered - $final_quantity_received; // Recalculate remaining if quantity locked
            $quantity_added_to_stock = 0; // No stock change if already complete
        }


        // Prepare the UPDATE statement for order_product
        $stmt_order_product = $conn->prepare("
            UPDATE order_product
            SET 
                quantity_received = :quantity_received,
                remaining_quantity = :remaining_quantity,
                manufactured_at = :manufactured_at,
                expiration = :expiration,
                status = :status_val
            WHERE id = :id
        ");

        $stmt_order_product->bindParam(':quantity_received', $final_quantity_received, PDO::PARAM_INT);
        $stmt_order_product->bindParam(':remaining_quantity', $remaining_quantity, PDO::PARAM_INT);
        $stmt_order_product->bindParam(':manufactured_at', $manufacturedAt);
        $stmt_order_product->bindParam(':expiration', $expiration);
        $stmt_order_product->bindParam(':status_val', $calculated_status);
        $stmt_order_product->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_order_product->execute();

        // **NEW LOGIC: Update products.stock**
        // Only add to stock if there's a positive quantity to add
        if ($quantity_added_to_stock > 0) {
            $stmt_update_stock = $conn->prepare("
                UPDATE products
                SET stock = stock + :quantity_added
                WHERE id = :product_id
            ");
            $stmt_update_stock->bindParam(':quantity_added', $quantity_added_to_stock, PDO::PARAM_INT);
            $stmt_update_stock->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt_update_stock->execute();
        }

        $success_count++;
    }

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Successfully updated ' . $success_count . ' purchase order items and updated product stock.';

} catch (PDOException $e) {
    $conn->rollBack();
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("Database error in update_purchase_order.php: " . $e->getMessage());
} catch (Exception $e) {
    $conn->rollBack();
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    error_log("General error in update_purchase_order.php: " . $e->getMessage());
}

echo json_encode($response);
?>