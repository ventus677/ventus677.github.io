<?php
// export_fifo_cost_report.php

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

    // --- SQL Query for FIFO Cost Lot Tracking ---
    $query = "
        SELECT 
            p.product_name,
            op.batch AS batch_ref_no,
            op.quantity_received AS current_stock,
            p.cost AS unit_cost,
            op.manufactured_at,
            op.expiration,
            op.created_at AS date_received 
        FROM 
            order_product op
        JOIN 
            products p ON p.id = op.product
        WHERE 
            op.quantity_received > 0 OR op.quantity_received > 0
        ORDER BY 
            op.created_at ASC, op.manufactured_at ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- CSV/Excel Output ---
    $filename = "FIFO_Cost_Report_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // 1. Write Header Row (Column Names)
    $header = array(
        'Product Name', 'Batch/Lot No.', 'Date Received', 'Manufactured Date', 
        'Expiration Date', 'Unit Cost (PHP)', 'Stock Qty (Remaining)', 
        'Total Lot Value (PHP)'
    );
    fputcsv($output, $header);

    // 2. Write Data Rows
    $grand_total_stock = 0;
    $grand_total_value = 0;
    
    foreach ($results as $row) {
        $current_stock = (int)($row['current_stock'] ?? 0);
        $unit_cost = (float)($row['unit_cost'] ?? 0);
        $total_lot_value = $current_stock * $unit_cost;
        
        $grand_total_stock += $current_stock;
        $grand_total_value += $total_lot_value;

        $csv_row = [
            $row['product_name'],
            $row['batch_ref_no'],
            $row['date_received'],
            $row['manufactured_at'] ?? 'N/A',
            $row['expiration'] ?? 'N/A',
            number_format($unit_cost, 2, '.', ''),
            number_format($current_stock, 0, '.', ''),
            number_format($total_lot_value, 2, '.', '')
        ];
        fputcsv($output, $csv_row);
    }
    
    // 3. Write Summary Row (Totals)
    if (!empty($results)) {
        fputcsv($output, ['']); // Empty row for separation
        fputcsv($output, [
            'GRAND TOTALS (Total Remaining Stock):', 
            '', '', '', '', '',
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