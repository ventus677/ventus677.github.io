<?php
require_once 'connect.php';
header('Content-Type: application/json'); 

$input = json_decode(file_get_contents('php://input'), true);
$batchId = $input['batch'] ?? null;

if (!$batchId) {
    echo json_encode(['success' => false, 'message' => 'Missing batch ID']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT 
        op.*, p.product_name, s.supplier_name 
        FROM order_product op 
        JOIN products p ON op.product = p.id 
        JOIN suppliers s ON op.supplier = s.supplier_id 
        WHERE op.batch = ?");
    
    $stmt->execute([$batchId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($orders) {
        echo json_encode(['success' => true, 'orders' => $orders]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
