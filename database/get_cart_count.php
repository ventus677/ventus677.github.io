<?php
session_start(); // Dapat ito ang pinakaunang linya
include('connect.php'); // Ipagpalagay na ang connect.php ay nagtatatag ng $conn PDO object

header('Content-Type: application/json');

$cart_count = 0;

$is_customer_logged_in = isset($_SESSION['customer']) && isset($_SESSION['customer']['id']);
$is_user_logged_in = isset($_SESSION['user']) && isset($_SESSION['user']['id']);

// ORIGINAL CODE: if (isset($_SESSION['customer']) && isset($_SESSION['customer']['id'])) {
if ($is_customer_logged_in || $is_user_logged_in) { // MODIFIED CONDITION: Added || $is_user_logged_in
    // Kung naka-login ang user, kunin ang cart count mula sa database
    
    // ADDITION: Determine which session to use for the ID
    $session_key = $is_customer_logged_in ? 'customer' : 'user';
    $customer_id = $_SESSION[$session_key]['id'];// Tumpak na kinukuha ang customer ID mula sa session
    try {
        // Ang query na ito ay nagsasama-sama ng mga quantity ng lahat ng item sa cart ng customer
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_quantity FROM customer_cart WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $result['total_quantity'] ?? 0; // Gamitin ang 0 kung walang item sa cart
    } catch (PDOException $e) {
        error_log("Error fetching cart count from DB: " . $e->getMessage());
        // Fallback sa session kung magkaroon ng database error. Ito ay para sa graceful degradation.
        // Dapat ay tumpak na ang $_SESSION['cart'] dahil sa pag-sync sa customer_auth at customer_cart.php
        $cart_count = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
    }
} else {
    // Kung hindi naka-login ang user, bilangin ang mga item mula sa session cart (kung sinusuportahan ang guest cart)
    $cart_count = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
}

echo json_encode(['count' => (int)$cart_count, 'success' => true]); // Idinagdag ang 'success' key para sa consistency
exit;
