<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// session_start();
// if (!isset($_SESSION['user'])) {
//     http_response_code(403);
//     exit;
// }

// Adjust this path if 'connect.php' is not in 'database/' relative to search_handler.php
include('database/connect.php'); 

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '') {
    // If query is empty, output nothing. The JS will then hide the container.
    echo '';
    exit;
}

// Configuration for each table to search
$tables_config = [
    'products' => [
        'main_column' => 'product_name',
        'search_columns' => ['product_name', 'brand_name', 'supplier_name'], // Search these for products
        // Explicitly select product columns AND supplier_name from the join
        'select_columns' => 'p.*, s.supplier_name', 
        // Crucial: LEFT JOIN to get supplier_name for products
        'joins' => 'LEFT JOIN productsuppliers ps ON ps.product = p.id LEFT JOIN suppliers s ON ps.supplier = s.supplier_id',
        'link_prefix' => 'product_detail.php?id=', // Ensure this is the correct product detail page
        'id_column' => 'id',
        'display_func' => function($row) {
            $product_name = htmlspecialchars($row['product_name'] ?? 'N/A Product');
            $brand_name = htmlspecialchars($row['brand_name'] ?? 'N/A Brand');
            $supplier_name = htmlspecialchars($row['supplier_name'] ?? 'No Supplier'); // Now available due to JOIN
            $price = number_format($row['price'] ?? 0, 2);

            return "<strong>$product_name</strong>" .
                   "<span>Brand: $brand_name</span>" .
                   "<span>Supplier: $supplier_name</span>" .
                   "<span>Price: ₱$price</span>";
        }
    ],
    'users' => [
        'main_column' => 'first_name',
        'search_columns' => ['first_name', 'last_name'], // Columns to search against
        'select_columns' => '*', // Select all columns for users
        'joins' => '', // No joins needed for users table
        'link_prefix' => 'users.php?id=',
        'id_column' => 'id',
        'display_func' => function($row) {
            return htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) . ' (User)';
        }
    ],
    'suppliers' => [
        'main_column' => 'supplier_name',
        'search_columns' => ['supplier_name'],
        'select_columns' => '*', // Select all columns for suppliers
        'joins' => '', // No joins needed for suppliers table
        'link_prefix' => 'view_suppliers.php?id=', // Ensure this is the correct supplier detail page
        'id_column' => 'supplier_id',
        'display_func' => function($row) {
            return htmlspecialchars($row['supplier_name'] ?? 'N/A Supplier') . ' (Supplier)';
        }
    ],
];

$results_by_table = [];
$total_results_count = 0;

try {
    foreach ($tables_config as $table_name => $config) {
        $search_conditions = [];
        foreach ($config['search_columns'] as $col) {
            // For products, use alias 'p' for product columns
            $prefixed_col = ($table_name === 'products' && strpos($col, '_') === false) ? "p.$col" : $col;
            // Handle supplier_name for products specifically if it's from the joined table 's'
            if ($table_name === 'products' && $col === 'supplier_name') {
                 $prefixed_col = "s.supplier_name";
            }
            $search_conditions[] = "$prefixed_col LIKE :query";
        }
        $where_clause = implode(' OR ', $search_conditions);

        $sql = "SELECT {$config['select_columns']} FROM $table_name ";
        
        // Add table alias for products if joins are present
        if ($table_name === 'products' && !empty($config['joins'])) {
            $sql .= "p {$config['joins']}";
        } elseif (!empty($config['joins'])) {
            $sql .= $config['joins'];
        }
        
        $sql .= " WHERE $where_clause";

        // Add GROUP BY for products to avoid duplicate product entries if multiple suppliers exist
        if ($table_name === 'products') {
            $sql .= " GROUP BY p.id";
        }

        $sql .= " ORDER BY created_at DESC LIMIT 10"; // Assuming 'created_at' exists in all tables

        $stmt = $conn->prepare($sql);
        $stmt->execute(['query' => '%' . $q . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results_count += count($rows);
        $results_by_table[$table_name] = $rows;
    }

    if ($total_results_count === 0) {
        echo '<p class="no-results">No results found.</p>';
    } else {
        echo '<div class="search-results-wrapper">'; // Use a wrapper div for all results
        foreach ($results_by_table as $table_name => $rows) {
            if (!empty($rows)) { // Only show heading if there are results for this category
                echo '<p style="font-weight:bold;">' . ucfirst($table_name) . '</p>';
                echo '<ul>'; // Start list for each category
                foreach ($rows as $row) {
                    $link = $tables_config[$table_name]['link_prefix'] . $row[$tables_config[$table_name]['id_column']];
                    $display_html = $tables_config[$table_name]['display_func']($row);

                    echo '<li>'; // Start list item
                    echo '<a href="' . htmlspecialchars($link) . '">';
                    echo $display_html; // This now contains strong and span tags
                    echo '</a>'; // Close the anchor tag
                    echo '</li>'; // Close list item
                }
                echo '</ul>'; // Close the unordered list
            } else {
                // Optionally, you can still show "No results found for X" if you want
                // echo "<p class='no-results'>No results found for $table_name</p>";
            }
        }
        echo '</div>'; // Close the wrapper div
    }
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    echo '<p class="error">Error processing search: ' . htmlspecialchars($e->getMessage()) . '</p>'; // Show actual error for debugging
}
?>