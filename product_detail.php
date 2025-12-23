<?php
// Start the session.
session_start();

// Check if the user is logged in. 
$_SESSION['table'] = 'products';
$user = $_SESSION['user'] ?? null;
require_once 'database/connect.php'; // Assuming this connects to your database

if (!$user) {
    header('Location: index.php');
    exit;
}

// Include the database connection file.
include('database/connect.php');

$product = null; // Initialize product variable

// Check if a product ID is provided in the URL.
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Prepare and execute the query to fetch product details by ID.
    $query = "
        SELECT
            p.*,
            u.first_name AS created_by_first_name,
            u.last_name AS created_by_last_name,
            GROUP_CONCAT(s.supplier_name SEPARATOR ', ') AS supplier_names
        FROM
            products p
        LEFT JOIN
            users u ON p.created_by = u.id
        LEFT JOIN
            productsuppliers ps ON ps.product = p.id
        LEFT JOIN
            suppliers s ON s.supplier_id = ps.supplier
        WHERE
            p.id = :product_id
        GROUP BY
            p.id
    ";

    try {
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            // Product not found, redirect or show an error.
            header('Location: view_products.php?error=product_not_found');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching product details: " . $e->getMessage());
        header('Location: view_products.php?error=db_error');
        exit;
    }
} else {
    // No product ID provided, redirect back to the products list.
    header('Location: view_products.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['product_name'] ?? 'Product Details') ?> - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="products.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

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

            <a href="view_productOverview.php" data-page="view_productOverview.php">
                <img src="images/iconBack.png" alt="Back Icon" class="backButton">
            </a> 

            <h3 style="text-align: center;">Product Details</h3>
            <p style="text-align: center; margin-bottom: 20px;">Here are the detailed specifications for this product.</p>           

            <?php if ($product): ?>
                <div class="product-detail-container">
                    <div class="product-detail-image">
                        <?php
                        $image_src = isset($product['img']) && !empty($product['img']) ? 'uploads/products/' . htmlspecialchars($product['img']) : 'https://placehold.co/300x300/cccccc/333333?text=No+Image';
                        $product_name_alt = htmlspecialchars($product['product_name'] ?? 'Product Image');
                        ?>
                        <img src="<?= $image_src ?>" alt="<?= $product_name_alt ?>" onerror="this.onerror=null;this.src='https://placehold.co/300x300/cccccc/333333?text=Image+Error';">
                    </div>
                    <div class="product-detail-info">
                        <h1><?= htmlspecialchars($product['product_name'] ?? 'N/A') ?></h1>
                        <p><strong>Brand:</strong> <?= htmlspecialchars($product['brand_name'] ?? 'N/A') ?></p>
                        <p><strong>Category:</strong> <?= htmlspecialchars($product['category'] ?? 'N/A') ?></p>
                        <p><strong>Product Type:</strong> <?= htmlspecialchars($product['product_type'] ?? 'N/A') ?></p>
                        <p><strong>Weight:</strong> <?= htmlspecialchars($product['weight'] ?? 'N/A') . ' ' . htmlspecialchars($product['choose'] ?? 'N/A')?></p>
                        <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($product['description'] ?? 'N/A')) ?></p>
                        <p><strong>Ingredients:</strong> <?= htmlspecialchars($product['ingredients'] ?? 'N/A') ?></p>
                        <p><strong>Cost: </strong> ₱<?= htmlspecialchars($product['cost'] ?? 'N/A') ?></p>
                        <p><strong>Stock Available:</strong> 
                            <?= htmlspecialchars($product['stock'] > 0 ? $product['stock'] : 'Not Available') ?>
                        </p>
                        <p><strong>Created By:</strong> <?= htmlspecialchars($product['created_by_first_name'] . ' ' . $product['created_by_last_name'] ?? 'Unknown') ?></p>
                        <p><strong>Last Updated:</strong> <?= htmlspecialchars($product['updated_at'] ?? 'N/A') ?></p>
                        <p><strong>Suppliers:</strong>
                            <?php
                            if (!empty($product['supplier_names'])) {
                                echo htmlspecialchars($product['supplier_names']);
                            } else {
                                echo 'No suppliers assigned.';
                            }
                            ?>
                        </p>
                        <div class="product-detail-price">₱<?= htmlspecialchars(number_format($product['price'] ?? 0, 2)) ?></div>
                    </div>
                </div>
                <div class="button-container">
                    <a href="edit_product.php?id=<?= htmlspecialchars($product['id']) ?>" class="action-button edit-button">
                        <i class="fas fa-edit"></i> Edit Product
                    </a>
                    <a href="database/delete_product.php?id=<?= htmlspecialchars($product['id']) ?>" class="action-button delete-button" onclick="return confirm('Are you sure you want to delete this product?');">
                        <i class="fas fa-trash-alt"></i> Delete Product
                    </a>
                </div>
            </section>
        </main>
    </div>
    <script src="script.js"></script>
    </script>
</body>
<?php endif; ?>
</html>