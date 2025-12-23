<?php
session_start();
include('database/connect.php'); // Ensure this path is correct for your database connection

$user = isset($_SESSION['user']) ? $_SESSION['user'] : ['first_name' => 'Guest', 'last_name' => ''];

// Pagination settings
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Sorting settings
$currentSortColumn = $_GET['sort'] ?? 'sold_at'; // Default sort column
$currentSortOrder = $_GET['order'] ?? 'DESC'; // Default sort order

// Allowed columns for sorting to prevent SQL injection
$allowedSortColumns = ['id', 'product_name', 'quantity_sold', 'price_per_unit', 'total_sale_amount', 'sold_at', 'recorded_by_name'];
if (!in_array($currentSortColumn, $allowedSortColumns)) {
    $currentSortColumn = 'sold_at';
}
if (!in_array(strtoupper($currentSortOrder), ['ASC', 'DESC'])) {
    $currentSortOrder = 'DESC';
}

// Search settings
$searchColumn = $_GET['search_column'] ?? '';
$searchTerm = $_GET['search_term'] ?? '';
$searchQuery = '';

$allowedSearchColumns = ['product_name', 'sold_at', 'recorded_by_name', 'id']; // Columns allowed for search
if (!empty($searchTerm) && in_array($searchColumn, $allowedSearchColumns)) {
    // Special handling for date search
    if ($searchColumn === 'sold_at') {
        $searchQuery = " AND DATE(sp.sold_at) = :searchTerm ";
    } else if ($searchColumn === 'recorded_by_name') {
        // Concatenate first_name and last_name for search
        $searchQuery = " AND (u.first_name LIKE :searchTerm OR u.last_name LIKE :searchTerm OR CONCAT(u.first_name, ' ', u.last_name) LIKE :searchTerm) ";
    } else if ($searchColumn === 'id') {
        $searchQuery = " AND sp.id = :searchTerm "; // Exact match for ID
    }
    else {
        $searchQuery = " AND " . $conn->quote($searchColumn) . " LIKE :searchTerm ";
    }
}

// Total number of records (for pagination)
$totalSalesCountQuery = "
    SELECT COUNT(sp.id)
    FROM sold_products sp
    JOIN products p ON sp.product_id = p.id
    LEFT JOIN users u ON sp.recorded_by = u.id
    WHERE 1 " . $searchQuery; // Base WHERE clause for potential search

$countStmt = $conn->prepare($totalSalesCountQuery);
if (!empty($searchTerm) && in_array($searchColumn, $allowedSearchColumns) && $searchColumn !== 'id') {
    if ($searchColumn === 'sold_at') {
        // For date search, searchTerm should be exact date string
        $countStmt->bindValue(':searchTerm', $searchTerm);
    } else if ($searchColumn === 'recorded_by_name') {
         $countStmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    }
    else {
        $countStmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    }
} else if ($searchColumn === 'id' && !empty($searchTerm)) {
    $countStmt->bindValue(':searchTerm', $searchTerm);
}
$countStmt->execute();
$totalSales = $countStmt->fetchColumn();
$totalPages = ceil($totalSales / $limit);

// Fetch sales data
$query = "
    SELECT 
        sp.id, 
        p.product_name, 
        sp.quantity_sold, 
        sp.price_per_unit, 
        sp.total_sale_amount, 
        sp.sold_at,
        CONCAT(u.first_name, ' ', u.last_name) AS recorded_by_name
    FROM sold_products sp
    JOIN products p ON sp.product_id = p.id
    LEFT JOIN users u ON sp.recorded_by = u.id
    WHERE 1 " . $searchQuery . "
    ORDER BY " . $currentSortColumn . " " . $currentSortOrder . "
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

if (!empty($searchTerm) && in_array($searchColumn, $allowedSearchColumns) && $searchColumn !== 'id') {
    if ($searchColumn === 'sold_at') {
        $stmt->bindValue(':searchTerm', $searchTerm);
    } else if ($searchColumn === 'recorded_by_name') {
         $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    }
    else {
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    }
} else if ($searchColumn === 'id' && !empty($searchTerm)) {
    $stmt->bindValue(':searchTerm', $searchTerm);
}

$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - Keepkit</title> <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="tables.css">
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        /* Your existing CSS styles */
        .list {
            padding: 5px 10px;
            width: 100%;
            font-weight: bold;
            margin-bottom: 20px;
            border: 1px solid black;
        }

        .sales-table { /* Changed class from orders-table */
            width: 100%;
            border-collapse: collapse;
        }

        .sales-table th, .sales-table td { /* Changed class */
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .sales-table th { /* Changed class */
            background-color: #f2f2f2;
            cursor: pointer;
        }

        .sales-table th:hover { /* Changed class */
            background-color: #e0e0e0;
        }

        .action-button {
            background-color: #4CAF50;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .action-button:hover {
            opacity: 0.8;
        }

        /* Styles for the modal (removed for sales, but kept if you reintroduce similar functionality) */
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; border-radius: 8px; }
        .close-button { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close-button:hover, .close-button:focus { color: black; text-decoration: none; cursor: pointer; }

        .pagination {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }
        .pagination a, .pagination span {
            margin: 0 5px;
            padding: 8px 16px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
        }
        .pagination a:hover:not(.active) {
            background-color: #f2f2f2;
        }
        .pagination span.active {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }

        /* Search and filter container */
        .filter-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-controls label {
            font-weight: bold;
        }
        .filter-controls select, .filter-controls input[type="text"], .filter-controls input[type="date"], .filter-controls button {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .filter-controls button {
            background-color: #323232;
            color: white;
            cursor: pointer;
        }
        .filter-controls button:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
            <h3>&nbsp;&nbsp;Keepkit</h3>
        </a>
        <div class="search-container">
            <input type="search" id="searchInput" placeholder="Search..." autocomplete="off">
            <div id="searchResults"></div>
        </div>
        <div class="right-element">
            <a href="database/logout.php" ><img src= "images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>

        <main class="main">
            <div class="container">
                <div class="header">Sales History</div> <div class="filter-controls">
                    <label for="searchColumn">Search By:</label>
                    <select id="searchColumn">
                        <option value="">All</option>
                        <option value="id" <?= $searchColumn === 'id' ? 'selected' : '' ?>>Sale ID</option>
                        <option value="product_name" <?= $searchColumn === 'product_name' ? 'selected' : '' ?>>Product Name</option>
                        <option value="sold_at" <?= $searchColumn === 'sold_at' ? 'selected' : '' ?>>Sale Date</option>
                        <option value="recorded_by_name" <?= $searchColumn === 'recorded_by_name' ? 'selected' : '' ?>>Recorded By</option>
                    </select>
                    <input type="text" id="searchTerm" placeholder="Enter search term..." value="<?= htmlspecialchars($searchTerm) ?>">
                    <button id="searchButton">Search</button>
                    <button id="resetButton">Reset</button>
                </div>
                
                <table class="sales-table"> <thead>
                        <tr>
                            <th data-column="id">Sale ID</th>
                            <th data-column="product_name">Product Name</th>
                            <th data-column="quantity_sold">Quantity Sold</th>
                            <th data-column="price_per_unit">Price Per Unit</th>
                            <th data-column="total_sale_amount">Total Sale Amount</th>
                            <th data-column="sold_at">Sold At</th>
                            <th data-column="recorded_by_name">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($sales) > 0): ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sale['id']) ?></td>
                                    <td><?= htmlspecialchars($sale['product_name']) ?></td>
                                    <td><?= htmlspecialchars($sale['quantity_sold']) ?></td>
                                    <td>₱<?= number_format(htmlspecialchars($sale['price_per_unit']), 2) ?></td>
                                    <td>₱<?= number_format(htmlspecialchars($sale['total_sale_amount']), 2) ?></td>
                                    <td><?= htmlspecialchars($sale['sold_at']) ?></td>
                                    <td><?= htmlspecialchars($sale['recorded_by_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No sales records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php
                            $paginationParams = [
                                'page' => $i,
                                'sort' => $currentSortColumn,
                                'order' => $currentSortOrder,
                                'search_column' => $searchColumn,
                                'search_term' => $searchTerm
                            ];
                        ?>
                        <a href="?<?= http_build_query($paginationParams) ?>" class="<?= ($i === $page) ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Function to get current URL parameters
            function getUrlParameter(name) {
                name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
                var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
                var results = regex.exec(location.search);
                return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
            };

            // Set initial values for search inputs from URL parameters
            $('#searchColumn').val(getUrlParameter('search_column'));
            $('#searchTerm').val(getUrlParameter('search_term'));

            // Adjust input type for date search
            $('#searchColumn').on('change', function() {
                if ($(this).val() === 'sold_at') {
                    $('#searchTerm').attr('type', 'date');
                } else {
                    $('#searchTerm').attr('type', 'text');
                }
                // Clear search term when column changes
                $('#searchTerm').val('');
            }).trigger('change'); // Trigger on load to set correct input type

            // Handle sorting
            $('.sales-table th').on('click', function() {
                const column = $(this).data('column');
                if (column) { // Only sortable columns have data-column
                    let currentSortColumn = getUrlParameter('sort');
                    let currentSortOrder = getUrlParameter('order');

                    let newSortOrder = 'ASC';
                    if (currentSortColumn === column && currentSortOrder === 'ASC') {
                        newSortOrder = 'DESC';
                    }

                    const params = new URLSearchParams(window.location.search);
                    params.set('sort', column);
                    params.set('order', newSortOrder);
                    // Preserve search parameters when sorting
                    params.set('search_column', getUrlParameter('search_column'));
                    params.set('search_term', getUrlParameter('search_term'));
                    params.set('page', 1); // Reset to first page on sort
                    window.location.href = '?' + params.toString();
                }
            });

            // Handle search
            $('#searchButton').on('click', function() {
                const searchColumn = $('#searchColumn').val();
                const searchTerm = $('#searchTerm').val();
                const params = new URLSearchParams(window.location.search);
                params.set('search_column', searchColumn);
                params.set('search_term', searchTerm);
                params.set('page', 1); // Reset to first page on new search
                // Preserve sort parameters when searching
                params.set('sort', getUrlParameter('sort'));
                params.set('order', getUrlParameter('order'));
                window.location.href = '?' + params.toString();
            });

            // Reset search filters
            $('#resetButton').on('click', function() {
                window.location.href = 'view_sold.php'; // Go back to default view
            });

            // Allow pressing Enter in search field to trigger search
            $('#searchTerm').on('keypress', function(e) {
                if (e.which === 13) { // Enter key pressed
                    $('#searchButton').click();
                }
            });

        });
    </script>
</body>
</html>