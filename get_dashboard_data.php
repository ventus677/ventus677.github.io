<?php
session_start();
ini_set('display_errors', 1); // For debugging – disable in production
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

include_once('database/connect.php'); // Assumes $conn is PDO
include_once('database/get_permissions.php'); // Role-based access

$response = [
    'success' => false,
    'message' => 'Something went wrong.',
    'lineChart' => ['labels' => [], 'data' => []],
    'barChart' => ['labels' => [], 'data' => []],
    'pieChart' => ['labels' => [], 'data' => []]
];

// Authentication
$user = $_SESSION['user'] ?? null;
if (!$user) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

// Permissions
$user_permissions = getPermissions($user['role'] ?? 'guest');
if (!isset($user_permissions['dashboard']) || !in_array('view', $user_permissions['dashboard'])) {
    $response['message'] = 'You do not have permission to view dashboard data.';
    echo json_encode($response);
    exit;
}

try {
    if (!($conn instanceof PDO)) {
        throw new Exception("Invalid PDO connection.");
    }

    /** -----------------------------
     * 1. Monthly Sales Line Chart
     * ----------------------------- */
    $stmt_sales_trend = $conn->prepare("
        SELECT
            DATE_FORMAT(oc.order_date, '%Y-%m') AS sales_month,
            SUM(oc.total_amount) AS monthly_sales
        FROM orders_user oc
        WHERE oc.order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY sales_month
        ORDER BY sales_month ASC
    ");
    $stmt_sales_trend->execute();
    $sales_trend = $stmt_sales_trend->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sales_trend as $row) {
        $response['lineChart']['labels'][] = date('M Y', strtotime($row['sales_month'] . '-01'));
        $response['lineChart']['data'][] = floatval($row['monthly_sales']);
    }

    /** ------------------------------------------
     * 2. Sales by Category (Bar Chart Placeholder)
     * ------------------------------------------ */
    $stmt_bar = $conn->prepare("
        SELECT
            p.category AS category_name,
            SUM(oc.total_amount) AS category_sales
        JOIN products p ON oi.product_id = p.id
        JOIN orders_user oc ON oc.id = oi.order_id
        WHERE oc.order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY p.category
        ORDER BY category_sales DESC
    ");
    $stmt_bar->execute();
    $bar_data = $stmt_bar->fetchAll(PDO::FETCH_ASSOC);

    foreach ($bar_data as $row) {
        $response['barChart']['labels'][] = $row['category_name'] ?? 'Uncategorized';
        $response['barChart']['data'][] = floatval($row['category_sales']);
    }

  /** -------------------------------------------------
     * 3. Product Category Distribution (Current Stock)
     * ------------------------------------------------- */
    $stmt_pie = $conn->prepare("
        SELECT
            category AS category_name,
            COUNT(*) AS count
        FROM products
        GROUP BY category
    ");
    $stmt_pie->execute();
    $pie_data = $stmt_pie->fetchAll(PDO::FETCH_ASSOC);

    foreach ($pie_data as $row) {
        $response['pieChart']['labels'][] = $row['category_name'] ?? 'Uncategorized';
        $response['pieChart']['data'][] = intval($row['count']);
    }

    /** ----------------------------
     * Final Success Response
     * ---------------------------- */
    $response['success'] = true;
    $response['message'] = 'Dashboard data fetched successfully.';

} catch (PDOException $e) {
    error_log("PDOException in get_dashboard_data.php: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("Exception in get_dashboard_data.php: " . $e->getMessage());
    $response['message'] = 'Server error: ' . $e->getMessage();
}


echo json_encode($response);
?>
