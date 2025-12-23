<?php
session_start();
// Include the database connection file.
include('../database/connect.php'); 
include('header_public.php'); // Assuming this includes your header, navigation, and opens the <body> tag
include('customer_sidebar.php');
// Check if the customer is logged in. If not, redirect.
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) { 
    header('Location: customer_auth.php'); 
    exit;
}

// REVISION: Gamitin ang user_id mula sa session
$user_id = $_SESSION['user']['id']; 
$user_role = $_SESSION['user']['role'] ?? 'customer'; // Kunin ang role mula sa session

// Retrieve and clear the success ID for displaying a one-time message.
$success_order_id = $_SESSION['order_success_id'] ?? null;
unset($_SESSION['order_success_id']); 

$orders = [];
$error_message = '';
$success_message = '';

// KUNIN ang status messages mula sa status processor file
$status_action_message = $_SESSION['status_action_message'] ?? null;
$status_action_type = $_SESSION['status_action_type'] ?? null;
unset($_SESSION['status_action_message']); 
unset($_SESSION['status_action_type']);

// KUNIN ang Return/Refund action message at type
$rr_action_message = $_SESSION['rr_action_message'] ?? null;
$rr_action_type = $_SESSION['rr_action_type'] ?? null;
unset($_SESSION['rr_action_message']); 
unset($_SESSION['rr_action_type']);

// I-set ang message variables para sa HTML display
if ($rr_action_message) {
    // Priority ang RR message
    if ($rr_action_type === 'success') {
        $success_message = $rr_action_message;
    } else {
        $error_message = $rr_action_message;
    }
} elseif ($status_action_message) {
    // General status message
    if ($status_action_type === 'success') {
        $success_message = $status_action_message;
    } else {
        $error_message = $status_action_message;
    }
}


// Helper function to generate star HTML
function generate_stars($rating) {
    $html = '';
    $rating = round($rating * 2) / 2; // Round to nearest half
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 == $rating) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star"></i>';
        }
    }
    return $html;
}


// --- REVISED: Function to fetch order items with ROBUST EXCEPTION HANDLING ---
function getOrderItems(int $order_id, $user_id, $conn): array {
    $items = [];
    $fetched_items = [];
    
    try {
        // 1. Fetch ALL product items for the order (MAIN QUERY) - MABILIS AT LIGTAS NA FETCH
        $stmt = $conn->prepare("
            SELECT 
                id AS order_product_id, /* ITO ANG UNIQUE ID NG BINILI ITEM SA ORDER */
                product_id,             
                quantity, 
                price_at_order, 
                product_name 
            FROM 
                order_products_user 
            WHERE 
                order_id = ?
        ");
        $stmt->execute([$order_id]);
        $fetched_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Process each item to check for review/RR status using foreach for safety
        foreach ($fetched_items as $item) {
            // Default data structure
            $item_data = [
                'order_product_id' => $item['order_product_id'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price_at_order'],
                'product_name' => $item['product_name'],
                'has_reviewed' => false,
                'review' => [],
                'rr_request' => null,
            ];
            
            // NESTED TRY/CATCH: This handles potential errors in product_reviews/returns_refunds tables
            // without crashing the whole order item list.
            try {
                // --- REVIEW CHECK ---
                $stmt_check_review = $conn->prepare("
                    SELECT 
                        pr.id AS review_id, 
                        pr.rating, 
                        pr.comment, 
                        pr.created_at,
                        GROUP_CONCAT(ri.image_path ORDER BY ri.id ASC SEPARATOR '|||') AS image_paths
                    FROM 
                        product_reviews pr
                    LEFT JOIN
                        review_images ri ON pr.id = ri.review_id 
                    WHERE 
                        pr.order_product_id = ? 
                    GROUP BY
                        pr.id, pr.rating, pr.comment, pr.created_at
                ");
                $stmt_check_review->execute([$item['order_product_id']]); 
                $review_data = $stmt_check_review->fetch(PDO::FETCH_ASSOC);

                if ($review_data) {
                    $item_data['has_reviewed'] = true;
                    $item_data['review'] = $review_data;
                }

                // --- RETURN/REFUND CHECK ---
                $stmt_check_rr = $conn->prepare("
                    SELECT 
                        rr.*
                    FROM 
                        returns_refunds rr
                    WHERE 
                        rr.user_id = ? 
                        AND rr.order_id = ? 
                        AND rr.product_id = ? 
                    ORDER BY rr.request_date DESC
                    LIMIT 1
                ");
                // SQL REVISION: Pinalitan ang rr.customer_id ng rr.user_id (Gaya ng sa original)
                $stmt_check_rr->execute([$user_id, $order_id, $item['product_id']]);
                $rr_request_data = $stmt_check_rr->fetch(PDO::FETCH_ASSOC);

                if ($rr_request_data) {
                    $item_data['rr_request'] = $rr_request_data;
                }
                
            } catch (PDOException $e) {
                // IMPORTANT: Log the error and continue to the next item. 
                error_log("Sub-query failed for order_product_id " . $item['order_product_id'] . ": " . $e->getMessage());
                // The item will still be included in the list, but with default null/false review/rr status.
            }
            
            $items[] = $item_data;
        }

    } catch (PDOException $e) {
        // Log if the main query fails
        error_log("Main order products query failed for order ID " . $order_id . ": " . $e->getMessage());
        return []; // Return empty array if the main query failed.
    }
    
    return $items;
}


// --- Fetch ONLY Completed customer orders ---
try {
    // SQL REVISION: Pinalitan ang orders_customer ng orders_user at customer_id ng user_id
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
            user_id = ? 
            AND status = 'Completed' 
        ORDER BY 
            order_date DESC
    ");
    // REVISION: Ipinasa ang $user_id
    $stmt_orders->execute([$user_id]);
    $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch COMPLETED orders: " . $e->getMessage());
    $error_message = "Could not load your order history due to a system error. Please check your database table names and column names.";
}

// --- HTML Output ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Order History</title>
    <link rel="icon" type="image/png" href="../images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="home.css"> 
    <style>
        /* Modern Styles for Order History / Review Page */
        :root {
            --primary-color: #e67e22; 
            --background-color: #f4f7f6;
            --card-background: #ffffff;
            --text-color: #2c3e50;
            --light-text: #7f8c8d;
            --border-color: #ecf0f1;
            --shadow: 0 4px 15px rgba(0,0,0,0.08);
            --success-color: #1abc9c;
            --warning-color: #f39c12;
            --error-color: #e74c3c;
            --info-color: #3498db;
        }

        /* --- DARK MODE STYLES --- */
        body.dark-mode {
            --background-color: #1a1a1a;
            --card-background: #252525;
            --text-color: #f0f0f0;
            --light-text: #b0b0b0;
            --border-color: #333333;
            --shadow: 0 4px 15px rgba(0,0,0,0.4);
        }
        body.dark-mode .order-history-container h2 {
            color: var(--text-color);
        }
        body.dark-mode .order-items-table th {
            background-color: #303030;
            color: #f0f0f0;
        }
        body.dark-mode .order-items-table td {
            background-color: #2c2c2c;
            border-bottom: 1px solid #333333;
        }
        body.dark-mode .review-body, body.dark-mode .admin-notes-display {
            background-color: #3a3a3a;
            border: 1px solid #444444;
        }
        body.dark-mode .message-box.success-message {
            background-color: #2ecc7133; /* Light success in dark mode */
            color: #1abc9c;
            border: 1px solid #16a085;
        }
        body.dark-mode .message-box.error-message {
            background-color: #e74c3c33; /* Light error in dark mode */
            color: #e74c3c;
            border: 1px solid #c0392b;
        }
        /* --- END DARK MODE STYLES --- */


        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            padding-left: 250px; /* Space for the fixed sidebar */
        }
        
        .order-history-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
        }
        .order-history-container h2 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            border-bottom: 4px solid var(--primary-color);
            padding-bottom: 10px;
            font-weight: 800;
        }
        .order-card {
            background-color: var(--card-background);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .order-card:hover {
             box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .order-id {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--primary-color);
        }
        .order-date {
            font-size: 0.9em;
            color: var(--light-text);
            margin-top: 5px;
            display: block;
        }
        .order-details p {
            margin: 5px 0;
            font-size: 0.95em;
        }
        .order-details p strong {
            color: var(--text-color);
            font-weight: 600;
        }
        .order-status {
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 6px;
            color: white;
            min-width: 120px; 
            text-align: center;
            font-size: 0.9em;
        }
        .order-status.Completed { background-color: var(--success-color); } 
        
        .order-items-table {
            width: 100%;
            border-collapse: separate; 
            border-spacing: 0 5px; 
            margin-top: 20px;
        }
        .order-items-table th, .order-items-table td {
            padding: 12px 8px;
            text-align: left;
            vertical-align: middle;
            background-color: #fcfcfc;
            border-bottom: 1px solid var(--border-color);
        }
        body.dark-mode .order-items-table td {
            background-color: #2c2c2c;
        }
        .order-items-table th {
            background-color: #ecf0f1;
            font-weight: 700;
            color: var(--text-color);
            border-bottom: 2px solid var(--primary-color);
        }
        
        /* New Styles for Product-level RR Management */
        .rr-status-mini {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .rr-status-badge {
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-size: 0.8em;
            min-width: 80px;
            text-align: center;
            text-transform: uppercase;
        }
        .rr-status-badge.pending, .rr-status-badge.processing { background-color: var(--warning-color); } 
        /* UPDATED: Uses ACCEPTED/DECLINED from admin_return_refund.php */
        .rr-status-badge.accepted, .rr-status-badge.refunded { background-color: var(--success-color); } 
        .rr-status-badge.declined, .rr-status-badge.cancelled { background-color: var(--error-color); } 

        .rr-btn {
            background-color: var(--info-color);
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            font-weight: 600;
            transition: background-color 0.2s;
            white-space: nowrap;
            margin: 0;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .rr-btn:hover { background-color: #2980b9; }
        .rr-btn.cancel { background-color: var(--error-color); }
        .rr-btn.cancel:hover { background-color: #c0392b; }
        .btn-view-proof-mini { background-color: var(--light-text); }
        .btn-view-proof-mini:hover { background-color: #6c7a89; }

        .rr-expired-note-mini {
            color: var(--error-color);
            font-size: 0.85em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Review Button Styles */
        .review-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            font-weight: 600;
            transition: background-color 0.2s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .review-btn:hover { background-color: #d35400; }
        .reviewed-status {
            color: var(--success-color);
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Modal Styles (General) */
        .rr-modal, .review-modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 90%;
            max-width: 600px;
            position: relative;
        }
        body.dark-mode .modal-content {
            background-color: #363636;
        }
        .close-btn {
            color: var(--light-text);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close-btn:hover, .close-btn:focus { color: var(--text-color); }
        .modal-content h3 {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .modal-content label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-color);
        }
        .modal-content input[type="text"], .modal-content input[type="number"], .modal-content select, .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            box-sizing: border-box;
            resize: vertical;
            background-color: var(--card-background); /* Use card-background for inputs */
            color: var(--text-color);
        }
        .message-box {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
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
        .review-rating-display .stars .fas, .review-rating-display .stars .far {
            color: #ffc107;
        }
        .review-body, .admin-notes-display {
            background-color: #f7f7f7;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            white-space: pre-wrap;
            word-wrap: break-word;
            border: 1px solid var(--border-color);
        }

        /* Star Rating in Modals (HORIZONTAL AND 5-STAR LIMIT) */
        .star-input-group {
            direction: rtl; /* Para magsimula sa kanan ang 5 star */
            display: inline-flex; 
            margin-bottom: 15px;
            max-width: fit-content; 
        }
        .star-input-group input {
            display: none;
        }
        .star-input-group label {
            color: #ccc;
            font-size: 30px;
            padding: 0 2px;
            cursor: pointer;
            line-height: 1; 
        }
        .star-input-group label:hover,
        .star-input-group label:hover ~ label,
        .star-input-group input:checked ~ label {
            color: #ffc107;
        }
        /* Ito ang nagre-render ng STAR icon base sa Font Awesome character code */
        .star-input-group label::before, 
        .star-input-group label:has(~ input:checked)::before,
        .star-input-group label:hover::before,
        .star-input-group label:hover ~ label::before {
            content: "\f005"; /* Font Awesome star icon */
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
        }


        /* Image Viewer Modal Specific */
        #imageViewModal .modal-content {
            max-width: 90%; 
            background: none; 
            box-shadow: none;
        }
        #proofImageDisplay {
            max-width: 100%;
            max-height: 90vh;
            display: block;
            margin: auto;
        }
        
        /* === MODERN REVIEW MODAL STYLES (NEW/MODIFIED) === */
        #writeReviewModal .modal-content, #viewReviewModal .modal-content {
            max-width: 500px;
            padding: 40px 30px;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .review-form-group {
            margin-bottom: 20px;
        }

        #reviewForm button[type="submit"], #rrForm button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        #reviewForm button[type="submit"]:hover, #rrForm button[type="submit"]:hover {
            background-color: #16a085;
            transform: translateY(-1px);
        }
        #reviewForm button[type="submit"]:disabled, #rrForm button[type="submit"]:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }
        
        /* View Review Styles */
        #viewReviewModal h3 {
            color: var(--text-color);
            font-size: 1.6em;
            font-weight: 800;
            margin-bottom: 20px;
            border-bottom: none;
            text-align: center;
        }

        .review-rating-display {
            text-align: center;
            margin-bottom: 20px;
        }

        .review-rating-display p {
            margin: 5px 0;
            font-size: 1em;
        }

        .review-rating-display .stars .fas, .review-rating-display .stars .far {
            font-size: 24px;
        }

        #viewReviewComment {
            padding: 20px;
            min-height: 80px;
            font-style: italic;
            font-size: 1.05em;
            border-left: 5px solid var(--primary-color);
            background-color: #fcfcfc;
        }
        body.dark-mode #viewReviewComment {
            background-color: #3a3a3a;
        }
        
        #viewReviewImages {
            justify-content: center;
        }

    </style>
</head>
<body>
    <?php include('header_public.php'); // Siguraduhin na kasama ito ?>

    <div class="order-history-container">
        <h2>My Order History</h2>

        <?php if ($success_message): ?>
            <div class="message-box success-message" id="globalSuccessMessage">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message-box error-message" id="globalErrorMessage">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <p style="text-align: center; color: var(--light-text); padding: 50px;">You have no completed orders to review or manage.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php $order_id = $order['id']; ?>
                <?php 
                    // --- RETURN/REFUND TIMING CHECK (Per Order, applied to all items) --- 
                    $order_date_timestamp = strtotime($order['order_date']);
                    // Ang 7 days ay 604800 seconds (7 * 24 * 60 * 60)
                    $seven_days_limit = $order_date_timestamp + (7 * 24 * 60 * 60);
                    $can_request_rr = (time() < $seven_days_limit);
                ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">Order #<?= htmlspecialchars($order_id) ?></span>
                            <span class="order-date">Date Completed: <?= date('F j, Y, g:i a', strtotime($order['order_date'])) ?></span>
                        </div>
                        <span class="order-status Completed">
                            <?= htmlspecialchars($order['status']) ?>
                        </span>
                    </div>
                    <div class="order-details">
                        <p><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>
                        <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                        <p><strong>Shipping To:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                    </div>
                    <h4 style="margin-top: 20px; margin-bottom: 10px; border-top: 1px solid var(--border-color); padding-top: 10px;">Items to Review/Manage:</h4>
                    <div style="overflow-x: auto;">
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Product Name</th>
                                    <th style="text-align: center; width: 10%;">Qty</th>
                                    <th style="text-align: right; width: 15%;">Price</th> <th style="text-align: center; width: 20%;">Review Status</th>
                                    <th style="text-align: center; width: 20%;">Return/Refund</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $items = getOrderItems($order_id, $user_id, $conn); ?>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--light-text);">
                                            No products found for this order. Please check the 'order_products_user' table, or if related tables (reviews/returns) are accessible.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): 
                                        // 1. Kuhanin ang original price
                                        $original_price = $item['unit_price']; 
                                        
                                        // 2. LOGIC REVISION: Check role for discount
                                        // Kung ang role ay 'user', apply 20% discount. Kung 'customer', original price.
                                        if ($user_role === 'user') {
                                            $final_display_price = $original_price * 0.80; 
                                        } else {
                                            $final_display_price = $original_price;
                                        }
                                    ?>
                                    <tr id="row-order-product-<?= htmlspecialchars($item['order_product_id']) ?>">
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td style="text-align: center;"><?= htmlspecialchars($item['quantity']) ?></td>
                                        <td style="text-align: right;">₱<?= number_format($final_display_price, 2) ?></td> <td style="text-align: center;">
                                            <?php if ($item['has_reviewed']): ?>
                                                <div class="reviewed-status open-review-view-modal" 
                                                    data-order-product-id="<?= htmlspecialchars($item['order_product_id']) ?>"
                                                    data-product-name="<?= htmlspecialchars($item['product_name']) ?>"
                                                    data-review-rating="<?= htmlspecialchars($item['review']['rating']) ?>"
                                                    data-review-comment="<?= htmlspecialchars($item['review']['comment']) ?>"
                                                    data-review-date="<?= date('F j, Y', strtotime($item['review']['created_at'])) ?>"
                                                    data-review-images="<?= htmlspecialchars($item['review']['image_paths'] ?? '') ?>">
                                                    <i class="fas fa-check-circle"></i> Reviewed
                                                </div>
                                            <?php else: ?>
                                                <button type="button" class="review-btn open-review-modal" 
                                                    data-order-product-id="<?= htmlspecialchars($item['order_product_id']) ?>"
                                                    data-product-name="<?= htmlspecialchars($item['product_name']) ?>">
                                                    <i class="fas fa-comment-dots"></i> Review Now
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php $rr_request = $item['rr_request']; ?>
                                            <?php if ($rr_request): // May existing request ?>
                                                <?php 
                                                    $rr_status = strtoupper($rr_request['status']);
                                                    // I-normalize ang status para sa CSS class
                                                    $rr_status_class = strtolower($rr_status);
                                                    
                                                    // FIXED: Used in_array() for PHP syntax
                                                    if (in_array($rr_status_class, ['accepted', 'refunded'])) { 
                                                        $rr_status_class = 'accepted';
                                                    } elseif (in_array($rr_status_class, ['declined', 'cancelled'])) {
                                                        $rr_status_class = 'declined';
                                                    } else {
                                                        $rr_status_class = 'pending'; // Default for Pending, Processing, etc.
                                                    }
                                                ?>
                                                <div class="rr-status-mini">
                                                    <span class="rr-status-badge <?= $rr_status_class ?>">
                                                        <?= htmlspecialchars($rr_status) ?>
                                                    </span>
                                                    <button type="button" class="rr-btn btn-view-rr-details" 
                                                        data-request-id="<?= htmlspecialchars($rr_request['id']) ?>"
                                                        data-order-id="<?= htmlspecialchars($rr_request['order_id']) ?>"
                                                        data-product-name="<?= htmlspecialchars($item['product_name']) ?>"
                                                        data-status="<?= htmlspecialchars($rr_status) ?>"
                                                        data-type="<?= htmlspecialchars($rr_request['request_type'] ?? '') ?>"
                                                        data-quantity="<?= htmlspecialchars($rr_request['return_quantity'] ?? '') ?>"
                                                        data-reason="<?= htmlspecialchars($rr_request['reason'] ?? '') ?>"
                                                        data-admin-notes="<?= htmlspecialchars($rr_request['admin_notes'] ?? '') ?>"
                                                        data-proof-path="<?= htmlspecialchars($rr_request['proof_image_path'] ?? '') ?>">
                                                        <i class="fas fa-info-circle"></i> Details
                                                    </button>
                                                </div>
                                            <?php else: // Walang existing request ?>
                                                <?php if ($can_request_rr): ?>
                                                    <button type="button" class="rr-btn open-rr-product-modal" 
                                                        data-order-id="<?= htmlspecialchars($order_id) ?>" 
                                                        data-product-id="<?= htmlspecialchars($item['product_id']) ?>"
                                                        data-product-name="<?= htmlspecialchars($item['product_name']) ?>"
                                                        data-product-quantity="<?= htmlspecialchars($item['quantity']) ?>">
                                                        <i class="fas fa-exchange-alt"></i> Request
                                                    </button>
                                                <?php else: ?>
                                                    <span class="rr-expired-note-mini">
                                                        <i class="fas fa-times-circle"></i> Expired (7-day)
                                                    </span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="writeReviewModal" class="review-modal">
        <div class="modal-content">
            <span class="close-btn" data-modal="writeReviewModal">&times;</span>
            <h3>Write a Review for <span id="modalProductName" style="color: var(--primary-color); font-weight: 700;"></span></h3>
            <div id="review-submission-status" class="message-box" style="display: none;"></div>
            <form id="reviewForm" action="../database/submit_review.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="order_product_id" id="modalOrderProductId">
                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($user_id) ?>">
                
                <div class="review-form-group">
                    <label>Rating:</label>
                    <div class="star-input-group">
                        <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="5 stars"></label>
                        <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars"></label>
                        <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars"></label>
                        <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars"></label>
                        <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star"></label>
                    </div>
                </div>

                <div class="review-form-group">
                    <label for="comment">Comment (Max 500 characters):</label>
                    <textarea id="comment" name="comment" rows="4" placeholder="Share your experience with the product..." maxlength="500" required></textarea>
                </div>
                
                <div class="review-form-group">
                    <label for="review_images_input" class="rr-btn" style="background-color: var(--light-text); display: flex; justify-content: center; width: 100%; margin-bottom: 5px;">
                        <i class="fas fa-camera"></i> Upload Photo (Max 3 Images)
                    </label>
                    <input type="file" id="review_images_input" name="review_images[]" accept="image/*" multiple style="display: none;">
                    <span id="fileCount" style="display: block; text-align: center; color: var(--light-text); font-size: 0.9em;">No file selected</span>
                </div>

                <button type="submit" id="submitReviewButton"><i class="fas fa-paper-plane"></i> Submit Review</button>
            </form>
        </div>
    </div>

    <div id="viewReviewModal" class="review-modal">
        <div class="modal-content">
            <span class="close-btn" data-modal="viewReviewModal">&times;</span>
            <h3>Your Review for <span style="color: var(--primary-color); font-weight: 700;"></span></h3>
            <div class="review-rating-display">
                <p><strong>Rating:</strong> <span id="viewReviewRatingStars" class="stars"></span> (<span id="viewReviewRatingScore"></span>/5)</p>
                <p style="color: var(--light-text);">Reviewed on <span id="viewReviewDate"></span></p>
            </div>
            
            <p style="font-weight: 600; margin-bottom: 5px;">Comment:</p>
            <div id="viewReviewComment" class="review-body"></div>
            
            <div style="margin-top: 20px;">
                <p style="font-weight: 600; margin-bottom: 10px;">Images (Click to view full size):</p>
                <div id="viewReviewImages" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 10px;">
                    <p id="noReviewImages" style="color: var(--light-text); display: none;">No images uploaded for this review.</p>
                </div>
            </div>

        </div>
    </div>
    <div id="rrRequestProductModal" class="rr-modal">
        <div class="modal-content">
            <span class="close-btn" data-modal="rrRequestProductModal">&times;</span>
            <h3>Return/Refund Request for <span id="rrModalProductName"></span></h3>
            <form id="rrForm" method="POST" action="../database/customer_return_refund.php" enctype="multipart/form-data">
                <input type="hidden" name="order_id" id="rrModalOrderId">
                <input type="hidden" name="product_id" id="rrModalProductId">
                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($user_id) ?>">

                <div class="form-group">
                    <label for="request_type">Request Type:</label>
                    <select id="request_type" name="request_type" required>
                        <option value="">Select Type</option>
                        <option value="Return and Refund">Return and Refund</option>
                        <option value="Refund Only">Refund Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quantity_to_return">Quantity to Return (Max: <span id="maxQuantity">1</span>):</label>
                    <input type="number" id="quantity_to_return" name="quantity_to_return" min="1" required>
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Request:</label>
                    <textarea id="reason" name="reason" rows="4" placeholder="Provide a detailed reason for your return or refund request." required></textarea>
                </div>

                <div class="form-group">
                    <label for="rr_proof_image" class="rr-btn" style="display: flex; justify-content: center;">
                        <i class="fas fa-camera"></i> Upload Proof Photo (Optional)
                    </label>
                    <input type="file" id="rr_proof_image" name="proof_image" accept="image/*" style="display: none;">
                    <span id="rrFileCount" style="display: block; text-align: center; margin-top: 5px;">No file selected</span>
                </div>

                <button type="submit" id="rrSubmitButton"><i class="fas fa-paper-plane"></i> Submit Request</button>
            </form>
        </div>
    </div>


    <div id="rrViewDetailModal" class="rr-modal rr-view-detail-modal">
        <div class="modal-content">
            <span class="close-btn" data-modal="rrViewDetailModal">&times;</span>
            <h3>Return/Refund Request Details</h3>
            <div class="rr-detail-info">
                <p><strong>Order # & Product:</strong> <span id="viewRROrderId"></span> - <span id="viewRRProductName"></span></p>
                <p><strong>Request ID:</strong> #<span id="viewRRRequestId"></span></p>
                <p><strong>Status:</strong> <span id="viewRRStatusBadge" class="rr-status-badge"></span></p>
                <p><strong>Request Type:</strong> <span id="viewRRType"></span></p>
                <p><strong>Quantity:</strong> <span id="viewRRQuantity"></span></p>
                <p><strong>Reason:</strong> <span id="viewRRReason" class="review-body"></span></p>
                
                <div id="viewRRAdminNotesBlock" style="display: none; margin-top: 15px;">
                    <strong>Admin Notes:</strong> 
                    <div id="viewRRAdminNotes" class="admin-notes-display"></div>
                </div>

                <div class="rr-actions" style="margin-top: 20px;">
                    <button type="button" class="rr-btn btn-view-proof" id="viewRRProofButton" style="display:none;">
                        <i class="fas fa-eye"></i> View Proof
                    </button>
                    <button type="button" class="rr-btn cancel btn-cancel-rr-details" data-request-id="" style="display:none;">
                        <i class="fas fa-times-circle"></i> Cancel Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="imageViewModal" class="rr-modal">
        <div class="modal-content" style="max-width: 90%; background: none; box-shadow: none;">
            <span class="close-btn" data-modal="imageViewModal" style="color: white; text-shadow: 0 0 5px black; right: 20px; top: 20px;">&times;</span>
            <img id="proofImageDisplay" src="" alt="Proof Image">
        </div>
    </div>


<script src="customers_order_history.js"></script>

</body>
</html>