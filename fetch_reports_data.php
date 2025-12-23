<?php
// fetch_reports_data.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

include('database/connect.php'); // Siguraduhing tama ang path na ito

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
    'data' => []
];

if (empty($_SESSION['user'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

$period = $_GET['period'] ?? 'monthly'; 
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

try {
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception("Database connection object is invalid or missing. Check database/connect.php");
    }

    // --- Determine Date Range and Clauses ---
    $where_date_clause = "";
    $params = [];
    $today = date('Y-m-d');
    $current_month_start = date('Y-m-01');
    $current_month_end = date('Y-m-t');
    $current_year_start = date('Y-01-01');
    $current_year_end = date('Y-12-31');
    $display_period = '';

    if ($start_date && $end_date) {
        $where_date_clause = " AND oc.order_date >= :date_start AND oc.order_date <= :date_end ";
        $params[':date_start'] = $start_date . " 00:00:00";
        $params[':date_end'] = $end_date . " 23:59:59";
        $display_period = 'custom';
    } else {
        $display_period = $period;
        switch ($period) {
            case 'daily':
                $where_date_clause = " AND DATE(oc.order_date) = :date_start ";
                $params[':date_start'] = $today;
                break;
            case '1month':
            case '3months':
            case '6months':
            case '12months':
                $days = match($period) {
                    '1month' => 30,
                    '3months' => 90,
                    '6months' => 180,
                    '12months' => 365,
                };
                $date_end = new DateTime();
                $date_start = (clone $date_end)->sub(new DateInterval("P{$days}D")); 
                $where_date_clause = " AND oc.order_date >= :date_start AND oc.order_date <= :date_end ";
                $params[':date_start'] = $date_start->format('Y-m-d H:i:s');
                $params[':date_end'] = $date_end->format('Y-m-d H:i:s');
                break;
            case 'monthly':
                $where_date_clause = " AND oc.order_date >= :month_start AND oc.order_date <= :month_end ";
                $params[':month_start'] = $current_month_start . " 00:00:00";
                $params[':month_end'] = $current_month_end . " 23:59:59";
                break;
            case 'yearly':
                $where_date_clause = " AND oc.order_date >= :year_start AND oc.order_date <= :year_end ";
                $params[':year_start'] = $current_year_start . " 00:00:00";
                $params[':year_end'] = $current_year_end . " 23:59:59";
                break;
            case 'overall':
                $where_date_clause = "";
                $params = [];
                break;
            default:
                $where_date_clause = " AND oc.order_date >= :month_start AND oc.order_date <= :month_end ";
                $params[':month_start'] = $current_month_start . " 00:00:00";
                $params[':month_end'] = $current_month_end . " 23:59:59";
                $display_period = 'monthly';
                break;
        }
    }
    
    // --- Determine Grouping for Trend Chart ---
    $group_by_format = (in_array($display_period, ['6months', '12months', 'yearly', 'overall', 'custom'])) 
                       ? '%Y-%m-01' : '%Y-%m-%d';

    // --- 1. Main Query: Summary Data (Adjusted for Discounts) ---
    // Gumagamit ng subquery para makuha ang total discount sa orders_user table
    $summary_query = "
        SELECT
            COALESCE(SUM(op.quantity * op.price_at_order), 0) AS gross_sales,
            COALESCE(SUM(op.quantity * p.cost), 0) AS total_cost,
            (SELECT COALESCE(SUM(oc2.discount_amount), 0) 
             FROM orders_user oc2 
             WHERE oc2.status = 'completed' " . str_replace('oc.', 'oc2.', $where_date_clause) . ") AS total_discount
        FROM
            orders_user oc
        JOIN
            order_products_user op ON oc.id = op.order_id
        JOIN
            products p ON op.product_id = p.id
        WHERE
            oc.status = 'completed'
            {$where_date_clause}
    ";
    
    $stmt_summary = $conn->prepare($summary_query);
    foreach ($params as $key => $value) { $stmt_summary->bindValue($key, $value); }
    $stmt_summary->execute();
    $summary_data = $stmt_summary->fetch(PDO::FETCH_ASSOC);

    $gross_sales = (float) $summary_data['gross_sales'];
    $total_discount = (float) $summary_data['total_discount'];
    $total_cost = (float) $summary_data['total_cost'];
    
    // Net Sales = Gross - Discount
    $sales = $gross_sales - $total_discount;
    $profit = $sales - $total_cost;

    // --- 2. Chart Query: Trend Data (Net Sales per Day/Month) ---
    $trend_query = "
        SELECT
            DATE_FORMAT(oc.order_date, '{$group_by_format}') AS date_label,
            -- Ibinabawas ang discount sa daily totals
            COALESCE(SUM(op.quantity * op.price_at_order), 0) - 
            (SELECT COALESCE(SUM(oc2.discount_amount), 0) 
             FROM orders_user oc2 
             WHERE DATE_FORMAT(oc2.order_date, '{$group_by_format}') = DATE_FORMAT(oc.order_date, '{$group_by_format}')
             AND oc2.status = 'completed') AS daily_sales
        FROM
            orders_user oc
        JOIN
            order_products_user op ON oc.id = op.order_id
        WHERE
            oc.status = 'completed'
            {$where_date_clause}
        GROUP BY
            date_label
        ORDER BY
            date_label ASC
    ";

    $stmt_trend = $conn->prepare($trend_query);
    foreach ($params as $key => $value) { $stmt_trend->bindValue($key, $value); }
    $stmt_trend->execute();
    $trend_data = $stmt_trend->fetchAll(PDO::FETCH_ASSOC);

    // --- Combine Results ---
    $response['success'] = true;
    $response['message'] = 'Reports data fetched successfully.';
    $response['data'] = [
        'sales' => number_format($sales, 2, '.', ''),
        'total_cost' => number_format($total_cost, 2, '.', ''),
        'profit' => number_format($profit, 2, '.', ''),
        'total_discount' => number_format($total_discount, 2, '.', ''), // Dagdag na info
        'period' => $display_period,
        'trend_data' => $trend_data, 
        'profit_for_chart' => $profit,
        'cost_for_chart' => $total_cost
    ];

} catch (PDOException $e) {
    error_log("Reports Database Error: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log("Reports Server Error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;