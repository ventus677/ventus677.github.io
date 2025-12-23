<?php
// sidebar.php
// NOTE: This file expects $user and $pending_rr_count to be defined 
// in the main page (home.php) before this file is included.

// Ensure session is started for robustness, though usually done in the main file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define admin status based on the $user array passed from the main script.
// Assuming 'role' key holds the user type (e.g., 'admin', 'user')
$current_user_role = strtolower($user['role'] ?? 'user');
$is_admin = ($current_user_role === 'admin');
$is_regular_user = ($current_user_role === 'user');

// Define pending_rr_count if not available, though it should be passed from home.php
$pending_rr_count = $pending_rr_count ?? 0;
?>

<nav class="sidebar">
    <ul class="sidebar-menu">
        <h3 class="user-name">
            <a href="user_profile.php" class="profile-link"> 
                <?php
                $profile_pic_path = 'images/iconUser.png'; // Default image
                // Check if 'profile_picture' key exists and is not empty in the session user array
                if (isset($user['profile_picture']) && !empty($user['profile_picture'])) {
                    $actual_path = 'uploads/profiles/' . htmlspecialchars($user['profile_picture']);
                    // Check if file exists to prevent broken image icon
                    if (file_exists($actual_path)) {
                        $profile_pic_path = $actual_path;
                    }
                }
                ?>
                <img src="<?= $profile_pic_path ?>" alt="User Profile" class="icon profile-pic-icon">
                <span class="first-name"><?= htmlspecialchars($user['first_name'] ?? '') ?></span>
                <span class="last-name"><?= htmlspecialchars($user['last_name'] ?? '') ?></span>
            </a>
        </h3>
        
        <li>
            <a href="home.php" data-page="home.php" class="sidebar-link active"> <img src="images/iconHome.png" alt="Home Icon" class="icon">Home
            </a>
        </li>
        <?php if ($is_admin): ?>
        <li>
            <a href="dashboard.php" data-page="dashboard.php" class="sidebar-link">
                <img src="images/iconDashboard.png" alt="Dashboard Icon" class="icon">
                Dashboard
            </a>
        </li>
        <li>
            <a href="reports.php" data-page="reports.php" class="sidebar-link">
                <img src="images/iconReports.png" alt="Reports Icon" class="icon">
                Reports
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="products.php" data-page="products.php" class="sidebar-link">
                <img src="images/iconProducts.png" alt="Products Icon" class="icon">
                Products
            </a>
        </li>
        <li>
            <a href="supplier.php" data-page="supplier.php" class="sidebar-link">
                <img src="images/iconSuppliers.png" alt="Suppliers Icon" class="icon">
                Suppliers
            </a>
        </li>
        <li>
            <a href="users.php" data-page="users.php" class="sidebar-link">
                <img src="images/iconUsers.png" alt="Users Icon" class="icon">
                Users
            </a>
        </li>
        <li>
            <a href="customer_list.php" data-page="customer_list.php" class="sidebar-link active">
                <img src="images/iconCustomers.png" alt="Customers Icon" class="icon">
                Customers
            </a>
        </li>
        
        <?php if ($is_regular_user): ?>
        <li>
            <a href="Ecommerce/user_products.php" data-page="Ecommerce/user_products.php" class="sidebar-link">
                <img src="images/iconShop.png" alt="Shop Icon" class="icon">
                Shop
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($is_admin): ?>
        <li>
            <a href="admin_return_refund.php" data-page="admin_return_refund.php" class="sidebar-link return-refund-link">
                <img src="images/iconRefund.png" alt="Refund Icon" class="icon">Returns
                <?php if ($pending_rr_count > 0): ?>
                    <span class="notification-badge"><?= $pending_rr_count ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>