<?php
session_start();

include('productstable.php');
include('connect.php');
 

if (!isset($_SESSION['table']) || !isset($table_columns_mapping[$_SESSION['table']])) {
    die("Invalid table name: " . ($_SESSION['table'] ?? 'NOT SET'));
}

$table_name = $_SESSION['table'];
$columns = $table_columns_mapping[$table_name];
$user = $_SESSION['user'] ?? ['id' => null];
$userId = $user['id'] ?? null;

$db_arr = [];

foreach ($columns as $column) {
    // Skip created_at and updated_at here for general tables,
    // as they might be handled by database defaults or set specifically for 'products' later.
    if (in_array($column, ['created_at', 'updated_at'])) {
        continue; 
    } elseif ($column === 'created_by') {
        $value = $userId;
    } elseif ($column == 'img') {
        if (isset($_FILES[$column]) && $_FILES[$column]['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/products/";
            $file_data = $_FILES[$column];

            $file_name = $file_data['name'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            // Ensure unique file name to prevent overwrites
            $file_name = 'products' . time() . '.' . $file_ext; 

            $check = getimagesize($file_data['tmp_name']);

            if ($check) {
                if (move_uploaded_file($file_data['tmp_name'], $target_dir . $file_name)) {
                    $value = $file_name;
                } else {
                    $value = ''; // File move failed
                }
            } else {
                $value = ''; // Not a valid image file
            }
        } else {
            $value = ''; // No file uploaded or an upload error occurred
        }
    } else {
        // Sanitize string inputs
        $value = isset($_POST[$column]) ? filter_input(INPUT_POST, $column, FILTER_SANITIZE_STRING) : '';

        // Handle price and weight as floats
        if (in_array($column, ['price', 'weight', 'cost'])) {
            $value = isset($_POST[$column]) ? filter_input(INPUT_POST, $column, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
        }
    }

    $db_arr[$column] = $value;
}

// --- Specific handling for 'products' table created_at and updated_at ---
// If the current table is 'products', explicitly set created_at and updated_at to current datetime.
if ($table_name === 'products') {
    $current_datetime = date('Y-m-d H:i:s');
    $db_arr['created_at'] = $current_datetime;
    $db_arr['updated_at'] = $current_datetime;
}
// --- End of specific handling ---


// Hash password for 'users' table
if ($table_name === 'users' && isset($_POST['password'])) {
    $db_arr['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
}

// Prepare SQL query placeholders
$placeholders = array_map(fn($k) => ":$k", array_keys($db_arr));
$sql = "INSERT INTO $table_name (" . implode(", ", array_keys($db_arr)) . ") VALUES (" . implode(", ", $placeholders) . ")";

if ($userId === null) {
    $response = ['success' => false, 'message' => 'User not logged in.'];
} else {
    // Check if the user exists in the database
    $checkUserQuery = "SELECT 1 FROM users WHERE id = ?";
    $checkUserStmt = $conn->prepare($checkUserQuery);
    $checkUserStmt->execute([$userId]);

    if ($checkUserStmt->rowCount() == 0) {
        $response = ['success' => false, 'message' => "User ID {$userId} not found."];
    } else {
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($db_arr); // Execute the main insert query
            $response = ['success' => true, 'message' => "$table_name entry added successfully."];
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $response = ['success' => false, 'message' => "Database Error: " . $e->getMessage()];
        }
    }
}

// Add Supplier relationships if the table is 'products'
if ($table_name == 'products') {
    $product_id = $conn->lastInsertId(); // Get the ID of the newly inserted product

    // Get selected suppliers from the form, default to empty array if none
    $selected_suppliers = $_POST['suppliers'] ?? [];

    // Ensure $selected_suppliers is always an array
    if (!is_array($selected_suppliers)) {
        $selected_suppliers = [$selected_suppliers];
    }

    foreach ($selected_suppliers as $supplier_id) {
        $sql = "INSERT INTO productsuppliers (supplier, product, updated_at, created_at)
                VALUES (:supplier_id, :product_id, :updated_at, :created_at)";

        $supplier_data = [
            ':supplier_id' => $supplier_id,
            ':product_id' => $product_id,
            ':updated_at' => date('Y-m-d H:i:s'), // Set datetime for productsuppliers
            ':created_at' => date('Y-m-d H:i:s')  // Set datetime for productsuppliers
        ];

        $stmt = $conn->prepare($sql);
        $stmt->execute($supplier_data);

        // Log any errors during productsupplier insertion
        if ($stmt->errorCode() !== '00000') {
            $errorInfo = $stmt->errorInfo();
            error_log("Error inserting into productsuppliers: " . print_r($errorInfo, true));
        }
    }
}

// Store the response in session and redirect
$_SESSION['response'] = $response;
header('Location: ../view_productOverview.php');
exit();
?>
