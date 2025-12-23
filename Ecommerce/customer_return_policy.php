<?php
session_start();
// include('../database/connect.php'); 

$customer = $_SESSION['customer'] ?? null; 
$total_items_in_cart = $_SESSION['total_items_in_cart'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return and Refund Policy | KeepKit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer_products.css">
    <link rel="stylesheet" href="customer_footer.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="customer_return_policy.css"> 
</head>
<body>
    
    <?php include('header_public.php'); ?>

    <main class="info-page-container">
        <h2>Return and Refund Policy</h2>
        <span class="page-subtitle">Your satisfaction is guaranteed. Please review the steps below.</span>

        <p>At KeepKit, we want you to be completely satisfied with your purchase. If there is an issue, please follow these steps for a quick and orderly return or refund process.</p>

        <h3>General Policy Details</h3>
        <ul class="policy-details-list">
            <li>Returns and Refunds are valid only within 7 days from the date you received the item.</li>
            <li>The item must be in original condition with all original packaging intact.</li>
            <li>A proof of purchase (receipt or order number) is required.</li>
            <li>This policy does not cover damages caused by misuse or normal wear and tear.</li>
        </ul>

        <h3>How to File a Return (3 Easy Steps)</h3>
        <div class="return-steps-container">
            
            <div class="return-step">
                <i class="fas fa-headset"></i>
                <span class="step-number">STEP 1</span>
                <h4>CONTACT SUPPORT</h4>
                <p>Immediately email our support team at <a href="mailto:keepkit3a@gmail.com">keepkit3a@gmail.com</a>. Please provide your Order Number and the detailed reason for the return.</p>
            </div>
            
            <div class="return-step">
                <i class="fas fa-clipboard-check"></i>
                <span class="step-number">STEP 2</span>
                <h4>WAIT FOR APPROVAL</h4>
                <p>Wait for confirmation and return instructions (including the correct shipping address) from our team before sending the item back.</p>
            </div>
            
            <div class="return-step">
                <i class="fas fa-shipping-fast"></i>
                <span class="step-number">STEP 3</span>
                <h4>SHIP BACK THE ITEM</h4>
                <p>Properly package and send the item back. You will be responsible for the return shipping cost, unless the item is defective or incorrect.</p>
            </div>
            
        </div>
        
        <h3>Refund Process</h3>
        <p>Once our team receives and inspects the item, we will process your refund. This may take 5-10 business days depending on your bank or payment method.</p>
        
    </main>

    <?php include('footer_public.php'); ?>
    
</body>
</html>