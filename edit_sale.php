<?php
session_start();
include('database/connect.php'); // Ensure database connection is established

// Redirect if user is not set or empty
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$sale_id = $_GET['id'] ?? null;
$sale_data = null;
$message = '';
$message_type = ''; // 'success' or 'error'

if ($sale_id) {
    try {
        // Fetch current sale data
        $stmt = $conn->prepare("
            SELECT
                cs.order_id,
                cs.customer_name,
                cs.products_summary,
                cs.total_amount,
                cs.order_status,
                cs.payment_method,
                cs.created_at
            FROM (
                -- Orders from 'orders' table with items from 'order_products'
                SELECT
                    o.id AS order_id,
                    o.customer_name AS customer_name,
                    GROUP_CONCAT(CONCAT(p.product_name, ' (', op.quantity, ')') ORDER BY p.product_name SEPARATOR ', ') AS products_summary,
                    o.total_amount,
                    NULL AS order_status,
                    NULL AS payment_method,
                    o.order_date AS created_at,
                    'orders' AS source_table -- Indicate source table
                FROM
                    orders o
                JOIN
                    order_products op ON o.id = op.order_id
                JOIN
                    products p ON op.product_id = p.id
                GROUP BY
                    o.id, o.customer_name, o.total_amount, o.order_date

                UNION ALL

                -- Orders from 'orders' table with items from 'order_items'
                SELECT
                    o.id AS order_id,
                    o.customer_name AS customer_name,
                    GROUP_CONCAT(CONCAT(p.product_name, ' (', oi.quantity, ')') ORDER BY p.product_name SEPARATOR ', ') AS products_summary,
                    o.total_amount,
                    NULL AS order_status,
                    NULL AS payment_method,
                    o.order_date AS created_at,
                    'orders' AS source_table
                FROM
                    orders o
                JOIN
                    order_items oi ON o.id = oi.order_id
                JOIN
                    products p ON oi.product_id = p.id
                GROUP BY
                    o.id, o.customer_name, o.total_amount, o.order_date

                UNION ALL

                -- Orders from 'orders_customer' table
                SELECT
                    oc.id AS order_id,
                    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                    GROUP_CONCAT(CONCAT(p.product_name, ' (', op.quantity, ')') ORDER BY p.product_name SEPARATOR ', ') AS products_summary,
                    oc.total_amount,
                    oc.status AS order_status,
                    oc.payment_method AS payment_method,
                    oc.order_date AS created_at,
                    'orders_customer' AS source_table
                FROM
                    orders_customer oc
                JOIN
                    customers c ON oc.customer_id = c.id
                LEFT JOIN
                    order_products op ON oc.id = op.order_id
                LEFT JOIN
                    products p ON op.product_id = p.id
                GROUP BY
                    oc.id, c.first_name, c.last_name, oc.total_amount, oc.status, oc.payment_method, oc.order_date
            ) AS cs
            WHERE cs.order_id = ?
        ");
        $stmt->execute([$sale_id]);
        $sale_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sale_data) {
            $message = 'Sale record not found.';
            $message_type = 'error';
        }

    } catch (PDOException $e) {
        error_log("Database Error in edit_sale.php (fetch): " . $e->getMessage());
        $message = 'Error fetching sale record: ' . $e->getMessage();
        $message_type = 'error';
    }
} else {
    $message = 'No sale ID provided.';
    $message_type = 'error';
}

// Handle form submission for updating the sale record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sale_id) {
    $new_total_amount = $_POST['total_amount'] ?? null;
    $new_order_status = $_POST['order_status'] ?? null;
    $new_payment_method = $_POST['payment_method'] ?? null;

    // Basic validation
    if (!is_numeric($new_total_amount) || $new_total_amount < 0) {
        $message = 'Invalid total amount.';
        $message_type = 'error';
    } elseif (empty($new_order_status)) {
        $message = 'Order status cannot be empty.';
        $message_type = 'error';
    } elseif (empty($new_payment_method)) {
        $message = 'Payment method cannot be empty.';
        $message_type = 'error';
    } else {
        try {
            // Determine which table to update based on the original source
            // This is a crucial step and needs to be handled carefully.
            // For simplicity, we'll assume 'orders_customer' is the primary editable source
            // as it has 'status' and 'payment_method'.
            // If an order came from 'orders' table, you might only update 'total_amount'
            // or migrate it to 'orders_customer' if status/payment is needed.
            // For this example, we'll try to update 'orders_customer' first, then 'orders'.

            $update_success = false;

            // Attempt to update 'orders_customer' table
            $stmt = $conn->prepare("
                UPDATE orders_customer
                SET total_amount = ?, status = ?, payment_method = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_total_amount, $new_order_status, $new_payment_method, $sale_id]);
            if ($stmt->rowCount() > 0) {
                $update_success = true;
                $message = 'Sale record updated successfully in orders_customer table.';
                $message_type = 'success';
            } else {
                // If not found/updated in orders_customer, try updating 'orders' table
                // Note: 'orders' table doesn't have 'status' or 'payment_method' by default.
                // You would need to add these columns to your 'orders' table if you want to edit them there.
                // For now, we'll only update 'total_amount' for 'orders' table.
                $stmt = $conn->prepare("
                    UPDATE orders
                    SET total_amount = ?
                    WHERE id = ?
                ");
                $stmt->execute([$new_total_amount, $sale_id]);
                if ($stmt->rowCount() > 0) {
                    $update_success = true;
                    $message = 'Sale record updated successfully in orders table (total amount only).';
                    $message_type = 'success';
                }
            }

            if (!$update_success) {
                 $message = 'No record found to update or no changes were made for the given Order ID. It might be from a table that doesn\'t support these fields directly.';
                 $message_type = 'error';
            }

            // Re-fetch data to show updated values on the form
            $stmt = $conn->prepare("
                SELECT
                    cs.order_id,
                    cs.customer_name,
                    cs.products_summary,
                    cs.total_amount,
                    cs.order_status,
                    cs.payment_method,
                    cs.created_at
                FROM (
                    -- Orders from 'orders' table with items from 'order_products'
                    SELECT
                        o.id AS order_id,
                        o.customer_name AS customer_name,
                        GROUP_CONCAT(CONCAT(p.product_name, ' (', op.quantity, ')') ORDER BY p.product_name SEPARATOR ', ') AS products_summary,
                        o.total_amount,
                        NULL AS order_status,
                        NULL AS payment_method,
                        o.order_date AS created_at
                    FROM
                        orders o
                    JOIN
                        order_products op ON o.id = op.order_id
                    JOIN
                        products p ON op.product_id = p.id
                    GROUP BY
                        o.id, o.customer_name, o.total_amount, o.order_date

                    UNION ALL

                    -- Orders from 'orders' table with items from 'order_items'
                    SELECT
                        o.id AS order_id,
                        o.customer_name AS customer_name,
                        GROUP_CONCAT(CONCAT(p.product_name, ' (', oi.quantity, ')') ORDER BY p.product_name SEPARATOR ', ') AS products_summary,
                        o.total_amount,
                        NULL AS order_status,
                        NULL AS payment_method,
                        o.order_date AS created_at
                    FROM
                        orders o
                    JOIN
                        order_items oi ON o.id = oi.order_id
                    JOIN
                        products p ON oi.product_id = p.id
                    GROUP BY
                        o.id, o.customer_name, o.total_amount, o.order_date

                    UNION ALL

                    -- Orders from 'orders_customer' table
                    SELECT
                        oc.id AS order_id,
                        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                        GROUP_CONCAT(CONCAT(p.product_name, ' (', op.quantity, ')') ORDER BY p.product_name SEPARATOR ', ') AS products_summary,
                        oc.total_amount,
                        oc.status AS order_status,
                        oc.payment_method AS payment_method,
                        oc.order_date AS created_at
                    FROM
                        orders_customer oc
                    JOIN
                        customers c ON oc.customer_id = c.id
                    LEFT JOIN
                        order_products op ON oc.id = op.order_id
                    LEFT JOIN
                        products p ON op.product_id = p.id
                    GROUP BY
                        oc.id, c.first_name, c.last_name, oc.total_amount, oc.status, oc.payment_method, oc.order_date
                ) AS cs
                WHERE cs.order_id = ?
            ");
            $stmt->execute([$sale_id]);
            $sale_data = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database Error in edit_sale.php (update): " . $e->getMessage());
            $message = 'Error updating sale record: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sale - Keepkit</title>
    <link rel="stylesheet" href="home.css">
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Add specific styles for the edit form here */
        .edit-form-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 40px auto;
            max-width: 600px;
        }

        .edit-form-container h1 {
            color: #333;
            margin-bottom: 25px;
            text-align: center;
        }

        .edit-form-container .form-group {
            margin-bottom: 18px;
        }

        .edit-form-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .edit-form-container input[type="text"],
        .edit-form-container input[type="number"],
        .edit-form-container select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
        }

        .edit-form-container input[type="text"]:focus,
        .edit-form-container input[type="number"]:focus,
        .edit-form-container select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .edit-form-container .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }

        .edit-form-container button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s ease;
        }

        .edit-form-container .save-btn {
            background-color: #28a745;
            color: white;
        }

        .edit-form-container .save-btn:hover {
            background-color: #218838;
        }

        .edit-form-container .cancel-btn {
            background-color: #6c757d;
            color: white;
        }

        .edit-form-container .cancel-btn:hover {
            background-color: #5a6268;
        }

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
            <section id="editSalePage" class="active">
                <div class="edit-form-container">
                    <h1>Edit Sale Record</h1>
                    <?php if (!empty($message)): ?>
                        <div class="message <?= $message_type ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sale_data): ?>
                        <form action="edit_sale.php?id=<?= htmlspecialchars($sale_id) ?>" method="POST">
                            <div class="form-group">
                                <label for="order_id">Order ID:</label>
                                <input type="text" id="order_id" name="order_id" value="<?= htmlspecialchars($sale_data['order_id']) ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="customer_name">Customer Name:</label>
                                <input type="text" id="customer_name" name="customer_name" value="<?= htmlspecialchars($sale_data['customer_name']) ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="products_summary">Products Summary:</label>
                                <input type="text" id="products_summary" name="products_summary" value="<?= htmlspecialchars($sale_data['products_summary'] ?? 'N/A') ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="total_amount">Total Amount:</label>
                                <input type="number" id="total_amount" name="total_amount" step="0.01" value="<?= htmlspecialchars($sale_data['total_amount']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="order_status">Order Status:</label>
                                <select id="order_status" name="order_status" required>
                                    <option value="pending" <?= ($sale_data['order_status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= ($sale_data['order_status'] == 'processing') ? 'selected' : '' ?>>Processing</option>
                                    <option value="completed" <?= ($sale_data['order_status'] == 'completed' || $sale_data['order_status'] === NULL) ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= ($sale_data['order_status'] == 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="payment_method">Payment Method:</label>
                                <select id="payment_method" name="payment_method" required>
                                    <option value="Cash on Delivery" <?= ($sale_data['payment_method'] == 'Cash on Delivery' || $sale_data['payment_method'] === NULL) ? 'selected' : '' ?>>Cash on Delivery</option>
                                    <option value="Credit Card" <?= ($sale_data['payment_method'] == 'Credit Card') ? 'selected' : '' ?>>Credit Card</option>
                                    <option value="Bank Transfer" <?= ($sale_data['payment_method'] == 'Bank Transfer') ? 'selected' : '' ?>>Bank Transfer</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="created_at">Order Date:</label>
                                <input type="text" id="created_at" name="created_at" value="<?= htmlspecialchars($sale_data['created_at']) ?>" readonly>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="save-btn">Save Changes</button>
                                <button type="button" class="cancel-btn" onclick="window.location.href='view_sales.php'">Cancel</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p style="text-align: center;">Unable to load sale record for editing.</p>
                        <div class="form-actions" style="justify-content: center;">
                            <button type="button" class="cancel-btn" onclick="window.location.href='view_sales.php'">Back to Sales List</button>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
</body>
</html>