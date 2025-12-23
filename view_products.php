<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $user = $_SESSION['user'];
     if(!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit; // Important: Exit after redirect
    $_SESSION['table'] = 'users';
  $_user = $_SESSION['user'];
     }

     require 'database/connect.php'; // include your PDO connection

// Fetch products from the database
try {
    $stmt = $conn->query("SELECT * FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="tables.css"/>
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

            <nav class="tablesOption">
                <div class="tablesOptionContainer">
                    <h3>List of Tables <br><p>Manage all your product-related data here.</p></h3>
                    <div class="tablesMenu">
                        <li class="tablesItem">
                            <a href="view_products.php" data-page="view_products.php" class="tablesLinks" style="background-color: #151515;color: white;">Products</a>
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

                <h3>Products Table <br><p>Complete list of product information.</p></h3><br>
                <a href="add_product.php" data-page="add_product.php" class="add-button" id="showUserFormBtn">Add Item</a><br>
                <div class="section_content">
                    <div class="tableContainer">
                        <?php if (!empty($products)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Weight</th>
                                    <th>Description</th>
                                    <th>Ingredients</th>
                                    <th>Cost</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['id']) ?></td>
                                    <td>
                                        <?php if (!empty($product['img'])): ?>
                                            <img src="uploads/products/<?= htmlspecialchars($product['img']) ?>" alt="Product Image" width="50">
                                        <?php else: ?>
                                            No image
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                                    <td><?= htmlspecialchars($product['brand_name']) ?></td>
                                    <td>₱<?= htmlspecialchars($product['price']) ?></td>
                                    <td><?= htmlspecialchars($product['category']) ?></td>
                                    <td><?= htmlspecialchars($product['product_type']) ?></td>
                                    <td><?= htmlspecialchars($product['weight']) ?></td>
                                    <td><?= htmlspecialchars($product['description']) ?></td>
                                    <td><?= htmlspecialchars($product['ingredients']) ?></td>
                                    <td>₱<?= htmlspecialchars($product['cost']) ?></td>
                                    <td><?= htmlspecialchars($product['created_by']) ?></td>
                                    <td><?= htmlspecialchars($product['created_at']) ?></td>
                                    <td><?= htmlspecialchars($product['updated_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <p>No products found.</p>
                        <?php endif; ?>
                    </div>
                </div>        

               
            </section>
        </main>
    </div>
        
    <script src="script.js"></script>

</body>
</html>