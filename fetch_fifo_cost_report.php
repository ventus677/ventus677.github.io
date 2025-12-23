<?php
// fetch_fifo_cost_report.php

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

/**
 * Helper function to safely format a date or return 'N/A' if empty/invalid.
 */
function formatBatchDate($date) {
    // Check for empty, NULL, or default zero date string
    if (empty($date) || $date === '0000-00-00' || $date === 'N/A') {
        return 'N/A';
    }
    // Attempt to format the date string to Y-m-d
    try {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return 'N/A'; // Handle dates that strtotime can't parse
        }
        return date('Y-m-d', $timestamp);
    } catch (\Exception $e) {
        return 'N/A';
    }
}

try {
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception("Database connection object is invalid or missing. Check database/connect.php");
    }

    // --- SQL Query for FIFO Cost Lot Tracking ---
    /*
        FIXED: Gumagamit ng quantity_received > 0 OR remaining_quantity > 0
        upang masigurong kasama ang lahat ng natanggap na batch, at ang mga may natitira pang stock.
    */
    $query = "
        SELECT 
            p.product_name,
            op.batch AS batch_ref_no,
            op.quantity_received AS current_stock, /* Ito ang gagamitin para sa Stock Qty at Value */
            p.cost AS unit_cost, /* Unit cost from the products table */
            op.manufactured_at,
            op.expiration,
            op.created_at AS date_received 
        FROM 
            order_product op
        JOIN 
            products p ON p.id = op.product
        WHERE 
            op.remaining_quantity > 0 OR op.quantity_received > 0 /* Ipakita ang lahat ng natanggap O may natitirang stock */
        ORDER BY 
            op.created_at ASC, op.manufactured_at ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Generate HTML Table Output ---
    $grand_total_stock = 0;
    $grand_total_value = 0;
?>

<div class="report-header" style="margin-bottom: 20px;">
    <h3 style="margin-top: 0;">FIFO Cost Lot Tracking Report (All Active & Received Batches)</h3>
    <p>Ipinapakita ang lahat ng batch na may naitalang natanggap na stock O may natitirang stock.</p>
</div>

<?php if (count($lots) > 0): ?>
    <div style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
        <table class="report-table" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2; text-align: left;">Product Name</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2; text-align: left;">Batch/Lot No.</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Date Received</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Manufactured Date</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Expiration Date</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Unit Cost (₱)</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Stock Qty (Received)</th>
                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">Total Lot Value (₱)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lots as $lot): 
                    // Use COALESCE (?? 0) to ensure numbers are used, even if NULL from DB
                    $current_stock = (int)($lot['current_stock'] ?? 0);
                    $unit_cost = (float)($lot['unit_cost'] ?? 0);
                    $total_lot_value = $current_stock * $unit_cost;
                    
                    // Only sum the grand totals for the REMAINING stock
                    $grand_total_stock += $current_stock;
                    $grand_total_value += $total_lot_value;
                ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: left;"><?= htmlspecialchars($lot['product_name'] ?? 'N/A Product') ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: left;"><?= htmlspecialchars($lot['batch_ref_no'] ?? 'N/A') ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center;"><?= formatBatchDate($lot['date_received']) ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center;"><?= formatBatchDate($lot['manufactured_at']) ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center;"><?= formatBatchDate($lot['expiration']) ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: right;"><?= htmlspecialchars(number_format($unit_cost, 2)) ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center; font-weight: bold;"><?= htmlspecialchars(number_format($current_stock)) ?></td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: right;"><?= htmlspecialchars(number_format($total_lot_value, 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row" style="background-color: #f8f9fa;">
                    <td colspan="6" style="text-align: right; font-weight: bold; padding: 12px; border: 1px solid #ddd;">Grand Totals (Total Remaining Stock):</td>
                    <td style="text-align: center; font-weight: bold; padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars(number_format($grand_total_stock)) ?></td>
                    <td style="text-align: right; font-weight: bold; padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars(number_format($grand_total_value, 2)) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="placeholder-text">Walang nakitang records sa Order Product table na may na-receive O may natitirang stock.</p>
<?php endif; ?>

<?php
} catch (PDOException $e) {
    echo "<p class='error-message'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Error in fetch_fifo_cost_report.php: " . $e->getMessage());
} catch (Exception $e) {
    echo "<p class='error-message'>Server error: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Error in fetch_fifo_cost_report.php: " . $e->getMessage());
}
?>