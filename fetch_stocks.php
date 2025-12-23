<?php
// fetch_stocks.php
// Ensure this path is correct for your database connection
include('database/connect.php'); // Adjust path if your connect.php is elsewhere

// Check if JSON output is requested for the dashboard
$isJsonRequest = isset($_GET['json']) && $_GET['json'] === 'true';

$sortColumn = $_GET['sort_column'] ?? 'stock';
$sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');
$searchTerm = $_GET['search_term'] ?? '';
$searchColumn = $_GET['search_column'] ?? 'product_name';

// --- Input Validation for Security ---
// Whitelist allowed sort columns - removed brand_name and category
$allowedSortColumns = ['product_name', 'stock', 'cost', 'total_stock_value'];
if (!in_array($sortColumn, $allowedSortColumns)) {
    $sortColumn = 'stock'; // Default if invalid
}

// Whitelist allowed sort order
if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC'; // Default if invalid
}

// Whitelist allowed search columns - removed brand_name and category
$allowedSearchColumns = ['product_name', 'stock', 'cost'];
if (!in_array($searchColumn, $allowedSearchColumns)) {
    $searchColumn = 'product_name'; // Default if invalid
}
// --- End Input Validation ---

$whereClause = '';
$params = [];

if (!empty($searchTerm)) {
    // Handle numeric columns specifically: Quantity, Cost
    if ($searchColumn === 'stock' || $searchColumn === 'cost') {
        if ($searchTerm === 'low') {
            // Special handling for 'low stock' search
            $whereClause = "WHERE stock <= :low_threshold AND stock > 0"; // Exclude exactly 0 stock for 'low', but it will be included in low_stock_count
            $params[':low_threshold'] = 10; // Assuming a low stock threshold of 10
        } else if (is_numeric($searchTerm)) {
            $whereClause = "WHERE " . $searchColumn . " = :" . $searchColumn;
            $params[":" . $searchColumn] = $searchTerm;
        }
    } else {
        // For string columns (product_name), use LIKE for wildcard search
        $whereClause = "WHERE " . $searchColumn . " LIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }
}


try {
    if (!($conn instanceof PDO)) {
        throw new Exception("Database connection object is not a PDO instance.");
    }

    // Construct the ORDER BY clause
    $orderByClause = '';
    if ($sortColumn === 'total_stock_value') {
        // Sort by calculated total stock value
        $orderByClause = "ORDER BY (stock * cost) {$sortOrder}";
    } else {
        // Sort by selected column
        $orderByClause = "ORDER BY {$sortColumn} {$sortOrder}";
    }

    // Base query for fetching product stock data - removed joins to brands and categories
    $sql = "
        SELECT
            id,
            product_name,
            stock,
            cost
        FROM
            products
        {$whereClause}
        {$orderByClause}
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grand_total_quantity = 0;
    $grand_total_stock_value = 0;
    $low_stock_count = 0;
    $low_stock_items = [];
    $low_stock_threshold = 10; // Define your low stock threshold here

    foreach ($stocks as $stock) {
        $grand_total_quantity += $stock['stock'];
        $grand_total_stock_value += ($stock['stock'] * $stock['cost']);

        if ($stock['stock'] <= $low_stock_threshold) { // Include 0 stock in low stock count
            $low_stock_count++;
            $low_stock_items[] = ['name' => $stock['product_name'], 'stock' => $stock['stock']];
        }
    }

    // If requested for JSON output (for dashboard AJAX)
    if ($isJsonRequest) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Stock data fetched for dashboard.',
            'total_stock_items' => $grand_total_quantity,
            'low_stock_count' => $low_stock_count,
            'low_stock_items_list' => $low_stock_items,
            'data' => $stocks // Send full stock data if needed by dashboard for other charts (e.g., for Product Stock Distribution chart)
        ]);
        exit; // Stop further HTML output
    }

    // Otherwise, continue with HTML output for view_stocks.php (original functionality)
?>
<?php if (!empty($stocks)): ?>
    <table class="stocks-table">
        <thead>
            <tr>
                <th data-column="product_name">Product Name
                    <span class="sort-icon"><?= ($sortColumn === 'product_name' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
                <th data-column="stock">Quantity
                    <span class="sort-icon"><?= ($sortColumn === 'stock' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
                <th data-column="cost">Cost per product
                    <span class="sort-icon"><?= ($sortColumn === 'cost' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
                <th data-column="total_stock_value">Total Stock Value
                    <span class="sort-icon"><?= ($sortColumn === 'total_stock_value' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($stocks as $stock):
                // Calculate total stock value for the current product
                $total_stock_value_per_product = $stock['stock'] * $stock['cost'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($stock['product_name']) ?></td>
                    <td><?= htmlspecialchars($stock['stock']) ?></td>
                    <td><?= htmlspecialchars(number_format($stock['cost'], 2)) ?></td>
                    <td><?= htmlspecialchars(number_format($total_stock_value_per_product, 2)) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="1" style="text-align: right; font-weight: bold;">Grand Totals:</td> <td><?= htmlspecialchars(number_format($grand_total_quantity)) ?></td>
                <td></td>
                <td><?= htmlspecialchars(number_format($grand_total_stock_value, 2)) ?></td>
            </tr>
        </tbody>
    </table>
<?php else: ?>
    <p>No stock data found for the current criteria.</p>
<?php endif; ?>

<?php
} catch (PDOException $e) {
    if ($isJsonRequest) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } else {
        echo "<p class='error-message'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    error_log("Error in fetch_stocks.php: " . $e->getMessage());
} catch (Exception $e) {
    if ($isJsonRequest) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    } else {
        echo "<p class='error-message'>Server error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    error_log("Error in fetch_stocks.php: " . $e->getMessage());
}
?>