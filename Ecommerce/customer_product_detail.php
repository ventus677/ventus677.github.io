<?php
session_start(); // Dapat ito ang pinakaunang linya
include('../database/connect.php'); // Siguraduhin na tama ang path

// =========================================================
// PHP LOGIC: Kuhanin ang Product Details, Ratings, at Reviews
// =========================================================
$product = null;
$product_id = $_GET['id'] ?? null;
$average_rating = 0;
$total_reviews = 0;
$reviews = [];

// Helper function to generate star HTML
function generate_stars($rating) {
    $html = '';
    $rating = round($rating * 2) / 2; // Round to nearest half
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 == $rating) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star"></i>';
        }
    }
    return $html;
}

// Helper function to obscure customer name
function get_obfuscated_name($firstName, $lastName) {
    if (empty($firstName) || empty($lastName)) {
        return "Anonymous Customer";
    }
    // First letter, three asterisks, last letter of first name
    $obf_first = strtoupper(substr($firstName, 0, 1)) . '***' . strtolower(substr($firstName, -1));
    // First letter of last name
    $obf_last = strtoupper(substr($lastName, 0, 1)) . '.';
    return $obf_first . ' ' . $obf_last;
}


if ($product_id) {
    try {
        // 1. Kuhanin ang Product Details
        $stmt = $conn->prepare("
            SELECT
                p.id,
                p.product_name,
                p.brand_name,
                p.category,
                p.product_type,
                p.weight,
                p.description,
                p.ingredients,
                p.price,
                p.stock,
                p.units_sold, 
                p.img,
                GROUP_CONCAT(s.supplier_name SEPARATOR ', ') AS supplier_names
            FROM
                products p
            LEFT JOIN
                productsuppliers ps ON ps.product = p.id
            LEFT JOIN
                suppliers s ON s.supplier_id = ps.supplier
            WHERE
                p.id = ?
            GROUP BY
                p.id
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;

        if (!$product) {
            $_SESSION['message'] = 'Product not found.';
            header('Location: user_products.php');
            exit;
        }

        // 2. Kuhanin ang Average Rating at Total Reviews
        $stmt_rating = $conn->prepare("
            SELECT 
                COUNT(id) AS total_reviews, 
                AVG(rating) AS average_rating 
            FROM 
                product_reviews 
            WHERE 
                product_id = ?
        ");
        $stmt_rating->execute([$product_id]);
        $rating_data = $stmt_rating->fetch(PDO::FETCH_ASSOC);
        
        $total_reviews = (int)($rating_data['total_reviews'] ?? 0);
        $average_rating = (float)($rating_data['average_rating'] ?? 0);

        // 3. Kuhanin ang Reviews at Images (Wala nang limit)
        $stmt_reviews = $conn->prepare("
            SELECT 
                pr.id AS review_id,
                pr.rating, 
                pr.comment, 
                pr.created_at,
                c.first_name, 
                c.last_name,
                GROUP_CONCAT(ri.image_path ORDER BY ri.id ASC SEPARATOR '|||') AS image_paths
            FROM 
                product_reviews pr
            JOIN 
                users c ON c.id = pr.user_id
            LEFT JOIN
                review_images ri ON ri.review_id = pr.id
            WHERE 
                pr.product_id = ?
            GROUP BY
                pr.id
            ORDER BY 
                pr.created_at DESC
        ");
        $stmt_reviews->execute([$product_id]);
        $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error fetching product detail or reviews: " . $e->getMessage());
        $_SESSION['message'] = 'Error loading product details.';
    }
} else {
    header('Location: user_products.php');
    exit;
}

// Kunin ang kasalukuyang cart item count
$total_items_in_cart = $_SESSION['total_items_in_cart'] ?? 0;

// I-pass ang login status sa JavaScript batay sa `users` table structure
$is_logged_in = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
$user_role = $is_logged_in ? ($_SESSION['user']['role'] ?? 'guest') : 'guest';

// Ang parehong 'user' at 'customer' role ay pinapayagang mag-add to cart
$can_add_to_cart = ($user_role === 'user' || $user_role === 'customer');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['product_name'] ?? 'Product Detail') ?> - Keepkit Customer</title>
    <link rel="icon" type="image/png" href="../images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="home.css">
    <style>
        /* ======================================= */
        /* LIGHT MODE (DEFAULT) STYLES - Enhanced  */
        /* ======================================= */
        :root {
            --primary-color: #e67e22; /* Orange accent */
            --secondary-color: #3498db; /* Blue for Cart */
            --background-color: #f4f7f6;
            --card-background: #ffffff;
            --text-color: #2c3e50;
            --light-text: #7f8c8d;
            --border-color: #ecf0f1;
            --shadow: 0 6px 20px rgba(0,0,0,0.08);
            --review-alert-bg: #ffebeb;
            --review-alert-border: #f5c2c2;
            --review-alert-text: #842029;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            padding-top: 70px;
        }
        .product-detail-container {
            padding: 25px 0;
            margin: 0 auto;
            max-width: 1000px;
        }
        .product-detail-content {
            background-color: var(--card-background);
            border-radius: 12px;
            box-shadow: var(--shadow);
            display: flex;
            flex-wrap: wrap;
            padding: 30px;
            gap: 40px;
            margin: 20px;
        }
        .product-image-section {
            flex: 1;
            min-width: 300px;
            max-width: 45%;
            text-align: center;
        }
        .product-image-section img {
            width: 100%;
            height: auto;
            max-height: 450px;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .product-info-section {
            flex: 1.5;
            min-width: 350px;
            padding: 10px 0;
        }
        .product-info-section h1 {
            font-size: 2.2em;
            margin-top: 0;
            margin-bottom: 5px;
            color: var(--text-color);
            font-weight: 700;
        }
        .product-info-section .brand-name {
            font-size: 1.1em;
            color: var(--light-text);
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .price-rating-section {
            display: flex;
            align-items: center;
            background-color: #fef0f0; 
            padding: 15px 20px;
            margin: 10px 0 25px 0;
            border-radius: 4px;
        }
        .product-info-section .price {
            font-size: 2.5em;
            font-weight: 800;
            color: var(--primary-color);
            margin-right: 30px;
            margin-bottom: 0;
        }
        .rating-summary {
            display: flex;
            flex-direction: column; 
            justify-content: center;
            font-size: 1em;
            color: var(--light-text);
            height: 100%; 
        }
        .star-rating {
            display: flex;
            align-items: center; 
            color: #ffc107;
            font-size: 1.2em;
            margin-bottom: 5px;
        }
        .star-rating .avg-score {
            font-weight: 700;
            color: var(--primary-color);
            margin-right: 5px;
            font-size: 1.1em;
        }
        .star-rating i {
            font-size: 1em;
        }
        .rating-summary a {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
        }
        .rating-summary a:hover {
            text-decoration: underline;
        }

        .detail-group {
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px dashed var(--border-color);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .detail-group:last-child {
            border-bottom: none;
        }
        .detail-group strong {
            flex: 0 0 100px; 
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0;
        }
        .detail-group span {
            flex: 1; 
            color: #555;
            font-size: 0.95em;
            white-space: pre-wrap; 
            word-break: break-word; 
        }

        .detail-group.description {
             display: block; 
        }
        .detail-group.description strong {
            display: block;
            margin-bottom: 5px;
            flex: none;
        }
        .detail-group.description span {
            display: block;
        }
        .stock-status {
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px 0;
            border-top: 1px solid #f0f0f0;
        }
        .stock-status span {
            padding-left: 8px;
            font-weight: 800;
        }
        .stock-available {
            color: #2ecc71;
        }
        .stock-not-available {
            color: #e74c3c;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        .quantity-control label {
            margin-right: 15px;
            font-weight: 600;
            color: var(--text-color);
        }
        .quantity-control input[type="number"] {
            width: 80px;
            padding: 10px;
            border: 2px solid #bdc3c7;
            border-radius: 6px;
            text-align: center;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        .quantity-control input[type="number"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .action-buttons button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .action-buttons button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            opacity: 0.7;
            transform: none;
            box-shadow: none;
        }

        .add-to-cart-btn {
            background-color: var(--secondary-color);
            color: white;
        }
        .add-to-cart-btn:hover:not(:disabled) {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(52, 152, 219, 0.3);
        }
        .buy-now-btn {
            background-color: var(--primary-color);
            color: white;
        }
        .buy-now-btn:hover:not(:disabled) {
            background-color: #d35400;
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(230, 126, 34, 0.4);
        }
        
        .reviews-container {
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin: 20px;
            margin-top: 40px;
        }
        .reviews-container h2 {
            font-size: 1.5em;
            color: var(--text-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .review-card {
            border-bottom: 1px solid var(--border-color);
            padding: 15px 0;
        }
        .review-card:last-child {
            border-bottom: none;
        }
        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .review-header .user-name {
            font-weight: 600;
            margin-right: 15px;
            color: var(--text-color);
        }
        .review-star-rating {
            color: #ffc107;
            font-size: 0.9em;
        }
        .review-body {
            color: var(--light-text);
            margin-bottom: 10px;
            word-wrap: break-word;
        }
        .review-meta {
            font-size: 0.8em;
            color: #aaa;
        }
        .review-images {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .review-images img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            cursor: zoom-in;
        }
        
        @media (max-width: 800px) {
            .product-detail-content {
                flex-direction: column;
                padding: 20px;
                gap: 20px;
                margin: 10px;
            }
            .product-image-section, .product-info-section {
                max-width: 100%;
                min-width: 100%;
                padding: 0;
            }
            .product-image-section img {
                max-height: 300px;
            }
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            .action-buttons button {
                width: 100%;
            }
        }
        
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        body.dark-mode .product-detail-content {
            background-color: #2d2d2d;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }
        body.dark-mode .product-info-section h1 {
            color: #f1c40f;
        }
        body.dark-mode .product-info-section .brand-name {
            color: #aaaaaa;
        }
        body.dark-mode .price-rating-section {
            background-color: #3a3a3a;
        }
        body.dark-mode .product-info-section .price {
            color: #f1c40f;
        }
        body.dark-mode .rating-summary {
            color: #aaaaaa;
        }
        body.dark-mode .star-rating .avg-score {
            color: #f1c40f;
        }
        body.dark-mode .rating-summary a {
            color: #3498db;
        }
        body.dark-mode .detail-group {
            border-bottom: 1px dashed #444;
        }
        body.dark-mode .detail-group strong {
            color: #e0e0e0;
        }
        body.dark-mode .detail-group span {
            color: #bbbbbb;
        }
        body.dark-mode .stock-status {
            border-top: 1px solid #333;
        }
        body.dark-mode .stock-available {
            color: #2ecc71;
        }
        body.dark-mode .stock-not-available {
            color: #e74c3c;
        }
        body.dark-mode .quantity-control label {
            color: #e0e0e0;
        }
        body.dark-mode .quantity-control input[type="number"] {
            background-color: #3a3a3a;
            color: #e0e0e0;
            border: 2px solid #555;
        }
        body.dark-mode .quantity-control input[type="number"]:focus {
            border-color: #f1c40f;
        }
        body.dark-mode .add-to-cart-btn {
            background-color: #2980b9;
        }
        body.dark-mode .add-to-cart-btn:hover:not(:disabled) {
            background-color: #3498db;
            box-shadow: 0 6px 10px rgba(41, 128, 185, 0.3);
        }
        body.dark-mode .buy-now-btn {
            background-color: #d35400;
        }
        body.dark-mode .buy-now-btn:hover:not(:disabled) {
            background-color: #e67e22;
            box-shadow: 0 6px 10px rgba(211, 84, 0, 0.4);
        }
        body.dark-mode .action-buttons button:disabled {
            background-color: #555;
        }
        body.dark-mode .reviews-container {
            background-color: #2d2d2d;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }
        body.dark-mode .reviews-container h2 {
            color: #f1c40f;
            border-bottom-color: #f1c40f;
        }
        body.dark-mode .review-card {
            border-bottom: 1px solid #444;
        }
        body.dark-mode .review-header .user-name {
            color: #e0e0e0;
        }
        body.dark-mode .review-body {
            color: #bbbbbb;
        }
        body.dark-mode .review-meta {
            color: #777;
        }
        body.dark-mode .review-images img {
            border-color: #555;
        }
    </style>
</head>
<body>
    <?php include('header_public.php'); ?>

    <div class="product-detail-container">
        <div class="product-detail-content">
            <?php if ($product): ?>
                <div class="product-image-section">
                    <?php
                        $image_src = '../uploads/products/' . htmlspecialchars($product['img']);
                        if (empty($product['img']) || !file_exists($image_src)) {
                            $image_src = 'https://placehold.co/400x400/cccccc/333333?text=No+Image';
                        }
                    ?>
                    <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" onerror="this.onerror=null;this.src='https://placehold.co/400x400/cccccc/333333?text=Image+Error';">
                </div>
                <div class="product-info-section">
                    <h1><?= htmlspecialchars($product['product_name']) ?></h1>
                    <span class="brand-name">Brand: <?= htmlspecialchars($product['brand_name'] ?? 'N/A') ?></span>
                    
                    <div class="price-rating-section">
                        <span class="price">₱<?= htmlspecialchars(number_format($product['price'], 2)) ?></span>
                        <div class="rating-summary">
                            <div class="star-rating">
                                <span class="avg-score"><?= htmlspecialchars(number_format($average_rating, 1)) ?></span>
                                <?= generate_stars($average_rating) ?>
                            </div>
                            (<?= htmlspecialchars($total_reviews) ?> Ratings) <a href="#product-reviews">View all reviews</a>
                        </div>
                    </div>

                    <div class="detail-group">
                        <strong>Category:</strong>
                        <span><?= htmlspecialchars($product['category'] ?? 'N/A') ?></span>
                    </div>

                    <div class="detail-group">
                        <strong>Product Type:</strong>
                        <span><?= htmlspecialchars($product['product_type'] ?? 'N/A') ?></span>
                    </div>

                    <div class="detail-group">
                        <strong>Weight:</strong>
                        <span><?= htmlspecialchars($product['weight'] ?? 'N/A') ?></span>
                    </div>

                    <div class="detail-group">
                        <strong>Description:</strong>
                        <span><?= nl2br(htmlspecialchars($product['description'] ?? 'N/A')) ?></span>
                    </div>

                    <div class="detail-group">
                        <strong>Ingredients:</strong>
                        <span><?= htmlspecialchars($product['ingredients'] ?? 'N/A') ?></span>
                    </div>

                    <div class="detail-group">
                        <strong>Suppliers:</strong>
                        <span><?php
                            if (!empty($product['supplier_names'])) {
                                echo htmlspecialchars($product['supplier_names']);
                            } else {
                                echo 'No suppliers assigned.';
                            }
                            ?></span>
                    </div>
                    
                    <div class="detail-group">
                        <strong>Units Sold:</strong>
                        <span><?= htmlspecialchars(number_format($product['units_sold'] ?? 0)) ?> units</span>
                    </div>
                    <div class="stock-status">
                        Stocks Available:
                        <span class="<?= $product['stock'] > 0 ? 'stock-available' : 'stock-not-available' ?>">
                            <?= htmlspecialchars($product['stock'] > 0 ? $product['stock'] : 'Not Available') ?>
                        </span>
                    </div>

                    <div class="quantity-control">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" value="1" min="1" max="<?= htmlspecialchars($product['stock']) ?>">
                    </div>

                    <div class="action-buttons">
                        <button id="addToCartBtn" class="add-to-cart-btn" data-product-id="<?= htmlspecialchars($product['id']) ?>" <?= !$can_add_to_cart && $is_logged_in ? 'disabled' : '' ?>>
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                        <button id="buyNowBtn" class="buy-now-btn" data-product-id="<?= htmlspecialchars($product['id']) ?>" <?= !$can_add_to_cart && $is_logged_in ? 'disabled' : '' ?>>
                            <i class="fas fa-shopping-cart"></i> Buy Now
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="product-info-section" style="text-align: center;">
                    <h2>Product not found.</h2>
                    <p>The product details could not be loaded.</p>
                    <a href="user_products.php" class="buy-now-btn" style="text-decoration: none; display: inline-block; padding: 10px 20px;">Back to Shop</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="product-reviews" class="reviews-container">
            <h2><i class="fas fa-comments"></i> Product Ratings & Reviews (<?= htmlspecialchars($total_reviews) ?>)</h2>

            <div id="reviewsList">
                <?php if ($total_reviews > 0): ?>
                    <?php foreach ($reviews as $review): 
                        $image_paths = !empty($review['image_paths']) ? explode('|||', $review['image_paths']) : [];
                        $user_name = get_obfuscated_name($review['first_name'], $review['last_name']);
                    ?>
                        <div class="review-card">
                            <div class="review-header">
                                <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
                                <div class="review-star-rating">
                                    <?= generate_stars($review['rating']) ?>
                                </div>
                            </div>
                            <div class="review-body">
                                <?= nl2br(htmlspecialchars($review['comment'])) ?>
                            </div>
                            <?php if (!empty($image_paths)): ?>
                                <div class="review-images">
                                    <?php foreach ($image_paths as $path): ?>
                                        <?php if (!empty($path)): ?>
                                            <img src="<?= htmlspecialchars('../' . $path) ?>" alt="Review Image" loading="lazy">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="review-meta">Reviewed on <?= date('Y-m-d', strtotime($review['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; padding: 15px; color: var(--light-text);">
                        Be the first to review this product!
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.getElementById('quantity');
            const addToCartBtn = document.getElementById('addToCartBtn');
            const buyNowBtn = document.getElementById('buyNowBtn');
            const cartItemCountBadge = document.getElementById('cartCountBadge'); 
            
            // I-pass ang login status mula sa PHP patungo sa JavaScript
            const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;

            const availableStock = <?= json_encode($product['stock'] ?? 0) ?>;
            if (quantityInput && availableStock <= 0) {
                quantityInput.value = 0;
                quantityInput.setAttribute('disabled', 'disabled');
                if (addToCartBtn) addToCartBtn.setAttribute('disabled', 'disabled');
                if (buyNowBtn) buyNowBtn.setAttribute('disabled', 'disabled');
            } else if (quantityInput) {
                quantityInput.setAttribute('max', availableStock);
                quantityInput.addEventListener('input', function() {
                    let value = parseInt(this.value);
                    if (value > availableStock) {
                        this.value = availableStock;
                        alert('Ang dami ay lumampas sa available na stock.');
                    }
                    if (value < 1 || isNaN(value)) {
                        this.value = 1; 
                    }
                });
            }

            function updateCartCountBadgeLocal(count) {
                if (cartItemCountBadge) {
                    cartItemCountBadge.textContent = count;
                }
            }
            
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', async function() {
                    if (!isLoggedIn) {
                        alert('You must be logged in to add an item to cart.');
                        window.location.href = 'user_auth.php';
                        return;
                    }

                    const productId = this.dataset.productId;
                    const quantity = parseInt(quantityInput.value);

                    if (quantity <= 0 || isNaN(quantity)) {
                        alert('Please enter a valid quantity.');
                        return;
                    }

                    if (quantity > availableStock) {
                        alert('The quantity exceeds available stock.');
                        return;
                    }

                    this.disabled = true; 
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

                    try {
                        const formData = new URLSearchParams();
                        formData.append('product_id', productId);
                        formData.append('quantity', quantity);

                        const response = await fetch('add_to_cart.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData.toString()
                        });
                        const data = await response.json();
                        
                        this.disabled = false;
                        this.innerHTML = originalText;

                        if (data.success) {
                            alert(data.message);
                            updateCartCountBadgeLocal(data.cart_count);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (error) {
                        this.disabled = false;
                        this.innerHTML = originalText;
                        console.error('Error adding to cart:', error);
                        alert('An error occurred while adding to cart. Please try again.');
                    }
                });
            }

            if (buyNowBtn) {
                buyNowBtn.addEventListener('click', async function() {
                    if (!isLoggedIn) {
                        alert('You need to Login.');
                        window.location.href = 'user_auth.php';
                        return;
                    }

                    const productId = this.dataset.productId;
                    const quantity = parseInt(quantityInput.value);

                    if (quantity <= 0 || isNaN(quantity)) {
                        alert('Please enter a valid quantity.');
                        return;
                    }
                    if (quantity > availableStock) {
                        alert('Quantity exceeds available stock.');
                        return;
                    }

                    if (confirm('Do you want to be directed to the cart page?')) {
                        this.disabled = true; 
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                        try {
                            const formData = new URLSearchParams();
                            formData.append('product_id', productId);
                            formData.append('quantity', quantity);

                            const response = await fetch('add_to_cart.php', { 
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: formData.toString()
                            });
                            const data = await response.json();
                            
                            this.disabled = false;
                            this.innerHTML = originalText;

                            if (data.success) {
                                window.location.href = 'user_cart.php'; 
                            } else {
                                alert('Error adding to cart: ' + data.message);
                            }
                        } catch (error) {
                            this.disabled = false;
                            this.innerHTML = originalText;
                            console.error('Error processing Buy Now (redirect to cart):', error);
                            alert('An error occurred. Please try again.');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>