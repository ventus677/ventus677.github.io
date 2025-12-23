<?php
include('connect.php'); // Database connection

$table_name = $_SESSION['table'] ?? ''; // Get the table name from session

// Only allow safe table names
$allowed_tables = ['supplier', 'products'];
if (!in_array($table_name, $allowed_tables)) {
    return []; // return empty if table is invalid
}

try {
    $stmt = $conn->prepare("SELECT * FROM $table_name ORDER BY id ASC");
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $rows = $stmt->fetchAll();
    return $rows;
} catch (PDOException $e) {
    error_log("Database error in show_products.php: " . $e->getMessage());
    return [];
}
?>
