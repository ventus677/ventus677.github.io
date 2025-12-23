<?php
require_once('connect.php');

try {
  
    //
    $stmt = $conn->query("SELECT id, first_name, last_name, email, role FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    return $users;

} catch (PDOException $e) {
    // Log the error message for debugging purposes.
    // In a production environment, you might log this to a file and not display it to the user.
    error_log("Error fetching users from database: " . $e->getMessage());

    // Return an empty array to indicate no users could be fetched due to an error.
    // The calling script (users.php) should handle this gracefully.
    return [];
}
?>