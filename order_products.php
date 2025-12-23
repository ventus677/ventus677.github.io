<?php
session_start();
$_SESSION['table'] = 'products';
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
    <link rel="stylesheet" href="products.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
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

        .orderProductBtn {
            height: 33px;
            border: none;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 16px;
            text-decoration: none; /* This removes the underline */
            display: inline-block; /* Treat it as a block element so padding works correctly */
            line-height: 33px; /* Center text vertically if height is fixed */
            text-align: center; /* Center text horizontally */
        }
        .orderProductBtn { background: #ffa53c; color: #fff; }
        .submitorderProductBtn {
            background: #323232;
            color: #fff;
            font-weight: bold;
            margin-left: 10px; /* Add some space between buttons */
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .marginTop20 { margin-top: 20px; }
        div.row { display: flex; flex-direction: row; flex-wrap: wrap; width: 100%; }
        .alignRight { text-align:right; }
        .supplierName {
            margin-left: 12px;
            font-size: 15px;
            font-weight: bold;
            color: #a1a1a1;
            text-transform: uppercase;
        }
        .supplier-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px 0px;
        }
        .removeSupplierBtn {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
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
        .removeProductRowBtn {
            background-color: #a93131;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
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
            width: calc(100% - 30px); /* Adjust based on parent padding/margins */
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
            border: 2px solid #ffa53c;
            box-shadow: 0 0 5px rgba(0,0,0,0.3);
        }

        .user-name span.first-name,
        .user-name span.last-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* New styles for cost and total display */
        .product-cost-display, .product-subtotal-cost-display, .overall-total-cost-display {
            margin-left: 12px;
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }
        .overall-total-cost-display {
            font-size: 20px;
            color: #a93131;
            text-align: right;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .price-quantity-row { /* Renaming this class might be good, e.g., cost-quantity-row */
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        .price-quantity-row > div {
            flex: 1;
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
            <form action="database/save_order.php" method="POST">
                <div class="container">
                    <div class="header"><span class="plus-icon">+</span> Order Product</div>
                        <div class="alignRight marginTop20">
                            <a href="view_order.php" class="ordertBtn submitorderProductBtn">View Orders</a>
                        </div>                 
                    <?php if (!empty($response_message)): ?>
                        <div class="message <?= $response_success ? 'success' : 'error' ?>">
                            <?= htmlspecialchars($response_message) ?>
                        </div>
                    <?php endif; ?>

                    <div id="orderProductLists"></div>

                    <div class="overall-total-cost-display">
                        <strong>Total Purchase Cost: &#8369; <span id="overallTotalCost">0.00</span></strong>
                    </div>

                    <div class="alignRight marginTop20">
                        <button class="orderProductBtn" id="orderProductBtn" type="button">Add New Product Order</button>
                        <button class="submitorderProductBtn" type="submit">Submit Order</button>
                    </div>
                </div>
            </form>
        </section>
    </main>
</div>
</body>
</html>

<script>
    let productOrderCounter = 0;

    function ScriptApp() {
        var vm = this;

        this.initialize = function () {
            this.registerEvents();
            // Recalculate total on page load in case of previously submitted data
            this.calculateOverallTotalCost();
        };

        this.registerEvents = function () {
            const orderProductLists = document.getElementById('orderProductLists');

            document.getElementById('orderProductBtn').addEventListener('click', function () {
                const productRow = document.createElement('div');
                productRow.className = 'orderProductRow';
                productRow.dataset.counter = productOrderCounter;
                productRow.innerHTML = `
                    ${vm.getProductSelectHtml(productOrderCounter)}
                    <div class="supplierRows" id="supplierRows_${productOrderCounter}" data-counter="${productOrderCounter}"></div>
                    <hr>
                    <div class="price-quantity-row">
                        <div>
                            <label><strong>Unit Cost:</strong></label>
                            <span class="product-cost-display" id="productCostDisplay_${productOrderCounter}">&#8369; 0.00</span>
                            <input type="hidden" class="hiddenProductCost" id="hiddenProductCost_${productOrderCounter}" value="0">
                        </div>
                        <div style="text-align: right;">
                            <label><strong>Product Subtotal Cost:</strong></label>
                            <span class="product-subtotal-cost-display" id="productSubtotalCostDisplay_${productOrderCounter}">&#8369; 0.00</span>
                            <input type="hidden" class="hiddenProductSubtotalCost" id="hiddenProductSubtotalCost_${productOrderCounter}" value="0">
                        </div>
                    </div>
                    <div class="row-controls">
                        <button type="button" class="removeProductRowBtn">Remove Product Order</button>
                    </div>
                `;
                orderProductLists.appendChild(productRow);
                
                // Attach event listeners for the new autocomplete input
                const newProductSearchInput = productRow.querySelector('.productSearchInput');
                const newProductIdInput = productRow.querySelector('.productIdInput');
                const newSearchResultsDiv = productRow.querySelector('.autocomplete-results-container');
                
                // Pass vm instance to attachProductAutocomplete
                attachProductAutocomplete(newProductSearchInput, newProductIdInput, newSearchResultsDiv, productOrderCounter, vm);

                productOrderCounter++;
                vm.calculateOverallTotalCost(); // Recalculate total after adding a new row
            });

            orderProductLists.addEventListener('click', function (e) {
                if (e.target.classList.contains('removeProductRowBtn')) {
                    const row = e.target.closest('.orderProductRow');
                    if (row) {
                        row.remove();
                        vm.calculateOverallTotalCost(); // Recalculate total after removing a row
                    }
                }
                if (e.target.classList.contains('removeSupplierBtn')) {
                    const supplierRow = e.target.closest('.supplier-row');
                    if (supplierRow) {
                        supplierRow.remove();
                        // Removing a supplier doesn't directly affect product subtotal (unless you want to treat it as multiple purchases)
                        // For this implementation, the product subtotal is based on the single product cost and total quantity across all suppliers for that product.
                        vm.calculateProductSubtotalCost(supplierRow.dataset.counter);
                    }
                }
            });

            // Delegate change event for quantity inputs to recalculate product subtotal
            orderProductLists.addEventListener('input', function(e) {
                if (e.target.name && e.target.name.startsWith('quantity')) {
                    const productCounterId = e.target.closest('.orderProductRow').dataset.counter;
                    vm.calculateProductSubtotalCost(productCounterId);
                }
            });
        };

        this.getProductSelectHtml = function(currentCounter) {
            // We're replacing the <select> with a text input for autocomplete
            return `
                <label><strong>PRODUCT NAME</strong></label>
                <div style="position: relative;">
                    <input type="text"
                           class="productSearchInput"
                           placeholder="Search product..."
                           autocomplete="off">
                    <input type="hidden"
                           class="productIdInput"
                           name="products[${currentCounter}]"
                           value="">
                    <div class="autocomplete-results-container"></div>
                </div>
            `;
        };

        this.renderSupplierRows = function(suppliers, productCost, currentProductCounterId) {
            let html = '';
            if (suppliers.length === 0) {
                html = '<p style="margin-left: 12px; color: #a1a1a1;">No suppliers found for this product.</p>';
            } else {
                suppliers.forEach(supplier => {
                    html += `
                        <div class="supplier-row" data-counter="${currentProductCounterId}">
                            <div style="width: 25%;">
                                <p class="supplierName"><strong>${supplier.supplier_name}</strong></p>
                            </div>
                            <div style="width: 20%;">
                                <label for="quantity_${currentProductCounterId}_${supplier.supplier_id}">Qty:</label>
                                <input type="number"
                                       name="quantity[${currentProductCounterId}][${supplier.supplier_id}]"
                                       id="quantity_${currentProductCounterId}_${supplier.supplier_id}"
                                       placeholder="Qty"
                                       min="0"
                                       value="0"
                                       class="quantity-input"
                                       style="width: 80%; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div style="width: 5%; text-align: right;">
                                <button type="button" class="removeSupplierBtn" data-supplier-id="${supplier.supplier_id}" data-counter="${currentProductCounterId}">Remove</button>
                            </div>
                        </div>
                    `;
                });
            }

            const supplierContainer = document.getElementById(`supplierRows_${currentProductCounterId}`);
            if (supplierContainer) {
                supplierContainer.innerHTML = html;
            }

            // Update product cost display and hidden input
            const productCostDisplay = document.getElementById(`productCostDisplay_${currentProductCounterId}`);
            const hiddenProductCost = document.getElementById(`hiddenProductCost_${currentProductCounterId}`);
            if (productCostDisplay && hiddenProductCost) {
                productCostDisplay.textContent = `₱ ${parseFloat(productCost).toFixed(2)}`;
                hiddenProductCost.value = parseFloat(productCost).toFixed(2);
            }

            this.calculateProductSubtotalCost(currentProductCounterId); // Calculate initial subtotal
        };

        this.calculateProductSubtotalCost = function(productCounterId) {
            const productRow = document.querySelector(`.orderProductRow[data-counter="${productCounterId}"]`);
            if (!productRow) return;

            const unitCost = parseFloat(productRow.querySelector(`#hiddenProductCost_${productCounterId}`).value);
            let totalQuantity = 0;
            productRow.querySelectorAll('.quantity-input').forEach(input => {
                totalQuantity += parseInt(input.value) || 0;
            });

            const subtotalCost = unitCost * totalQuantity;
            productRow.querySelector(`#productSubtotalCostDisplay_${productCounterId}`).textContent = `₱ ${subtotalCost.toFixed(2)}`;
            productRow.querySelector(`#hiddenProductSubtotalCost_${productCounterId}`).value = subtotalCost.toFixed(2);

            this.calculateOverallTotalCost();
        };

        this.calculateOverallTotalCost = function() {
            let overallTotalCost = 0;
            document.querySelectorAll('.hiddenProductSubtotalCost').forEach(input => {
                overallTotalCost += parseFloat(input.value) || 0;
            });
            document.getElementById('overallTotalCost').textContent = overallTotalCost.toFixed(2);
        };
    }

    // --- JAVASCRIPT FOR AUTOCLOMPETE SEARCH ---
    let currentSelectedProduct = null; // To track selection for keyboard navigation

    // Now accepts vmInstance as an argument
    function attachProductAutocomplete(searchInput, hiddenInput, resultsContainer, counterId, vmInstance) {
        let timeout = null;
        let selectedIndex = -1;

        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value;
            resultsContainer.innerHTML = ''; // Clear previous results
            hiddenInput.value = ''; // Clear hidden input when typing
            // Also clear supplier rows, cost, and subtotal when product input is cleared
            document.getElementById(`supplierRows_${counterId}`).innerHTML = '';
            document.getElementById(`productCostDisplay_${counterId}`).textContent = '₱ 0.00';
            document.getElementById(`hiddenProductCost_${counterId}`).value = '0';
            document.getElementById(`productSubtotalCostDisplay_${counterId}`).textContent = '₱ 0.00';
            document.getElementById(`hiddenProductSubtotalCost_${counterId}`).value = '0';
            vmInstance.calculateOverallTotalCost();


            if (query.length < 1) { // Start search after 1 character
                return;
            }

            timeout = setTimeout(() => {
                fetch(`database/search_products.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(products => {
                        resultsContainer.innerHTML = ''; // Clear previous results
                        selectedIndex = -1; // Reset selection

                        if (products.length > 0) {
                            const ul = document.createElement('ul');
                            ul.classList.add('autocomplete-results');
                            products.forEach((product, index) => {
                                const li = document.createElement('li');
                                li.textContent = product.product_name;
                                li.dataset.productId = product.id;
                                li.dataset.productName = product.product_name;
                                // No longer storing product cost in dataset directly, fetched from get_supplierproduct.php
                                li.addEventListener('click', function() {
                                    searchInput.value = this.dataset.productName;
                                    const selectedProductId = this.dataset.productId;
                                    hiddenInput.value = selectedProductId;
                                    resultsContainer.innerHTML = ''; // Hide results

                                    // Directly call renderSupplierRows using the passed vmInstance
                                    fetch(`database/get_supplierproduct.php?id=${selectedProductId}`)
                                        .then(res => {
                                            if (!res.ok) {
                                                // If response is not OK, throw an error to catch it
                                                return res.text().then(text => { throw new Error(`HTTP error! Status: ${res.status} - ${res.statusText}. Response: ${text}`); });
                                            }
                                            return res.json();
                                        })
                                        .then(data => { // data will contain product_cost and suppliers
                                            // Assuming the backend (get_supplierproduct.php) now returns 'product_cost' instead of 'product_price'
                                            vmInstance.renderSupplierRows(data.suppliers, data.product_cost, counterId); // Use vmInstance
                                        })
                                        .catch(err => {
                                            console.error('Error fetching suppliers and product cost:', err);
                                            alert(`Failed to load supplier list and product cost: ${err.message}`);
                                        });
                                });
                                ul.appendChild(li);
                            });
                            resultsContainer.appendChild(ul);
                        } else {
                            const noResults = document.createElement('div');
                            noResults.textContent = 'No products found.';
                            noResults.style.padding = '8px 10px';
                            noResults.style.color = '#888';
                            resultsContainer.appendChild(noResults);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching products:', error);
                        resultsContainer.innerHTML = '<div style="color: red; padding: 8px 10px;">Error loading products.</div>';
                    });
            }, 300); // Debounce search
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

    document.addEventListener('DOMContentLoaded', function () {
        (new ScriptApp()).initialize();
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
    // You might need a separate function for this search bar if it has a different purpose
    const mainSearchInput = document.getElementById('searchInput');
    const mainSearchResults = document.getElementById('searchResults');

    if (mainSearchInput && mainSearchResults) {
        // Implement your global search logic here, similar to attachProductAutocomplete
        // but perhaps redirecting to a search results page or filtering a main table.
        // For now, it's just a placeholder.
        mainSearchInput.addEventListener('input', function() {
            // console.log("Main search input:", this.value);
            // Example: If this is for a global product search page
            // fetch(`search_global_products.php?q=${encodeURIComponent(this.value)}`)
            // .then(response => response.json())
            // .then(data => { /* render global results */ });
        });
    }
</script>