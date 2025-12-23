<?php
// export_sales_profit.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// Include database connection
include('database/connect.php'); 

if (empty($_SESSION['user'])) {
    exit('User not logged in.');
}

// --- Get Filters ---
$period = $_GET['period'] ?? 'monthly'; 
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// --- Setup Date Range Logic (CORRECTED: Using oc.order_date based on fetch_reports_data.php) ---
$where_date_clause = "";
$params = [];
$today = date('Y-m-d');
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');
$current_year_start = date('Y-01-01');
$current_year_end = date('Y-12-31');

if ($start_date && $end_date) {
    // Custom date range logic
    $where_date_clause = " AND oc.order_date >= :date_start AND oc.order_date <= :date_end ";
    $params[':date_start'] = $start_date . " 00:00:00";
    $params[':date_end'] = $end_date . " 23:59:59";
} else {
    // Fixed period logic
    switch ($period) {
        case 'daily':
            $where_date_clause = " AND DATE(oc.order_date) = :date_start ";
            $params[':date_start'] = $today;
            break;

        case '1month':
        case '3months':
        case '6months':
        case '12months':
            $days = 0;
            switch ($period) {
                case '1month': $days = 30; break;
                case '3months': $days = 90; break;
                case '6months': $days = 180; break;
                case '12months': $days = 365; break;
            }
            // Use DateTime for accurate subtraction
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
            // Default to monthly if period is invalid
            $where_date_clause = " AND oc.order_date >= :month_start AND oc.order_date <= :month_end ";
            $params[':month_start'] = $current_month_start . " 00:00:00";
            $params[':month_end'] = $current_month_end . " 23:59:59";
            break;
    }
}

try {
    if (!isset($conn) || !($conn instanceof PDO)) {
        exit("Database connection error.");
    }

    // --- Main Query (FIXED: Using oc.order_date) ---
    $query = "
        SELECT
            oc.id AS order_id,
            oc.order_date,
            op.product_id,
            p.product_name,
            op.quantity,
            op.price_at_order AS selling_price,
            p.cost AS unit_cost,
            (op.quantity * op.price_at_order) AS total_sales,
            (op.quantity * p.cost) AS total_cost,
            ((op.quantity * op.price_at_order) - (op.quantity * p.cost)) AS gross_profit
        FROM
            orders_customer oc
        JOIN
            order_products op ON oc.id = op.order_id
        JOIN
            products p ON op.product_id = p.id
        WHERE
            oc.status = 'completed'
            {$where_date_clause}
        ORDER BY
            oc.order_date DESC
    ";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) { 
        $stmt->bindValue($key, $value); 
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- CSV/Excel Output ---
    $filename = "Sales_Profit_Report_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // 1. Write Header Row (Column Names)
    $header = array(
        'Order ID', 'Order Date', 'Product ID', 'Product Name', 'Quantity Sold', 
        'Selling Price (PHP)', 'Unit Cost (PHP)', 'Total Sales (PHP)', 
        'Total Cost (PHP)', 'Gross Profit (PHP)'
    );
    fputcsv($output, $header);

    // 2. Write Data Rows
    foreach ($results as $row) {
        $csv_row = [
            $row['order_id'],
            $row['order_date'],
            $row['product_id'],
            $row['product_name'],
            $row['quantity'],
            number_format($row['selling_price'], 2, '.', ''),
            number_format($row['unit_cost'], 2, '.', ''),
            number_format($row['total_sales'], 2, '.', ''),
            number_format($row['total_cost'], 2, '.', ''),
            number_format($row['gross_profit'], 2, '.', '')
        ];
        fputcsv($output, $csv_row);
    }
    
    // 3. Write Summary Row (Totals)
    if (!empty($results)) {
        $total_sales = array_sum(array_column($results, 'total_sales'));
        $total_cost = array_sum(array_column($results, 'total_cost'));
        $gross_profit = array_sum(array_column($results, 'gross_profit'));

        fputcsv($output, ['']); // Empty row for separation
        fputcsv($output, ['GRAND TOTALS:', '', '', '', '', '', '', number_format($total_sales, 2, '.', ''), number_format($total_cost, 2, '.', ''), number_format($gross_profit, 2, '.', '')]);
    }


    fclose($output);
    exit;

} catch (PDOException $e) {
    exit("Database error during export: " . $e->getMessage() . ". Final attempt error. Please check if 'oc.order_date' exists in your 'orders_customer' table.");
} catch (Exception $e) {
    exit("Server error during export: " . $e->getMessage());
}
?>