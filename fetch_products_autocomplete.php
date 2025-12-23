<?php
// fetch_products_autocomplete.php - Handles AJAX search for products
session_start();
header('Content-Type: application/json');

// NOTE: Please ensure 'database/connect.php' exists and establishes the $conn PDO object.
include('database/connect.php'); 

$response = ['success' => false, 'products' => []];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['query'])) {
    echo json_encode($response);
    exit;
}

$query = '%' . trim($_POST['query']) . '%'; // Prepare query for LIKE search

try {
    // Search products by name or ID
    $stmt = $conn->prepare("
        SELECT id, product_name
        FROM products 
        WHERE product_name LIKE :query OR CAST(id AS CHAR) LIKE :query
        LIMIT 10
    ");
    $stmt->bindParam(':query', $query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($products) {
        $response['success'] = true;
        $response['products'] = $products;
    }

} catch (PDOException $e) {
    error_log("Product Autocomplete Error: " . $e->getMessage());
    $response['message'] = 'Database Error during product search.';
}

echo json_encode($response);
?>