<?php
session_start();


$customer = $_SESSION['customer'] ?? null; 
$total_items_in_cart = $_SESSION['total_items_in_cart'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About KeepKit</title> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer_products.css">
    <link rel="stylesheet" href="customer_footer.css">
    <link rel="stylesheet" href="customer_about_v2.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet"> 
</head>
<body>
    
    <?php include('header_public.php'); ?>

    <main class="info-page-container">
        <h2>Who We Are</h2>
        <span class="page-subtitle">KEEPKIT</span>

        <p style="text-align: center;">Welcome to Keepkit, a keeper of quality makeup kits.</p>

        <h3 style="text-align: center;">Our Mission</h3>
        <p style="text-align: center;">Empower your beauty journey by providing reliable, and high-quality products that you can trust for lasting results.</p>
        <h3>Our Core Values</h3>
        <div class="about-values-grid">
            <div class="about-value-item">
                <i class="fas fa-hand-holding-heart"></i>                 <h4>Quality & Assurance</h4>
                <p style="text-align: center;">We guarantee the integrity and efficacy of every product, building trust through transparency and proven results.</p>
            </div>
            
            <div class="about-value-item">
                <i class="fas fa-magic"></i>                 <h4>Innovation & Performance</h4>
                <p>We are committed to sourcing the most and reliable brands for noticeable, professional-grade results.</p>
            </div>

            <div class="about-value-item">
                <i class="fas fa-smile-wink"></i>                 <h4>Customer Focus & Support</h4>
                <p>Your satisfaction and success with our products are our priority. We are here to support your journey to radiant skin every step of the way.</p>
            </div>
        </div>
        
    </main>

    <?php include('footer_public.php');?>
</body>
</html>