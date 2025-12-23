<?php
session_start();

// Include database connection
// Assuming connect.php establishes $conn PDO object
include('connect.php');

// Enable error logging for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
error_log("--- Starting selling_product insertion script ---");

// Define the target table
$table_name = 'selling_product';

// Define the columns expected from POST and those to be inserted into 'selling_product'
// We now expect 'product_id' and 'price' from POST
$required_post_data = ['product_id', 'price']; // Data we expect from the form

$response = ['success' => false, 'message' => 'An unexpected error occurred.']; // Default response

$user = $_SESSION['user'] ?? ['id' => null];
$userId = $user['id'] ?? null;
$timestamp = date('Y-m-d H:i:s'); // Current timestamp for created_at and updated_at

// Set the redirect URL to the correct page
$redirect_url = '../create_selling.php'; // <--- CHANGED THIS LINE TO create_selling.php

// 1. Validate User Session
if (!$userId) {
    error_log("User not logged in. Session user ID is null.");
    $response['message'] = 'User not logged in. Please log in again.';
    $_SESSION['response'] = $response;
    header('Location: ' . $redirect_url);
    exit();
}

// 2. Check if the 'created_by' user exists in the database
try {
    $checkUserQuery = "SELECT id FROM users WHERE id = :userId";
    $checkUserStmt = $conn->prepare($checkUserQuery);
    $checkUserStmt->execute([':userId' => $userId]);

    if ($checkUserStmt->rowCount() == 0) {
        error_log("User ID {$userId} not found in the users table.");
        $response['message'] = "User ID {$userId} not found. Cannot create selling product entry.";
        $_SESSION['response'] = $response;
        header('Location: ' . $redirect_url);
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error checking user existence: " . $e->getMessage());
    $response['message'] = "Database error while verifying user: " . $e->getMessage();
    $_SESSION['response'] = $response;
    header('Location: ' . $redirect_url);
    exit();
}


// 3. Prepare data for insertion into 'selling_product' table
$db_arr = [];

// Validate and sanitize POST data
foreach ($required_post_data as $column) {
    if (!isset($_POST[$column]) || empty(trim($_POST[$column])) ) { // Added trim to check for empty strings
        error_log("Missing or empty POST data for column: $column");
        $response['message'] = "Missing required data for " . str_replace('_', ' ', $column) . ".";
        $_SESSION['response'] = $response;
        header('Location: ' . $redirect_url);
        exit();
    }

    $value = $_POST[$column];

    if ($column === 'product_id') {
        $db_arr[$column] = filter_var($value, FILTER_VALIDATE_INT);
        if ($db_arr[$column] === false || $db_arr[$column] <= 0) {
            $response['message'] = 'Invalid product selection. Please select a product from the list.';
            $_SESSION['response'] = $response;
            header('Location: ' . $redirect_url);
            exit();
        }
    } elseif ($column === 'price') {
        $db_arr[$column] = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if ($db_arr[$column] === false || $db_arr[$column] < 0) {
            $response['message'] = 'Invalid price. Please enter a non-negative number.';
            $_SESSION['response'] = $response;
            header('Location: ' . $redirect_url);
            exit();
        }
    }
}

// Now, fetch the actual product_name from the 'products' table using product_id
try {
    $getProductQuery = "SELECT product_name FROM products WHERE id = :product_id";
    $getProductStmt = $conn->prepare($getProductQuery);
    $getProductStmt->execute([':product_id' => $db_arr['product_id']]);
    $product_data = $getProductStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product_data) {
        error_log("Product with ID {$db_arr['product_id']} not found in the products table.");
        $response['message'] = "Selected product not found. Please select a valid product.";
        $_SESSION['response'] = $response;
        header('Location: ' . $redirect_url);
        exit();
    }
    // Add product_name to the array for insertion into selling_product
    $db_arr['product_name'] = $product_data['product_name'];
} catch (PDOException $e) {
    error_log("Database error fetching product name: " . $e->getMessage());
    $response['message'] = "Database error while fetching product name: " . $e->getMessage();
    $_SESSION['response'] = $response;
    header('Location: ' . $redirect_url);
    exit();
}


// Add auto-generated columns
$db_arr['created_at'] = $timestamp;
$db_arr['updated_at'] = $timestamp;
$db_arr['created_by'] = $userId; // Assign the validated user ID

// 4. Construct SQL query for 'selling_product' table
// Make sure your selling_product table has columns: product_id, product_name, price, created_at, updated_at, created_by
$columns_sql = implode(", ", array_keys($db_arr));
$placeholders_sql = implode(", ", array_map(fn($k) => ":$k", array_keys($db_arr)));

$sql = "INSERT INTO $table_name ($columns_sql) VALUES ($placeholders_sql)";
error_log("SQL Query: " . $sql);
error_log("Parameters for SQL: " . print_r($db_arr, true));

// 5. Execute the insertion
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($db_arr); // Use the prepared array for execution

    $response = ['success' => true, 'message' => "Product added to {$table_name} successfully."];
    error_log("Product added successfully to selling_product.");

} catch (PDOException $e) {
    error_log("Database error during insert into selling_product: " . $e->getMessage());
    // Check for duplicate entry error (e.g., if product_id is unique in selling_product)
    if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $response['message'] = "This product is already listed for selling.";
    } else {
        $response['message'] = "Database Error: " . $e->getMessage();
    }
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    $response['message'] = "An unexpected error occurred: " . $e->getMessage();
}


// 6. Store response in session and redirect
$_SESSION['response'] = $response;
header('Location: ' . $redirect_url); // Redirect back to create_selling.php
exit();

?>