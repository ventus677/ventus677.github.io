<?php
include('database/connect.php'); // Assuming connect.php is in the same directory
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];

// STEP 1: Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Input validation (existing)
    if (
        !isset($_POST['id']) || !is_numeric($_POST['id']) ||
        empty($_POST['product_name']) ||
        empty($_POST['category']) ||
        !is_numeric($_POST['price']) ||
        !is_numeric($_POST['cost'])
    ) {
        die("Missing or invalid input data.");
    }

    $productId = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $productName = filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $cost = filter_input(INPUT_POST, 'cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Handle weight and unit
    $weight = null;
    $unit = null;

    if (isset($_POST['weight']) && $_POST['weight'] !== '' && is_numeric($_POST['weight'])) {
        $weight = filter_input(INPUT_POST, 'weight', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    if (isset($_POST['choose']) && ($_POST['choose'] === 'mL' || $_POST['choose'] === 'g')) {
        $unit = $_POST['choose'];
    }

    $img = '';
    if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/products/"; // Adjust path if necessary
        $file_data = $_FILES['img'];
        $file_name = 'products' . time() . '.' . pathinfo($file_data['name'], PATHINFO_EXTENSION);

        if (move_uploaded_file($file_data['tmp_name'], $target_dir . $file_name)) {
            $img = $file_name;
        } else {
            die("Error uploading image.");
        }
    }

    // Construct SQL query for update
    $sql_parts = ["product_name = ?", "category = ?", "price = ?", "cost = ?"   ];
    $params = [$productName, $category, $price, $cost];

    // Handle weight and unit (explicitly set to NULL if not provided)
    if ($weight !== null) {
        $sql_parts[] = "weight = ?";
        $params[] = $weight;
    } else {
        $sql_parts[] = "weight = NULL";
    }

    if ($unit !== null) {
        $sql_parts[] = "choose = ?";
        $params[] = $unit;
    } else {
        $sql_parts[] = "choose = NULL";
    }

    if ($img !== '') {
        $sql_parts[] = "img = ?";
        $params[] = $img;
    }

    $sql = "UPDATE products SET " . implode(', ', $sql_parts) . " WHERE id = ?";
    $params[] = $productId;

    $stmt = $conn->prepare($sql);
    try {
        $stmt->execute($params);
        header("Location: view_productOverview.php"); // Adjust path if necessary
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// STEP 2: Show edit form
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $productId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    try {
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            die("Product not found.");
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Edit Product - Keepkit</title>
            <link rel="stylesheet" href="home.css"/>
            <link rel="stylesheet" href="products.css"/>
            <link rel="stylesheet" href="tables.css"/>
            <link rel="icon" type="image/png" href="../images/KeepkitFavicon.png"/>
            <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

            <style>
                /* --- General Layout (from view_productOverview.php) --- */
                .page {
                    display: flex;
                    align-items: flex-start; /* Aligns sidebar and main content to the top */
                    min-height: 100vh;
                }

                /* --- Sidebar User Name (from view_productOverview.php) --- */
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

                /* --- Sidebar Active Link (from view_productOverview.php) --- */
                .sidebar-menu .sidebar-link.active {
                    background-color: #007bff; /* Example active color */
                    color: white;
                    border-radius: 5px;
                }
                /* Ensure general sidebar link styling from home.css is respected */

                /* --- Main Content Area for Form --- */
                .main {
                    flex-grow: 1; /* Allows main content to fill available space */
                    padding: 20px;
                    background-color: #f0f2f5; /* A slightly softer background for the main area */
                }

                /* --- Form Container --- */
                .form-container {
                    max-width: 700px; /* Limit width for better readability */
                    margin: 40px auto; /* Center the form and give it vertical margin */
                    background-color: #ffffff;
                    padding: 30px;
                    border-radius: 12px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1); /* Softer, more pronounced shadow */
                }

                .form-container h1 {
                    text-align: center;
                    color: #333;
                    margin-bottom: 30px;
                    font-size: 2em; /* Larger heading */
                    font-weight: 700;
                }

                /* --- Form Groups (Labels and Inputs) --- */
                .form-group {
                    margin-bottom: 20px; /* More space between groups */
                }

                .form-group label {
                    display: block;
                    margin-bottom: 8px; /* Space between label and input */
                    font-weight: 600; /* Slightly bolder labels */
                    color: #555;
                    font-size: 1.05em;
                }

                .form-group input[type="text"],
                .form-group input[type="number"],
                .form-group select {
                    width: 100%; /* Full width within its container */
                    padding: 12px; /* More padding */
                    border: 1px solid #ced4da; /* Light gray border */
                    border-radius: 8px; /* Slightly more rounded corners */
                    box-sizing: border-box;
                    font-size: 1em;
                    transition: border-color 0.2s, box-shadow 0.2s; /* Smooth transition for focus */
                }

                .form-group input[type="text"]:focus,
                .form-group input[type="number"]:focus,
                .form-group select:focus {
                    border-color: #80bdff; /* Blue border on focus */
                    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); /* Blue glow on focus */
                    outline: none; /* Remove default outline */
                }

                .form-group input[type="file"] {
                    padding: 10px 0; /* Adjust padding for file input */
                }

                /* --- Combined Weight and Unit Input --- */
                .weight-unit-group {
                    display: flex; /* Use flexbox to put them side-by-side */
                    gap: 15px; /* Space between weight input and select */
                    align-items: flex-end; /* Align inputs at the bottom */
                    margin-bottom: 20px;
                }

                .weight-unit-group .weight-input-wrapper,
                .weight-unit-group .unit-select-wrapper {
                    flex: 1; /* Allow both to take equal space */
                }
                
                .weight-unit-group .unit-select-wrapper {
                    flex: 0 0 120px; /* Give the select a fixed width */
                }

                /* --- Image Preview --- */
                .img-preview {
                    margin-top: 25px;
                    padding: 15px;
                    background-color: #f8f9fa;
                    border: 1px dashed #ced4da; /* Dashed border for preview area */
                    border-radius: 8px;
                    text-align: center;
                }

                .img-preview p {
                    color: #6c757d;
                    margin-bottom: 15px;
                }

                .img-preview img {
                    max-width: 250px; /* Larger preview image */
                    height: auto;
                    border: 2px solid #e9ecef; /* Lighter border */
                    padding: 5px;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08); /* Subtle shadow for image */
                }

                /* --- Submit Button --- */
                .submit-button {
                    display: block;
                    width: 100%;
                    padding: 12px 20px;
                    background-color: #28a745; /* Green for save changes */
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-size: 1.1em;
                    font-weight: 600;
                    cursor: pointer;
                    transition: background-color 0.2s ease, transform 0.1s ease;
                    margin-top: 30px;
                }

                .submit-button:hover {
                    background-color: #218838; /* Darker green on hover */
                    transform: translateY(-1px); /* Slight lift effect */
                }

                .submit-button:active {
                    transform: translateY(0); /* Press effect */
                }
            </style>
        </head>
        <body>
            <header>
                <a href="ndex.php" id="navbar__logo"> <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
                    <h3>&nbsp;&nbsp;Keepkit</h3>
                </a>
                
                <div class="right-element">
                    <a href="database/logout.php" ><img src= "images/iconLogout.png">
                    </a>
                </div>
            </header>

            <div class="page" id="page">
           <?php include('sidebar.php'); ?> 

                <main class="main">
                    <div class="form-container">
                        <h1>Edit Product</h1>
                        <form method="post" action="edit_product.php" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">

                            <div class="form-group">
                                <label for="product_name">Product Name:</label>
                                <input type="text" name="product_name" id="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="category">Category:</label>
                                <input type="text" name="category" id="category" value="<?= htmlspecialchars($product['category']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="price">Price:</label>
                                <input type="number" step="0.01" name="price" id="price" value="<?= htmlspecialchars($product['price']) ?>" required>
                            </div>
                             <div class="form-group">
                                <label for="cost">Cost:</label>
                                <input type="number" step="0.01" name="cost" id="price" value="<?= htmlspecialchars($product['cost']) ?>" required>
                            </div>

                            <div class="weight-unit-group">
                                <div class="weight-input-wrapper">
                                    <label for="weight">Weight (Optional):</label>
                                    <input type="number" id="weight" name="weight" step="any" value="<?= htmlspecialchars($product['weight'] ?? '') ?>">
                                </div>
                                <div class="unit-select-wrapper">
                                    <label for="unit">Unit:</label>
                                    <select name="choose" id="unit">
                                        <option value="" <?= empty($product['choose']) ? 'selected' : '' ?>>None</option>
                                        <option value="mL" <?= ($product['choose'] ?? '') === 'mL' ? 'selected' : '' ?>>mL</option>
                                        <option value="g" <?= ($product['choose'] ?? '') === 'g' ? 'selected' : '' ?>>g</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="img">Image (optional):</label>
                                <input type="file" name="img" id="img">
                            </div>

                            <div class="img-preview">
                                <?php if ($product['img']): ?>
                                    <p>Current Image:</p>
                                    <img src='uploads/products/<?= htmlspecialchars($product['img']) ?>' alt="Current Product Image">
                                <?php else: ?>
                                    <p>No current image.</p>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="submit-button">Save Changes</button>
                        </form>
                    </div>
                </main>
            </div>

            <script src="../script.js"></script> <script>
                // Mark the 'Products' link as active in the sidebar
                $(document).ready(function() {
                    $('.sidebar-menu .sidebar-link').removeClass('active');
                    $('.sidebar-menu a[data-page="products.php"]').addClass('active');
                });

                // Placeholder for global search functionality (if needed)
                const mainSearchInput = document.getElementById('searchInput');
                const mainSearchResults = document.getElementById('searchResults');

                if (mainSearchInput && mainSearchResults) {
                    mainSearchInput.addEventListener('input', function() {
                        // Implement your search logic here
                    });
                }
            </script>
        </body>
        </html>
        <?php
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    die("Product ID not specified.");
}
?>