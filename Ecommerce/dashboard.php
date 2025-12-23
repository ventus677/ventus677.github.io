<?php
session_start();
include('../database/connect.php');

// Siguraduhin na naka-login ang user bago makita ang dashboard
if (!isset($_SESSION['user'])) {
    header('Location: user_auth.php');
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// Kumuha ng simpleng stats para sa dashboard
try {
    // Bilang ng orders ng customer (halimbawa lang, i-adjust kung iba ang table name mo)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_orders = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_orders = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Keepkit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="customer_home.css">
    <style>
        .main-content {
            margin-left: 260px; /* Adjust base sa sidebar width */
            padding: 40px;
            background: #f9f9f9;
            min-height: 100vh;
        }
        .welcome-box {
            background: linear-gradient(135deg, #6a0dad, #a020f0);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .stat-card i {
            font-size: 2rem;
            color: #6a0dad;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            margin: 10px 0;
            font-size: 1.5rem;
        }
        .stat-card p {
            color: #777;
            margin: 0;
        }
        .quick-actions {
            margin-top: 40px;
        }
        .action-btn {
            display: inline-block;
            padding: 12px 25px;
            background: #6a0dad;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-right: 10px;
            transition: 0.3s;
        }
        .action-btn:hover {
            background: #4b0082;
        }
    </style>
</head>
<body>

    <?php include('customer_sidebar.php'); ?>

    <div class="main-content">
        <div class="welcome-box">
            <h1>Welcome back, <?= htmlspecialchars($user['first_name']) ?>!</h1>
            <p>Narito ang summary ng iyong account sa Keepkit.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-shopping-bag"></i>
                <h3><?= $total_orders ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-tag"></i>
                <h3><?= ucfirst($user['role']) ?></h3>
                <p>Account Type</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <p>Active Member Since</p>
                <small><?= date('M Y') ?></small>
            </div>
        </div>

        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <a href="user_products.php" class="action-btn">Shop Now</a>
            <a href="customer_profile.php" class="action-btn" style="background: #333;">Edit Profile</a>
        </div>
    </div>

</body>
</html>