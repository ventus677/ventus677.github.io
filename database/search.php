<?php
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// !!!!!! DANGER: REMOVE THESE LINES IN A PRODUCTION ENVIRONMENT !!!
// !!!!!! These lines will display ALL PHP errors directly in the browser.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// !!!!!! END DANGER ZONE !!!!!!

require_once 'connect.php'; // Ensure this path is correct relative to search.php

header('Content-Type: application/json'); // Crucial: Tell the browser to expect JSON data

$searchTerm = $_GET['q'] ?? ''; // Get the 'q' parameter from the URL query string
$searchTerm = trim($searchTerm); // Remove leading/trailing whitespace

$products = []; // Initialize an empty array for products

// Only proceed if a search term is provided and it has at least 1 character
if (strlen($searchTerm) >= 1) {
    try {
        // Prepare SQL statement to search for product names
        // Select 'id' and 'product_name' from the 'products' table.
        // Make sure your 'products' table exists and has 'id' and 'product_name' columns.
        $stmt = $conn->prepare("SELECT id, product_name FROM products WHERE product_name LIKE :searchTerm LIMIT 10");
        
        // Bind the search term with wildcards for case-insensitive partial match
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
        
        // Execute the prepared statement
        $stmt->execute();

        // Fetch all results as an associative array (containing 'id' and 'product_name' for each row)
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Log any database errors for debugging (check your PHP error logs or server logs)
        // In a production environment, you might log this to a file and return an empty array
        // or a generic error message, NOT the actual PDOException message.
        error_log("Database error fetching products for autocomplete: " . $e->getMessage());
        // For debugging, you can also output the error directly:
        // echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
        $products = []; // Return empty array on error to prevent breaking JSON structure
    }
}

// Encode the array of product objects into a JSON string and output it
echo json_encode($products);
exit(); // Stop script execution after sending the JSON response
?>