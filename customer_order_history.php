<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Redirect to login page if user is not logged in or session user data is empty
$user = (array) ($_SESSION['user'] ?? []);
if (empty($user)) {
    header('Location: index.php');
    exit;
}

// Fetch permissions for the logged-in user
$user_permissions = (array) ($user['permissions'] ?? []);

// Check if current user has permission to view customer orders
if (!isset($user_permissions['customer']) || !in_array('view', $user_permissions['customer'])) {
    $_SESSION['response'] = [
        'message' => 'You do not have permission to view customer order history.',
        'success' => false
    ];
    header('Location: home.php'); // Redirect to home or an unauthorized page
    exit;
}

include('database/connect.php'); // Ensure the path to the database connection is correct

$customer_id = $_GET['customer_id'] ?? null;
$customer_details = null;
$orders = [];

// Retrieve search and sort parameters from the URL
$search_query = $_GET['search_query'] ?? '';
$search_column = $_GET['search_column'] ?? 'order_id'; // Default search column
$sort_column = $_GET['sort_column'] ?? 'order_date'; // Default sort column
$sort_order = $_GET['sort_order'] ?? 'DESC'; // Default sort order

// Basic validation for the sort order to prevent SQL injection.
$allowed_sort_orders = ['ASC', 'DESC'];
if (!in_array(strtoupper($sort_order), $allowed_sort_orders)) {
    $sort_order = 'DESC'; // Fallback to 'DESC' if an invalid sort order is provided.
}

// Mapping of frontend column names to actual SQL alias names in orders_user table.
$column_map = [
    'order_id' => 'ou.id',
    'order_date' => 'ou.order_date',
    'total_amount' => 'ou.total_amount',
    'status' => 'ou.status',
    'shipping_address' => 'ou.shipping_address',
    'payment_method' => 'ou.payment_method',
    'products_summary' => 'products_summary'
];

// Validate the sort column using the mapping; default to 'order_date' if not valid.
$sql_sort_column = $column_map[$sort_column] ?? 'ou.order_date';


if ($customer_id && is_numeric($customer_id)) {
    try {
        // Fetch customer details from users table
        $stmt_customer = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ? LIMIT 1");
        $stmt_customer->execute([$customer_id]);
        $customer_details = $stmt_customer->fetch(PDO::FETCH_ASSOC);

        if ($customer_details) {
            // UPDATED SQL: Using orders_user and order_products_user based on your requirement
            $sql_orders = "
                SELECT
                    ou.id,
                    ou.order_date,
                    ou.total_amount,
                    ou.status,
                    ou.shipping_address,
                    ou.payment_method,
                    GROUP_CONCAT(opu.product_name ORDER BY opu.product_name SEPARATOR ', ') AS products_summary
                FROM
                    orders_user ou
                LEFT JOIN
                    order_products_user opu ON ou.id = opu.order_id
                WHERE
                    ou.user_id = ?
            ";
            $params = [$customer_id];

            // Add search conditions if a search query is provided
            if (!empty($search_query)) {
                $search_term = '%' . $search_query . '%';
                if (array_key_exists($search_column, $column_map)) {
                    $sql_orders .= " AND " . $column_map[$search_column] . " LIKE ?";
                    $params[] = $search_term;
                }
            }

            // Group by order details to aggregate products_summary correctly
            $sql_orders .= "
                GROUP BY
                    ou.id, ou.order_date, ou.total_amount, ou.status, ou.shipping_address, ou.payment_method
            ";

            // Append the ORDER BY clause
            $sql_orders .= " ORDER BY {$sql_sort_column} {$sort_order}";


            $stmt_orders = $conn->prepare($sql_orders);
            $stmt_orders->execute($params);
            $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

        } else {
            $_SESSION['response'] = [
                'message' => 'Account not found.',
                'success' => false
            ];
            header('Location: customer_list.php');
            exit;
        }

    } catch (PDOException $e) {
        error_log("Database error fetching order history: " . $e->getMessage());
        $_SESSION['response'] = [
            'message' => 'Database Error: ' . $e->getMessage(),
            'success' => false
        ];
        header('Location: customer_list.php');
        exit;
    }
} else {
    $_SESSION['response'] = [
        'message' => 'Invalid ID provided.',
        'success' => false
    ];
    header('Location: customer_list.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History for <?= htmlspecialchars($customer_details['first_name'] . ' ' . $customer_details['last_name']) ?> - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="users.css"/> <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .responseMessage {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 300px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            opacity: 0; 
            transition: opacity 0.5s ease-in-out;
            z-index: 1000;
        }

        .responseMessage p {
            padding: 15px 20px;
            margin: 0;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .responseMessage_success {
            color: #28a745;
            border-left: 5px solid #28a745;
        }

        .responseMessage_error {
            color: #dc3545;
            border-left: 5px solid #dc3545;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .order-history-container {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .order-history-container h3 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        .customer-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .customer-info p {
            margin: 5px 0;
            color: #555;
            font-size: 1.1em;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .orders-table th, .orders-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .orders-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
            cursor: pointer; 
        }
        .orders-table th .sort-icon {
            margin-left: 5px;
            font-size: 0.8em;
            vertical-align: middle;
        }
        .orders-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .orders-table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .no-orders-message {
            text-align: center;
            padding: 50px;
            color: #777;
            font-size: 1.2em;
        }
        .back-button-container {
            margin-top: 20px;
            text-align: center;
        }
        .back-button {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            transition: background-color 0.2s ease;
        }
        .back-button:hover {
            background-color: #5a6268;
        }

        .search-sort-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
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
            background-color: #007bff;
            color: white;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s ease;
        }

        .search-sort-container button:hover {
            background-color: #0056b3;
        }

        .reset-button {
            background-color: #6c757d;
        }

        .reset-button:hover {
            background-color: #5a6268;
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
            <section id="customerOrderHistoryPage" class="active">
                <h3>Order History for <?= htmlspecialchars($customer_details['first_name'] . ' ' . $customer_details['last_name']) ?></h3>
                <p class="customer-info">Email: <?= htmlspecialchars($customer_details['email']) ?></p>
                <br>
                <div class="search-sort-container">
                    <label for="orderSearchInput">Search:</label>
                    <input type="text" id="orderSearchInput" name="search_query" placeholder="Search orders..." value="<?= htmlspecialchars($search_query) ?>">
                    <select id="orderSearchColumn" name="search_column">
                        <option value="order_id" <?= (($search_column ?? '') == 'order_id') ? 'selected' : '' ?>>Order ID</option>
                        <option value="order_date" <?= (($search_column ?? '') == 'order_date') ? 'selected' : '' ?>>Order Date</option>
                        <option value="total_amount" <?= (($search_column ?? '') == 'total_amount') ? 'selected' : '' ?>>Total Amount</option>
                        <option value="status" <?= (($search_column ?? '') == 'status') ? 'selected' : '' ?>>Status</option>
                        <option value="shipping_address" <?= (($search_column ?? '') == 'shipping_address') ? 'selected' : '' ?>>Shipping Address</option>
                        <option value="payment_method" <?= (($search_column ?? '') == 'payment_method') ? 'selected' : '' ?>>Payment Method</option>
                        <option value="products_summary" <?= (($search_column ?? '') == 'products_summary') ? 'selected' : '' ?>>Product Name</option>
                    </select>
                    <button id="searchOrdersBtn">Search</button>
                    <button id="resetOrdersBtn" class="reset-button">Reset</button>
                </div>

                <div class="section_content">
                    <?php if (!empty($orders)): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th data-column="order_id">Order ID <span class="sort-icon"></span></th>
                                    <th data-column="order_date">Order Date <span class="sort-icon"></span></th>
                                    <th data-column="total_amount">Total Amount <span class="sort-icon"></span></th>
                                    <th data-column="status">Status <span class="sort-icon"></span></th>
                                    <th data-column="shipping_address">Shipping Address <span class="sort-icon"></span></th>
                                    <th data-column="payment_method">Payment Method <span class="sort-icon"></span></th>
                                    <th data-column="products_summary">Products <span class="sort-icon"></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['id'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($order['order_date'] ?? 'N/A') ?></td>
                                        <td>₱<?= htmlspecialchars(number_format($order['total_amount'] ?? 0, 2)) ?></td>
                                        <td><?= htmlspecialchars($order['status'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($order['shipping_address'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($order['products_summary'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-orders-message">No orders found for this account.</p>
                    <?php endif; ?>
                </div>
                <div class="back-button-container">
                    <a href="customer_list.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Accounts</a>
                </div>
            </section>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
    <script>
        $(document).ready(function() {
            const orderSearchInput = $('#orderSearchInput');
            const orderSearchColumn = $('#orderSearchColumn');
            const searchOrdersBtn = $('#searchOrdersBtn');
            const resetOrdersBtn = $('#resetOrdersBtn');
            const sortableHeaders = $('.orders-table th[data-column]');

            function applySearchAndSort() {
                const urlParams = new URLSearchParams(window.location.search);
                const searchQuery = orderSearchInput.val();
                const searchColumn = orderSearchColumn.val();
                const customerId = urlParams.get('customer_id');
                urlParams.set('customer_id', customerId);

                if (searchQuery) {
                    urlParams.set('search_query', searchQuery);
                    urlParams.set('search_column', searchColumn);
                } else {
                    urlParams.delete('search_query');
                    urlParams.delete('search_column');
                }

                const currentSortColumn = urlParams.get('sort_column');
                const currentSortOrder = urlParams.get('sort_order');
                if (currentSortColumn && currentSortOrder) {
                     urlParams.set('sort_column', currentSortColumn);
                     urlParams.set('sort_order', currentSortOrder);
                }
                window.location.href = '?' + urlParams.toString();
            }

            searchOrdersBtn.on('click', applySearchAndSort);
            orderSearchInput.on('keypress', function(e) { if (e.which === 13) applySearchAndSort(); });

            resetOrdersBtn.on('click', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const customerId = urlParams.get('customer_id');
                window.location.href = 'customer_order_history.php?customer_id=' + customerId;
            });

            sortableHeaders.on('click', function() {
                const column = $(this).data('column');
                const urlParams = new URLSearchParams(window.location.search);
                let currentSortColumn = urlParams.get('sort_column');
                let currentSortOrder = urlParams.get('sort_order');
                let newSortOrder = 'ASC';

                if (currentSortColumn === column) {
                    newSortOrder = (currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
                } else {
                    currentSortColumn = column;
                    newSortOrder = 'ASC';
                }

                urlParams.set('sort_column', currentSortColumn);
                urlParams.set('sort_order', newSortOrder);

                const searchQuery = orderSearchInput.val();
                const searchColumn = orderSearchColumn.val();
                if (searchQuery && searchColumn) {
                    urlParams.set('search_query', searchQuery);
                    urlParams.set('search_column', searchColumn);
                } else {
                    urlParams.delete('search_query');
                    urlParams.delete('search_column');
                }
                window.location.href = '?' + urlParams.toString();
            });

            const initialSortCol = new URLSearchParams(window.location.search).get('sort_column');
            const initialSortOrder = new URLSearchParams(window.location.search).get('sort_order');
            if (initialSortCol) {
                const header = $(`.orders-table th[data-column="${initialSortCol}"]`);
                if (header.length) {
                    const sortIcon = initialSortOrder === 'ASC' ? '▲' : '▼';
                    header.find('.sort-icon').text(sortIcon);
                }
            }
        });
    </script>
</body>
</html>