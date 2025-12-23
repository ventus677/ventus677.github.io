<?php
// database/fetch_orders.php
include('connect.php'); // Your database connection file

header('Content-Type: application/json');

try {
    // Get sorting and search parameters from POST request
    $sortColumn = $_POST['sort_column'] ?? 'created_at';
    $sortOrder = $_POST['sort_order'] ?? 'DESC';
    $searchTerm = $_POST['search_term'] ?? '';
    $searchTargetColumn = $_POST['search_target_column'] ?? 'all';

    // Validate sort column to prevent SQL injection
    $allowedSortColumns = [
        'id', 'product_name', 'cost', 'quantity_ordered', 'quantity_received',
        'remaining_quantity', 'manufactured_at', 'expiration', 'supplier_name',
        'status', 'ordered_by_name', 'created_at'
    ];
    if (!in_array($sortColumn, $allowedSortColumns)) {
        $sortColumn = 'created_at'; // Default to a safe column
    }

    // Validate sort order
    $sortOrder = strtoupper($sortOrder);
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        $sortOrder = 'DESC'; // Default to DESC
    }

    $sql = "SELECT
                op.id,
                op.product,
                p.product_name,
                p.cost, -- Changed from p.price to p.cost
                op.supplier,
                s.supplier_name,
                op.quantity_ordered,
                op.quantity_received,
                op.remaining_quantity,
                op.status,
                op.batch,
                op.manufactured_at,
                op.expiration,
                op.created_at,
                u.first_name,
                u.last_name
            FROM
                order_product op
            JOIN
                products p ON op.product = p.id
            JOIN
                suppliers s ON op.supplier = s.supplier_id
            JOIN
                users u ON op.created_by = u.id";

    $conditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        $searchLike = '%' . $searchTerm . '%';
        if ($searchTargetColumn === 'all') {
            $conditions[] = "(
                op.batch LIKE :searchTerm_batch OR
                p.product_name LIKE :searchTerm_product OR
                s.supplier_name LIKE :searchTerm_supplier OR
                op.status LIKE :searchTerm_status OR
                u.first_name LIKE :searchTerm_fname OR
                u.last_name LIKE :searchTerm_lname OR
                op.manufactured_at LIKE :searchTerm_mfg OR
                op.expiration LIKE :searchTerm_exp
            )";
            $params[':searchTerm_batch'] = $searchLike;
            $params[':searchTerm_product'] = $searchLike;
            $params[':searchTerm_supplier'] = $searchLike;
            $params[':searchTerm_status'] = $searchLike;
            $params[':searchTerm_fname'] = $searchLike;
            $params[':searchTerm_lname'] = $searchLike;
            $params[':searchTerm_mfg'] = $searchLike;
            $params[':searchTerm_exp'] = $searchLike;
        } else {
            // Map searchTargetColumn to actual table column names for query
            $columnMap = [
                'batch' => 'op.batch',
                'product_name' => 'p.product_name',
                'supplier_name' => 's.supplier_name',
                'status' => 'op.status',
                'ordered_by_name' => 'u.first_name', // Will search first name, last name is also possible but for simplicity, focusing on one.
                'manufactured_at' => 'op.manufactured_at',
                'expiration' => 'op.expiration'
            ];
            $dbColumn = $columnMap[$searchTargetColumn] ?? null;

            if ($dbColumn) {
                $conditions[] = "$dbColumn LIKE :searchTerm";
                $params[':searchTerm'] = $searchLike;
            } elseif ($searchTargetColumn === 'ordered_by_name') {
                // Special handling for ordered_by_name to search both first and last names
                $conditions[] = "(u.first_name LIKE :searchTerm_fname OR u.last_name LIKE :searchTerm_lname)";
                $params[':searchTerm_fname'] = $searchLike;
                $params[':searchTerm_lname'] = $searchLike;
            }
        }
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY " . $sortColumn;
    // Special handling for ordered_by_name as it's a concatenated field
    if ($sortColumn === 'ordered_by_name') {
        $sql .= " " . $sortOrder . ", u.last_name " . $sortOrder; // Sort by first then last name
    } else {
        $sql .= " " . $sortOrder;
    }


    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $purchaseOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by batch ID
    $groupedOrders = [];
    foreach ($purchaseOrders as $order) {
        $groupedOrders[$order['batch']][] = $order;
    }

    echo json_encode([
        'success' => true,
        'purchase_orders' => $groupedOrders
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>