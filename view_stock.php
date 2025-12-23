<?php
session_start();
include('database/connect.php'); // Ensure this path is correct for your database connection

$user = isset($_SESSION['user']) ? $_SESSION['user'] : ['first_name' => 'Guest', 'last_name' => ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stocks - Keepkit</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="tables.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        .list {
            padding: 5px 10px;
            width: 45%; /* Adjusted for better responsiveness with new controls */
            font-weight: bold;
            margin-bottom: 20px;
            border: 1px solid black;
        }

        .stocks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .stocks-table th,
        .stocks-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .stocks-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            cursor: pointer; /* Indicate sortable columns */
            position: relative;
        }

        .stocks-table th .sort-icon {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8em;
        }

        .status-available {
            color: green;
            font-weight: bold;
        }

        .status-not-available {
            color: red;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #e6e6e6;
        }
        .search-sort-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap; /* Allows wrapping on smaller screens */
            gap: 10px; /* Space between search elements */
        }
        .search-input-group {
            display: flex;
            gap: 5px;
            flex-grow: 1; /* Allows it to take up available space */
        }
        .search-column-select, #searchInput, #searchButton {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        #searchButton {
            background-color: #a93131;
            color: white;
            cursor: pointer;
            border: none;
            font-size: 18px;
            padding: 0 30px;
        }
        #searchButton:hover {
            background: linear-gradient(to left, #a93131, #151515);
        }
        /* Override header search container */
        header .search-container {
            display: none; /* Hide the existing header search bar */
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
                            <a href="view_products.php" data-page="view_products.php" class="tablesLinks" >Products</a>
                        </li>
                        <li class="tablesItem">
                            <a href="view_stock.php" data-page="view_stock.php" class="tablesLinks" style="background-color: #151515;color: white;">Stock</a>
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

                <h1>Stock Table</h1>
                <p>Stock in, stock out. View and manage stock levels.</p> <br>

                <div class="search-sort-container">
                    <div class="search-input-group">
                        <select id="searchColumn" class="search-column-select">
                            <option value="product_name">Product Name</option>
                            <option value="stock"<?= ($currentSortColumn  <= 1) ? 'pc' : 'pcs' ?>>Quantity</option>
                            <option value="cost">Cost per product</option>
                            </select>
                        <input type="text" id="searchInput" placeholder="Search..." autocomplete="off">
                        <button id="searchButton">Search</button>
                    </div>
                </div>

                <div id="stockTableContainer">
                    <p>Loading stock data...</p>
                </div>
            </section>
        </main>
    </div> <script>
        $(document).ready(function() {
            let currentSortColumn = 'stock';
            let currentSortOrder = 'DESC';
            let currentSearchColumn = 'product_name';
            let currentSearchTerm = '';

            function fetchStockData() {
                $.ajax({
                    url: 'fetch_stocks.php', // This will fetch your stock data
                    type: 'GET',
                    data: {
                        sort_column: currentSortColumn,
                        sort_order: currentSortOrder,
                        search_column: currentSearchColumn,
                        search_term: currentSearchTerm
                    },
                    success: function(response) {
                        $('#stockTableContainer').html(response);
                        attachSortEventListeners(); // Re-attach listeners after content update
                    },
                    error: function(xhr, status, error) {
                        $('#stockTableContainer').html('<p>Error loading stock data: ' + error + '</p>');
                    }
                });
            }

            function attachSortEventListeners() {
                $('.stocks-table th').off('click').on('click', function() {
                    const column = $(this).data('column');
                    if (column) { // Only sortable columns have data-column
                        if (currentSortColumn === column) {
                            currentSortOrder = (currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
                        } else {
                            currentSortColumn = column;
                            currentSortOrder = 'ASC'; // Default to ASC when changing column
                        }
                        fetchStockData();
                    }
                });
            }

            $('#searchButton').on('click', function() {
                currentSearchColumn = $('#searchColumn').val();
                currentSearchTerm = $('#searchInput').val();
                fetchStockData();
            });

            $('#searchInput').on('keypress', function(e) {
                if (e.which === 13) { // Enter key pressed
                    $('#searchButton').click();
                }
            });

            $('#searchColumn').on('change', function() {
                // Clear search input when column changes to avoid type mismatches
                $('#searchInput').val('');
            });

            // Initial load of stock data
            fetchStockData();
        });
    </script>
    <script src="script.js"></script>
</body>
</html>