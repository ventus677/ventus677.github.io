<?php
session_start();
include('database/connect.php');

// Fetch user data from session, with defaults for safety
$user = $_SESSION['user'] ?? null;

// Redirect if user is not set or empty
if (!isset($user) || empty($user)) {
    header('Location: index.php');
    exit;
}

// Include the search and sort logic file. This file *must* populate $sales_data.
include('selling_products_search.php');

// Get messages from URL parameters if redirected from edit/delete
$message = $_GET['message'] ?? '';
$message_type = $_GET['type'] ?? ''; // 'success' or 'error'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - Keepkit</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="tables.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Your existing CSS styles from the previous view_sales.php file */
        .list {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .list h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .search-sort-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap; /* Allows items to wrap on smaller screens */
        }

        .search-sort-container label {
            font-weight: bold;
        }

        .search-sort-container input[type="text"],
        .search-sort-container select,
        .search-sort-container button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .search-sort-container button {
            background-color: #a93131;
            color: white;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s ease;
        }

        .search-sort-container button:hover {
            background-color: #ffa53c;
        }

        .reset-button {
            background-color: #6c757d;
        }

        .reset-button:hover {
            background-color: #5a6268;
        }

        .table-responsive {
            overflow-x: auto; /* Enables horizontal scrolling for tables */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #e9ecef;
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
        }

        table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            cursor: pointer; /* Indicates sortable columns */
        }

        table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table tbody tr:hover {
            background-color: #e2e6ea;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons button {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .action-buttons .edit-btn {
            background-color: #ffc107;
            color: #333;
        }

        .action-buttons .edit-btn:hover {
            background-color: #e0a800;
        }

        .action-buttons .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .action-buttons .delete-btn:hover {
            background-color: #c82333;
        }
        /* Styles for the user profile section in the sidebar */
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

        /* Styles for messages */
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
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
    </style>
</head>
<body>
    <header>
        <a href="home.php" id="navbar__logo">
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
                            <a href="view_stock.php" data-page="view_stock.php" class="tablesLinks">Stock</a>
                        </li>
                        <li class="tablesItem">
                            <a href="view_sales.php" data-page="view_sales.php" class="tablesLinks" style="background-color: #151515;color: white;">Sales</a>
                        </li>
                        <li class="tablesItem">
                            <a href="view_order.php" data-page="view_order.php" class="tablesLinks">Orders</a>
                        </li>
                    </div>
                </div>
            </nav>
            <br><br><br>

                <h1>Sales Log</h1>
                <p>View and manage your sales records.</p> <br>

                <?php if (!empty($message)): ?>
                    <div class="message <?= htmlspecialchars($message_type) ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                    <div class="search-sort-container">
                        <input type="text" id="salesSearchInput" name="search_query" placeholder="Search sales..." value="<?= htmlspecialchars($_GET['search_query'] ?? '') ?>">
                        <select id="salesSearchColumn" name="search_column">
                            <option value="customer_name" <?= (($_GET['search_column'] ?? '') == 'customer_name') ? 'selected' : '' ?>>Customer Name</option>
                            <option value="order_id" <?= (($_GET['search_column'] ?? '') == 'order_id') ? 'selected' : '' ?>>Order ID</option>
                            <option value="total_amount" <?= (($_GET['search_column'] ?? '') == 'total_amount') ? 'selected' : '' ?>>Total Amount</option>
                            <option value="order_status" <?= (($_GET['search_column'] ?? '') == 'order_status') ? 'selected' : '' ?>>Order Status</option>
                            <option value="payment_method" <?= (($_GET['search_column'] ?? '') == 'payment_method') ? 'selected' : '' ?>>Payment Method</option>
                            </select>
                        <button id="searchSalesBtn">Search</button>
                        <button id="resetSalesBtn" class="reset-button">Reset</button>
                    </div>

                    <div class="table-responsive">
                        <table id="salesTable">
                            <thead>
                                <tr>
                                    <th data-column="order_id">Order ID <span class="sort-icon"></span></th>
                                    <th data-column="customer_name">Customer Name <span class="sort-icon"></span></th>
                                    <th data-column="total_amount">Total Amount <span class="sort-icon"></span></th>
                                    <th data-column="order_status">Status <span class="sort-icon"></span></th>
                                    <th data-column="payment_method">Payment Method <span class="sort-icon"></span></th>
                                    <th data-column="created_at">Order Date <span class="sort-icon"></span></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($sales_data['error'])): ?>
                                    <tr>
                                        <td colspan="8" style="color: red; font-weight: bold; text-align: center;">
                                            Error: <?= htmlspecialchars($sales_data['error']) ?>
                                            <br>Please check your database connection (`database/connect.php`) or server logs for more details.
                                        </td>
                                    </tr>
                                <?php elseif (!empty($sales_data)): ?>
                                    <?php foreach ($sales_data as $sale): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($sale['order_id']) ?></td>
                                            <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                            <td>₱<?= htmlspecialchars(number_format($sale['total_amount'], 2)) ?></td>
                                            <td><?= htmlspecialchars($sale['order_status'] ?? 'completed') ?></td>
                                            <td><?= htmlspecialchars($sale['payment_method'] ?? 'Cash on Delivery') ?></td>
                                            <td><?= htmlspecialchars($sale['created_at']) ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="edit-btn" onclick="location.href='edit_sale.php?id=<?= htmlspecialchars($sale['order_id']) ?>'">Edit</button>
                                                    <button class="delete-btn" onclick="if(confirm('Are you sure you want to delete this sales record? This action cannot be undone.')) { location.href='delete_sale.php?id=<?= htmlspecialchars($sale['order_id']) ?>'; }">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8">No sales records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                
            </section>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
    <script>
        $(document).ready(function() {
            const salesSearchInput = $('#salesSearchInput');
            const salesSearchColumn = $('#salesSearchColumn');
            const searchSalesBtn = $('#searchSalesBtn');
            const resetSalesBtn = $('#resetSalesBtn');
            const sortableHeaders = $('#salesTable th[data-column]');

            // Function to update URL and trigger page reload for search/sort
            function applySearchAndSort() {
                const urlParams = new URLSearchParams(window.location.search);

                const searchQuery = salesSearchInput.val();
                const searchColumn = salesSearchColumn.val();

                if (searchQuery) {
                    urlParams.set('search_query', searchQuery);
                    urlParams.set('search_column', searchColumn);
                } else {
                    urlParams.delete('search_query');
                    urlParams.delete('search_column');
                }

                // Preserve sorting parameters if they exist from a previous click
                const currentSortColumn = urlParams.get('sort_column');
                const currentSortOrder = urlParams.get('sort_order');
                if (currentSortColumn && currentSortOrder) {
                     urlParams.set('sort_column', currentSortColumn);
                     urlParams.set('sort_order', currentSortOrder);
                }

                // Remove message parameters before reloading
                urlParams.delete('message');
                urlParams.delete('type');

                window.location.href = '?' + urlParams.toString();
            }

            searchSalesBtn.on('click', applySearchAndSort);

            salesSearchInput.on('keypress', function(e) {
                if (e.which === 13) { // Enter key pressed
                    applySearchAndSort();
                }
            });

            resetSalesBtn.on('click', function() {
                window.location.href = 'view_sales.php'; // Simply reload the page without params
            });

            // Event Listeners for Sortable Headers
            sortableHeaders.on('click', function() {
                const column = $(this).data('column');
                const urlParams = new URLSearchParams(window.location.search);

                let currentSortColumn = urlParams.get('sort_column');
                let currentSortOrder = urlParams.get('sort_order');
                let newSortOrder = 'ASC'; // Default to ASC

                // Directly use the column from data-column as it now matches database/aliased names
                const dbColumn = column;

                if (currentSortColumn === dbColumn) {
                    newSortOrder = (currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
                } else {
                    currentSortColumn = dbColumn;
                    newSortOrder = 'ASC';
                }

                urlParams.set('sort_column', currentSortColumn);
                urlParams.set('sort_order', newSortOrder);

                // Preserve search query if it exists
                const searchQuery = salesSearchInput.val();
                const searchColumn = salesSearchColumn.val();
                if (searchQuery && searchColumn) {
                    urlParams.set('search_query', searchQuery);
                    urlParams.set('search_column', searchColumn);
                } else {
                    // Clear search parameters if the fields are empty
                    urlParams.delete('search_query');
                    urlParams.delete('search_column');
                }

                // Remove message parameters before reloading
                urlParams.delete('message');
                urlParams.delete('type');

                window.location.href = '?' + urlParams.toString();
            });

            // Apply active class to sidebar link
            const currentPage = 'view_sales.php';
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                if (link.dataset.page === currentPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });

            // Initial sort icon display
            const initialSortCol = new URLSearchParams(window.location.search).get('sort_column');
            const initialSortOrder = new URLSearchParams(window.location.search).get('sort_order');
            if (initialSortCol) {
                // Use the initialSortCol directly as it now correctly matches the data-column attribute
                const header = $(`#salesTable th[data-column="${initialSortCol}"]`);
                if (header.length) {
                    const sortIcon = initialSortOrder === 'ASC' ? '▲' : '▼';
                    header.find('.sort-icon').text(sortIcon);
                }
            }
        });
    </script>
</body>
</html>