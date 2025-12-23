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
    <title>Contact Us | KeepKit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/customer_products.css">
    <link rel="stylesheet" href="customer_footer.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    
    <?php include('header_public.php'); ?>

    <main class="info-page-container">
        <h2>Get In Touch</h2>
        <span class="page-subtitle">We're here to help with your inquiries and support needs.</span>

        <p>Do you have a question, suggestion, or need help? Don't hesitate to contact us. We are here to help you!</p>

        <h3>Email & Business Support</h3>
        <p>For order inquiries, warranty, or formal questions, use our official email:</p>
        
        <div class="contact-social-links-container">
            
            <a href="mailto:markedsel.morales@cvsu.edu.ph" class="contact-social-link">
                <i class="fas fa-envelope"></i>
                <div class="link-details">
                    <span>Email Support</span>
                    <small>markedsel.morales@cvsu.edu.ph</small>
                </div>
            </a>

            <div class="contact-social-link" style="cursor: default; background-color: var(--value-box-bg);">
                <i class="fas fa-clock"></i>
                <div class="link-details">
                    <span>Business Hours</span>
                    <small>Monday - Friday, 9:00 AM - 5:00 PM (PST)</small>
                </div>
            </div>
            
        </div>
        
        <h3 style="margin-top: 50px;">Connect Via Social Media</h3>
        <p>You can message or follow us on our social media accounts. This is the fastest way for general inquiries:</p>
        
        <div class="contact-social-links-container" style="max-width: 900px; gap: 15px;">
            
            <a href="https://www.facebook.com/admiral.ventus" target="_blank" class="contact-social-link" style="width: 48%;">
                <i class="fab fa-facebook-f"></i>
                <div class="link-details">
                    <span>Facebook: /admiral.ventus</span>
                    <small>Send a Messenger message</small>
                </div>
            </a>
            
            <a href="https://www.tiktok.com/@admiral.ventus" target="_blank" class="contact-social-link" style="width: 48%;">
                <i class="fab fa-tiktok"></i>
                <div class="link-details">
                    <span>TikTok: @admiral.ventus</span>
                    <small>You can DM or comment</small>
                </div>
            </a>
            
        </div>
        
    </main>

    <?php include('footer_public.php'); ?>
    
</body>
</html>