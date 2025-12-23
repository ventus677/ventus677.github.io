<?php
include('connect.php');

if (isset($_GET['id'])) {
    $productId = $_GET['id'];

    try {
        // First, fetch product details including cost
        // Assuming your product cost is stored in a 'products' table with an 'id' and 'cost' column.
        $product_stmt = $conn->prepare("SELECT cost FROM products WHERE id = ?");
        $product_stmt->execute([$productId]);
        $product_data = $product_stmt->fetch(PDO::FETCH_ASSOC);
        $product_cost = $product_data['cost'] ?? 0; // Default to 0 if not found or cost is null

        // Then, fetch suppliers as you were doing
        $supplier_stmt = $conn->prepare("
            SELECT s.supplier_id, s.supplier_name
            FROM suppliers s
            JOIN productsuppliers ps ON s.supplier_id = ps.supplier
            WHERE ps.product = ?
        ");
        $supplier_stmt->execute([$productId]);
        $suppliers = $supplier_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Encode both product cost and suppliers into a single JSON response
        echo json_encode(['product_cost' => $product_cost, 'suppliers' => $suppliers]);

    } catch (PDOException $e) {
        http_response_code(500); // Set error status code
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400); // Set bad request status code
    echo json_encode(["error" => "Product ID not provided."]);
}
?>