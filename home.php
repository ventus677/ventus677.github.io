<?php
//Start the session.
session_start();
$users = include('database/show_users.php'); // Assuming this file fetches all users, though not directly used on this page
$user = $_SESSION['user']; // Get the logged-in user's data from the session

if(!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit; // Important: Exit after redirect
}

// Include the database connection file.
// This file should establish a PDO connection and assign it to the $conn variable.
// It's included here once at the top to make the connection available globally for the page.
include('database/connect.php');

// --- START: NEW RETURN/REFUND NOTIFICATION LOGIC ---
$pending_rr_count = 0;
$rr_notification_query = "SELECT COUNT(*) AS total_pending FROM returns_refunds WHERE status = 'PENDING'";

try {
    // Execute the query to count PENDING Return/Refund requests
    $stmt_rr = $conn->query($rr_notification_query);
    $result_rr = $stmt_rr->fetch(PDO::FETCH_ASSOC);
    $pending_rr_count = $result_rr['total_pending'] ?? 0;
} catch (PDOException $e) {
    // Log error but allow page to load with 0 count
    error_log("Error fetching pending RR count: " . $e->getMessage());
    $pending_rr_count = 0; 
}
// --- END: NEW RETURN/REFUND NOTIFICATION LOGIC ---


// --- Product Grid Data Fetch ---
// This section fetches data specifically for the product grid display.
$product_grid_query = "SELECT * FROM products";
try {
    // Execute the query for the product grid.
    $stmt_grid = $conn->query($product_grid_query);
    // Fetch all results for the grid as an associative array.
    $products_for_grid = $stmt_grid->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors gracefully for the product grid.
    error_log("Error fetching products for grid: " . $e->getMessage());
    $products_for_grid = []; // Ensure $products_for_grid is an empty array on error.
}

// --- Product Table Data Fetch (This section is not currently displayed on home.php, but included if needed elsewhere) ---
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
    <title>Home - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="products.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
</head>


<body>
    <header>
        <a href="index.php" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
            <h3>&nbsp;&nbsp;Keepkit</h3>
        </a>
        <div class="right-element">
            <a href="database/logout.php" ><img src= "images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>

        <main class="main">
            <section id="homePage" class="active">
                <h1>Welcome to your inventory!</h1>
                <p>Track, manage, and optimize your products.</p>
                <div class="cards">
                    <?php if ($is_admin): ?>
                    <a class="card" id="cardDashboard" data-page="dashboard.php">
                        <img src="images/iconDashboard.png" alt="Dashboard Icon" class="icon">
                        <span>Dashboard</span>
                    </a>
                    <a class="card" id="cardReports" data-page="reports.php">
                        <img src="images/iconReports.png" alt="Reports Icon" class="icon">
                        <span>Reports</span>
                    </a>
                    <?php endif; ?>
                    <a class="card" id="cardProducts" data-page="products.php">
                        <img src="images/iconProducts.png" alt="Products Icon" class="icon">
                        <span>Products</span>
                    </a>
                    <a class="card" id="cardSuppliers" data-page="supplier.php">
                        <img src="images/iconSuppliers.png" alt="Users Icon" class="icon">
                        <span>Suppliers</span>
                    </a>
                    <a class="card" id="cardUsers" data-page="users.php">
                        <img src="images/iconUsers.png" alt="Users Icon" class="icon">
                        <span>Users</span>
                    </a>
                    <a class="card" id="cardCustomers" data-page="customer_list.php">
                        <img src="images/iconCustomers.png" alt="Customers Icon" class="icon">
                        <span>Customers</span>
                    </a>
                </div>
            </section>

            <br><br><br><br>

            <section id="productsPage" class="active">
                <h1 style="text-align: center;">Product Overview</h1>
                <p style="text-align: center;"><br>All products are listed here with their core details for easy review.</p>
                <br>
                <div class="search-container" style="text-align: center;">
                    <div style="display: inline-block;">
                        <input type="search" id="searchInput" placeholder="Search..." autocomplete="off" style="display: block; margin-bottom: 10px;">
                        <div id="searchResults"></div>
                    </div>
                </div>

                <div class="product-grid">
                    <?php if (empty($products_for_grid)): ?>
                        <p style="grid-column: 1 / -1; text-align: center; color: #555;">No products found for grid display.</p>
                    <?php else: ?>
                        <?php foreach ($products_for_grid as $product): ?>
                            <a href="product_detail_home.php?id=<?= htmlspecialchars($product['id'] ?? '') ?>" class="product-card">
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

</body>
</html>