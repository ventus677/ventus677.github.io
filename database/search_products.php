<?php
require_once 'connect.php'; // Make sure this path is correct

header('Content-Type: application/json');

$searchTerm = $_GET['q'] ?? '';
$searchTerm = trim($searchTerm); // Remove leading/trailing whitespace

$products = [];

if (strlen($searchTerm) >= 1) { // Changed to >=1 to show results immediately, adjust as needed
    // Using LIKE with wildcards (%) for partial matching
    $stmt = $conn->prepare("SELECT id, product_name FROM products WHERE product_name LIKE :searchTerm LIMIT 10"); // Limit results
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($products);
?>