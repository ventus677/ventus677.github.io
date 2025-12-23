<?php
session_start(); // Dapat ito ang pinakaunang linya
include('connect.php'); // Siguraduhin na tama ang path (o baka kailangan ay '../database/connect.php'?)

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'cart_count' => 0];

// ----------------------------------------------------------------------------------
// --- START: FIXED CODE FOR UNIFIED LOGIN CHECK (MODIFIED) ---

$customer_id = null;
$session_key = null; // Ito ang magsasabi kung 'customer' o 'user' ang naka-login

// Suriin kung naka-login bilang customer (Priority 1)
if (isset($_SESSION['customer']) && isset($_SESSION['customer']['id']) && !empty($_SESSION['customer']['id'])) {
    $customer_id = $_SESSION['customer']['id'];
    $session_key = 'customer';
} 
// **MODIFIED CHECK (PRIORITY 2): Suriin kung naka-login bilang regular user (role: 'user')**
// Gagamitin ang role check na ipinadala mo, upang tumugma sa structure ng iyong 'users' table.
else if (isset($_SESSION['user']) && 
         isset($_SESSION['user']['id']) && 
         !empty($_SESSION['user']['id']) && 
         (($_SESSION['user']['role'] ?? '') === 'user')) { 
    $customer_id = $_SESSION['user']['id'];
    $session_key = 'user';
} 
// TANDAAN: Kung gusto mo ring i-allow ang 'admin' na mag-add to cart, palitan ang itaas ng:
/*
else if (isset($_SESSION['user']) && isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']) && (($_SESSION['user']['role'] ?? '') === 'user' || ($_SESSION['user']['role'] ?? '') === 'admin')) { 
    $customer_id = $_SESSION['user']['id'];
    $session_key = 'user';
}
*/

// Kung walang nakuha na ID ($customer_id ay null), mag-exit at ibalik ang JSON.
if (is_null($customer_id)) { 
    // Ito ang message na nakikita mo sa frontend, na nagsasabi sa AJAX handler na mag-redirect.
    $response['message'] = 'You need to be logged in to add items to your cart. Redirecting to login page.'; 
    echo json_encode($response);
    exit;
}

// --- END: FIXED CODE FOR UNIFIED LOGIN CHECK (MODIFIED) ---
// ----------------------------------------------------------------------------------


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 1;
    // Ang $customer_id ay nakuha na sa taas (customer o user ID)

    // I-validate ang product ID at quantity
    if (!is_numeric($product_id) || $product_id <= 0 || !is_numeric($quantity) || $quantity <= 0) {
        $response['message'] = 'Invalid product ID or quantity.';
        echo json_encode($response);
        exit;
    }

    try {
        // Kunin ang kumpletong detalye ng produkto mula sa database
        $stmt = $conn->prepare("SELECT id, product_name, price, stock, img FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product_data === false) { // Suriin kung ang produkto ay umiiral
            $response['message'] = 'Product not found.';
            echo json_encode($response);
            exit;
        }
        
        $product_stock = $product_data['stock'];
        $product_name = $product_data['product_name'];
        $product_price = $product_data['price'];
        $product_img = $product_data['img'];

        // ----------------------------------------------------------------------------------
        // --- START: CRITICAL FIX - DYNAMIC CART TABLE SELECTION ---

        $cart_table = 'customer_cart';
        $id_column = 'customer_id'; 
        
        // **FIX:** Gamitin ang tamang table at column base sa naka-login na user
        if ($session_key === 'user') {
            $cart_table = 'user_cart'; 
            $id_column = 'user_id';    // Base sa iyong user_cart SQL schema
        } else if ($session_key === 'customer') {
            $cart_table = 'customer_cart';
            $id_column = 'customer_id'; 
        }
        // Kung hindi 'user' o 'customer', hindi na dapat umabot dito dahil sa redirect check sa taas.

        // --- END: CRITICAL FIX - DYNAMIC CART TABLE SELECTION ---
        // ----------------------------------------------------------------------------------

        
        // Suriin kung ang produkto ay nasa cart na ng customer (sa database)
        // Gagamitin na ngayon ang $cart_table at $id_column
        $stmt_check_cart = $conn->prepare("SELECT quantity FROM $cart_table WHERE $id_column = ? AND product_id = ?");
        $stmt_check_cart->execute([$customer_id, $product_id]); // Gamitin ang UNIFIED $customer_id
        $existing_quantity_in_db = $stmt_check_cart->fetchColumn();
        $new_total_quantity_for_db = $quantity; // Default sa bagong quantity

        if ($existing_quantity_in_db !== false) { // Kung ang produkto ay umiiral sa DB cart
            $new_total_quantity_for_db = $existing_quantity_in_db + $quantity;
        }

        // Suriin kung lalagpas sa stock ang bagong quantity
        if ($new_total_quantity_for_db > $product_stock) {
            $response['message'] = 'Adding this quantity would exceed available stock. Max available: ' . $product_stock;
            echo json_encode($response);
            exit;
        }

        if ($existing_quantity_in_db !== false) {
            // Kung ang produkto ay umiiral sa DB cart, i-update ang quantity
            $stmt = $conn->prepare("UPDATE $cart_table SET quantity = ? WHERE $id_column = ? AND product_id = ?");
            $stmt->execute([$new_total_quantity_for_db, $customer_id, $product_id]);
            $response['message'] = 'Quantity updated in cart.';
        } else {
            // Kung hindi umiiral, i-insert sa cart
            $stmt = $conn->prepare("INSERT INTO $cart_table ($id_column, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$customer_id, $product_id, $quantity]);
            $response['message'] = 'Product added to cart successfully.';
        }

        // Tiyakin na ang $_SESSION['cart'] ay isang array
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $_SESSION['cart'][$product_id] = [
            'id' => $product_id,
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => $new_total_quantity_for_db, // Gamitin ang na-update na total quantity
            'img' => $product_img,
            'stock' => $product_stock
        ];

        // I-recalculate ang total cart count mula sa database para sa katumpakan
        $stmt_total_count = $conn->prepare("SELECT SUM(quantity) as total_quantity FROM $cart_table WHERE $id_column = ?");
        $stmt_total_count->execute([$customer_id]);
        $total_cart_quantity = $stmt_total_count->fetchColumn();
        $_SESSION['total_items_in_cart'] = $total_cart_quantity ?? 0; // I-update ang session cart count para sa header badge

        $response['success'] = true;
        $response['cart_count'] = $_SESSION['total_items_in_cart']; // Ibalik ang na-updated na count

    } catch (PDOException $e) {
        error_log("Error in add_to_cart.php: " . $e->getMessage());
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        error_log("General Error in add_to_cart.php: " . $e->getMessage());
        $response['message'] = 'System error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
// ----------------------------------------------------------------------------------
// --- END OF FILE ---
// ----------------------------------------------------------------------------------
?>