<?php
session_start();
include('database/connect.php'); 

$user = isset($_SESSION['user']) ? $_SESSION['user'] : ['first_name' => 'Guest', 'last_name' => ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Items - Keepkit</title>
    <link rel="stylesheet" href="home.css">
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        .list {
            padding: 10px 20px; /* Adjusted padding */
            width: 45%;
            font-weight: bold;
            margin-bottom: 20px;
            border: 1px solid #ccc; /* Lighter border */
            border-radius: 8px; /* Rounded corners */
            background-color: #f9f9f9; /* Light background */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Subtle shadow */
            color: #333; /* Darker text */
        }

        .order-items-table { /* Renamed for better semantic meaning */
            width: 100%;
            border-collapse: separate; /* Use separate for rounded corners on cells */
            border-spacing: 0; /* Remove space between cells */
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); /* More prominent shadow for the table */
            border-radius: 10px; /* Rounded corners for the whole table */
            overflow: hidden; /* Ensures rounded corners apply to content */
        }

        .order-items-table th,
        .order-items-table td {
            border: 1px solid #e0e0e0; /* Lighter cell borders */
            padding: 12px 15px; /* Increased padding */
            text-align: left;
            transition: background-color 0.3s ease; /* Smooth hover effect */
        }

        .order-items-table th {
            background-color: #007bff; /* Primary blue header background */
            color: white; /* White text for headers */
            font-weight: 600; /* Slightly bolder font weight */
            text-transform: uppercase; /* Uppercase headers */
            letter-spacing: 0.5px; /* Slight letter spacing */
            border-color: #007bff; /* Match border color to background */
            cursor: pointer; /* Indicate sortable columns */
            position: relative;
        }

        .order-items-table th .sort-icon {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8em;
        }

        .order-items-table tbody tr:nth-child(even) {
            background-color: #f8f8f8; /* Light gray for even rows */
        }

        .order-items-table tbody tr:hover {
            background-color: #e9ecef; /* Lighter gray on hover */
        }

        .total-row {
            background-color: #e0e0e0; /* Distinct background for total row */
            font-weight: bold;
            color: #333;
        }

        .total-row td {
            border-top: 2px solid #bbb; /* Thicker border on top of total row */
        }

        /* Adjusting for the first and last cell of the table for rounded corners */
        .order-items-table thead tr:first-child th:first-child {
            border-top-left-radius: 10px;
        }
        .order-items-table thead tr:first-child th:last-child {
            border-top-right-radius: 10px;
        }
        .order-items-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 10px;
        }
        .order-items-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 10px;
        }

        /* Status classes might not be directly applicable to order_items, 
            but keeping them in case you want to use them for something else later */
        .status-available { 
            color: green;
            font-weight: bold;
        }

        .status-not-available {
            color: red;
            font-weight: bold;
        }

        /* New styles for search and sort controls within the main content area */
        .local-search-sort-container { /* Renamed to avoid conflict with header search */
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap; /* Allows wrapping on smaller screens */
            gap: 10px; /* Space between search elements */
        }
        .local-search-input-group { /* Renamed to avoid conflict */
            display: flex;
            gap: 5px;
            flex-grow: 1; /* Allows it to take up available space */
        }
        .local-search-column-select, #localSearchInput, #localSearchButton { /* Renamed IDs */
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        #localSearchButton {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            border: none;
        }
        #localSearchButton:hover {
            background-color: #0056b3;
        }
        /* Ensure the header search container remains visible */
        header .search-container {
            display: flex; /* Keep the header search bar visible */
            /* Add any specific styling for the header search if needed */
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            <section id="orderItemsPage" class="active">
                <div class="list">Order Item Details</div>

                <div class="local-search-sort-container">
                    <div class="local-search-input-group">
                        <select id="localSearchColumn" class="local-search-column-select">
                            <option value="oi.order_id">Order ID</option>
                            <option value="o.customer_name">Customer Name</option>
                            <option value="p.product_name">Product Name</option>
                            <option value="oi.quantity">Quantity Ordered</option>
                            <option value="oi.price_at_order">Price at Order</option>
                            <option value="oi.item_total">Item Total</option>
                            <option value="o.transaction_date">Transaction Date</option>
                            <option value="user_name">Input Sale By</option>
                        </select>
                        <input type="text" id="localSearchInput" placeholder="Search order items..." autocomplete="off">
                        <button id="localSearchButton">Search</button>
                    </div>
                </div>

                <div id="orderItemsTableContainer">
                    <p>Loading order item data...</p>
                    </div>
            </section>
        </main>
    </div> <script>
        $(document).ready(function() {
            // Initial sort and search parameters for ORDER ITEMS
            let currentOrderItemSortColumn = 'oi.order_id';
            let currentOrderItemSortOrder = 'DESC';
            let currentOrderItemSearchColumn = 'oi.order_id'; // Default search column
            let currentOrderItemSearchTerm = ''; // Default empty search term

            function fetchOrderItemData() {
                $.ajax({
                    url: 'fetch_order_items.php', // This will fetch your order item data
                    type: 'GET',
                    data: {
                        sort_column: currentOrderItemSortColumn,
                        sort_order: currentOrderItemSortOrder,
                        search_column: currentOrderItemSearchColumn, // Pass search column
                        search_term: currentOrderItemSearchTerm     // Pass search term
                    },
                    success: function(response) {
                        $('#orderItemsTableContainer').html(response);
                        // Re-attach sort event listeners after new content is loaded
                        attachOrderItemSortEventListeners();
                    },
                    error: function(xhr, status, error) {
                        $('#orderItemsTableContainer').html('<p>Error loading order item data: ' + error + '</p>');
                    }
                });
            }

            // Function to attach click listeners for sorting to Order Items table headers
            function attachOrderItemSortEventListeners() {
                // IMPORTANT: Target the table headers within the #orderItemsTableContainer
                $('#orderItemsTableContainer .order-items-table th').off('click').on('click', function() {
                    const column = $(this).data('column');
                    if (column) { // Only sortable columns will have a data-column attribute
                        if (currentOrderItemSortColumn === column) {
                            // If same column, toggle order
                            currentOrderItemSortOrder = (currentOrderItemSortOrder === 'ASC') ? 'DESC' : 'ASC';
                        } else {
                            // If new column, set it and default to ASC
                            currentOrderItemSortColumn = column;
                            currentOrderItemSortOrder = 'ASC';
                        }
                        fetchOrderItemData(); // Fetch data with new sort parameters
                    }
                });
            }

            // Event listener for the NEW local search button
            $('#localSearchButton').on('click', function() {
                currentOrderItemSearchColumn = $('#localSearchColumn').val(); // Get selected search column
                currentOrderItemSearchTerm = $('#localSearchInput').val();   // Get search input value
                fetchOrderItemData(); // Fetch data with new search parameters
            });

            // Event listener for Enter key on NEW local search input
            $('#localSearchInput').on('keypress', function(e) {
                if (e.which === 13) { // 13 is the Enter key code
                    $('#localSearchButton').click(); // Simulate click on search button
                }
            });

            // Clear search input when the NEW local search column changes
            $('#localSearchColumn').on('change', function() {
                $('#localSearchInput').val('');
            });

            // Initial load of order item data when the page loads
            fetchOrderItemData();

            // --- Existing header search functionality (if any) should remain here ---
            // You might have JavaScript for #searchInput and #searchResults in script.js or here.
            // Make sure it doesn't conflict with the new local search elements.
            // For example, if script.js handles #searchInput, ensure it only targets that specific ID
            // and not a general class that might affect the new local search.
        });
    </script>
    <script src="script.js"></script>
</body>
</html>