<?php
// database/search_selling_products.php
require_once 'connect.php'; // Assuming this connects to your database

header('Content-Type: application/json');

$searchTerm = $_GET['q'] ?? '';

if (empty($searchTerm)) {
    echo json_encode([]);
    exit;
}

try {
    // Search for products by product_name
    $stmt = $conn->prepare("SELECT id as product_id, product_name, price FROM products WHERE product_name LIKE :searchTerm LIMIT 10");
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    error_log("Error in search_selling_products.php: " . $e->getMessage());
}
?>