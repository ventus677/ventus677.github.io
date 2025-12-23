<?php
// Start the session.
session_start();

// Check if the user is logged in. If not, redirect to the index page.
// This ensures only authenticated users can access this page.
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit; // Important: Exit after redirect to prevent further script execution.
}

// Assign the user session data to a variable for easier access.
$user = $_SESSION['user'];

// Include the database connection file.
// This file should establish a PDO connection and assign it to the $conn variable.
// It's included here once at the top to make the connection available globally for the page.
include('database/connect.php');

// Handle search term and selected category for the product grid
$search_term = $_GET['search'] ?? '';
$selected_category = $_GET['category'] ?? ''; // Default to empty string for 'All Products'

// --- Category Data Fetch ---
// Fetches distinct categories from the database for the filter sidebar.
$categories = [];
try {
    $category_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
    $stmt_categories = $conn->query($category_query);
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    // In a production environment, you might display a user-friendly error or fallback.
}

// --- Product Grid Data Fetch ---
// This section fetches data specifically for the product grid display, applying search and category filters.
$products_for_grid = [];
try {
    $sql_grid = "SELECT id, product_name, price, img, category, stock FROM products WHERE 1"; // Start with a true condition
    $params_grid = [];

    // Apply search filter if a search term is provided
    if (!empty($search_term)) {
        $sql_grid .= " AND product_name LIKE ?";
        $params_grid[] = '%' . $search_term . '%';
    }

    // Apply category filter if a category is selected (and it's not 'All Products' which is represented by an empty string)
    if (!empty($selected_category)) {
        $sql_grid .= " AND category = ?";
        $params_grid[] = $selected_category;
    }

    // Execute the query for the product grid.
    $stmt_grid = $conn->prepare($sql_grid);
    $stmt_grid->execute($params_grid);
    // Fetch all results for the grid as an associative array.
    $products_for_grid = $stmt_grid->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors gracefully for the product grid.
    error_log("Error fetching products for grid: " . $e->getMessage());
    $products_for_grid = []; // Ensure $products_for_grid is an empty array on error.
}

// --- Product Table Data Fetch (Existing) ---
// This section fetches data for the product table, including supplier names and creator.
// This query uses LEFT JOINs to retrieve related information from 'users', 'productsuppliers', and 'suppliers' tables.
$product_table_query = "
SELECT
    p.*,
    u.first_name AS created_by_name,
    GROUP_CONCAT(s.supplier_name SEPARATOR ', ') AS supplier_names
FROM
    products p
LEFT JOIN
    users u ON p.created_by = u.id
LEFT JOIN
    productsuppliers ps ON ps.product = p.id
LEFT JOIN
    suppliers s ON s.supplier_id = ps.supplier
GROUP BY
    p.id
";

try {
    // Prepare and execute the query for the product table.
    $stmt_table = $conn->prepare($product_table_query);
    $stmt_table->execute();
    // Fetch all results for the table as an associative array.
    $products_for_table = $stmt_table->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors gracefully for the product table.
    error_log("Error fetching products for table: " . $e->getMessage());
    $products_for_table = []; // Ensure $products_for_table is an empty array on error.
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="products.css"/>
    <link rel="stylesheet" href="tables.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* New CSS additions for categories-sidebar and its container */
        /* This ensures the categories sidebar stays fixed while scrolling */
        .categories-sidebar {
            position: sticky;
            top: 0; /* Sticks to the top of the viewport when scrolling */
            align-self: flex-start; /* Ensures it aligns to the start of the cross-axis in a flex container */
            height: fit-content; /* Prevents it from taking full height and allows it to stick properly */
            z-index: 10; /* Optional: ensures it stays above other content if overlaps occur */

            /* The existing inline styles are still applied from your HTML:
               background-color: #343a40;
               padding: 20px 15px;
               color: #f8f9fa;
               width: 200px;
               box-shadow: 2px 0 5px rgba(0,0,0,0.2);
            */
        }

        /* For .categories-sidebar to be effectively sticky and positioned "katabi" (beside)
           the main sidebar, its direct parent container (.page) must be a flexbox or grid container.
           Please ensure these styles are applied to your .page element's CSS (e.g., in home.css or another CSS file).
           If they are not present, add them to allow the sticky behavior to work as expected. */
        .page {
            display: flex;
            align-items: flex-start; /* Aligns items to the top of the cross axis */
            min-height: 100vh; /* Ensures the page has enough height to scroll and trigger sticky */
        }

        /* Category link active state (you can customize this further in products.css if you wish) */
        .categories-sidebar .category-link.active {
            font-weight: bold;
            background-color: #495057; /* Slightly darker background for active link */
            border-radius: 4px;
            padding: 8px 10px; /* Adjust padding to match and ensure visual consistency */
        }
        /* Hover effect for category links */
        .categories-sidebar .category-link:hover {
            background-color: #495057; /* Darker on hover */
        }

        /* Specific styles for the categories-sidebar itself, to override/complement inline styles */
        .categories-sidebar {
            background-color: #343a40; /* Dark background similar to main sidebar */
            padding: 20px 15px;
            color: #f8f9fa;
            width: 200px; /* Example width, adjust as needed or let CSS handle */
            box-shadow: 2px 0 5px rgba(0,0,0,0.2); /* Subtle shadow */
        }
        .categories-sidebar h3 {
            margin-top: 0;
            color: #f8f9fa;
            padding-bottom: 10px;
            border-bottom: 1px solid #444;
            margin-bottom: 15px;
        }
        .categories-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .categories-sidebar ul li {
            margin-bottom: 10px;
        }
        .categories-sidebar .category-link {
            color: #f8f9fa;
            text-decoration: none;
            display: block;
            padding: 8px 0;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
          .user-name {
    background-color: #343a40;
    padding: 15px 10px;
    border-bottom: 1px solid #444;
    margin-bottom: 20px;
    text-align: left;
    display: flex;
    align-items: center;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.2s ease;
}

.user-name:hover {
    background-color: #495057;
}

.user-name a.profile-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #f8f9fa;
    font-size: 1.15rem;
    font-weight: 600;
    width: 100%;
}

.user-name img.profile-pic-icon {
    height: 35px;
    width: 35px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 12px;
    border: 2px solid #ffc107;
    box-shadow: 0 0 5px rgba(0,0,0,0.3);
}

.user-name span.first-name,
.user-name span.last-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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
            <section id="productsPage" class="active">

                <a href="products.php" data-page="products.php">
                    <img src="images/iconBack.png" alt="Back Icon" class="backButton">
                </a>

                <nav class="tablesOption">
                    <div class="tablesOptionContainer">
                        <h3>List of Tables <br><p>Manage all your product-related data here.</p></h3>
                        <div class="tablesMenu">
                            <li class="tablesItem">
                                <a href="view_products.php" data-page="view_products.php" class="tablesLinks">Products</a>
                            </li>
                            <li class="tablesItem">
                                <a href="view_stock.php" data-page="view_stock.php" class="tablesLinks">Stock</a>
                            </li>
                            <li class="tablesItem">
                                <a href="view_sales.php" data-page="view_sales.php" class="tablesLinks">Sales</a>
                            </li>
                            <li class="tablesItem">
                                <a href="view_order.php" data-page="view_order.php" class="tablesLinks">Orders</a>
                            </li>
                        </div>
                    </div>
                </nav>

                <br><br><br>
                
                <h3 style="text-align:center;">Product Overview</h3>
                <p style="text-align:center;">All products are listed here with their core details for easy review.</p><br>
                <a href="add_product.php" data-page="add_product.php" class="add-button" style="display: block; width: max-content; margin: 0 auto;">Add Item</a>
                
                <div class="product-grid">
                    <?php if (empty($products_for_grid)): ?>
                        <p style="flex-direction: row; text-align: center; color: #151515;">No products found for grid display matching your criteria.</p>
                    <?php else: ?>
                        <?php foreach ($products_for_grid as $product): ?>
                            <a href="product_detail.php?id=<?= htmlspecialchars($product['id'] ?? '') ?>" class="product-card">
                                <div class="product-wrapper">
                                    <?php if (isset($product['price']) && $product['price'] == 1): ?>
                                        <div class="product-label">New User Exclusive</div>
                                    <?php endif; ?>
                                    <div class="product-img">
                                        <?php
                                        // Check if 'img' key exists and is not empty, otherwise use a placeholder.
                                        $image_src = isset($product['img']) && !empty($product['img']) ? 'uploads/products/' . htmlspecialchars($product['img']) : 'https://placehold.co/160x160/cccccc/333333?text=No+Image';
                                        $product_name_alt = htmlspecialchars($product['product_name'] ?? 'Product Image');
                                        ?>
                                        <img src="<?= $image_src ?>" alt="<?= $product_name_alt ?>" onerror="this.onerror=null;this.src='https://placehold.co/160x160/cccccc/333333?text=Image+Error';">
                                    </div>
                                </div>
                                <div class="product-info">
                                    <div class="product-name"><?= htmlspecialchars($product['product_name'] ?? 'Untitled Product') ?></div>
                                    <div class="product-stock">stock: <?= htmlspecialchars($product['stock'] ?? 'Untitled Product') ?></div>
                                    <div class="product-price">₱<?= htmlspecialchars(number_format($product['price'] ?? 0, 2)) ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    
    <script src="script.js"></script>
    <script>
        $(document).ready(function() {
            // Function to update URL with current search term and category
            function updateProductGridUrl() {
                const searchTerm = $('#productGridSearchInput').val();
                // Get the category from the currently active category link or from the URL if no link is active (initial load)
                const activeCategoryLink = $('.categories-sidebar .category-link.active');
                let selectedCategory = '';
                if (activeCategoryLink.length > 0) {
                    selectedCategory = activeCategoryLink.data('category');
                } else {
                    // Fallback to URL parameter if no active link (e.g., direct access with category in URL)
                    const urlParams = new URLSearchParams(window.location.search);
                    selectedCategory = urlParams.get('category') || '';
                }

                const url = new URL(window.location.origin + window.location.pathname);
                if (searchTerm) {
                    url.searchParams.set('search', searchTerm);
                } else {
                    url.searchParams.delete('search'); // Remove search param if empty
                }

                if (selectedCategory) {
                    url.searchParams.set('category', selectedCategory);
                } else {
                    url.searchParams.delete('category'); // Remove category param if 'All Products' or empty
                }
                
                window.location.href = url.toString();
            }

            // Event listener for the "Apply Search" button
            // Note: There is no 'productGridSearchInput' or 'applyGridFilterBtn' in your current HTML.
            // If you add a search input specifically for the product grid, ensure it has the ID 'productGridSearchInput'
            // and a button with ID 'applyGridFilterBtn' for these JS events to work.
            $('#applyGridFilterBtn').on('click', function() {
                updateProductGridUrl();
            });

            // Event listener for Enter key in the search input
            $('#productGridSearchInput').on('keypress', function(e) {
                if (e.which === 13) { // Enter key pressed
                    updateProductGridUrl();
                }
            });

            // Event listener for clicks on category items
            $('.categories-sidebar .category-link').on('click', function(event) {
                event.preventDefault(); // Prevent default link behavior

                // Remove 'active' class from all category links
                $('.categories-sidebar .category-link').removeClass('active');
                // Add 'active' class to the clicked category link
                $(this).addClass('active');

                // Update the URL based on the new active category and existing search term
                updateProductGridUrl();
            });

            // Existing JavaScript for global search (from your original code)
            const mainSearchInput = document.getElementById('searchInput');
            const mainSearchResults = document.getElementById('searchResults');

            if (mainSearchInput && mainSearchResults) {
                mainSearchInput.addEventListener('input', function() {
                    // Your global search logic here, if different from the product grid filter
                    // This is currently a placeholder as per your original file.
                });
            }
        });
    </script>
</body>
</html>