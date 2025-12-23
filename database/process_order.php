<?php
session_start();
require_once 'connect.php'; // Assuming this connects to your database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $products_ordered = $_POST['products'] ?? [];
    $grand_total_amount = filter_input(INPUT_POST, 'grand_total_amount', FILTER_VALIDATE_FLOAT);

    if (empty($customer_name)) {
        $_SESSION['response'] = ['success' => false, 'message' => 'Customer name is required.'];
        header('Location: ../selling_product.php');
        exit;
    }

    if (empty($products_ordered)) {
        $_SESSION['response'] = ['success' => false, 'message' => 'No products selected for the order.'];
        header('Location: ../selling_product.php');
        exit;
    }

    try {
        $conn->beginTransaction();

        // Define the payment method as 'Cash on Delivery'
        $payment_method = 'Cash on Delivery'; 

        // 1. Insert into orders table, including the payment method
        $stmt_insert_order = $conn->prepare("
            INSERT INTO orders (customer_name, order_date, total_amount, payment_method)
            VALUES (:customer_name, NOW(), :total_amount, :payment_method)
        ");
        $stmt_insert_order->bindParam(':customer_name', $customer_name, PDO::PARAM_STR);
        $stmt_insert_order->bindParam(':total_amount', $grand_total_amount);
        $stmt_insert_order->bindParam(':payment_method', $payment_method, PDO::PARAM_STR); // Bind the new parameter
        $stmt_insert_order->execute();

        // Get the last inserted order_id
        $order_id = $conn->lastInsertId();

        // Check if order_id was successfully obtained
        if (!$order_id) {
            throw new Exception("Failed to get order ID after inserting into orders table.");
        }

        // 2. Loop through products and insert into order_items table and deduct stock
        foreach ($products_ordered as $product) {
            $product_id = filter_var($product['product_id'], FILTER_VALIDATE_INT);
            $quantity = filter_var($product['quantity'], FILTER_VALIDATE_INT);
            $price_at_sale = filter_var($product['price'], FILTER_VALIDATE_FLOAT); // Use the price from the form

            if (!$product_id || $quantity <= 0 || !$price_at_sale) {
                throw new Exception("Invalid product data in order. Product ID: " . ($product_id ?: 'N/A') . ", Quantity: " . ($quantity ?: 'N/A') . ", Price: " . ($price_at_sale ?: 'N/A'));
            }

            // Check current stock before deduction
            $stmt_check_stock = $conn->prepare("SELECT stock FROM products WHERE id = :product_id FOR UPDATE"); // FOR UPDATE for pessimistic locking
            $stmt_check_stock->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt_check_stock->execute();
            $current_stock = $stmt_check_stock->fetchColumn();

            if ($current_stock === false || $current_stock < $quantity) {
                throw new Exception("Insufficient stock for product ID " . $product_id . ". Available: " . ($current_stock ?: 0) . ", Ordered: " . $quantity);
            }

            // Deduct stock from products table
            $stmt_deduct_stock = $conn->prepare("
                UPDATE products
                SET stock = stock - :quantity
                WHERE id = :product_id
            ");
            $stmt_deduct_stock->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt_deduct_stock->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt_deduct_stock->execute();

            // Insert into order_items table
            $stmt_insert_item = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price_at_sale)
                VALUES (:order_id, :product_id, :quantity, :price_at_sale)
            ");
            $stmt_insert_item->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt_insert_item->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt_insert_item->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt_insert_item->bindParam(':price_at_sale', $price_at_sale);
            $stmt_insert_item->execute();
        }

        $conn->commit();
        $_SESSION['response'] = ['success' => true, 'message' => 'Order placed successfully and stock updated!'];

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['response'] = ['success' => false, 'message' => 'Order failed: ' . $e->getMessage()];
        error_log("Order processing error: " . $e->getMessage());
    }

    header('Location: ../selling_product.php');
    exit;
} else {
    // If not a POST request, redirect back
    header('Location: ../selling_product.php');
    exit;
}
?>