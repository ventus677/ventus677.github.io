<?php
include('connect.php');

if (isset($_GET['id'])) {
    $productId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$productId]);

    if ($stmt->rowCount() > 0) {
        header("Location: ../view_productOverview.php");
        exit();
    } else {
        die("Product not found.");
    }
} else {
    die("Product ID not specified.");
}
?>