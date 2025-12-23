<?php
session_start(); 

include('../database/connect.php'); 

// ----------------------------------------------------------------------------------
// --- START: UNIFIED LOGIN CHECK (USER & CUSTOMER) ---

$customer_id = null; 
$is_employee_discount = false; 
$user_role_display = ''; 

if (isset($_SESSION['user']) && isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id'])) {
    $customer_id = $_SESSION['user']['id'];
    $user_role_display = $_SESSION['user']['role'] ?? 'customer';

    // FIX: Only 'user' role gets the discount. 'customer' role does NOT.
    if ($user_role_display === 'user') {
        $is_employee_discount = true; 
    } elseif ($user_role_display === 'customer') {
        $is_employee_discount = false; 
    } else {
        header('Location: ../admin/dashboard.php');
        exit;
    }
}

if (is_null($customer_id)) {
    header('Location: user_auth.php'); 
    exit; 
}

$cart_items = []; 
// --- END: UNIFIED LOGIN CHECK ---
// ----------------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'] ?? '';
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null; 

    header('Content-Type: application/json'); 
    $response = ['success' => false, 'message' => 'Invalid action or data.', 'cart_count' => 0];

    if (!is_numeric($product_id) || $product_id <= 0) {
        $response['message'] = "Invalid Product ID.";
        echo json_encode($response);
        exit;
    }

    try {
        if ($action === 'update' && is_numeric($quantity) && $quantity >= 0) {
            $quantity = (int)$quantity;
            
            $stmt_stock = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt_stock->execute([$product_id]);
            $product_stock = $stmt_stock->fetchColumn();

            if ($product_stock === false) {
                $response['message'] = "Product not found.";
            } elseif ($quantity > $product_stock) {
                $quantity = $product_stock;
                $response['message'] = "Quantity limited to current stock of: {$product_stock}";
                $response['quantity_adjusted'] = $quantity; 
                $response['success'] = true; 
                
                $stmt = $conn->prepare("UPDATE user_cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $customer_id, $product_id]); 

            } elseif ($quantity > 0) {
                $stmt = $conn->prepare("UPDATE user_cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $customer_id, $product_id]); 
                $response['message'] = "Cart updated successfully.";
                $response['success'] = true;
            } else {
                $action = 'remove';
            }
        }
        
        if ($action === 'remove') {
            $stmt = $conn->prepare("DELETE FROM user_cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$customer_id, $product_id]); 
            $response['message'] = "Item removed successfully.";
            $response['success'] = true;
        }

        $stmt_count = $conn->prepare("SELECT SUM(quantity) FROM user_cart WHERE user_id = ?");
        $stmt_count->execute([$customer_id]); 
        $total_items = (int)$stmt_count->fetchColumn();
        $_SESSION['total_items_in_cart'] = $total_items; 
        $response['cart_count'] = $total_items;

    } catch (PDOException $e) {
        $response['message'] = "Database error.";
    }

    echo json_encode($response);
    exit; 
}

include('header_public.php'); 

try {
    $stmt = $conn->prepare("
        SELECT c.product_id, c.quantity AS cart_quantity, p.product_name, p.price, p.img, p.stock 
        FROM user_cart c  
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? 
    ");
    $stmt->execute([$customer_id]); 
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $_SESSION['total_items_in_cart'] = array_sum(array_column($cart_items, 'cart_quantity'));

} catch (PDOException $e) {
    $cart_items = []; 
} 
?>
<head>
    <title>My Shopping Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #ffa53c; 
            --secondary-color: #b86433; 
            --text-color: #333;
            --bg-color: #f0f2f5; 
            --card-bg: #fff;
            --border-color: #ddd;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            padding-top: 70px; 
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        .cart-layout {
            display: flex;
            gap: 20px;
            align-items: flex-start; 
        }

        .cart-items-panel {
            flex-grow: 1;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .cart-summary-panel {
            width: 350px;
            flex-shrink: 0;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            position: sticky; 
            top: 90px;
        }
        
        .select-row {
            display: flex;
            align-items: center;
            padding: 10px 0;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .select-row label {
            margin-left: 10px;
            cursor: pointer;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
        }
        
        .cart-item:hover {
            background-color: #f9f9f9;
        }

        .item-checkbox-col {
            flex-shrink: 0;
            padding-right: 15px;
        }
        
        .product-details-col {
            display: flex;
            align-items: center;
            flex-grow: 1;
            gap: 15px;
        }

        .product-details-col img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #eee;
        }
        
        .product-info-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex-grow: 1;
        }
        
        .product-info-text .name {
            font-weight: 600;
            font-size: 1.1em;
            color: var(--text-color);
            text-decoration: none;
            margin-bottom: 5px;
        }
        
        .product-info-text .price {
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .quantity-col, .subtotal-col, .actions-col {
            width: 150px; 
            text-align: center;
            flex-shrink: 0;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center; 
            gap: 0; 
        }
        
        .qty-btn {
            background: #f0f0f0;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 1em;
            height: 38px;
            width: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-btn:first-child {
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
        }
        
        .qty-btn:last-child {
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }

        .qty-btn:hover {
            background-color: #e0e0e0;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            padding: 8px 5px;
            border: 1px solid var(--border-color);
            border-left: none; 
            border-right: none; 
            background-color: var(--card-bg);
            box-sizing: border-box;
            height: 38px;
        }
        
        .subtotal-col .subtotal-display {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.2em;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #a93131;
            cursor: pointer;
            font-size: 1.5em;
            transition: color 0.2s;
        }
        
        .remove-btn:hover {
            color: #a93131;
        }

        .stock-warning {
            color: #a93131;
            font-weight: bold;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .cart-summary-panel h2 {
            font-size: 1.5em;
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 1em;
        }

        .payment-method-group {
            border: 1px solid var(--border-color);
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            margin-bottom: 20px;
            background-color: #fafafa;
        }

        .payment-method-group span {
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
            color: var(--secondary-color);
        }
        
        .payment-method-group label {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .payment-method-group input[type="radio"] {
            margin-right: 10px;
            accent-color: var(--primary-color);
        }
        
        .total-row-net {
            border-top: 2px solid var(--border-color);
            padding-top: 15px;
            font-weight: bold;
            font-size: 1.4em;
            color: var(--primary-color);
        }
        
        .discount-row {
            color: green;
            font-weight: bold;
        }
        
        .subtotal-row-gross {
            text-decoration: line-through;
            color: #777;
        }

        .checkout-btn {
            width: 100%;
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
            transition: background-color 0.2s;
        }
        
        .checkout-btn:hover:not(:disabled) {
            background-color: #e64a19;
        }

        .checkout-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .no-items-message {
            text-align: center;
            padding: 50px;
            font-size: 1.5em;
            color: #777;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .no-items-message .fas {
            font-size: 4em; 
            color: #ccc;
            margin-bottom: 15px;
        }

        #responseMessage {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 30px;
            border-radius: 5px;
            font-weight: bold;
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.5s, transform 0.5s;
        }

        #responseMessage.show {
            display: block;
            opacity: 1;
        }

        #responseMessage.success {
            background-color: #28a745;
            color: white;
        }

        #responseMessage.error {
            background-color: #dc3545;
            color: white;
        }
        
        body.dark-mode {
            background-color: #121212;
            color: #e0e0e0; 
        }

        body.dark-mode .cart-items-panel,
        body.dark-mode .cart-summary-panel {
            background-color: #1e1e1e; 
            box-shadow: 0 4px 8px rgba(255, 255, 255, 0.1);
        }
        
        body.dark-mode h1 {
            color: var(--primary-color); 
        }
        
        body.dark-mode .select-row {
            border-bottom: 2px solid #333;
        }

        body.dark-mode .cart-item {
            border-bottom: 1px solid #333;
        }

        body.dark-mode .cart-item:hover {
            background-color: #2a2a2a;
        }
        
        body.dark-mode .product-info-text .name {
            color: #f0f0f0;
        }

        body.dark-mode .qty-btn {
            background: #444;
            border: 1px solid #555;
            color: #e0e0e0;
        }
        
        body.dark-mode .qty-btn:hover {
            background-color: #555;
        }
        
        body.dark-mode .quantity-input {
            background-color: #333;
            color: #e0e0e0;
            border: 1px solid #555;
            border-left: none; 
            border-right: none; 
        }

        body.dark-mode .remove-btn {
            color: #ff6b6b; 
        }
        
        body.dark-mode .payment-method-group {
            background-color: #2a2a2a;
            border-color: #333;
        }

        body.dark-mode .total-row-net {
            border-top: 2px solid #333;
        }
    </style>
</head>

<body style="display: flex; flex-direction: column; min-height: 100vh;">
    <div id="responseMessage"></div>

    <div class="container">
        <h1>My Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div class="no-items-message">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty. Start shopping now!</p>
                <a href="user_products.php" class="checkout-btn" style="width: 250px; margin-top: 20px;">Browse Products</a>
            </div>
        <?php else: ?>
        
        <div class="cart-layout">
            
            <div class="cart-items-panel">
                
                <div class="select-row">
                    <input type="checkbox" id="selectAllItems" class="select-item-checkbox">
                    <label for="selectAllItems">Select All (<?= count($cart_items) ?> items)</label>
                </div>
                
                <?php foreach ($cart_items as $item): 
                    $product_img_path = !empty($item['img']) ? '../uploads/products/' . htmlspecialchars($item['img']) : 'https://via.placeholder.com/90?text=No+Image';
                    $subtotal_initial = $item['price'] * $item['cart_quantity'];
                    $is_low_stock = $item['stock'] < $item['cart_quantity'];
                ?>
                <div class="cart-item" data-product-id="<?= htmlspecialchars($item['product_id']) ?>" data-price="<?= htmlspecialchars($item['price']) ?>" data-stock="<?= htmlspecialchars($item['stock']) ?>">
                    
                    <div class="item-checkbox-col">
                        <input type="checkbox" class="select-item-checkbox item-checkbox" name="selected_items[]" value="<?= htmlspecialchars($item['product_id']) ?>" data-product-id="<?= htmlspecialchars($item['product_id']) ?>" 
                            <?php if ($is_low_stock) echo 'disabled'; ?>
                        >
                    </div>

                    <div class="product-details-col">
                        <img src="<?= $product_img_path ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                        <div class="product-info-text">
                            <a href="customer_product_detail.php?id=<?= htmlspecialchars($item['product_id']) ?>" class="name"><?= htmlspecialchars($item['product_name']) ?></a>
                            <span class="price">₱<?= number_format($item['price'], 2) ?></span>
                            <?php if ($is_low_stock): ?>
                                <p class="stock-warning">Insufficient stock! Max available: <?= htmlspecialchars($item['stock']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="quantity-col">
                        <div class="quantity-controls">
                            <button class="qty-btn qty-minus" data-id="<?= htmlspecialchars($item['product_id']) ?>">-</button>
                            <input type="number" 
                                class="quantity-input" 
                                value="<?= htmlspecialchars($item['cart_quantity']) ?>" 
                                min="1" 
                                max="<?= htmlspecialchars($item['stock']) ?>" 
                                data-id="<?= htmlspecialchars($item['product_id']) ?>"
                            >
                            <button class="qty-btn qty-plus" data-id="<?= htmlspecialchars($item['product_id']) ?>">+</button>
                        </div>
                    </div>
                    
                    <div class="subtotal-col">
                        <span class="subtotal-display">₱<?= number_format($subtotal_initial, 2) ?></span>
                    </div>
                    
                    <div class="actions-col">
                        <button class="remove-btn" data-id="<?= htmlspecialchars($item['product_id']) ?>"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <form id="checkoutForm" method="POST" action="process_order.php" class="cart-summary-panel">
                <h2>Order Summary</h2>
                
                <div class="summary-row">
                    <span>Selected Items:</span>
                    <span id="selectedItemsCount">0</span>
                </div>
                
                <div class="payment-method-group">
                    <span>Payment Method:</span>
                    <label>
                        <input type="radio" name="payment_method" value="Cash on Delivery (COD)" checked> Cash on Delivery (COD)
                    </label>
                    <label>
                        <input type="radio" name="payment_method" value="GCash"> GCash
                    </label>
                    <label>
                        <input type="radio" name="payment_method" value="PayMaya"> PayMaya
                    </label>
                    <label>
                        <input type="radio" name="payment_method" value="Bank Transfer"> Bank Transfer
                    </label>
                </div>
                
                <div class="summary-row" id="subtotalGrossRow"> 
                    <span>Subtotal (Gross):</span>
                    <span id="subtotalGrossAmount">₱0.00</span>
                </div>
                
                <div class="summary-row discount-row" id="discountRow" style="display: none;">
                    <span>20% Employee Discount:</span>
                    <span id="discountAmount">₱0.00</span>
                </div>
                
                <div class="summary-row total-row-net">
                    <span>Total Payment:</span>
                    <span id="totalPaymentAmount" style="font-size: 1.5em; color: var(--primary-color);">₱0.00</span>
                </div>
                <input type="hidden" name="selected_product_data" id="selectedProductData"> 

                <button type="submit" id="checkoutButton" class="checkout-btn" disabled>Proceed to Checkout</button>
            </form>
            
        </div>
        <?php endif; ?>
        
    </div>
    
    <script>
        // UPDATED: Logic strictly depends on role 'user'
        const IS_EMPLOYEE = <?= json_encode($is_employee_discount) ?>; 
        const DISCOUNT_RATE = 0.20; 
        const CUSTOMER_ID = '<?= $customer_id ?? 'guest' ?>'; 

        document.addEventListener('DOMContentLoaded', function() {
            console.log("Discount Eligibility (User Role Only):", IS_EMPLOYEE); 
            
            const checkoutForm = document.getElementById('checkoutForm');
            const checkoutButton = document.getElementById('checkoutButton');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox:not(:disabled)');
            const selectAllCheckbox = document.getElementById('selectAllItems');
            const quantityInputs = document.querySelectorAll('.quantity-input');
            
            const subtotalGrossAmountSpan = document.getElementById('subtotalGrossAmount');
            const discountRowDiv = document.getElementById('discountRow');
            const discountAmountSpan = document.getElementById('discountAmount');
            const totalPaymentAmountSpan = document.getElementById('totalPaymentAmount');
            const selectedItemsCountSpan = document.getElementById('selectedItemsCount');
            const selectedProductDataInput = document.getElementById('selectedProductData');
            const subtotalGrossRow = document.getElementById('subtotalGrossRow'); 

            function getSavedState() {
                const savedState = localStorage.getItem('customerCartSelection_' + CUSTOMER_ID);
                return savedState ? JSON.parse(savedState) : {};
            }

            function saveState(state) {
                localStorage.setItem('customerCartSelection_' + CUSTOMER_ID, JSON.stringify(state));
            }
            
            function showResponseMessage(message, type) {
                const responseMessageDiv = document.getElementById('responseMessage');
                if (responseMessageDiv) {
                    responseMessageDiv.className = '';
                    responseMessageDiv.textContent = message;
                    responseMessageDiv.classList.add(type); 
                    responseMessageDiv.classList.add('show');
                    responseMessageDiv.style.display = 'block';

                    setTimeout(() => {
                        responseMessageDiv.classList.remove('show');
                        setTimeout(() => {
                            responseMessageDiv.style.display = 'none';
                        }, 500); 
                    }, 4000);
                }
            }
            
            function initializeCart() {
                const savedState = getSavedState();
                itemCheckboxes.forEach(checkbox => {
                    const productId = checkbox.dataset.productId;
                    if (savedState[productId] === true && !checkbox.disabled) {
                        checkbox.checked = true;
                    } else {
                        checkbox.checked = false;
                        delete savedState[productId];
                    }
                });
                saveState(savedState);
                updateCartTotals();
            }
            
            function updateSelectAllCheckbox() {
                const selectableCheckboxes = Array.from(itemCheckboxes).filter(cb => !cb.disabled);
                const allChecked = selectableCheckboxes.length > 0 && selectableCheckboxes.every(checkbox => checkbox.checked);
                selectAllCheckbox.checked = allChecked;
            }
            
            function updateCartTotals() {
                let subtotalGross = 0; 
                let selectedCount = 0;
                const currentSelectionState = {};
                const selectedItemsForCheckout = []; 
                
                const allItems = document.querySelectorAll('.cart-item');

                allItems.forEach(row => {
                    const checkbox = row.querySelector('.item-checkbox');
                    const productId = row.dataset.productId;
                    const price = parseFloat(row.dataset.price);
                    const quantityInput = row.querySelector('.quantity-input');
                    const quantity = parseInt(quantityInput.value);
                    const subtotalDisplay = row.querySelector('.subtotal-display');

                    const itemSubtotal = price * quantity;
                    subtotalDisplay.textContent = `₱${itemSubtotal.toFixed(2)}`;
                    
                    if (checkbox && checkbox.checked && !checkbox.disabled) {
                        subtotalGross += itemSubtotal;
                        selectedCount++;
                        currentSelectionState[productId] = true;
                        
                        selectedItemsForCheckout.push({
                            product_id: productId,
                            quantity: quantity
                        });
                    }
                });
                
                let discount = 0.00;
                let finalTotal = subtotalGross;
                
                if (IS_EMPLOYEE && subtotalGross > 0) {
                    discount = subtotalGross * DISCOUNT_RATE;
                    finalTotal = subtotalGross - discount;
                    discountRowDiv.style.display = 'flex';
                    discountAmountSpan.textContent = `- ₱${discount.toFixed(2)}`;
                    subtotalGrossRow.classList.add('subtotal-row-gross');
                } else {
                    finalTotal = subtotalGross; 
                    discountRowDiv.style.display = 'none';
                    discountAmountSpan.textContent = '₱0.00';
                    subtotalGrossRow.classList.remove('subtotal-row-gross');
                }
                
                subtotalGrossAmountSpan.textContent = `₱${subtotalGross.toFixed(2)}`;
                totalPaymentAmountSpan.textContent = `₱${finalTotal.toFixed(2)}`;
                selectedItemsCountSpan.textContent = selectedCount;
                
                selectedProductDataInput.value = JSON.stringify(selectedItemsForCheckout);
                checkoutButton.disabled = selectedCount === 0;
                
                saveState(currentSelectionState); 
                updateSelectAllCheckbox();
            }

            selectAllCheckbox.addEventListener('change', function() {
                itemCheckboxes.forEach(checkbox => {
                    if (!checkbox.disabled) { 
                        checkbox.checked = this.checked;
                    }
                });
                updateCartTotals();
            });

            itemCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCartTotals); 
            });

            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    let quantity = parseInt(this.value);
                    const productId = this.dataset.id;
                    const maxStock = parseInt(this.max);

                    if (isNaN(quantity) || quantity < 1) {
                        quantity = 1;
                    } else if (quantity > maxStock) {
                        quantity = maxStock;
                        showResponseMessage(`Quantity limited to max stock of: ${maxStock}`, 'error');
                    }
                    this.value = quantity;
                    
                    updateCartItem(productId, quantity, () => {
                        updateCartTotals(); 
                    });
                });
            });

            async function updateCartItem(productId, quantity, callback) {
                try {
                    const formData = new URLSearchParams();
                    formData.append('action', 'update');
                    formData.append('product_id', productId);
                    formData.append('quantity', quantity);
                    
                    const response = await fetch('user_cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData.toString()
                    });
                    
                    const data = await response.json(); 
                    
                    if (data.success) {
                        if (data.quantity_adjusted) {
                            const input = document.querySelector(`.quantity-input[data-id="${productId}"]`);
                            input.value = data.quantity_adjusted; 
                            showResponseMessage(data.message, 'error');
                            
                            const row = input.closest('.cart-item');
                            row.dataset.stock = data.quantity_adjusted; 
                            input.max = data.quantity_adjusted; 
                            
                            const warningElement = row.querySelector('.stock-warning');
                            if (warningElement) {
                                warningElement.textContent = `Insufficient stock! Max available: ${data.quantity_adjusted}`;
                            }
                        } 
                        
                        if (!data.quantity_adjusted) {
                             showResponseMessage(data.message, 'success');
                        }
                        
                        if (window.updateCartCountBadge) {
                            window.updateCartCountBadge(data.cart_count);
                        }
                        
                        if (callback) callback();
                        
                    } else {
                        showResponseMessage(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error updating cart:', error);
                    showResponseMessage('An error occurred while updating the cart.', 'error'); 
                }
            }

            async function removeCartItem(productId) {
                try {
                    const formData = new URLSearchParams();
                    formData.append('action', 'remove');
                    formData.append('product_id', productId);
                    
                    const response = await fetch('user_cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData.toString()
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showResponseMessage(data.message, 'success');
                        
                        const rowToRemove = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                        if (rowToRemove) {
                            rowToRemove.remove();
                        }
                        
                        const savedState = getSavedState();
                        delete savedState[productId];
                        saveState(savedState);

                        if (window.updateCartCountBadge) {
                            window.updateCartCountBadge(data.cart_count);
                        }

                        const cartItemsContainer = document.querySelector('.cart-items-panel');
                        if (cartItemsContainer && document.querySelectorAll('.cart-item').length === 0) { 
                             window.location.reload(); 
                        } else {
                            initializeCart(); 
                        }
                        
                    } else {
                        showResponseMessage(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error removing item:', error);
                    showResponseMessage('An error occurred while removing the item.', 'error');
                }
            }
            
            const qtyPlusButtons = document.querySelectorAll('.qty-plus');
            const qtyMinusButtons = document.querySelectorAll('.qty-minus');
            const removeButtons = document.querySelectorAll('.remove-btn');

            qtyPlusButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.id;
                    const input = document.querySelector(`.quantity-input[data-id="${productId}"]`);
                    let quantity = parseInt(input.value);
                    const maxStock = parseInt(input.max);
                    
                    if (quantity < maxStock) {
                        quantity++;
                        input.value = quantity;
                        const changeEvent = new Event('change');
                        input.dispatchEvent(changeEvent);
                    } else {
                        showResponseMessage(`Quantity limited to max stock of: ${maxStock}`, 'error');
                    }
                });
            });
            
            qtyMinusButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.id;
                    const input = document.querySelector(`.quantity-input[data-id="${productId}"]`);
                    let quantity = parseInt(input.value);
                    
                    if (quantity > 1) {
                        quantity--;
                        input.value = quantity;
                        const changeEvent = new Event('change');
                        input.dispatchEvent(changeEvent);
                    }
                });
            });

            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.id;
                    if (confirm('Are you sure you want to remove this item from your cart?')) {
                        removeCartItem(productId);
                    }
                });
            });


            checkoutForm.addEventListener('submit', async function(e) {
                e.preventDefault(); 
                
                const selectedItemsData = JSON.parse(selectedProductDataInput.value || '[]');
                
                if (selectedItemsData.length === 0) {
                    showResponseMessage('Please select at least one item to checkout.', 'error');
                    return;
                }
                
                checkoutButton.disabled = true;
                checkoutButton.textContent = 'Processing...';

                try {
                    
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                    
                    let subtotal = 0;
                    const allItems = document.querySelectorAll('.cart-item');
                    allItems.forEach(row => {
                        const checkbox = row.querySelector('.item-checkbox');
                        if (checkbox && checkbox.checked && !checkbox.disabled) {
                            const price = parseFloat(row.dataset.price);
                            const quantity = parseInt(row.querySelector('.quantity-input').value);
                            subtotal += price * quantity;
                        }
                    });

                    let discount = 0;
                    if (IS_EMPLOYEE && subtotal > 0) { 
                        discount = subtotal * DISCOUNT_RATE;
                    }
                    
                    const finalTotal = subtotal - discount;
                    
                    const checkoutData = {
                        payment_method: paymentMethod,
                        selected_items: selectedItemsData,
                        total_amount: finalTotal.toFixed(2), 
                        discount_amount: IS_EMPLOYEE ? discount.toFixed(2) : 0.00, 
                        discount_reason: IS_EMPLOYEE ? 'Employee discount (20%)' : null 
                    };

                    const response = await fetch('process_order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }, 
                        body: JSON.stringify(checkoutData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showResponseMessage(data.message, 'success');
                        
                        if (window.updateCartCountBadge) {
                            window.updateCartCountBadge(data.cart_count);
                        }
                        
                        const savedState = getSavedState();
                        selectedItemsData.forEach(item => {
                            if (savedState[item.product_id]) {
                                delete savedState[item.product_id];
                            }
                        });
                        saveState(savedState);
                        
                        setTimeout(() => {
                             window.location.href = 'customers_active_orders.php';
                        }, 1000); 
                    } else {
                        showResponseMessage('Checkout Error: ' + data.message, 'error');
                        checkoutButton.disabled = false; 
                        checkoutButton.textContent = 'Proceed to Checkout';
                    }
                } catch (error) {
                    console.error('Checkout Fetch Error:', error);
                    showResponseMessage('An error occurred during checkout. Please try again.', 'error');
                    checkoutButton.disabled = false; 
                    checkoutButton.textContent = 'Proceed to Checkout';
                }
            });

            initializeCart();
        });
    </script>
<footer style="z-index: 999; padding: 0; margin: 0;" >
    <?php include('footer_public.php');?> 
</footer>
</body>
</html>