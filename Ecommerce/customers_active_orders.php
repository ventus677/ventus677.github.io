<?php
session_start();
// Include the database connection file.
include('../database/connect.php'); 
include('header_public.php'); 
include('customer_sidebar.php'); 

// Check if the USER is logged in. If not, redirect.
// NOTE: Para sumunod sa hiling mo na mag-redirect sa ../index.php kapag hindi naka-login:
/*
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) { 
    header('Location: ../index.php'); // Pagbabago sa customer_auth.php
    exit;
}
*/
// Ibinabalik sa 'customer_auth.php' ang redirection para sa proper access control:
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) { 
    header('Location: customer_auth.php'); 
    exit;
}

// Gamitin ang user_id mula sa session
$user_id = $_SESSION['user']['id']; 
$user_role = $_SESSION['user']['role'] ?? 'customer'; // Kuhanin ang role mula sa session
$orders = [];
$error_message = '';
$success_message = '';

// KUNIN ang status messages mula sa status processor file (e.g., customers_order_status.php)
$status_action_message = $_SESSION['status_action_message'] ?? null;
$status_action_type = $_SESSION['status_action_type'] ?? null;
unset($_SESSION['status_action_message']); // I-clear agad pagkatapos gamitin
unset($_SESSION['status_action_type']);

// I-set ang message variables para sa HTML display
if ($status_action_type === 'success') {
    $success_message = $status_action_message;
} elseif ($status_action_type === 'error') {
    $error_message = $status_action_message;
}


// --- Function to fetch order items (Uses order_products_user table) ---
function getOrderItems(int $order_id, $conn): array {
    $items = [];
    try {
        // Querying order_products_user table for item details
        $stmt = $conn->prepare("
            SELECT quantity, price_at_order, product_name 
            FROM order_products_user 
            WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);
        $fetched_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Rename the key 'price_at_order' to 'unit_price' for clarity in HTML output
        $items = array_map(function($item) {
            return [
                'quantity' => $item['quantity'],
                'unit_price' => $item['price_at_order'],
                'product_name' => $item['product_name']
            ];
        }, $fetched_items);

    } catch (PDOException $e) {
        error_log("Failed to fetch order items: " . $e->getMessage());
    }
    return $items;
}

// --- Fetch Active Orders (Pending, Processing, Shipped) ---
try {
    // Querying orders_user table with user_id
    $stmt_orders = $conn->prepare("
        SELECT 
            id, 
            order_date, 
            total_amount, 
            status,             
            payment_method,
            shipping_address
        FROM 
            orders_user     
        WHERE 
            user_id = ? AND status IN ('Pending', 'pending', 'Processing', 'Shipped') 
        ORDER BY 
            order_date DESC
    ");
    $stmt_orders->execute([$user_id]); 
    $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch active orders: " . $e->getMessage());
    $error_message = "Could not load your active orders due to a system error.";
}

// --- HTML Output ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Active Orders</title>
    <link rel="stylesheet" href="customer_products.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Specific styles for order history page (Reused/modified for Active Orders) */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6; /* Light background */
            color: #333;
        }

        .order-history-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
        }
        .order-history-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            border-bottom: 3px solid #ff5722;
            padding-bottom: 10px;
        }
        .order-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .order-details p {
            margin: 5px 0;
            font-size: 0.95em;
        }
        .order-details p strong {
            color: #333;
        }

        /* Order Status Styling */
        .order-status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            min-width: 100px; /* Uniform width */
            text-align: center;
        }
        /* Define status colors for Active Orders */
        .order-status.Pending, .order-status.pending { background-color: #ffc107; color: #333; }
        .order-status.Processing { background-color: #007bff; }
        .order-status.Shipped { background-color: #28a745; }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .order-items-table th, .order-items-table td {
            border: 1px solid #ddd; 
            padding: 8px;
            text-align: left;
        }
        .order-items-table th {
            background-color: #f8f8f8;
            font-weight: 600;
            color: #555;
        }
        
        /* Buttons */
        .cancel-btn, .receive-btn {
            padding: 8px 15px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s ease;
            margin-left: 10px;
        }
        .cancel-btn {
            background-color: #dc3545; 
            color: white; 
        }
        .cancel-btn:hover { background-color: #c82333; }

        .receive-btn {
            background-color: #17a2b8;
            color: white;
        }
        .receive-btn:hover { background-color: #138496; }

        /* Message Boxes */
        .message-box {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* ======================================= */
        /* DARK MODE STYLES                        */
        /* ======================================= */

        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        body.dark-mode .order-history-container h2 {
            color: #e0e0e0;
            border-bottom: 3px solid #ff5722;
        }
        body.dark-mode .order-card {
            background-color: #2d2d2d;
            box-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        body.dark-mode .order-header {
            border-bottom: 1px solid #444;
        }
        body.dark-mode .order-details p strong {
            color: #e0e0e0;
        }
        body.dark-mode .order-details p span {
            color: #bbb;
        }
        
        /* Dark Mode Status Colors (No change needed for background, just text) */
        body.dark-mode .order-status.Pending, body.dark-mode .order-status.pending { color: #333; } 
        body.dark-mode .order-status.Processing { color: white; }
        body.dark-mode .order-status.Shipped { color: white; }

        /* Dark Mode Table */
        body.dark-mode .order-items-table th, 
        body.dark-mode .order-items-table td {
            border: 1px solid #555;
        }
        body.dark-mode .order-items-table th {
            background-color: #3a3a3a;
            color: #e0e0e0;
        }

        /* Dark Mode Buttons */
        body.dark-mode .cancel-btn {
            background-color: #c82333; 
        }
        body.dark-mode .cancel-btn:hover { background-color: #b81b2a; }

        body.dark-mode .receive-btn {
            background-color: #138496;
        }
        body.dark-mode .receive-btn:hover { background-color: #0d6e7f; }

        /* Dark Mode Message Boxes */
        body.dark-mode .success-message {
            background-color: #1c4d29;
            color: #d4edda;
            border: 1px solid #1c4d29;
        }
        body.dark-mode .error-message {
            background-color: #5d2528;
            color: #f8d7da;
            border: 1px solid #5d2528;
        }
        
        /* Responsive adjustments */
        @media (max-width: 600px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-status {
                margin-top: 5px;
                min-width: auto;
                width: 100%;
                text-align: left;
            }
            .order-card > div:last-child {
                text-align: left !important;
            }
            .cancel-btn, .receive-btn {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>

<div class="order-history-container">
    <h2>My Active Orders (Pending, Processing, Shipped)</h2>

    <?php if ($success_message): ?>
        <div class="message-box success-message">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message-box error-message">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p style="text-align: center; color: #777; padding: 50px;">You have no active orders (Pending, Processing, or Shipped).</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <strong style="font-size: 1.2em;">Order #<?= htmlspecialchars($order['id']) ?></strong>
                        <br>
                        <span style="font-size: 0.9em; color: #777;">Date: <?= date('F j, Y, g:i a', strtotime($order['order_date'])) ?></span>
                    </div>
                    <span class="order-status <?= htmlspecialchars($order['status']) ?>">
                        <?= htmlspecialchars($order['status']) ?>
                    </span>
                </div>

                <div class="order-details">
                    <p><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>
                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                    <p><strong>Shipping To:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                </div>

                <h4 style="margin-top: 20px; margin-bottom: 10px;">Items Ordered:</h4>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $items = getOrderItems($order['id'], $conn); ?>
                        <?php foreach ($items as $item): 
                            // Kuhanin ang original price
                            $original_price = $item['unit_price']; 
                            
                            // Logic: Kapag ang role ay 'user', apply 20% discount. 
                            // Kapag 'customer' (o iba pa), gamitin ang original price.
                            if ($user_role === 'user') {
                                $display_price = $original_price * 0.80;
                            } else {
                                $display_price = $original_price;
                            }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td style="text-align: center;"><?= htmlspecialchars($item['quantity']) ?></td>
                                <td style="text-align: right;">₱<?= number_format($display_price, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 15px; text-align: right;">
                    <?php 
                        // FIXED: Receive button ay lalabas kung ang status ay Pending, Processing, O Shipped. (Case insensitive)
                    ?>
                    <?php if (in_array(strtolower($order['status']), ['pending', 'processing', 'shipped'])): ?>
                        <form action="customers_order_status.php" method="POST" 
                            onsubmit="return confirm('Sigurado ka bang gusto mong markahan ang Order #<?= $order['id'] ?> bilang Completed (Natanggap)? Hindi na ito mababawi.');" 
                            style="display: inline-block;">
                            <input type="hidden" name="receive_order_id" value="<?= htmlspecialchars($order['id']) ?>">
                            <button type="submit" class="receive-btn">
                                Receive Order
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php 
                        // Cancel button: Lalabas sa Pending, Processing, O Shipped. (Case insensitive)
                    ?>
                    <?php if (in_array(strtolower($order['status']), ['pending', 'processing', 'shipped'])): ?>
                        <form action="customers_order_status.php" method="POST" onsubmit="return confirm('Sigurado ka bang gusto mong I-CANCEL ang Order #<?= $order['id'] ?>?');" style="display: inline-block;">
                            <input type="hidden" name="cancel_order_id" value="<?= htmlspecialchars($order['id']) ?>">
                            <button type="submit" class="cancel-btn">
                                Cancel  Order
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>