<?php
// fetch_order_items.php
// This file dynamically fetches and displays order item data based on sort and search parameters.

// Ensure this path is correct for your database connection
include('database/connect.php'); // Adjust path if your connect.php is elsewhere

// Get sort parameters from GET request, with defaults
$sortColumn = $_GET['sort_column'] ?? 'oi.order_id';
$sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC'); // Ensure uppercase for SQL

// Get search parameters from GET request, with defaults
$searchTerm = $_GET['search_term'] ?? '';
$searchColumn = $_GET['search_column'] ?? 'oi.order_id';

// --- Input Validation for Security ---
// It's crucial to whitelist allowed column names to prevent SQL injection.
$allowedSortColumns = [
    'oi.id', 'oi.order_id', 'o.transaction_date',
    'p.product_name', 'oi.quantity', 'oi.cost_at_order', 'oi.item_total',
    'user_name' // 'user_name' will be handled as a concatenated field in sorting
];
if (!in_array($sortColumn, $allowedSortColumns)) {
    $sortColumn = 'oi.order_id'; // Default to 'oi.order_id' if invalid
}

// Ensure sort order is either ASC or DESC
if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC'; // Default to 'DESC' if invalid
}

// Whitelist allowed search columns
$allowedSearchColumns = [
    'oi.id', 'oi.order_id', 'o.transaction_date',
    'p.product_name', 'oi.quantity', 'oi.cost_at_order', 'oi.item_total',
    'user_name' // 'user_name' for search will also be handled specially
];
if (!in_array($searchColumn, $allowedSearchColumns)) {
    $searchColumn = 'oi.order_id'; // Default to 'oi.order_id' if invalid
}
// --- End Input Validation ---

$whereClause = '';
$params = [];

if (!empty($searchTerm)) {
    if ($searchColumn === 'oi.quantity' || $searchColumn === 'oi.cost_at_order' || $searchColumn === 'oi.item_total' || $searchColumn === 'oi.order_id' || $searchColumn === 'oi.id') {
        // For numeric columns, search for exact matches
        if (is_numeric($searchTerm)) {
            $whereClause = "WHERE {$searchColumn} = :searchTerm";
            $params[':searchTerm'] = (float)$searchTerm;
        } else {
            // If searching a numeric column with non-numeric input, return no results
            $order_items = [];
            $grand_total_quantity_ordered = 0;
            $grand_total_items_value = 0;
            goto renderTable;
        }
    } elseif ($searchColumn === 'o.transaction_date') {
        // For date columns, allow partial search or exact date search
        $whereClause = "WHERE DATE({$searchColumn}) LIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    } elseif ($searchColumn === 'user_name') {
        // Special handling for 'Input Sale By' (concatenated first_name and last_name)
        $whereClause = "WHERE CONCAT(u.first_name, ' ', u.last_name) LIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    } else {
        // For other text columns, use LIKE for wildcard search
        $whereClause = "WHERE {$searchColumn} LIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }
}

try {
    // Construct the ORDER BY clause
    $orderByClause = '';
    if ($sortColumn === 'user_name') {
        $orderByClause = "ORDER BY CONCAT(u.first_name, ' ', u.last_name) {$sortOrder}";
    } else {
        $orderByClause = "ORDER BY {$sortColumn} {$sortOrder}";
    }

    $stmt = $conn->prepare("
        SELECT
            oi.id,
            oi.order_id,
            o.transaction_date,
            u.first_name,
            u.last_name,
            oi.product_id,
            p.product_name,
            oi.quantity,
            oi.cost_at_order,
            oi.item_total
        FROM
            order_items oi
        JOIN
            products p ON oi.product_id = p.id
        JOIN
            orders o ON oi.order_id = o.id
        JOIN
            users u ON o.created_by = u.id
        {$whereClause}
        {$orderByClause}
    ");
    $stmt->execute($params);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching order items data: " . $e->getMessage();
    $order_items = [];
}

// Initialize total variables before the loop (these will calculate grand totals of the *filtered/sorted* data)
$grand_total_quantity_ordered = 0;
$grand_total_items_value = 0;

renderTable: // Label for the goto statement
?>

<?php if (!empty($order_items)): ?>
    <table class="order-items-table">
        <thead>
            <tr>
                <th data-column="oi.id">Sold Item ID
                    <span class="sort-icon"><?= ($sortColumn === 'oi.id' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
                <th data-column="oi.order_id">Sales ID
                    <span class="sort-icon"><?= ($sortColumn === 'oi.order_id' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
                <th data-column="p.product_name">Product Name
                    <span class="sort-icon"><?= ($sortColumn === 'p.product_name' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
                <th data-column="oi.quantity">Quantity Sold
                    <span class="sort-icon"><?= ($sortColumn === 'oi.quantity' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
                <th data-column="oi.cost_at_order">Price per Unit
                    <span class="sort-icon"><?= ($sortColumn === 'oi.cost_at_order' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
                <th data-column="oi.item_total">Total Price
                    <span class="sort-icon"><?= ($sortColumn === 'oi.item_total' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
                <th data-column="o.transaction_date">Transaction Date
                    <span class="sort-icon"><?= ($sortColumn === 'o.transaction_date' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
                <th data-column="user_name">Inputted By
                    <span class="sort-icon"><?= ($sortColumn === 'user_name' ? ($sortOrder === 'ASC' ? '▲' : '▼') : '') ?></span>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($order_items as $item):
                $grand_total_quantity_ordered += $item['quantity'];
                $grand_total_items_value += $item['item_total'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($item['id']) ?></td>
                    <td><?= htmlspecialchars($item['order_id']) ?></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td>₱<?= htmlspecialchars(number_format($item['cost_at_order'], 2)) ?></td>
                    <td>₱<?= htmlspecialchars(number_format($item['item_total'], 2)) ?></td>
                    <td><?= htmlspecialchars($item['transaction_date']) ?></td>
                    <td><?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="3" style="text-align: right; font-weight: bold;">Grand Totals:</td>
                <td><?= htmlspecialchars(number_format($grand_total_quantity_ordered)) ?></td>
                <td></td>
                <td>₱<?= htmlspecialchars(number_format($grand_total_items_value, 2)) ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>
<?php else: ?>
    <p>No order item data found for the current criteria.</p>
<?php endif; ?>