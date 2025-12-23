<?php
session_start();
include('../database/connect.php'); 

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'cart_count' => 0];

// 1. TUKUYIN KUNG SINO ANG NAKA-LOGIN
// Chine-check natin pareho ang 'user' at 'customer' session keys para sigurado
$user_id = null;

if (isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id'];
} elseif (isset($_SESSION['customer']['id'])) {
    $user_id = $_SESSION['customer']['id'];
}

// Redirect kung walang session
if (is_null($user_id)) { 
    $response['message'] = 'You need to be logged in to add items to your cart.'; 
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if (!$product_id || $quantity <= 0) {
        $response['message'] = 'Invalid product or quantity.';
        echo json_encode($response);
        exit;
    }

    try {
        // 2. CHECK PRODUCT DATA & STOCK
        $stmt = $conn->prepare("SELECT id, product_name, price, stock, img FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $response['message'] = 'Product not found.';
            echo json_encode($response);
            exit;
        }

        if ($product['stock'] < $quantity) {
            $response['message'] = 'Insufficient stock. Available: ' . $product['stock'];
            echo json_encode($response);
            exit;
        }

        // 3. DATABASE OPERATIONS (user_cart table)
        // Check kung nasa cart na ang item
        $stmt_check = $conn->prepare("SELECT quantity FROM user_cart WHERE user_id = ? AND product_id = ?");
        $stmt_check->execute([$user_id, $product_id]);
        $existing_item = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing_item) {
            $new_qty = $existing_item['quantity'] + $quantity;
            
            // Re-check stock laban sa bagong total quantity
            if ($new_qty > $product['stock']) {
                $response['message'] = 'Total quantity in cart exceeds available stock.';
                echo json_encode($response);
                exit;
            }

            $stmt_update = $conn->prepare("UPDATE user_cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt_update->execute([$new_qty, $user_id, $product_id]);
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO user_cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt_insert->execute([$user_id, $product_id, $quantity]);
        }

        // 4. UPDATE SESSION TOTAL COUNT (Para sa header badge)
        $stmt_count = $conn->prepare("SELECT SUM(quantity) as total FROM user_cart WHERE user_id = ?");
        $stmt_count->execute([$user_id]);
        $total_items = $stmt_count->fetchColumn();
        
        $_SESSION['total_items_in_cart'] = $total_items ?? 0;

        $response['success'] = true;
        $response['message'] = 'Product added to cart successfully!';
        $response['cart_count'] = $_SESSION['total_items_in_cart'];

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>