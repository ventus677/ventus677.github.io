<?php
session_start();
// Path corrected: Assuming database/connect.php is directly under the same root as edit_supplier.php
include('database/connect.php');

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('Location: index.php'); // Corrected path: index.php is at the root
    exit;
}

// Check for session messages (e.g., success/error from previous operations)
$response_message = $_SESSION['response']['message'] ?? '';
$response_success = $_SESSION['response']['success'] ?? null;
unset($_SESSION['response']); // Clear the session message after displaying

$supplier_id = $_GET['id'] ?? null;
$message = ''; // Initialize a message variable for form processing

// Process the form submission for updating supplier
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_id = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
    $supplier_name = filter_input(INPUT_POST, 'supplier_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $supplier_address = filter_input(INPUT_POST, 'supplier_address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($supplier_id && $supplier_name && $email && $phone && $supplier_address) {
        try {
            $stmt = $conn->prepare("
                UPDATE suppliers
                SET
                    supplier_name = :supplier_name,
                    email = :email,
                    phone = :phone,
                    supplier_address = :supplier_address,
                    updated_at = NOW()
                WHERE supplier_id = :supplier_id
            ");
            $stmt->bindParam(':supplier_name', $supplier_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':supplier_address', $supplier_address);
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $_SESSION['response'] = ['success' => true, 'message' => 'Supplier updated successfully!'];
            } else {
                $_SESSION['response'] = ['success' => false, 'message' => 'No changes made or supplier not found.'];
            }
        } catch (PDOException $e) {
            $_SESSION['response'] = ['success' => false, 'message' => 'Database error updating supplier: ' . $e->getMessage()];
            error_log("Error updating supplier: " . $e->getMessage());
        }
    } else {
        $_SESSION['response'] = ['success' => false, 'message' => 'All fields are required and valid for update.'];
    }
    // Redirect back to view_suppliers.php after form submission
    header('Location: view_suppliers.php'); // Corrected path: view_suppliers.php is at the root
    exit;
}

// Fetch supplier data for displaying the form
$supplier = null;
if ($supplier_id) {
    try {
        if (!($conn instanceof PDO)) {
            throw new Exception("Database connection object is not a PDO instance, or connection failed.");
        }
        $stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = :id");
        $stmt->bindParam(':id', $supplier_id, PDO::PARAM_INT);
        $stmt->execute();
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching supplier for edit: " . $e->getMessage());
        $_SESSION['response'] = ['success' => false, 'message' => 'Database error fetching supplier: ' . $e->getMessage()];
        header('Location: view_suppliers.php'); // Corrected path
        exit;
    } catch (Exception $e) {
        error_log("General error fetching supplier for edit: " . $e->getMessage());
        $_SESSION['response'] = ['success' => false, 'message' => 'Server error fetching supplier: ' . $e->getMessage()];
        header('Location: view_suppliers.php'); // Corrected path
        exit;
    }
}

// If no supplier ID or supplier not found, redirect back
if (!$supplier) {
    $_SESSION['response'] = ['success' => false, 'message' => 'Supplier not found.'];
    header('Location: view_suppliers.php');
    exit;
}

// User data for header display (if needed, otherwise can be removed)
$user = $_SESSION['user'] ?? ['first_name' => '', 'last_name' => ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="products.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Custom styles for the form container, adjusted for potential full-width layout */
        .edit-supplier-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px; /* Still good to limit width for forms */
            margin: 40px auto; /* Centers the form horizontally */
        }

        .edit-supplier-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-size: 1.8em;
        }

        .edit-supplier-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .edit-supplier-container input[type="text"],
        .edit-supplier-container input[type="email"],
        .edit-supplier-container input[type="tel"] {
            width: calc(100% - 20px); /* Adjusting for padding */
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .edit-supplier-container input[type="text"]:focus,
        .edit-supplier-container input[type="email"]:focus,
        .edit-supplier-container input[type="tel"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .edit-supplier-container button[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .edit-supplier-container button[type="submit"]:hover {
            background-color: #218838;
        }

        .edit-supplier-container .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .edit-supplier-container .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .edit-supplier-container .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Basic styling for the back button */
        .back-button {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px; /* Adjust as needed */
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: #5a6268;
        }

        /* Adjust main-content for full width if no sidebar */
        .main-content {
            padding: 20px; /* Add some padding around the content */
            box-sizing: border-box; /* Include padding in width */
        }
        /* Top bar styling - copied from product.php/home.css assumptions */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #f8f9fa; /* Light background for header */
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .header-left h1 {
            margin: 0;
            color: #343a40;
            font-size: 1.5em;
        }

        .header-left h1 i {
            margin-right: 10px;
            color: #007bff;
        }

        .header-right .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .header-right .logout-btn:hover {
            background-color: #c82333;
        }

        .notification {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .user-name {
    background-color: #343a40;
    padding: 15px 10px;
    border-bottom: 1px solid #444;
    margin-bottom: 20px;
    text-align: left;
    display: flex;
    align-items: center;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.2s ease;
}

.user-name:hover {
    background-color: #495057;
}

.user-name a.profile-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #f8f9fa;
    font-size: 1.15rem;
    font-weight: 600;
    width: 100%;
}

.user-name img.profile-pic-icon {
    height: 35px;
    width: 35px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 12px;
    border: 2px solid #ffc107;
    box-shadow: 0 0 5px rgba(0,0,0,0.3);
}

.user-name span.first-name,
.user-name span.last-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
    

        /* Styles for search results */
      <?php
session_start();
// Path corrected: Assuming database/connect.php is directly under the same root as edit_supplier.php
include('database/connect.php');

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('Location: index.php'); // Corrected path: index.php is at the root
    exit;
}

// Check for session messages (e.g., success/error from previous operations)
$response_message = $_SESSION['response']['message'] ?? '';
$response_success = $_SESSION['response']['success'] ?? null;
unset($_SESSION['response']); // Clear the session message after displaying

$supplier_id = $_GET['id'] ?? null;
$message = ''; // Initialize a message variable for form processing

// Process the form submission for updating supplier
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_id = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
    $supplier_name = filter_input(INPUT_POST, 'supplier_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $supplier_address = filter_input(INPUT_POST, 'supplier_address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($supplier_id && $supplier_name && $email && $phone && $supplier_address) {
        try {
            $stmt = $conn->prepare("
                UPDATE suppliers
                SET
                    supplier_name = :supplier_name,
                    email = :email,
                    phone = :phone,
                    supplier_address = :supplier_address,
                    updated_at = NOW()
                WHERE supplier_id = :supplier_id
            ");
            $stmt->bindParam(':supplier_name', $supplier_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':supplier_address', $supplier_address);
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $_SESSION['response'] = ['success' => true, 'message' => 'Supplier updated successfully!'];
            } else {
                $_SESSION['response'] = ['success' => false, 'message' => 'No changes made or supplier not found.'];
            }
        } catch (PDOException $e) {
            $_SESSION['response'] = ['success' => false, 'message' => 'Database error updating supplier: ' . $e->getMessage()];
            error_log("Error updating supplier: " . $e->getMessage());
        }
    } else {
        $_SESSION['response'] = ['success' => false, 'message' => 'All fields are required and valid for update.'];
    }
    // Redirect back to view_suppliers.php after form submission
    header('Location: view_suppliers.php'); // Corrected path: view_suppliers.php is at the root
    exit;
}

// Fetch supplier data for displaying the form
$supplier = null;
if ($supplier_id) {
    try {
        if (!($conn instanceof PDO)) {
            throw new Exception("Database connection object is not a PDO instance, or connection failed.");
        }
        $stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = :id");
        $stmt->bindParam(':id', $supplier_id, PDO::PARAM_INT);
        $stmt->execute();
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching supplier for edit: " . $e->getMessage());
        $_SESSION['response'] = ['success' => false, 'message' => 'Database error fetching supplier: ' . $e->getMessage()];
        header('Location: view_suppliers.php'); // Corrected path
        exit;
    } catch (Exception $e) {
        error_log("General error fetching supplier for edit: " . $e->getMessage());
        $_SESSION['response'] = ['success' => false, 'message' => 'Server error fetching supplier: ' . $e->getMessage()];
        header('Location: view_suppliers.php'); // Corrected path
        exit;
    }
}

// If no supplier ID or supplier not found, redirect back
if (!$supplier) {
    $_SESSION['response'] = ['success' => false, 'message' => 'Supplier not found.'];
    header('Location: view_suppliers.php');
    exit;
}

// User data for header display (if needed, otherwise can be removed)
$user = $_SESSION['user'] ?? ['first_name' => '', 'last_name' => ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="products.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Custom styles for the form container, adjusted for potential full-width layout */
        .edit-supplier-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px; /* Still good to limit width for forms */
            margin: 40px auto; /* Centers the form horizontally */
        }

        .edit-supplier-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-size: 1.8em;
        }

        .edit-supplier-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .edit-supplier-container input[type="text"],
        .edit-supplier-container input[type="email"],
        .edit-supplier-container input[type="tel"] {
            width: calc(100% - 20px); /* Adjusting for padding */
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .edit-supplier-container input[type="text"]:focus,
        .edit-supplier-container input[type="email"]:focus,
        .edit-supplier-container input[type="tel"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .edit-supplier-container button[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .edit-supplier-container button[type="submit"]:hover {
            background-color: #218838;
        }

        .edit-supplier-container .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .edit-supplier-container .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .edit-supplier-container .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Basic styling for the back button */
        .back-button {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px; /* Adjust as needed */
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: #5a6268;
        }

        /* Adjust main-content for full width if no sidebar */
        .main-content {
            padding: 20px; /* Add some padding around the content */
            box-sizing: border-box; /* Include padding in width */
        }

        /* --- Start of Top Bar/Header styles based on users.php --- */
        header {
            display: flex;
            justify-content: space-between; /* To push logo to left and logout to right */
            align-items: center;
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: relative; /* Crucial for absolute positioning of search-container */
            z-index: 10;
            margin-bottom: 20px; /* Space below the header */
        }

        #navbar__logo {
            z-index: 1; /* Ensure logo is on top */
            display: flex; /* To align image and h3 */
            align-items: center;
            text-decoration: none; /* Remove underline from link */
            color: #343a40; /* Match text color */
        }
        #navbar__logo h3 {
            margin: 0; /* Remove default margin from h3 */
            font-size: 1.5em; /* Match h1 in previous header style */
        }

        .search-container {
            position: absolute; /* Absolute positioning */
            left: 50%; /* Center horizontally */
            transform: translateX(-50%); /* Adjust for true centering */
            width: 300px; /* Fixed width for the search bar */
            max-width: 40%; /* Responsive sizing */
            z-index: 0; /* Can be behind logo if overlap, adjust as needed */
        }

        #searchInput {
            width: 100%; /* Make search input fill its container */
            padding: 8px 15px;
            border: 1px solid #ccc;
            border-radius: 20px; /* Rounded corners */
            font-size: 1em;
            box-sizing: border-box; /* Include padding in width */
        }

        #searchResults {
            /* Styles for search results dropdown, if any, will go here */
        }

        .right-element {
            z-index: 1; /* Ensure logout is visible */
        }
        .right-element img {
            height: 30px; /* Adjust size for the logout icon */
            width: 30px;
            vertical-align: middle; /* Align with text if there was any */
            transition: transform 0.2s ease;
        }
        .right-element img:hover {
            transform: scale(1.1); /* Slight grow effect on hover */
        }
        /* --- End of Top Bar/Header styles based on users.php --- */

        .notification {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            <div class="main-content">
                    <div class="header-right">
                        <a href="view_suppliers.php" class="back-button">
                            <i class="fas fa-arrow-circle-left"></i> Back to Suppliers
                        </a>
                    </div>
                <div class="header">
                    <div class="header-left">
                        <h1><i class="fas fa-truck"></i> Edit Supplier</h1>
                    </div>
                
                </div>

                <?php if ($response_message): ?>
                    <div class="notification <?= $response_success ? 'success' : 'error' ?>">
                        <?= htmlspecialchars($response_message) ?>
                    </div>
                <?php endif; ?>

                <div class="edit-supplier-container">
                    <h2>Edit Supplier Details</h2>
                    <form method="post" action="edit_supplier.php?id=<?= htmlspecialchars($supplier_id) ?>">
                        <input type="hidden" name="supplier_id" value="<?= htmlspecialchars($supplier['supplier_id']) ?>">

                        <label for="supplier_name">Supplier Name:</label>
                        <input type="text" id="supplier_name" name="supplier_name" value="<?= htmlspecialchars($supplier['supplier_name']) ?>" required><br>

                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($supplier['email']) ?>" required><br>

                        <label for="phone">Phone:</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($supplier['phone']) ?>" required><br>

                        <label for="supplier_address">Supplier Address:</label>
                        <input type="text" id="supplier_address" name="supplier_address" value="<?= htmlspecialchars($supplier['supplier_address']) ?>" required><br>

                        <button type="submit">Update Supplier</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
    </style>
</head>
<body>
    <header>
        <a href="/" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
                &nbsp;&nbsp;Keepkit
        </a>
        <div class="right-element">
            <div class="search-container" style="text-align: center;">
                <div style="display: inline-block;">
                    <input type="search" id="searchInput" placeholder="Search products..." autocomplete="off" style="display: block; margin-bottom: 10px;">
                    <div id="searchResults"></div>
                </div>
            </div>
            <a href="database/logout.php" ><img src= "images/iconLogout.png" alt="Logout Icon"></a>
        </div>
    </header>

    <div class="page" id="page">
        <nav class="sidebar">
            <ul class="sidebar-menu">
                <h3 class="user-name">
                    <img src="images/iconUser.png" alt="user" class="icon">
                    <span class="first-name"><?= htmlspecialchars($user['first_name'] ?? '') ?></span>
                    <span class="last-name"><?= htmlspecialchars($user['last_name'] ?? '') ?></span>
                </h3>
                <li>
                <a href="home.php" data-page="home.php" class="sidebar-link">
                    <img src="images/iconHome.png" alt="Home Icon" class="icon">Home
                </a>
                </li>
                <li>
                <a href="dashboard.php" data-page="dashboard.php" class="sidebar-link">
                    <img src="images/iconDashboard.png" alt="Dashboard Icon" class="icon">
                    Dashboard
                </a>
                </li>
                <li>
                <a href="products.php" data-page="products.php" class="sidebar-link">
                    <img src="images/iconProducts.png" alt="Products Icon" class="icon">
                    Products
                </a>
                </li>
                <li>
                <a href="supplier.php" data-page="supplier.php" class="sidebar-link active">
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
            </ul>
        </nav>
        <main class="main">
            <div class="main-content">
                <div class="header">
                    <div class="header-left">
                        <h1><i class="fas fa-truck"></i> Edit Supplier</h1>
                    </div>
                    <div class="header-right">
                        <a href="view_suppliers.php" class="back-button">
                            <i class="fas fa-arrow-circle-left"></i> Back to Suppliers
                        </a>
                    </div>
                </div>

                <?php if ($response_message): ?>
                    <div class="notification <?= $response_success ? 'success' : 'error' ?>">
                        <?= htmlspecialchars($response_message) ?>
                    </div>
                <?php endif; ?>

                <div class="edit-supplier-container">
                    <h2>Edit Supplier Details</h2>
                    <form method="post" action="edit_supplier.php?id=<?= htmlspecialchars($supplier_id) ?>">
                        <input type="hidden" name="supplier_id" value="<?= htmlspecialchars($supplier['supplier_id']) ?>">

                        <label for="supplier_name">Supplier Name:</label>
                        <input type="text" id="supplier_name" name="supplier_name" value="<?= htmlspecialchars($supplier['supplier_name']) ?>" required><br>

                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($supplier['email']) ?>" required><br>

                        <label for="phone">Phone:</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($supplier['phone']) ?>" required><br>

                        <label for="supplier_address">Supplier Address:</label>
                        <input type="text" id="supplier_address" name="supplier_address" value="<?= htmlspecialchars($supplier['supplier_address']) ?>" required><br>

                        <button type="submit">Update Supplier</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="script.js"></script>
    <script>
        $(document).ready(function() {
            // Live search functionality
            $('#searchInput').on('input', function() {
                var searchQuery = $(this).val();
                var searchResultsDiv = $('#searchResults');

                if (searchQuery.length > 2) { // Only search if more than 2 characters are typed
                    $.ajax({
                        url: 'search_products.php', // This will be your new PHP file for searching
                        method: 'GET',
                        data: { query: searchQuery },
                        success: function(data) {
                            searchResultsDiv.empty();
                            if (data.length > 0) {
                                $.each(data, function(index, product) {
                                    // Make sure product_detail_home.php exists for this link
                                    searchResultsDiv.append('<a href="product_detail_home.php?id=' + product.id + '">' + htmlspecialchars(product.product_name) + ' (₱' + number_format(product.price, 2) + ')</a>');
                                });
                            } else {
                                searchResultsDiv.append('<p style="padding: 10px; text-align: center;">No products found.</p>');
                            }
                            searchResultsDiv.show();
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("Error searching products:", textStatus, errorThrown);
                            searchResultsDiv.empty().append('<p style="padding: 10px; text-align: center; color: red;">Error searching products.</p>');
                            searchResultsDiv.show();
                        }
                    });
                } else {
                    searchResultsDiv.empty().hide();
                }
            });

            // Hide search results when clicking outside
            $(document).on('click', function(event) {
                if (!$(event.target).closest('.search-container').length) {
                    $('#searchResults').hide();
                }
            });

            // Prevent hiding results when clicking inside search container
            $('.search-container').on('click', function(event) {
                event.stopPropagation();
            });

            // Helper function for HTML escaping (as used in home.php)
            function htmlspecialchars(str) {
                var map = {
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#039;"
                };
                return str.replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            // Helper function for number formatting (as used in home.php)
            function number_format(number, decimals, dec_point, thousands_sep) {
                // Strip all characters but numerical ones.
                number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
                var n = !isFinite(+number) ? 0 : +number,
                    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                    s = '',
                    toFixedFix = function (n, prec) {
                        var k = Math.pow(10, prec);
                        return '' + Math.round(n * k) / k;
                    };

                s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                if (s[0].length > 3) {
                    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                }
                if ((s[1] || '').length < prec) {
                    s[1] = s[1] || '';
                    s[1] += new Array(prec - s[1].length + 1).join('0');
                }
                return s.join(dec);
            }
        });
    </script>
</body>
</html>