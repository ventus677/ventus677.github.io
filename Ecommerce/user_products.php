<?php
session_start(); // This should be the very first line

include('../database/connect.php'); 

// --- UPDATED LOGIN CHECK ---
// Tinatanggap na natin ang 'user' o 'customer'
$is_logged_in = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
$user_role = $is_logged_in ? $_SESSION['user']['role'] : 'guest';

// Para sa check sa JS later
$can_add_to_cart = ($user_role === 'user' || $user_role === 'customer');

$total_items_in_cart = $_SESSION['total_items_in_cart'] ?? 0;

$search_term = $_GET['search'] ?? '';
$selected_category = $_GET['category'] ?? ''; 

$products = [];
try {
    $sql = "SELECT id, product_name, price, img, category, stock FROM products WHERE stock >= 0"; 
    $params = [];

    if (!empty($search_term)) {
        $sql .= " AND product_name LIKE ?";
        $params[] = '%' . $search_term . '%';
    }

    if (!empty($selected_category)) {
        $sql .= " AND category = ?";
        $params[] = $selected_category;
    }

    $sql .= " ORDER BY product_name ASC"; 

    $stmt = $conn->prepare($sql);
    $stmt->execute($params); 
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
}

if (!empty($products)) {
    usort($products, function($a, $b) {
        $stockA = $a['stock'] ?? 0;
        $stockB = $b['stock'] ?? 0;
        if ($stockA <= 0 && $stockB > 0) return 1; 
        if ($stockA > 0 && $stockB <= 0) return -1;
        return 0;
    });
}

$categories = [];
try {
    $stmt = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $raw_categories = $stmt->fetchAll(PDO::FETCH_COLUMN); 
    foreach ($raw_categories as $cat_name) {
        $categories[] = [
            'name' => $cat_name,
            'img' => strtolower(str_replace(' ', '_', $cat_name)) . '.png' 
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

$featured_products = []; 
try {
    $sql = "SELECT id, product_name, price, img, stock FROM products WHERE stock > 0 AND units_sold > 0 ORDER BY units_sold DESC LIMIT 12";
    $stmt = $conn->query($sql);
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch (PDOException $e) {
    error_log("Error fetching top sold products: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keepkit - Discover Your Perfect Beauty Match</title>
    <link rel="icon" type="image/png" href="../images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="customer_home.css"> 
    <link rel="stylesheet" href="customer_products.css">
</head>
<body>
    <?php include('header_public.php'); ?>

    <section class="hero-section">
        <div class="hero-content">
            <p class="hero-tag">Shop with Keepkit</p>
            <h1>Discover Your Perfect Beauty Match</h1>
            <p class="hero-description">
                Explore our curated collection of premium cosmetics, skincare, and fragrance. Quality products that enhance your natural beauty.
            </p>
            <div class="hero-buttons">
                <a href="user_cart.php" class="btn btn-primary">Shop Now</a>
                <a href="customer_all_products.php" class="btn btn-secondary">All Collection</a>
            </div>
        </div>
    </section>

    <section class="featured-products-section" style="margin-left: 140px;">
        <h2 style="padding: 15px 0;">Featured Products</h2>
        <p class="section-subtitle" style="padding: 0 0 30px 0;">Loved and purchased by our community</p>

        <div class="product-grid">
            <?php if (empty($featured_products)): ?>
                <p>We're still gathering sales data! Check back soon for our best sellers.</p>
            <?php else: ?>
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card"> 
                        <a href="customer_product_detail.php?id=<?= htmlspecialchars($product['id'] ?? '') ?>" class="product-link-overlay" style="display:block; width:100%; text-decoration:none; color:inherit;">
                            <?php
                                $image_src = '../uploads/products/' . htmlspecialchars($product['img'] ?? '');
                                if (empty($product['img']) || !file_exists($image_src)) {
                                    $image_src = 'https://placehold.co/300x300/cccccc/333333?text=No+Image'; 
                                }
                            ?>
                            <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($product['product_name'] ?? 'Product Image') ?>" onerror="this.onerror=null;this.src='https://placehold.co/300x300/cccccc/333333?text=Image+Error';">
                            <div class="product-info">
                                <div class="product-name"><?= htmlspecialchars($product['product_name'] ?? 'Untitled Product') ?></div>
                                <div class="product-price">₱<?= htmlspecialchars(number_format($product['price'] ?? 0, 2)) ?></div>
                            </div>
                        </a>
                        <div class="product-badge">Best Seller</div>
                        <?php if (($product['stock'] ?? 0) > 0): ?> 
                            <button class="add-to-cart-btn" data-product-id="<?= htmlspecialchars($product['id']) ?>">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        <?php else: ?>
                            <button class="add-to-cart-btn" disabled style="background-color: #ccc; cursor: not-allowed;">
                                Out of Stock
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <h2 style="text-align: center; padding: 50px 0 15px 0;" >Shop by Category</h2>
    <p style="text-align: center;">Browse through our collections</p>

    <section class="category-carousel-section">
        <button class="carousel-btn prev-btn"><i class="fas fa-chevron-left"></i></button>
        <div class="carousel-wrapper">
            <div class="carousel-track">
                <?php foreach ($categories as $category):
                    $category_image_filename = $category['img'] ?? 'default_cat_icon.png';
                    $category_image_path = '../images/categories/' . htmlspecialchars($category_image_filename);
                ?>
                    <a href="customer_category_products.php?category=<?= urlencode($category['name'] ?? 'All') ?>" class="category-link">
                        <div class="category-card carousel-item">
                            <div class="category-image-placeholder">
                                <img src="<?= $category_image_path ?>" alt="<?= htmlspecialchars($category['name'] ?? 'Category') ?>" onerror="this.onerror=null;this.src='../images/categories/default_cat_icon.png';">
                            </div>
                            <div class="category-name-label"><?= htmlspecialchars($category['name'] ?? 'Untitled') ?></div>
                        </div>
                    </a>
                <?php endforeach; ?> 
            </div>
        </div>
        <button class="carousel-btn next-btn"><i class="fas fa-chevron-right"></i></button>
    </section>

    <main class="product-listing-area" style="margin-left: 140px;">
        <?php if (empty($products)): ?>
            <p class="no-products-message">No products available right now.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card"> 
                        <a href="customer_product_detail.php?id=<?= htmlspecialchars($product['id'] ?? '') ?>" class="product-link-overlay" style="display:block; width:100%; text-decoration:none; color:inherit;">
                            <?php
                                $image_src = '../uploads/products/' . htmlspecialchars($product['img'] ?? '');
                                if (empty($product['img']) || !file_exists($image_src)) {
                                    $image_src = 'https://placehold.co/300x300/cccccc/333333?text=No+Image'; 
                                }
                            ?>
                            <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($product['product_name'] ?? 'Product Image') ?>" onerror="this.onerror=null;this.src='https://placehold.co/300x300/cccccc/333333?text=Image+Error';">
                            <div class="product-info">
                                <div class="product-name"><?= htmlspecialchars($product['product_name'] ?? 'Untitled Product') ?></div>
                                <div class="product-price">₱<?= htmlspecialchars(number_format($product['price'] ?? 0, 2)) ?></div>
                            </div>
                        </a>
                        <?php if (($product['stock'] ?? 0) > 0): ?> 
                            <button class="add-to-cart-btn" data-product-id="<?= htmlspecialchars($product['id']) ?>">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        <?php else: ?>
                            <button class="add-to-cart-btn out-of-stock-btn-hover" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
            const cartItemCountBadge = document.getElementById('cartCountBadge'); 
            
            // --- UPDATED: Dinamiko nang chine-check ang role ---
            const canAddToCart = <?php echo json_encode($can_add_to_cart); ?>;

            function updateCartCountBadge(count) {
                if (cartItemCountBadge) {
                    cartItemCountBadge.textContent = count;
                }
            }

            addToCartButtons.forEach(button => {
                button.addEventListener('click', async function(event) {
                    event.stopPropagation();
                    event.preventDefault();

                    if (!canAddToCart) {
                        alert('You need to be logged in as a User or Customer to add items to your cart.');
                        window.location.href = 'user_auth.php'; 
                        return;
                    }

                    const productId = this.dataset.productId;
                    try {
                        const formData = new URLSearchParams();
                        formData.append('product_id', productId);
                        formData.append('quantity', 1);
                        formData.append('action', 'add');

                        const response = await fetch('add_to_cart.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData.toString()
                        });
                        const data = await response.json();

                        if (data.success) {
                            alert(data.message);
                            updateCartCountBadge(data.cart_count);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error adding to cart:', error);
                        alert('An error occurred. Please try again.');
                    }
                });
            });

            // --- CAROUSEL LOGIC (UNTOUCHED) ---
            const track = document.querySelector('.carousel-track');
            const items = Array.from(document.querySelectorAll('.category-link'));
            const prevBtn = document.querySelector('.prev-btn');
            const nextBtn = document.querySelector('.next-btn');

            if (!track || items.length === 0) return;
            
            const buffer = 2;
            const totalRealItems = items.length;
            for (let i = 0; i < buffer; i++) {
                track.prepend(items[totalRealItems - 1 - i].cloneNode(true));
                track.appendChild(items[i].cloneNode(true));
            }
            
            const allItems = Array.from(track.querySelectorAll('.category-link'));
            let currentCenterIndex = buffer; 
            let itemWidth;

            function calculateItemWidth() {
                if (allItems.length > 0) {
                    const itemStyle = getComputedStyle(allItems[0]);
                    itemWidth = allItems[0].offsetWidth + parseFloat(itemStyle.marginRight) + parseFloat(itemStyle.marginLeft);
                }
            }

            function updateCarousel(instant = false) {
                if (!itemWidth) calculateItemWidth();
                const wrapperWidth = track.parentElement.offsetWidth;
                const centerOffset = (currentCenterIndex * itemWidth) - (wrapperWidth / 2) + (itemWidth / 2);

                track.style.transition = instant ? 'none' : 'transform 0.5s ease-in-out';
                track.style.transform = `translateX(-${centerOffset}px)`;

                if (!instant) {
                    if (currentCenterIndex === 0) {
                        setTimeout(() => { currentCenterIndex = totalRealItems + buffer - 1; updateCarousel(true); }, 500);
                    } else if (currentCenterIndex === allItems.length - 1) {
                        setTimeout(() => { currentCenterIndex = buffer; updateCarousel(true); }, 500);
                    }
                }
            }

            prevBtn.addEventListener('click', () => { currentCenterIndex--; updateCarousel(); });
            nextBtn.addEventListener('click', () => { currentCenterIndex++; updateCarousel(); });
            window.addEventListener('resize', () => { calculateItemWidth(); updateCarousel(true); });
            calculateItemWidth();
            updateCarousel(true);
        });
    </script>
    <?php include('footer_public.php');?>   
</body>
</html>