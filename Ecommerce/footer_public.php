<?php
// Tandaan: Ang session_start() ay dapat nasa tumatawag na pahina.
// Walang PHP logic dito, tanging HTML structure para sa footer.
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="footer_public.css">

<footer class="site-footer">
    <div class="footer-container">
        
        <div class="footer-section footer-brand">
            <a href="customer_products.php" class="footer-logo">Keepkit</a>
            <p class="brand-tagline">
                Your trusted source for innovative Beauty Products. 
                Committed to Quality and Customer Satisfaction.
            </p>
        </div>

        <div class="footer-section">
            <h4><i class="fas fa-info-circle"></i> Company Info</h4>
            <ul>
                <li><a href="customer_about.php">About Keepkit</a></li>
                <li><a href="customer_contact.php">Contact Us</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4><i class="fas fa-headset"></i> Customer Service</h4>
            <ul>
                <li><a href="customer_return_policy.php">Return & Refund Policy</a></li>
                <li><a href="customer_report_suspicious.php">Report Suspicious Activity</a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h4><i class="fas fa-share-alt"></i> Connect With Us</h4>
            <div class="social-links">
                <a href="https://www.facebook.com/admiral.ventus" target="_blank" class="social-icon facebook-icon" title="Facebook">
                    <i class="fab fa-facebook-f"></i> Facebook
                </a>
                <a href="https://www.tiktok.com/@admiral.ventus" target="_blank" class="social-icon tiktok-icon" title="TikTok">
                    <i class="fab fa-tiktok"></i> TikTok
                </a>
                </div>
        </div>

    </div>
    
    <div class="footer-bottom">
        <p class="copyright-text">Keepkit <?php echo date("Y"); ?>. Created for educational use.</i></p>
    </div>
</footer>