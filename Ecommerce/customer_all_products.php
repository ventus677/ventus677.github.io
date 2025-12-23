<?php
session_start(); // This should be the very first line

// Ensure this path is correct relative to customer_all_products.php
include('../database/connect.php'); 

$is_user = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
$is_logged_in = $is_user; // Only 'user' counts as logged in

$entity_type = $is_user ? 'user' : 'guest'; // 'user' or 'guest'

// Fetch total items in cart from session for the header badge.
// This value should be updated whenever items are added/removed from the cart.
$total_items_in_cart = $_SESSION['total_items_in_cart'] ?? 0;

// =========================================================================
// !!! NEW: SUGGESTION HANDLER (Handles AJAX requests) !!!
// If the request includes 'action=fetch_suggestions', execute the AJAX logic
// and exit immediately, preventing the rest of the page from loading.
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'fetch_suggestions') {
    
    header('Content-Type: application/json');

    $query = $_GET['term'] ?? '';
    $suggestions = [];

    if (!empty($query)) {
        $searchTerm = '%' . $query . '%';

        try {
            // Select product names that match the query
            $sql = "
                SELECT DISTINCT product_name 
                FROM products 
                WHERE product_name LIKE ? 
                LIMIT 10
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$searchTerm]);
            
            // Fetch results and format them as a simple array of strings
            $suggestions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            // Log the error but return an empty array to the user
            error_log("Suggestion error: " . $e->getMessage());
        }
    }

    echo json_encode($suggestions);
    exit; // Crucial: Stop processing the rest of the page
}
// =========================================================================
// !!! END SUGGESTION HANDLER !!!
// =========================================================================

// --- 1. GET ALL FILTER VARIABLES ---

// NOTE: $category_name is now ignored for the main product fetch, but retained for the 'Clear Filters' link.
$category_name = $_GET['category'] ?? ''; // Retain for safety, though unused for filtering
$selected_brands = $_GET['brand'] ?? []; 
$min_price_input = $_GET['min_price'] ?? null;
$max_price_input = $_GET['max_price'] ?? null;
$min_rating = $_GET['min_rating'] ?? null; 
$sort_by = $_GET['sort_by'] ?? 'name_asc'; // Default sort

// Read search term
$search_term = $_GET['search_term'] ?? '';

$products = [];
$product_count = 0;

// Variables to store the actual min/max prices from the entire database (updated below)
$db_min_price = 0;
$db_max_price = 1000; 

// Prepare header variables
$page_title = "All Products";
$category_description = "Explore our entire collection of products.";


// --- 1.5. Fetch GLOBAL Min/Max Prices for Slider Limits ---
try {
    $sql_limits = "
        SELECT 
            MIN(price) AS min_price, 
            MAX(price) AS max_price
        FROM products 
        WHERE price >= 0
    ";
    
    $stmt_limits = $conn->prepare($sql_limits);
    $stmt_limits->execute(); // No parameters needed
    
    $price_limits = $stmt_limits->fetch(PDO::FETCH_ASSOC);
    
    // Update the variables with fetched values
    if ($price_limits) {
        $db_min_price = floor($price_limits['min_price'] ?? 0); 
        $db_max_price = ceil($price_limits['max_price'] ?? 1000); 
    }
} catch (PDOException $e) {
    error_log("Error fetching global price limits: " . $e->getMessage());
}

// --- 2. FETCH ALL PRODUCTS (INCLUDES ALL FILTERS & SORTING) ---

try {
    // Base SQL: Select all product data needed
    $sql = "
        SELECT 
            id, product_name, price, img, category, stock, brand_name
        FROM products 
        WHERE 1=1 -- A true condition to allow easy appending of filters
    ";
    
    $params = [];

    // --- Search Filter ---
    if (!empty($search_term)) {
    // Filter by product_name containing the search term (case-insensitive)
    $sql .= " AND product_name LIKE ?";
    $params[] = '%' . $search_term . '%';
    }
    // -------------------------------

    // --- Price Filtering ---
    if (is_numeric($min_price_input) && $min_price_input >= 0) {
        $sql .= " AND price >= ?"; 
        $params[] = (float)$min_price_input; 
    }
    if (is_numeric($max_price_input) && $max_price_input > 0) {
        $sql .= " AND price <= ?";
        $params[] = (float)$max_price_input;
    }
    
    // --- Brand Filtering Logic ---
    if (!empty($selected_brands) && is_array($selected_brands)) {
        $placeholders = implode(',', array_fill(0, count($selected_brands), '?'));
        $sql .= " AND brand_name IN ({$placeholders})"; 
        $params = array_merge($params, $selected_brands);
    }
    
    // --- Dynamic Ordering Logic ---
    $order_col = 'product_name';
    $order_dir = 'ASC';

    if ($sort_by === 'price_asc') {
        $order_col = 'price';
        $order_dir = 'ASC';
    } elseif ($sort_by === 'price_desc') {
        $order_col = 'price';
        $order_dir = 'DESC'; 
    } elseif ($sort_by === 'name_desc') {
        $order_col = 'product_name';
        $order_dir = 'DESC'; 
    }
    
    $sql .= " ORDER BY {$order_col} {$order_dir}";

    // --- END Dynamic Ordering Logic ---

    $stmt = $conn->prepare($sql);
    $stmt->execute($params); 
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $product_count = count($products); 
    $stmt = null; 
    
} catch (PDOException $e) {
    error_log("Error fetching products with filters: " . $e->getMessage());
    $products = [];
    $product_count = 0;
}


// --- 3. CUSTOM SORT (PUSH OUT-OF-STOCK TO THE END - Using Robust PHP Sort) ---

if (!empty($products)) {
    usort($products, function($a, $b) use ($sort_by) {
        $stockA = $a['stock'] ?? 0;
        $stockB = $b['stock'] ?? 0;

        // Priority 1: Push out-of-stock items down
        if ($stockA <= 0 && $stockB > 0) { return 1; }
        if ($stockA > 0 && $stockB <= 0) { return -1; }
        
        // Priority 2: If stock status is the same, apply the requested sort
        switch ($sort_by) {
            case 'price_asc':
                return $a['price'] <=> $b['price'];
            case 'price_desc':
                return $b['price'] <=> $a['price']; 
            case 'name_desc':
                return strnatcmp($b['product_name'], $a['product_name']);
            case 'name_asc': 
            default:
                return strnatcmp($a['product_name'], $b['product_name']);
        }
    });
}


// --- 4. Fetch Unique Brands for Filtering (Global Fetch) ---

$brands = [];
try {
    $sql_brands = "
        SELECT DISTINCT brand_name 
        FROM products 
        WHERE brand_name IS NOT NULL
        AND brand_name != ''
        ORDER BY brand_name ASC
    ";
    
    $stmt_brands = $conn->prepare($sql_brands);
    $stmt_brands->execute(); // No category parameter needed
    $brands = $stmt_brands->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    error_log("Error fetching brands globally: " . $e->getMessage());
    $brands = []; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keepkit - <?= $page_title ?></title>
    <link rel="icon" type="image/png" href="../images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="customer_home.css"> 
    <link rel="stylesheet" href="customer_products.css">
    
</head>
<body>
    <?php include('header_public.php'); ?>

<section class="hero-section" style="height: 20px; ">
    <div class="hero-content">
        <a href="user_products.php" class="back-link" style="color: white;">&larr; Back to Home</a>
        <h1 style="font-size: max(54px, min(72px, 2.5vw));"><?= $page_title ?></h1>
        <p class="hero-description"><?= $category_description ?></p>
        <div class="hero-buttons">
            <a href="user_cart.php" class="btn btn-primary">Checkout</a>
            <a href="user_products.php#featured-products-section" class="btn btn-secondary">Featured</a>
        </div>
    </div>
</section>
    
<div class="category-page-container"> 
    
    <aside class="filter-sidebar">
        <h2 style="text-align: center;">Filters</h2>
        
        <form action="" method="get">
            
            <div class="filter-group price-filter-group">
                <h3 style="padding-top: 15px;">Price Range</h3>
                
                <input type="hidden" name="min_price" value="<?= $db_min_price ?>">

                <input type="range" 
                    name="price_slider" 
                    id="price_slider" 
                    min="<?= $db_min_price ?>" 
                    max="<?= $db_max_price ?>" 
                    step="1" 
                    value="<?= htmlspecialchars($max_price_input ?? $db_max_price) ?>" 
                    oninput="document.getElementById('display_max_price').value = this.value"
                >
                
                <div class="price-display-wrapper">
                    <label for="display_max_price">Up to:</label>
                    ₱<input type="number" 
                            name="max_price" 
                            id="display_max_price" 
                            min="<?= $db_min_price ?>" 
                            max="<?= $db_max_price ?>" 
                            value="<?= htmlspecialchars($max_price_input ?? $db_max_price) ?>"
                            oninput="document.getElementById('price_slider').value = this.value"
                    >
                    <span class="max-price-limit">/ ₱<?= $db_max_price ?></span>
                </div>
            </div>

            <div class="filter-group-brand ">
                <h3>Brand</h3>
                <?php if (!empty($brands)): ?>
                    <?php foreach ($brands as $brand): ?>
                        <label class="brand-item">
                            <input type="checkbox" name="brand[]" value="<?= htmlspecialchars($brand) ?>"
                                <?php if (in_array($brand, $selected_brands)) echo 'checked'; ?>
                            > 
                            <?= htmlspecialchars($brand) ?>
                        </label><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-brands-found">No brands found.</p>
                <?php endif; ?>
            </div>

            <div class="filter-group">
                <h3>Minimum Rating</h3>
                <select name="min_rating" id="min_rating">
                    <option value="">Any</option>
                    <?php for ($r = 5; $r >= 1; $r--): ?>
                        <option value="<?= $r ?>" 
                                <?php if ((int)$min_rating === $r) echo 'selected'; ?>
                        >
                            <?= $r ?> Stars & Up
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <hr>
            
            <button type="submit" class="apply-filters-btn">Apply Filters</button>
            
            <p><a href="customer_all_products.php" class="clear-all-link">Clear Filters</a></p>
        
        </aside>

    
    <section class="product-listing-results">
        <main class="product-listing-area">
            <div class="results-header">
                <span class="product-count">Showing <?= $product_count ?> Products</span>
                

                <div class="filter-group search-filter-group">
                    <h3>Search Products</h3>
                    <div class="search-input-wrapper">
                        <input type="text" 
                            name="search_term" 
                            id="search_term" 
                            placeholder="Enter product name..." 
                            value="<?= htmlspecialchars($search_term) ?>"
                            autocomplete="off" 
                        >
                        <button type="submit" class="search-btn" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="suggestions-dropdown"></div>
                </div>
                <div class="sort-by-dropdown">

                    <label for="sort_by">Sort By:</label>
                    
                    <select name="sort_by" id="sort_by" onchange="this.form.submit()">
                        <option value="name_asc" <?= ($sort_by == 'name_asc' || !$sort_by) ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="price_asc" <?= ($sort_by == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?= ($sort_by == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_desc" <?= ($sort_by == 'name_desc') ? 'selected' : ''; ?>>Name (Z-A)</option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($products)): ?>
                <p class="no-products-message">No products available right now.</p>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card"> 
                            <a href="customer_product_detail.php?id=<?= htmlspecialchars($product['id'] ?? '') ?>" class="product-link-overlay" style="display:block; width:100%; text-decoration:none; color:inherit;">
                                <?php
                                    $image_src = '../uploads/products/' . htmlspecialchars($product['img'] ?? '');
                                    // Fallback image if product image is missing or invalid
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
                                <button 
                                    class="add-to-cart-btn out-of-stock-btn-hover" 
                                    disabled
                                >
                                    Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </section>
</div>
</form> 
<script src="../script.js"></script>
<script>

    // --- NEW: Search Suggestions Logic ---
        const searchInput = document.getElementById('search_term');
        // This targets the <div> added below the search input in the HTML
        const dropdown = document.getElementById('suggestions-dropdown'); 
        
        // This is crucial: it finds the nearest parent form to submit when a suggestion is clicked
        const form = searchInput.closest('form'); 
        
        let timeout = null;
        const FETCH_DELAY = 300; // Wait 300ms before sending request after typing stops

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                // 1. Clear previous timer
                clearTimeout(timeout);
                const term = this.value.trim();
                
                // 2. Hide suggestions if the search term is too short
                if (term.length < 2) {
                    dropdown.style.display = 'none';
                    return;
                }

                // 3. Set a new timer to debounce the request
                timeout = setTimeout(() => {
                    fetchSuggestions(term);
                }, FETCH_DELAY);
            });
        }

        async function fetchSuggestions(term) {
            try {
                // CRITICAL: Ensure the path to your existing fetch_suggestions.php is correct
                // Assuming it's located in '../database/'
                const response = await fetch(`../database/fetch_suggestions.php?term=${encodeURIComponent(term)}`);
                const suggestions = await response.json();
                
                renderSuggestions(suggestions);

            } catch (error) {
                console.error('Error fetching suggestions:', error);
                dropdown.style.display = 'none';
            }
        }

        function renderSuggestions(suggestions) {
        dropdown.innerHTML = '';
        
        if (suggestions.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        suggestions.forEach(itemObject => { // Renamed the variable to reflect it might be an object
            // *** CRITICAL FIX: Access the specific property that holds the name ***
            // Try accessing the 'product_name' property
            const name = itemObject.product_name || 'Suggestion Name Missing'; 
            
            const item = document.createElement('div');
            item.classList.add('suggestion-item');
            item.textContent = name; // Now we display the string value
            
            // When clicked, use the property value
            item.addEventListener('click', function() {
                searchInput.value = name; 
                dropdown.style.display = 'none';
                form.submit(); 
            });

            dropdown.appendChild(item);
        });

        dropdown.style.display = 'block';
    }
        
        // Hide dropdown when clicking outside of the search area
        document.addEventListener('click', function(e) {
            // Check if the click target is outside the input and outside the dropdown
            if (e.target !== searchInput && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
            const cartItemCountBadge = document.getElementById('cartCountBadge'); // ID from header_public.php
            // --- UPDATED: Pass 'user' login status from PHP ---
            const loggedIn = <?php echo json_encode(isset($_SESSION['user'])); ?>; // Check for 'user' login

            // Function to update the cart badge count
            function updateCartCountBadge(count) {
                if (cartItemCountBadge) {
                    cartItemCountBadge.textContent = count;
                }
            }

            addToCartButtons.forEach(button => {
                button.addEventListener('click', async function(event) {
                    event.stopPropagation();
                    event.preventDefault();

                    if (!loggedIn) {
                        // --- UPDATED: Redirect to 'user' login page ---
                        alert('You need to be logged in to add items to your cart. Redirecting to login page.');
                        window.location.href = 'user_auth.php'; // Assuming 'user_auth.php' is the user login page
                        return; // Stop execution if not logged in
                    }

                    const productId = this.dataset.productId;
                    const quantity = 1; // Default quantity to add is 1

                    try {
                        const formData = new URLSearchParams();
                        formData.append('product_id', productId);
                        formData.append('quantity', quantity);
                        formData.append('action', 'add');
                        // No change needed in the fetch URL if add_to_cart.php handles both

                        const response = await fetch('../database/add_to_cart.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData.toString()
                        });
                        const data = await response.json();

                        if (data.success) {
                            alert(data.message);
                            updateCartCountBadge(data.cart_count); // Update badge with count from response
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error adding to cart:', error);
                        alert('An error occurred. Please try again.');
                    }
                });
            });

            // The header_public.php is responsible for rendering the initial cart count badge.
            // This script will only update it dynamically after an 'Add to Cart' action.
        });
</script>

<?php include('footer_public.php'); ?>