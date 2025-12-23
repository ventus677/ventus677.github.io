<?php
// fetch_filtered_sales_data.php
// Fetches sales and profit data based on a user-defined period.

session_start();
header('Content-Type: application/json');

include('database/connect.php'); 

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
    'data' => [
        'total_sales' => 0,
        'total_profit' => 0,
        'lineChartLabels' => [],
        'lineChartData' => []
    ]
];

$period = $_GET['period'] ?? '6m'; // Default to 6 months
$current_date = new DateTime();
// **FIX 1: ADJUST END DATE** - Para isama ang 2025 data, gagawin nating next year ang end date.
$end_date_limit = $current_date->modify('+1 year')->format('Y-m-d'); 
$current_date = new DateTime(); // Reset for start date calculation

// Calculate start date based on period
switch ($period) {
    case '1m': $start_date = $current_date->modify('-1 month')->format('Y-m-d'); break;
    case '3m': $start_date = $current_date->modify('-3 months')->format('Y-m-d'); break;
    case '6m': $start_date = $current_date->modify('-6 months')->format('Y-m-d'); break; 
    case '1y': $start_date = $current_date->modify('-1 year')->format('Y-m-d'); break;
    // **TEMP OVERRIDE for 2025 data:** Hayaan nating maging malawak ang range para makita lahat ng data.
    default: $start_date = (new DateTime('2020-01-01'))->format('Y-m-d'); break; 
}

try {
    if (!($conn instanceof PDO)) {
        throw new Exception("Invalid PDO connection.");
    }
    
    // --- 1. Total Sales and Profit for the Period ---
    $stmt_period_summary = $conn->prepare("
        SELECT
            SUM(oi.quantity * oi.price_at_order) AS total_sales,
            SUM(oi.quantity * (oi.price_at_order - oi.cost_at_order)) AS total_profit
        FROM 
            order_items oi
        JOIN 
            orders_customer oc ON oi.order_id = oc.id
        WHERE 
            oc.order_date BETWEEN :start_date AND :end_date AND oc.status = 'completed'
    ");

    $stmt_period_summary->execute([
        ':start_date' => $start_date,
        ':end_date' => $end_date_limit // **FIX 2:** Use the adjusted end date
    ]);
    $summary_data = $stmt_period_summary->fetch(PDO::FETCH_ASSOC);

    $total_sales = $summary_data['total_sales'] ?? 0;
    $total_profit = $summary_data['total_profit'] ?? 0;

    // --- 2. Sales Trend Data for the Chart (Aggregated Monthly) ---
    // Make sure the WHERE clause uses BETWEEN :start_date AND :end_date
    $stmt_trend = $conn->prepare("
        SELECT
            DATE_FORMAT(oc.order_date, '%Y-%m') AS sale_month,
            SUM(oi.quantity * oi.price_at_order) AS monthly_sales
        FROM 
            order_items oi
        JOIN 
            orders_customer oc ON oi.order_id = oc.id
        WHERE 
            oc.order_date BETWEEN :start_date AND :end_date AND oc.status = 'completed'
        GROUP BY 
            sale_month
        ORDER BY 
            sale_month ASC
    ");
    $stmt_trend->execute([
        ':start_date' => $start_date,
        ':end_date' => $end_date_limit // **FIX 3:** Use the adjusted end date
    ]);
    $trend_data = $stmt_trend->fetchAll(PDO::FETCH_ASSOC);

    // Prepare chart data (fill missing months with zero for a continuous line)
    $chart_labels = [];
    $chart_data = [];
    
    $interval = new DateInterval('P1M');
    $start = new DateTime($start_date);
    $end = new DateTime($end_date_limit);
    $end->modify('+1 day'); // Ensure the end month is included
    $period_range = new DatePeriod($start, $interval, $end);

    $monthly_sales_map = [];
    foreach($trend_data as $row) {
        $monthly_sales_map[$row['sale_month']] = $row['monthly_sales'];
    }

    foreach ($period_range as $dt) {
        $month_key = $dt->format('Y-m');
        // Only include dates up to the end_date_limit for presentation
        if ($dt->format('Y-m-d') <= $end_date_limit) {
            $chart_labels[] = $dt->format('M Y');
            $chart_data[] = floatval($monthly_sales_map[$month_key] ?? 0);
        }
    }

    $response['success'] = true;
    $response['message'] = 'Filtered sales data fetched successfully.';
    $response['data'] = [
        'total_sales' => $total_sales,
        'total_profit' => $total_profit,
        'lineChartLabels' => $chart_labels,
        'lineChartData' => $chart_data
    ];

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('PDO Error in fetch_filtered_sales_data.php: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    error_log('Error in fetch_filtered_sales_data.php: ' . $e->getMessage());
}

echo json_encode($response);
?>