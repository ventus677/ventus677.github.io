<?php
// fetch_profit_data.php

session_start();
header('Content-Type: application/json');

include('database/connect.php'); 

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
    'data' => []
];

// 1. Authentication Check
$user = (array) ($_SESSION['user'] ?? []);
if (empty($user)) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

// 2. Permissions Check
$user_permissions = (array) ($user['permissions'] ?? []);
if (!isset($user_permissions['reports']) || !in_array('view', $user_permissions['reports'])) {
    $response['message'] = 'You do not have permission to view this report.';
    echo json_encode($response);
    exit;
}

try {
    if (!($conn instanceof PDO)) {
        throw new Exception("Database connection object is not a PDO instance.");
    }

    $current_month_start = date('Y-m-01 00:00:00');
    $current_month_end = date('Y-m-t 23:59:59');

    /**
     * --- OVERALL CALCULATIONS ---
     * Base: orders_user (para sa sales at discount)
     * Base: order_items/products (para sa cost/puhunan)
     */

    // A. Kunin ang Total Sales at Total Discount mula sa orders_user
    $stmt_sales = $conn->prepare("
        SELECT 
            SUM(total_amount) as net_sales, 
            SUM(discount_amount) as total_discount 
        FROM orders_user 
        WHERE status = 'completed'
    ");
    $stmt_sales->execute();
    $sales_data = $stmt_sales->fetch(PDO::FETCH_ASSOC);

    $total_sales    = (float)($sales_data['net_sales'] ?? 0);
    $total_discount = (float)($sales_data['total_discount'] ?? 0);

    // B. Kunin ang Total Cost (Puhunan)
    // Ginagamit natin ang order_items para malaman kung anong produkto ang nabenta at ang cost nito sa products table
    $stmt_cost = $conn->prepare("
        SELECT SUM(oi.quantity * p.cost) as total_cost
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders_user ou ON oi.order_id = ou.id
        WHERE ou.status = 'completed'
    ");
    $stmt_cost->execute();
    $cost_data = $stmt_cost->fetch(PDO::FETCH_ASSOC);
    $total_cost = (float)($cost_data['total_cost'] ?? 0);

    // Kita Computation: (Net Sales - Cost)
    $total_profit = $total_sales - $total_cost;


    /**
     * --- MONTHLY CALCULATIONS ---
     */

    // A. Monthly Sales at Discount
    $stmt_m_sales = $conn->prepare("
        SELECT 
            SUM(total_amount) as monthly_net_sales,
            SUM(discount_amount) as monthly_discount
        FROM orders_user
        WHERE status = 'completed' 
        AND order_date BETWEEN :m_start AND :m_end
    ");
    $stmt_m_sales->execute([':m_start' => $current_month_start, ':m_end' => $current_month_end]);
    $m_sales_data = $stmt_m_sales->fetch(PDO::FETCH_ASSOC);

    $monthly_sales    = (float)($m_sales_data['monthly_net_sales'] ?? 0);
    $monthly_discount = (float)($m_sales_data['monthly_discount'] ?? 0);

    // B. Monthly Cost
    $stmt_m_cost = $conn->prepare("
        SELECT SUM(oi.quantity * p.cost) as monthly_cost
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders_user ou ON oi.order_id = ou.id
        WHERE ou.status = 'completed' 
        AND ou.order_date BETWEEN :m_start AND :m_end
    ");
    $stmt_m_cost->execute([':m_start' => $current_month_start, ':m_end' => $current_month_end]);
    $m_cost_data = $stmt_m_cost->fetch(PDO::FETCH_ASSOC);
    $monthly_cost = (float)($m_cost_data['monthly_cost'] ?? 0);

    $monthly_profit = $monthly_sales - $monthly_cost;

    // --- RESPONSE ---
    $response['success'] = true;
    $response['message'] = 'Profit data fetched successfully based on orders_user.';
    $response['data'] = [
        'total_sales'    => number_format($total_sales, 2, '.', ''),
        'total_profit'   => number_format($total_profit, 2, '.', ''),
        'monthly_sales'  => number_format($monthly_sales, 2, '.', ''),
        'monthly_profit' => number_format($monthly_profit, 2, '.', ''),
        'total_discount' => number_format($total_discount, 2, '.', '')
    ];

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);