<?php
// Include the database connection file. Adjust the path if 'connect.php' is not directly in the same directory.
require_once 'connect.php';
header('Content-Type: application/json');

// Initialize variables from GET parameters sent by the JavaScript in view_order.php
$searchTerm = $_GET['search'] ?? '';
$searchColumn = $_GET['column'] ?? 'order_id'; // Default search column if none is provided
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';

// Validate the searchColumn to prevent SQL injection.
$allowedColumns = ['order_id', 'supplier_name', 'product_name', 'status'];
if (!in_array($searchColumn, $allowedColumns)) {
    $searchColumn = 'order_id'; // Fallback to a safe default if an invalid column is requested
}

try {
    // Start building the SQL query to fetch purchase order details
    $sql = "
        SELECT
            op.id,
            op.batch AS order_id,
            s.supplier_name,
            p.product_name,
            op.quantity_ordered,
            op.quantity_received,
            op.remaining_quantity,
            op.status,
            op.created_at AS order_date, -- Mapped created_at to order_date
            op.updated_at AS last_updated_date -- You can use updated_at if it serves a purpose
        FROM
            order_product op
        JOIN
            products p ON op.product = p.id
        JOIN
            suppliers s ON op.supplier = s.supplier_id
        WHERE 1=1
    ";

    $params = []; // Array to store parameters for the prepared statement

    // Add search filter if a search term is provided
    if (!empty($searchTerm)) {
        // Map the search column from the client-side to the actual database column
        $dbColumn = '';
        switch ($searchColumn) {
            case 'order_id':
                $dbColumn = 'op.batch'; // Assuming 'batch' column in 'order_product' is used for order ID
                break;
            case 'supplier_name':
                $dbColumn = 's.supplier_name';
                break;
            case 'product_name':
                $dbColumn = 'p.product_name';
                break;
            case 'status':
                $dbColumn = 'op.status';
                break;
        }
        if (!empty($dbColumn)) {
            $sql .= " AND " . $dbColumn . " LIKE ?";
            $params[] = '%' . $searchTerm . '%'; // Add wildcard for partial matching
        }
    }

    // Add date range filters. Using op.created_at for date filtering.
    if (!empty($startDate)) {
        $sql .= " AND op.created_at >= ?";
        $params[] = $startDate;
    }
    if (!empty($endDate)) {
        $sql .= " AND op.created_at <= ?";
        $params[] = $endDate;
    }

    // Order the results, typically by order date or ID
    $sql .= " ORDER BY op.created_at DESC, op.batch DESC";

    // Prepare and execute the SQL query using PDO
    $stmt = $conn->prepare($sql);
    $stmt->execute($params); // Pass parameters to execute
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results as associative array

    // Return a successful JSON response with the fetched orders
    echo json_encode(['success' => true, 'orders' => $orders]);

} catch (PDOException $e) {
    // Catch and log any database errors
    error_log("DB Error in get_all_purchase_orders.php: " . $e->getMessage());
    // Return an error JSON response to the client
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again later.']);
}
?>