<?php
session_start();
include('../database/connect.php'); 

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$user_id = null;

// ----------------------------------------------------------------------------------
// --- FIXED: UNIFIED LOGIN CHECK (for customer OR any user role) ---

// Check if logged in as user (any role)
if (isset($_SESSION['user']) && 
    isset($_SESSION['user']['id']) && 
    !empty($_SESSION['user']['id']) ) { 
    
    $user_id = $_SESSION['user']['id'];
} 
// Check if logged in as customer
if (is_null($user_id) && isset($_SESSION['customer']) && 
    isset($_SESSION['customer']['id']) && 
    !empty($_SESSION['customer']['id']) ) {
        
    $user_id = $_SESSION['customer']['id'];
}


if (is_null($user_id)) { 
    $response['message'] = 'You must be logged in to place an order. Redirecting to login page.';
    $response['redirect_url'] = 'index.php'; 
    echo json_encode($response);
    exit;
}
// ----------------------------------------------------------------------------------

// --- DATABASE CONFIGURATION ---
$cart_table = 'user_cart'; 
$id_column = 'user_id'; 
$orders_table = 'orders_user'; 
$order_details_table = 'order_products_user'; 
// ------------------------------------------

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$payment_method_from_json = $data['payment_method'] ?? null;
// Muling kukunin ang shipping address, gagamitin ang default kung wala
$shipping_address_from_json = $data['shipping_address'] ?? 'Default Shipping Address'; 
$selected_items_from_json = $data['selected_items'] ?? [];

// NEW: Capture discount and final total from frontend (for verification)
$final_total_amount_from_json = $data['total_amount'] ?? 0.00;
$discount_amount_from_json = $data['discount_amount'] ?? 0.00;
$discount_reason_from_json = $data['discount_reason'] ?? null;


if (empty($selected_items_from_json) || !is_array($selected_items_from_json) || empty($payment_method_from_json)) {
    $response['message'] = 'Missing item details or payment method.';
    echo json_encode($response);
    exit;
}


$conn->beginTransaction();

try {
    
    $subtotal_for_verification = 0; // Ito ang Gross Subtotal
    $order_products_data = [];

    // 1. Check stock and calculate original subtotal
    foreach ($selected_items_from_json as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        $stmt_stock = $conn->prepare("SELECT stock, price, product_name FROM products WHERE id = ?");
        $stmt_stock->execute([$product_id]);
        $product = $stmt_stock->fetch(PDO::FETCH_ASSOC);

        if (!$product || $product['stock'] < $quantity) {
            $conn->rollBack();
            $response['message'] = 'Insufficient stock for ' . htmlspecialchars($product['product_name'] ?? 'Product ID ' . $product_id) . '.';
            echo json_encode($response);
            exit;
        }

        // Update stock
        $new_stock = $product['stock'] - $quantity;
        $stmt_update_stock = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt_update_stock->execute([$new_stock, $product_id]);
        
        $item_price = $product['price'];
        // Compute raw subtotal for verification
        $subtotal_for_verification += ($item_price * $quantity);
        
        $order_products_data[] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price_at_order' => $item_price, // Saved as ORIGINAL (Gross) Price - as requested
            'product_name' => $product['product_name'] 
        ];
    }
    
    // 2. Server-side recalculation and discount application
    // Discount for ANY logged-in user (since $user_id is not null)
    $is_discount_eligible_server = !is_null($user_id); 
    $discount_rate = 0.20;

    $expected_discount = 0.00;
    $expected_discount_reason = null;

    if ($is_discount_eligible_server && $subtotal_for_verification > 0) {
        $expected_discount = $subtotal_for_verification * $discount_rate;
        $expected_discount_reason = 'Logged-in Customer Discount (20%)';
    }
    
    $expected_final_total = $subtotal_for_verification - $expected_discount;

    // Use server-calculated amount
    $final_total_to_use = $expected_final_total;
    $discount_amount_to_use = $expected_discount;
    $discount_reason_to_use = $expected_discount_reason;
    
    // 3. Insert Order into orders_user table (CRITICAL FIX: Changed placeholders to 7)
    // Dito ang FIX: Status ay ginawang placeholder din, kaya 7 ang variables na ipapasa.
    $stmt_insert_order = $conn->prepare("
        INSERT INTO {$orders_table} (user_id, total_amount, payment_method, shipping_address, status, discount_amount, discount_reason) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_insert_order->execute([
        $user_id, 
        $final_total_to_use, // FINAL discounted total
        $payment_method_from_json, 
        $shipping_address_from_json,
        'pending', // Ika-5 parameter (status)
        $discount_amount_to_use, // Ika-6 parameter
        $discount_reason_to_use  // Ika-7 parameter
    ]);
    $order_id = $conn->lastInsertId();

    // 4. Insert Order Details into order_products_user table
    $stmt_insert_detail = $conn->prepare("
        INSERT INTO {$order_details_table} (order_id, product_id, quantity, price_at_order, product_name) 
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt_delete_cart_item = $conn->prepare("DELETE FROM $cart_table WHERE $id_column = ? AND product_id = ?");

    foreach ($order_products_data as $item) {
        $stmt_insert_detail->execute([
            $order_id, 
            $item['product_id'], 
            $item['quantity'], 
            $item['price_at_order'], // ORIGINAL price
            $item['product_name']
        ]);

        // Remove item from user_cart
        $stmt_delete_cart_item->execute([$user_id, $item['product_id']]);
    }

    // 5. Recalculate and update session cart count 
    $stmt_total_count = $conn->prepare("SELECT SUM(quantity) as total_quantity FROM $cart_table WHERE $id_column = ?");
    $stmt_total_count->execute([$user_id]);
    $total_cart_quantity = $stmt_total_count->fetchColumn();
    $_SESSION['total_items_in_cart'] = $total_cart_quantity ?? 0;
    
    $_SESSION['order_success_id'] = $order_id; 

    $conn->commit(); 

    $response['success'] = true;
    $response['message'] = 'Order placed successfully! Your order ID is ' . $order_id;

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack(); 
    }
    // Added a clearer error message indicating the column issue
    error_log("Error processing order checkout: " . $e->getMessage());
    $response['message'] = 'Database error. Please check your SQL syntax or ensure the columns **discount_amount** and **discount_reason** exist in your orders_user table. Full error: ' . $e->getMessage();
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack(); 
    }
    error_log("General error processing order checkout: " . $e->getMessage());
    $response['message'] = 'System error: ' . $e->getMessage();
}

echo json_encode($response);
?>