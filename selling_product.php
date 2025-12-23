<?php
session_start();
$_SESSION['table'] = 'products'; // This session variable might not be relevant for this page
$user = $_SESSION['user'] ?? null;
require_once 'database/connect.php'; // Assuming this connects to your database

if (!$user) {
    header('Location: index.php');
    exit;
}

// Check for session messages (e.g., success/error from process_order.php)
$response_message = $_SESSION['response']['message'] ?? '';
$response_success = $_SESSION['response']['success'] ?? null;
unset($_SESSION['response']); // Clear the session message after displaying
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Order Products - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="products.css"/> <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>

    <style>
        /* General styling for date inputs - adjust as needed for new layout */
        .date-input-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .date-input-field input[type="date"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #b0b0b0;
            border-radius: 4px;
            box-sizing: border-box;
            background: #fafafa;
            font-size: 16px;
        }

        .orderBtn { height: 33px; border: none; padding: 2px 10px; border-radius: 4px; font-size: 16px; }
        .orderProductBtn { background: #d93f65; color: #fff; }
        .submitOrderBtn { background: #323232; color: #fff; font-weight: bold; } /* Changed class name to match HTML */
        .marginTop20 { margin-top: 20px; }
        div.row { display: flex; flex-direction: row; flex-wrap: wrap; width: 100%; }
        .alignRight { text-align:right; }
        .product-price-qty-row {
            display: flex;
            align-items: center;
            justify-content: space-between; /* Spacing between elements */
            margin-top: 10px;
        }
        .product-price-qty-row > div {
            flex-grow: 1; /* Allow divs to grow */
            margin-right: 15px; /* Spacing between columns */
        }
        .product-price-qty-row > div:last-child {
            margin-right: 0;
        }
        .product-price-qty-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .product-price-qty-row input[type="number"],
        .product-price-qty-row .display-price {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #b0b0b0;
            border-radius: 4px;
            box-sizing: border-box;
            background: #fafafa;
            font-size: 16px;
        }
        .display-price {
            background-color: #e9e9e9; /* Slightly different background for read-only price */
            font-weight: bold;
            color: #333;
            text-align: right;
        }

        .removeProductRowBtn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .orderProductRow {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .orderProductRow hr {
            margin-top: 20px;
            margin-bottom: 10px;
            border: 0;
            border-top: 1px solid #eee;
        }
        .row-controls {
            text-align: right;
            margin-top: 10px;
        }
        /* Message styling */
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            margin-bottom: 15px; /* Add margin-bottom for spacing */
        }

        /* Styles for Autocomplete Search */
        .productSearchInput {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 5px;
            border: 1px solid #b0b0b0;
            border-radius: 4px;
            box-sizing: border-box;
            background: #fafafa;
            font-size: 16px;
        }
        .autocomplete-results {
            list-style-type: none;
            padding: 0;
            margin: 0;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            background-color: #fff;
            position: absolute; /* To layer over other elements */
            z-index: 1000; /* Ensure it appears on top */
            width: calc(100% - 30px); /* Adjust based on parent padding/margins for .container or similar */
            box-sizing: border-box;
        }
        .autocomplete-results li {
            padding: 8px 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .autocomplete-results li:last-child {
            border-bottom: none;
        }
        .autocomplete-results li:hover, .autocomplete-results li.selected {
            background-color: #f0f0f0;
        }

        /* Grand Total specific styling */
        .grand-total-container {
            text-align: right;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        .grand-total-container span {
            color: #d93f65;
            font-size: 1.3em;
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
        <section id="orderProductsPage" class="active"> <form action="database/process_order.php" method="POST" id="orderForm"> <div class="container">
                    <div class="header"><span class="plus-icon">+</span> Create New Order</div>
                    
                    <?php if (!empty($response_message)): ?>
                        <div class="message <?= $response_success ? 'success' : 'error' ?>">
                            <?= htmlspecialchars($response_message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="input-field">
                        <label for="customer_name">Customer Name:</label>
                        <input type="text" id="customer_name" name="customer_name" placeholder="Enter customer name" required>
                    </div>

                    <div id="orderProductLists"></div>
                    
                    <div class="grand-total-container">
                        Grand Total: <span id="grandTotalDisplay">0.00</span> PHP
                        <input type="hidden" name="grand_total_amount" id="grandTotalInput" value="0.00">
                    </div>

                    <div class="alignRight marginTop20">
                        <button class="orderProductBtn" id="orderProductBtn" type="button">Add New Product to Order</button>
                        <button class="submitOrderBtn" type="submit">Submit Order</button>
                    </div>
                </div>
            </form>
        </section>
    </main>
</div>

<script src="script.js"></script>
 <script>
    let productOrderCounter = 0; // Unique counter for each product row

    // Function to calculate and update the total price for a single product row
    function updateProductRowTotal(rowElement) {
        const quantityInput = rowElement.querySelector('.quantityInput');
        const priceInput = rowElement.querySelector('.productPriceInput'); // Hidden input for actual price
        const itemTotalDisplay = rowElement.querySelector('.itemTotalDisplay');

        const quantity = parseFloat(quantityInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;

        const itemTotal = quantity * price;
        itemTotalDisplay.textContent = itemTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        itemTotalDisplay.dataset.itemTotal = itemTotal; // Store raw value for grand total calculation

        updateGrandTotal();
    }

    // Function to calculate and update the overall grand total
    function updateGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.orderProductRow').forEach(rowElement => {
            const itemTotal = parseFloat(rowElement.querySelector('.itemTotalDisplay').dataset.itemTotal) || 0;
            grandTotal += itemTotal;
        });

        document.getElementById('grandTotalDisplay').textContent = grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('grandTotalInput').value = grandTotal.toFixed(2); // For submission
    }

    // --- JAVASCRIPT FOR AUTOCLOMPETE SEARCH ---
    // Moved attachProductAutocomplete outside ScriptApp to make it globally accessible
    function attachProductAutocomplete(searchInput, productIdInput, resultsContainer, priceDisplay, priceHiddenInput, quantityInput) {
        let timeout = null;
        let selectedIndex = -1;

        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const searchTerm = this.value;
            resultsContainer.innerHTML = '';
            productIdInput.value = '';
            priceDisplay.textContent = '0.00';
            priceDisplay.dataset.price = '0';
            priceHiddenInput.value = '0';
            updateProductRowTotal(searchInput.closest('.orderProductRow'));


            if (searchTerm.length < 1) {
                return;
            }

            timeout = setTimeout(() => {
                fetch(`database/search_selling_products.php?q=${encodeURIComponent(searchTerm)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        resultsContainer.innerHTML = '';
                        selectedIndex = -1;
                        if (data.error) {
                            resultsContainer.innerHTML = `<div style="color: red; padding: 8px 10px;">${data.error}</div>`;
                        } else if (data.length > 0) {
                            const ul = document.createElement('ul');
                            ul.classList.add('autocomplete-results');
                            data.forEach((product, index) => {
                                const li = document.createElement('li');
                                // Corrected currency symbol for Philippines Peso (PHP)
                                li.textContent = `${product.product_name} (Price: PHP ${parseFloat(product.price).toFixed(2)})`; 
                                li.dataset.productId = product.product_id;
                                li.dataset.productName = product.product_name;
                                li.dataset.productPrice = product.price;
                                li.addEventListener('click', function() {
                                    searchInput.value = this.dataset.productName;
                                    productIdInput.value = this.dataset.productId;
                                    priceDisplay.textContent = parseFloat(this.dataset.productPrice).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    priceDisplay.dataset.price = this.dataset.productPrice;
                                    priceHiddenInput.value = this.dataset.productPrice;
                                    resultsContainer.innerHTML = '';
                                    updateProductRowTotal(searchInput.closest('.orderProductRow'));
                                });
                                ul.appendChild(li);
                            });
                            resultsContainer.appendChild(ul);
                        } else {
                            resultsContainer.innerHTML = '<div style="padding: 8px 10px;">No products found.</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching products:', error);
                        resultsContainer.innerHTML = '<div style="color: red; padding: 8px 10px;">Error loading products.</div>';
                    });
            }, 300);
        });

        // Handle keyboard navigation (Arrow Up/Down, Enter)
        searchInput.addEventListener('keydown', function(e) {
            const results = resultsContainer.querySelector('.autocomplete-results');
            if (!results) return;

            const items = results.querySelectorAll('li');
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                updateSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex > -1) {
                    items[selectedIndex].click();
                }
            } else if (e.key === 'Escape') {
                resultsContainer.innerHTML = ''; // Hide results
            }
        });

        function updateSelection(items) {
            items.forEach((item, idx) => {
                if (idx === selectedIndex) {
                    item.classList.add('selected');
                    item.scrollIntoView({ block: 'nearest' }); // Scroll to selected item
                } else {
                    item.classList.remove('selected');
                }
            });
        }

        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.innerHTML = '';
            }
        });
    }
    // --- END JAVASCRIPT ---

    function ScriptApp() {
        var vm = this; // Store reference to ScriptApp instance

        this.initialize = function () {
            this.registerEvents();
        };

        this.registerEvents = function () {
            const orderProductLists = document.getElementById('orderProductLists');

            document.getElementById('orderProductBtn').addEventListener('click', function () {
                const productRow = document.createElement('div');
                productRow.className = 'orderProductRow';
                productRow.dataset.counter = productOrderCounter;
                productRow.innerHTML = `
                    <label><strong>PRODUCT NAME</strong></label>
                    <div style="position: relative;">
                        <input type="text"
                                class="productSearchInput"
                                placeholder="Search product..."
                                autocomplete="off"
                                required>
                        <input type="hidden"
                                class="productIdInput"
                                name="products[${productOrderCounter}][product_id]"
                                value="" required>
                        <div class="autocomplete-results-container"></div>
                    </div>
                    <div class="product-price-qty-row">
                        <div style="width: 30%;">
                            <label>Price:</label>
                            <span class="display-price" data-price="">0.00</span>
                            <input type="hidden" class="productPriceInput" name="products[${productOrderCounter}][price]" value="0">
                        </div>
                        <div style="width: 30%;">
                            <label for="quantity_${productOrderCounter}">Quantity:</label>
                            <input type="number"
                                        name="products[${productOrderCounter}][quantity]"
                                        id="quantity_${productOrderCounter}"
                                        class="quantityInput"
                                        placeholder="Qty"
                                        value="1" min="1" required>
                        </div>
                        <div style="width: 30%;">
                            <label>Total Price:</label>
                            <span class="display-price itemTotalDisplay" data-item-total="0.00">0.00</span>
                        </div>
                    </div>
                    <hr>
                    <div class="row-controls">
                        <button type="button" class="removeProductRowBtn">Remove Product Order</button>
                    </div>
                `;
                orderProductLists.appendChild(productRow);
                
                // Attach event listeners for the new autocomplete input
                const newProductSearchInput = productRow.querySelector('.productSearchInput');
                const newProductIdInput = productRow.querySelector('.productIdInput');
                const newSearchResultsContainer = productRow.querySelector('.autocomplete-results-container');
                const newProductPriceDisplay = productRow.querySelector('.display-price[data-price]');
                const newProductPriceInput = productRow.querySelector('.productPriceInput');
                const newQuantityInput = productRow.querySelector('.quantityInput');

                // Attach autocomplete for product search
                attachProductAutocomplete(
                    newProductSearchInput, 
                    newProductIdInput, 
                    newSearchResultsContainer, 
                    newProductPriceDisplay,
                    newProductPriceInput,
                    newQuantityInput // Pass quantity input for immediate calculation
                );

                // Add event listener for quantity change
                newQuantityInput.addEventListener('input', function() {
                    updateProductRowTotal(productRow);
                });

                productOrderCounter++;
                updateGrandTotal(); // Update grand total when a new row is added
            });

            // Delegation for removing product rows
            orderProductLists.addEventListener('click', function (e) {
                if (e.target.classList.contains('removeProductRowBtn')) {
                    const row = e.target.closest('.orderProductRow');
                    if (row) {
                        row.remove();
                        updateGrandTotal(); // Update grand total when a row is removed
                    }
                }
            });
        };
    }


    document.addEventListener('DOMContentLoaded', function () {
        (new ScriptApp()).initialize();
        // Trigger adding the first product order row automatically on page load
        document.getElementById('orderProductBtn').click(); 
    });

    // Sidebar submenu toggle (assuming this is for the main sidebar)
    document.querySelectorAll('.showHideSubmenu').forEach(item => {
        item.addEventListener('click', event => {
            const submenu = item.querySelector('.subMenus');
            if (submenu) {
                submenu.classList.toggle('active');
                item.querySelector('.fa-angle-left').classList.toggle('rotate');
            }
        });
    });

    // For the main header search input (if it's a global search)
    const mainSearchInput = document.getElementById('searchInput');
    const mainSearchResults = document.getElementById('searchResults');

    if (mainSearchInput && mainSearchResults) {
        mainSearchInput.addEventListener('input', function() {
            // Implement your global search logic here
        });
    }

    // Form submission validation
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        const customerName = document.getElementById('customer_name').value.trim();
        if (!customerName) {
            alert('Customer Name is required!');
            e.preventDefault(); // Stop form submission
            return;
        }

        const productRows = document.querySelectorAll('.orderProductRow');
        if (productRows.length === 0) {
            alert('Please add at least one product to the order!');
            e.preventDefault(); // Stop form submission
            return;
        }

        let allProductsValid = true;
        productRows.forEach(row => {
            const productId = row.querySelector('.productIdInput').value;
            const quantity = parseFloat(row.querySelector('.quantityInput').value);
            const price = parseFloat(row.querySelector('.productPriceInput').value); // Get the price from the hidden input

            if (!productId) {
                alert('Please select a product for all order entries.');
                allProductsValid = false;
                return;
            }
            if (isNaN(quantity) || quantity <= 0) {
                alert('Quantity must be a positive number for all ordered products.');
                allProductsValid = false;
                return;
            }
            if (isNaN(price) || price <= 0) {
                 alert('A valid price is required for all selected products.');
                 allProductsValid = false;
                 return;
            }
        });

        if (!allProductsValid) {
            e.preventDefault(); // Stop form submission
        }
    });

</script>
</body>
</html>