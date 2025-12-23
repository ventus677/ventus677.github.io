<?php
include('connect.php');

function get_suppliers($conn) {
    try {
        $sql = "SELECT supplier_id, supplier_name FROM suppliers";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
?>
