<?php
session_start();

// Helper function to check permissions
function hasPermission(array $userPermissions, string $module, string $action): bool {
    return isset($userPermissions[$module]) && in_array($action, $userPermissions[$module]);
}

// Get the user data from session.
$user = $_SESSION['user'] ?? null; // Use null coalescing to prevent undefined index error if session is not set

// If user is not logged in, redirect to auth.php (your login page)
if (!isset($user)) {
    header('Location: auth.php'); // Assuming auth.php is your login page
    exit;
}

// --- PERMISSION CHECK FOR THIS PAGE ---
// This page requires 'supplier' module 'create' permission
if (!hasPermission($user['permissions'] ?? [], 'supplier', 'create')) {
    // If the user does not have permission, set a response message in session
    $_SESSION['response'] = [
        'message' => 'You do not have permission to add suppliers.',
        'success' => false
    ];
    header('Location: home.php'); // Redirect to home.php or dashboard.php
    exit;
}
// --- END PERMISSION CHECK ---

$error_message = '';
$success_message = '';

// Check for success message from previous redirect
if (isset($_GET['success'])) {
    $success_message = "Supplier added successfully!";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include('database/connect.php'); // Ensure this path is correct

    $supplier_name = $_POST['supplier_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $supplier_address = $_POST['supplier_address'] ?? '';
    // Use user's ID from session for created_by
    $created_by = $user['id'] ?? null;
    $current_timestamp = date('Y-m-d H:i:s');

    if (empty($supplier_name) || empty($phone) || empty($email) || empty($supplier_address)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email address.";
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT * FROM suppliers WHERE email = :email");
            $stmt->execute(['email' => $email]);

            if ($stmt->rowCount() > 0) {
                $error_message = "Email address already exists!";
            } else {
                $insert = $conn->prepare("
                    INSERT INTO suppliers (supplier_name, phone, email, supplier_address, created_by, created_at, updated_at)
                    VALUES (:supplier_name, :phone, :email, :supplier_address, :created_by, :created_at, :updated_at)
                ");
                $insert->execute([
                    ':supplier_name' => $supplier_name,
                    ':phone' => $phone,
                    ':email' => $email,
                    ':supplier_address' => $supplier_address,
                    ':created_by' => $created_by,
                    ':created_at' => $current_timestamp,
                    ':updated_at' => $current_timestamp
                ]);

                // Set success message in session before redirecting
                $_SESSION['response'] = [
                    'message' => 'Supplier added successfully!',
                    'success' => true
                ];
                header("Location: add_suppliers.php"); // Redirect to supplier list page
                exit;
            }
        } catch (PDOException $e) {
            // Check for duplicate entry error (e.g., if email or phone is unique)
            if ($e->getCode() == '23000') { // SQLSTATE for Integrity Constraint Violation
                $error_message = "A supplier with this email or phone already exists.";
            } else {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Supplier - Keepkit</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <style>
        .signup-form { /* Renamed for clarity to add-supplier-form */
            width: 50%;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #f9f9f9; /* Added a light background */
        }
        .signup-form h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .signup-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .signup-form input, .signup-form textarea {
            width: calc(100% - 20px); /* Adjust for padding */
            padding: 10px;
            margin-bottom: 15px; /* Increased margin */
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }
        .signup-form button {
            background-color: #007bff; /* Changed to a more standard blue */
            color: white;
            border: none;
            padding: 12px 25px; /* Increased padding */
            border-radius: 5px; /* Slightly more rounded */
            cursor: pointer;
            font-size: 1.1em;
            display: block; /* Make button full width */
            width: 100%;
            transition: background-color 0.3s ease; /* Smooth transition */
        }
        .signup-form button:hover {
            background-color: #0056b3;
        }
        .error {
            color: #dc3545; /* Bootstrap red */
            background-color: #f8d7da; /* Light red background */
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            color: #28a745; /* Bootstrap green */
            background-color: #d4edda; /* Light green background */
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
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

        /* START: Adjusted styles for the View Suppliers button */
        .view-suppliers-button {
            background-color: #6c757d; /* A neutral color */
            color: white;
            border: none;
            padding: 8px 15px; /* Smaller padding */
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em; /* Smaller font size */
            display: block; /* Keep it as block for easy centering with margin: auto */
            width: fit-content; /* Make width fit content, not 100% */
            margin: 15px auto 0 auto; /* Center it horizontally, maintain top margin */
            text-align: center; /* Center the text inside the button */
            text-decoration: none; /* Remove underline for links */
            transition: background-color 0.3s ease;
        }

        .view-suppliers-button:hover {
            background-color: #5a6268; /* Darker shade on hover */
        }
        /* END: Adjusted styles for the View Suppliers button */
    </style>
</head>
<body>
    <header>
        <a href="home.php" id="navbar__logo"> <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
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
            <a href="view_suppliers.php" class="view-suppliers-button">View Suppliers</a>
            <div class="signup-form">
                <h2>Add New Supplier</h2>
                <?php if (!empty($error_message)): ?>
                    <div class="error"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <form action="add_suppliers.php" method="post">
                    <label for="supplier_name">Supplier Name:</label>
                    <input type="text" id="supplier_name" name="supplier_name" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>

                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" required>

                    <label for="supplier_address">Supplier Address:</label>
                    <textarea id="supplier_address" name="supplier_address" rows="4" required></textarea>

                    <input type="hidden" name="created_by" value="<?= htmlspecialchars($user['id']) ?>">
                    <button type="submit">Add Supplier</button>
                </form>

                
            </div>
        </main>
    </div>

<script src="script.js"></script>
</body>
</html>