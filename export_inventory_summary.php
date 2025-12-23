<?php
// export_inventory_summary.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// Include database connection
include('database/connect.php'); 

if (empty($_SESSION['user'])) {
    exit('User not logged in.');
}

try {
    if (!isset($conn) || !($conn instanceof PDO)) {
        exit("Database connection error.");
    }

    // --- SQL Query for Inventory Summary ---
    $query = "
        SELECT
            p.product_name,
            p.cost,
            p.stock AS current_stock,
            (p.stock * p.cost) AS inventory_value,
            COALESCE(SUM(op_in.quantity_received), 0) AS total_received,
            COALESCE(SUM(op_out.quantity), 0) AS total_sold
        FROM
            products p
        LEFT JOIN
            order_product op_in ON p.id = op_in.product
        LEFT JOIN
            order_products op_out ON p.id = op_out.product_id
        LEFT JOIN
            orders_customer oc ON op_out.order_id = oc.id AND oc.status = 'completed'
        GROUP BY
            p.id, p.product_name, p.stock, p.cost
        ORDER BY
            p.product_name ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- CSV/Excel Output ---
    $filename = "Inventory_Summary_Report_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // 1. Write Header Row (Column Names)
    $header = array(
        'Product Name', 'Unit Cost (PHP)', 'Total Received (IN)', 'Total Sold (OUT)', 
        'Current Stock (Closing)', 'Inventory Value (PHP)'
    );
    fputcsv($output, $header);

    // 2. Write Data Rows
    $grand_total_stock = 0;
    $grand_total_value = 0;
    $grand_total_received = 0;
    $grand_total_sold = 0;
    
    foreach ($results as $row) {
        $inventory_value = (float)($row['inventory_value'] ?? 0);
        
        $grand_total_stock += (int)($row['current_stock'] ?? 0);
        $grand_total_value += $inventory_value;
        $grand_total_received += (int)($row['total_received'] ?? 0);
        $grand_total_sold += (int)($row['total_sold'] ?? 0);

        $csv_row = [
            $row['product_name'],
            number_format($row['cost'], 2, '.', ''),
            number_format($row['total_received'], 0, '.', ''),
            number_format($row['total_sold'], 0, '.', ''),
            number_format($row['current_stock'], 0, '.', ''),
            number_format($inventory_value, 2, '.', '')
        ];
        fputcsv($output, $csv_row);
    }
    
    // 3. Write Summary Row (Totals)
    if (!empty($results)) {
        fputcsv($output, ['']); // Empty row for separation
        fputcsv($output, [
            'GRAND TOTALS:', 
            '', 
            number_format($grand_total_received, 0, '.', ''), 
            number_format($grand_total_sold, 0, '.', ''), 
            number_format($grand_total_stock, 0, '.', ''), 
            number_format($grand_total_value, 2, '.', '')
        ]);
    }


    fclose($output);
    exit;

} catch (PDOException $e) {
    exit("Database error during export: " . $e->getMessage());
} catch (Exception $e) {
    exit("Server error during export: " . $e->getMessage());
}
?>