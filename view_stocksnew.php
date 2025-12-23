<?php
session_start();
include('database/connect.php'); // Ensure this path is correct and the file exists

// User session data
$user = isset($_SESSION['user']) ? $_SESSION['user'] : ['first_name' => 'Guest', 'last_name' => ''];

// --- Sorting Logic ---
$sort_column = $_GET['sort_column'] ?? 'product_name'; // Default sort column
$sort_order = $_GET['sort_order'] ?? 'ASC'; // Default sort order

// Validate sort column to prevent SQL injection
$allowed_sort_columns = ['product_name', 'brand_name', 'stock', 'price'];
if (!in_array($sort_column, $allowed_sort_columns)) {
    $sort_column = 'product_name'; // Fallback to default
}

// Validate sort order
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'ASC'; // Fallback to default
}

// --- Search Logic ---
$search_query = trim($_GET['search_query'] ?? '');
$where_clauses = [];
$bind_params = [];

if (!empty($search_query)) {
    // Using '%' for LIKE wildcard. PDO handles escaping values when executing.
    $like_search_query = '%' . $search_query . '%';

    // Check if the search query is numeric (for price/stock)
    if (is_numeric($search_query)) {
        // Search in stock and price columns for numeric values
        $where_clauses[] = "(stock LIKE ? OR price LIKE ?)";
        $bind_params[] = $like_search_query;
        $bind_params[] = $like_search_query;
    } else {
        // Search in varchar columns (product_name, brand_name) for text
        $where_clauses[] = "(product_name LIKE ? OR brand_name LIKE ?)";
        $bind_params[] = $like_search_query;
        $bind_params[] = $like_search_query;
    }
}

// Construct the SQL query
$sql = "SELECT product_name, brand_name, stock, price FROM products";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// ORDER BY clause directly incorporates validated $sort_column and $sort_order
// Since $sort_column and $sort_order are validated against an allowed list,
// direct interpolation here is safe from SQL injection for these specific parts.
$sql .= " ORDER BY $sort_column $sort_order";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stocks - Keepkit</title>
    <link rel="stylesheet" href="home.css">
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        .list {
            padding: 5px 10px;
            width: 45%;
            font-weight: bold;
            margin-bottom: 20px;
            border: 1px solid black;
        }

        .stocks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .stocks-table th,
        .stocks-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .stocks-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .stocks-table th a {
            text-decoration: none;
            color: inherit;
            display: flex; /* Use flexbox for alignment */
            align-items: center; /* Vertically center items */
            justify-content: space-between; /* Space out text and arrow */
            width: 100%;
            height: 100%;
        }

        /* Ensure Font Awesome icons are styled correctly */
        .stocks-table th a i {
            margin-left: 8px; /* Space between text and arrow */
            font-size: 0.8em; /* Slightly smaller arrow */
            opacity: 0.7; /* Make arrows slightly transparent when not active */
        }

        /* Style for the active sort arrow */
        .stocks-table th a.active-sort i {
            opacity: 1; /* Full opacity for the active arrow */
            color: #333; /* Darker color for active arrow */
        }


        .status-available {
            color: green;
            font-weight: bold;
        }

        .status-not-available {
            color: red;
            font-weight: bold;
        }

        .search-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        #searchInput {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: 300px; /* Adjust as needed */
        }

        #searchResults {
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #fff;
            border: 1px solid #ddd;
            border-top: none;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            display: none; /* Hidden by default */
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); /* Subtle shadow */
        }

        #searchResults div {
            padding: 8px 12px;
            cursor: pointer;
        }

        #searchResults div:hover {
            background-color: #f0f0f0;
        }

        .total-row {
            font-weight: bold;
            background-color: #e9e9e9;
        }

        .total-row td {
            border-top: 2px solid #333; /* Stronger border for totals */
        }
    </style>
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <header>
        <a href="index.php" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
            <h3>&nbsp;&nbsp;Keepkit</h3>
        </a>
        <div class="search-container">
            <form action="view_stocksnew.php" method="GET" style="display: flex;">
                <input type="search" id="searchInput" name="search_query" placeholder="Search products, brands, quantity, or price..." autocomplete="off" value="<?= htmlspecialchars($search_query) ?>">
                <input type="hidden" name="sort_column" value="<?= htmlspecialchars($sort_column) ?>">
                <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sort_order) ?>">
                <button type="submit" style="margin-left: 5px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 5px; background-color: #f2f2f2; cursor: pointer;">Search</button>
            </form>
            <div id="searchResults"></div>
        </div>
        <div class="right-element">
            <a href="database/logout.php" ><img src= "images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>

<main class="main">
    <section id="stocksPage" class="active">
        <div class="list">Current Stock Levels</div>

        <?php
            try {
                $stmt = $conn->prepare($sql);

                // Dynamically bind parameters based on whether they exist
                if (!empty($bind_params)) {
                    $stmt->execute($bind_params);
                } else {
                    $stmt->execute();
                }

                $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Log the detailed error, show a generic message to the user
                error_log("Error fetching stock data: " . $e->getMessage());
                echo "<p style='color: red;'>An error occurred while fetching stock data. Please try again later.</p>";
                $stocks = []; // Ensure $stocks is an empty array to prevent further errors
            }

            $grand_total_quantity = 0;
            $grand_total_stock_value = 0;
        ?>

        <?php if (!empty($stocks)): ?>
            <table class="stocks-table">
                <thead>
                    <tr>
                        <th>
                            <?php
                                $product_name_sort_order = ($sort_column == 'product_name' && $sort_order == 'ASC') ? 'DESC' : 'ASC';
                                $product_name_active_class = ($sort_column == 'product_name') ? 'active-sort' : '';
                            ?>
                            <a href="?sort_column=product_name&sort_order=<?= $product_name_sort_order ?>&search_query=<?= urlencode($search_query) ?>" class="<?= $product_name_active_class ?>">
                                Product Name
                                <?php if ($sort_column == 'product_name'): ?>
                                    <i class="fas fa-<?= ($sort_order == 'ASC') ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <?php
                                $brand_name_sort_order = ($sort_column == 'brand_name' && $sort_order == 'ASC') ? 'DESC' : 'ASC';
                                $brand_name_active_class = ($sort_column == 'brand_name') ? 'active-sort' : '';
                            ?>
                            <a href="?sort_column=brand_name&sort_order=<?= $brand_name_sort_order ?>&search_query=<?= urlencode($search_query) ?>" class="<?= $brand_name_active_class ?>">
                                Brand Name
                                <?php if ($sort_column == 'brand_name'): ?>
                                    <i class="fas fa-<?= ($sort_order == 'ASC') ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <?php
                                $stock_sort_order = ($sort_column == 'stock' && $sort_order == 'ASC') ? 'DESC' : 'ASC';
                                $stock_active_class = ($sort_column == 'stock') ? 'active-sort' : '';
                            ?>
                            <a href="?sort_column=stock&sort_order=<?= $stock_sort_order ?>&search_query=<?= urlencode($search_query) ?>" class="<?= $stock_active_class ?>">
                                Quantity
                                <?php if ($sort_column == 'stock'): ?>
                                    <i class="fas fa-<?= ($sort_order == 'ASC') ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Status</th>
                        <th>
                            <?php
                                $price_sort_order = ($sort_column == 'price' && $sort_order == 'ASC') ? 'DESC' : 'ASC';
                                $price_active_class = ($sort_column == 'price') ? 'active-sort' : '';
                            ?>
                            <a href="?sort_column=price&sort_order=<?= $price_sort_order ?>&search_query=<?= urlencode($search_query) ?>" class="<?= $price_active_class ?>">
                                Price per product
                                <?php if ($sort_column == 'price'): ?>
                                    <i class="fas fa-<?= ($sort_order == 'ASC') ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Total Stock Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($stocks as $stock):
                        $status = ($stock['stock'] > 0) ? 'Available' : 'Not Available';
                        $status_class = ($stock['stock'] > 0) ? 'status-available' : 'status-not-available';

                        $total_stock_value_per_product = $stock['stock'] * $stock['price'];

                        $grand_total_quantity += $stock['stock'];
                        $grand_total_stock_value += $total_stock_value_per_product;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($stock['product_name']) ?></td>
                            <td><?= htmlspecialchars($stock['brand_name']) ?></td>
                            <td><?= htmlspecialchars($stock['stock']) ?></td>
                            <td><span class="<?= $status_class ?>"><?= $status ?></span></td>
                            <td>₱<?= htmlspecialchars(number_format($stock['price'], 2)) ?></td>
                            <td>₱<?= htmlspecialchars(number_format($total_stock_value_per_product, 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2" style="text-align: right;">Grand Totals:</td>
                        <td><?= htmlspecialchars(number_format($grand_total_quantity)) ?></td>
                        <td></td>
                        <td></td>
                        <td>₱<?= htmlspecialchars(number_format($grand_total_stock_value, 2)) ?></td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <p>No stock data found matching your criteria.</p>
        <?php endif; ?>
    </section>
</main>

    <script src="script.js"></script>
    <script>
        // JavaScript for sidebar active link (if not already in script.js)
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname.split('/').pop(); // Gets 'view_stocksnew.php'
            const sidebarLinks = document.querySelectorAll('.sidebar-link');

            sidebarLinks.forEach(link => {
                const linkPage = link.getAttribute('data-page');
                if (linkPage && linkPage === currentPath) {
                    link.classList.add('active'); // Add an 'active' class to highlight
                }
            });

            // Basic search input functionality (if no other script handles it)
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');

            searchInput.addEventListener('input', function() {
                // This is where you'd typically make an AJAX call to fetch autocomplete suggestions
                // For now, it just shows/hides the searchResults div
                if (this.value.length > 2) { // Show results after 2 characters
                    // Simulate some results (replace with actual AJAX)
                    searchResults.innerHTML = '<div>Suggestion 1</div><div>Suggestion 2</div>';
                    searchResults.style.display = 'block';
                } else {
                    searchResults.style.display = 'none';
                }
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(event) {
                if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
                    searchResults.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>