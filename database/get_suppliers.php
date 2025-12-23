<?php
include('connect.php');

try {
    $sql = "SELECT supplier_id, supplier_name FROM suppliers";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $suppliers; 
} catch (PDOException $e) {
    return []; 
}
?>
