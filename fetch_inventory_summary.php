<?php
// fetch_inventory_summary.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: text/html');

// Include database connection. Adjust path as needed.
include('database/connect.php'); 

if (empty($_SESSION['user'])) {
    echo "<p class='error-message'>Session expired. Please log in again.</p>";
    exit;
}

try {
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception("Database connection object is invalid or missing. Check database/connect.php");
    }

    // --- SQL Query for Inventory Summary (Stock In/Out/Closing) ---
    $query = "
        SELECT
            p.product_name,
            p.stock AS current_stock,
            p.cost,
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
    $summary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Generate HTML Table Output ---
    $grand_total_stock = 0;
    $grand_total_value = 0;
    $grand_total_received = 0;
    $grand_total_sold = 0;
?>

<div class="report-header" style="margin-bottom: 20px;">
    <h3 style="margin-top: 0;">Inventory Summary Report</h3>
    <p>Displays the total stock, total received (In), and total sold (Out) of each product.</p>
</div>

<?php if (count($summary_data) > 0): ?>
    <div style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
        <table class="report-table" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2; text-align: left;">Product Name</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Unit Cost (₱)</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Total Received (IN)</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Total Sold (OUT)</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Current Stock</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Inventory Value (₱)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($summary_data as $item): 
                    $current_stock = (int)($item['current_stock'] ?? 0);
                    $total_received = (int)($item['total_received'] ?? 0);
                    $total_sold = (int)($item['total_sold'] ?? 0);
                    $inventory_value = (float)($item['inventory_value'] ?? 0);
                    
                    $grand_total_stock += $current_stock;
                    $grand_total_value += $inventory_value;
                    $grand_total_received += $total_received;
                    $grand_total_sold += $total_sold;
                ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: left;"><?= htmlspecialchars($item['product_name'] ?? 'N/A Product') ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: right;"><?= htmlspecialchars(number_format($item['cost'] ?? 0, 2)) ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center;"><?= htmlspecialchars(number_format($total_received)) ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center;"><?= htmlspecialchars(number_format($total_sold)) ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center; font-weight: bold;"><?= htmlspecialchars(number_format($current_stock)) ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: right;"><?= htmlspecialchars(number_format($inventory_value, 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row" style="background-color: #f8f9fa;">
                    <td colspan="2" style="text-align: right; font-weight: bold; padding: 12px; border: 1px solid #ddd;">Grand Totals:</td>
                    <td style="text-align: center; font-weight: bold; padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars(number_format($grand_total_received)) ?></td>
                    <td style="text-align: center; font-weight: bold; padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars(number_format($grand_total_sold)) ?></td>
                    <td style="text-align: center; font-weight: bold; padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars(number_format($grand_total_stock)) ?></td>
                    <td style="text-align: right; font-weight: bold; padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars(number_format($grand_total_value, 2)) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="placeholder-text">Walang nakitang records sa Products table.</p>
<?php endif; ?>

<?php
} catch (PDOException $e) {
    echo "<p class='error-message'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Error in fetch_inventory_summary.php: " . $e->getMessage());
} catch (Exception $e) {
    echo "<p class='error-message'>Server error: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Error in fetch_inventory_summary.php: " . $e->getMessage());
}
?>