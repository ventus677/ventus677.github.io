<?php
// selling_products_search.php

// This file is designed to be included by view_sales.php.
// It assumes that 'database/connect.php' has already been included by view_sales.php,
// and a PDO database connection object named $conn is available.

$sales_data = []; // Initialize the array that will hold the sales records.

// Retrieve search and sort parameters from the URL.
$search_query = $_GET['search_query'] ?? '';
$search_column = $_GET['search_column'] ?? 'customer_name';
$sort_column = $_GET['sort_column'] ?? 'created_at'; // Default sort by order creation date
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Basic validation for the sort order to prevent SQL injection.
$allowed_sort_orders = ['ASC', 'DESC'];
if (!in_array(strtoupper($sort_order), $allowed_sort_orders)) {
    $sort_order = 'DESC'; // Fallback to 'DESC' if an invalid sort order is provided.
}

// Mapping of frontend column names (from JavaScript/HTML) to the actual SQL alias names.
// This is essential for dynamic sorting and searching.
$column_map = [
    'order_id' => 'order_id',
    'customer_name' => 'customer_name',
    'total_amount' => 'total_amount',
    'order_status' => 'order_status',
    'payment_method' => 'payment_method',
    'created_at' => 'created_at',
    'products_summary' => 'products_summary' // An aggregated string of product names
];

// Determine the SQL column name for sorting.
$sql_sort_column = $column_map[$sort_column] ?? 'created_at'; // Default to 'created_at' if not found

try {
    // Construct the CTE for combined sales data.
    // This CTE combines data from 'orders', 'order_products', 'order_items', and 'orders_customer'.
    $cte_sql = "
    WITH CombinedSales AS (
            -- Orders from 'orders' table with items from 'order_products'
            SELECT
                o.id AS order_id,
                o.customer_name AS customer_name,
                GROUP_CONCAT(CONCAT(p.product_name, ' (', op.quantity, ')') ORDER BY p.product_name SEPARATOR ', ') AS products_summary,
                o.total_amount,
                NULL AS order_status,     -- Not directly from 'orders' table
                NULL AS payment_method,   -- Not directly from 'orders' table
                o.order_date AS created_at
            FROM
                orders o
            JOIN
                order_products op ON o.id = op.order_id
            JOIN
                products p ON op.product_id = p.id
            GROUP BY
                o.id, o.customer_name, o.total_amount, o.order_date

            UNION ALL

            -- Orders from 'orders' table with items from 'order_items'
            SELECT
                o.id AS order_id,
                o.customer_name AS customer_name,
                GROUP_CONCAT(CONCAT(p.product_name, ' (', oi.quantity, ')') ORDER BY p.product_name SEPARATOR ', ') AS products_summary,
                o.total_amount,
                NULL AS order_status,     -- Not directly from 'orders' table
                NULL AS payment_method,   -- Not directly from 'orders' table
                o.order_date AS created_at
            FROM
                orders o
            JOIN
                order_items oi ON o.id = oi.order_id
            JOIN
                products p ON oi.product_id = p.id
            GROUP BY
                o.id, o.customer_name, o.total_amount, o.order_date

            UNION ALL

            -- Orders from 'orders_customer' table, now correctly linking to order_products and products
            SELECT
                oc.id AS order_id,
                CONCAT(c.first_name, ' ', c.last_name) AS customer_name, -- Fetch customer name from 'customers' table
                -- Now correctly populating products_summary for orders_customer
                GROUP_CONCAT(CONCAT(p.product_name, ' (', op.quantity, ')') ORDER BY p.product_name SEPARATOR ', ') AS products_summary,
                oc.total_amount,
                oc.status AS order_status,
                oc.payment_method AS payment_method,
                oc.order_date AS created_at
            FROM
                orders_customer oc
            JOIN
                customers c ON oc.customer_id = c.id
            LEFT JOIN
                order_products op ON oc.id = op.order_id -- Join to get product details for orders_customer
            LEFT JOIN
                products p ON op.product_id = p.id -- Join to get product names
            GROUP BY
                oc.id, c.first_name, c.last_name, oc.total_amount, oc.status, oc.payment_method, oc.order_date
        )
        -- Select all columns from the combined results, and apply filtering/sorting.
        SELECT *
        FROM CombinedSales
        WHERE 1=1
    ";

    $params = []; // Array to hold parameters for the prepared statement.

    // Add search conditions if a search query is provided.
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        if (array_key_exists($search_column, $column_map)) {
            $cte_sql .= " AND " . $column_map[$search_column] . " LIKE ?";
            $params[] = $search_term;
        }
    }

    // Append the ORDER BY clause.
    $cte_sql .= " ORDER BY {$sql_sort_column} {$sort_order}";

    $stmt = $conn->prepare($cte_sql);
    $stmt->execute($params);

    $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error in selling_products_search.php: " . $e->getMessage());
    $sales_data = ['error' => 'Failed to retrieve sales data.'];
}