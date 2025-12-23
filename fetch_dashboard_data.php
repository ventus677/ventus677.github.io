<?php
// fetch_dashboard_data.php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // Start the session to access user data if needed

header('Content-Type: application/json'); // Set header to indicate JSON response

include('database/connect.php'); // Include your database connection

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
    'data' => []
];

try {
    // --- Total Products / Total Stocks ---
    $stmt_total_products = $conn->query("SELECT COUNT(id) AS total_products FROM products");
    $total_products = $stmt_total_products->fetch(PDO::FETCH_ASSOC)['total_products'];

    // --- Low Stock Products (threshold < 10) ---
    $low_stock_threshold = 10; // Define your low stock threshold
    $stmt_low_stock = $conn->prepare("SELECT COUNT(id) AS low_stock_count FROM products WHERE stock < :threshold");
    $stmt_low_stock->bindParam(':threshold', $low_stock_threshold, PDO::PARAM_INT);
    $stmt_low_stock->execute();
    $low_stock_count = $stmt_low_stock->fetch(PDO::FETCH_ASSOC)['low_stock_count'];

    // --- Total Sales This Month & Gross Profit This Month ---
    // Assuming 'orders' table has 'total_amount' and 'transaction_date'
    // And 'order_items' table has 'product_id', 'quantity', 'price_at_order'
    // And 'products' table has 'cost_price'
    $current_month_start = date('Y-m-01 00:00:00');
    $current_month_end = date('Y-m-t 23:59:59'); // last day of month

    // Total Sales This Month
    $stmt_sales_this_month = $conn->prepare("
        SELECT SUM(total_amount) AS total_sales
        FROM orders
        WHERE transaction_date >= :start_date AND transaction_date <= :end_date
    ");
    $stmt_sales_this_month->bindParam(':start_date', $current_month_start);
    $stmt_sales_this_month->bindParam(':end_date', $current_month_end);
    $stmt_sales_this_month->execute();
    $total_sales_this_month = $stmt_sales_this_month->fetch(PDO::FETCH_ASSOC)['total_sales'] ?? 0;

    // Gross Profit This Month
    // Gross Profit = Total Sales - Total Cost of Goods Sold (COGS)
    // COGS for an item = quantity_sold * cost_price_at_time_of_sale (or current cost_price if not tracked historically)
    $stmt_gross_profit_this_month = $conn->prepare("
        SELECT
            SUM(oi.quantity * (oi.price_at_order - p.cost_price)) AS gross_profit
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.transaction_date >= :start_date AND o.transaction_date <= :end_date
    ");
    $stmt_gross_profit_this_month->bindParam(':start_date', $current_month_start);
    $stmt_gross_profit_this_month->bindParam(':end_date', $current_month_end);
    $stmt_gross_profit_this_month->execute();
    $gross_profit_this_month = $stmt_gross_profit_this_month->fetch(PDO::FETCH_ASSOC)['gross_profit'] ?? 0;

    // --- Line Chart Data (Sales over last 7 days) ---
    $labels = [];
    $line_chart_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M d', strtotime($date)); // Format for display on chart

        $day_start = $date . ' 00:00:00';
        $day_end = $date . ' 23:59:59';

        $stmt_daily_sales = $conn->prepare("
            SELECT SUM(total_amount) AS daily_sales
            FROM order_customer
            WHERE transaction_date >= :day_start AND transaction_date <= :day_end
        ");
        $stmt_daily_sales->bindParam(':day_start', $day_start);
        $stmt_daily_sales->bindParam(':day_end', $day_end);
        $stmt_daily_sales->execute();
        $sales = $stmt_daily_sales->fetch(PDO::FETCH_ASSOC)['daily_sales'] ?? 0;
        $line_chart_data[] = round($sales, 2);
    }

    // --- Pie Chart Data (Category Distribution) ---
    $stmt_category_distribution = $conn->query("
        SELECT
            c.category_name,
            COUNT(p.id) AS product_count
        FROM products p
        JOIN categories c ON p.category_id = c.id
        GROUP BY c.category_name
        ORDER BY product_count DESC
    ");
    $category_distribution = $stmt_category_distribution->fetchAll(PDO::FETCH_ASSOC);

    $pie_chart_labels = [];
    $pie_chart_data = [];
    foreach ($category_distribution as $category) {
        $pie_chart_labels[] = $category['category_name'];
        $pie_chart_data[] = $category['product_count'];
    }

    $response['success'] = true;
    $response['message'] = 'Dashboard data fetched successfully.';
    $response['data'] = [
        'totalProducts' => number_format($total_products),
        'lowStock' => number_format($low_stock_count),
        'salesThisMonth' => number_format($total_sales_this_month, 2),
        'grossProfitThisMonth' => number_format($gross_profit_this_month, 2),
        'lineChartLabels' => $labels,
        'lineChartData' => $line_chart_data,
        'pieChartLabels' => $pie_chart_labels,
        'pieChartData' => $pie_chart_data
    ];

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("Dashboard Data Fetch Error: " . $e->getMessage()); // Log error
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    error_log("Dashboard Data Fetch General Error: " . $e->getMessage()); // Log error
}

echo json_encode($response);
?>  