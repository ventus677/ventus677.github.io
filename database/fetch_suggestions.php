<?php
// database/fetch_suggestions.php
session_start();
include('connect.php'); // Siguraduhin na tama ang path ng connect.php mo

header('Content-Type: application/json');

$suggestions = [];
$search_term = $_GET['term'] ?? '';

if (!empty($search_term)) {
    try {
        $stmt = $conn->prepare("SELECT id, product_name, img FROM products WHERE product_name LIKE ? AND stock > 0 LIMIT 10");
        $stmt->execute(['%' . $search_term . '%']);
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // I-log ang error pero huwag ilantad ang sensitibong impormasyon
        error_log("Error fetching search suggestions: " . $e->getMessage());
        // Maaari kang magbalik ng empty array o error message sa client
        echo json_encode(['error' => 'Could not fetch suggestions.']);
        exit;
    }
}

echo json_encode($suggestions);
?>