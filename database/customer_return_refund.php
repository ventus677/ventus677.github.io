<?php
session_start();
// Include the database connection file.
include('connect.php'); 

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

// 1. Authentication Check
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) { 
    header('Location: ../index_auth.php'); 
    exit;
}

// REVISION: Gamitin ang user_id mula sa session
$user_id = $_SESSION['user']['id']; 
$user_role = $_SESSION['user']['role'] ?? 'customer'; // Kunin ang role mula sa session

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if it's a cancellation request first (uses 'action' field)
    if (isset($_POST['action']) && $_POST['action'] === 'cancel_request') {
        $request_id = filter_input(INPUT_POST, 'request_id', FILTER_SANITIZE_NUMBER_INT);
        
        if (!$request_id) {
            $response['message'] = 'Request ID is required for cancellation.';
            echo json_encode($response);
            exit;
        }

        // Only allow cancellation if status is 'Pending' or 'Processing'
        try {
            $conn->beginTransaction();
            
            $stmt_check = $conn->prepare("
                SELECT status FROM returns_refunds WHERE id = ? AND user_id = ?
            ");
            $stmt_check->execute([$request_id, $user_id]);
            $current_status = $stmt_check->fetchColumn();

            if ($current_status === false) {
                $response['message'] = 'Request not found or does not belong to your account.';
                $conn->rollBack();
                echo json_encode($response);
                exit;
            }

            if ($current_status !== 'Pending' && $current_status !== 'Processing') {
                $response['message'] = "Cannot cancel request with status: " . htmlspecialchars($current_status) . ".";
                $conn->rollBack();
                echo json_encode($response);
                exit;
            }
            
            // Update status to Cancelled
            $stmt_update = $conn->prepare("
                UPDATE returns_refunds 
                SET status = 'CANCELLED', admin_notes = 'Cancelled by user' 
                WHERE id = ? AND user_id = ?
            ");
            $stmt_update->execute([$request_id, $user_id]);
            
            $conn->commit();

            $response['success'] = true;
            $response['message'] = 'Return/Refund request has been successfully cancelled.';

        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                 $conn->rollBack();
            }
            error_log("Cancellation Error: " . $e->getMessage());
            $response['message'] = 'Database error: Could not cancel the request.';
        }
        
        echo json_encode($response);
        exit;
    }


    // --- NORMAL SUBMISSION LOGIC ---
    // 2. Input Validation and Sanitization
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT); 
    $request_type = filter_input(INPUT_POST, 'request_type', FILTER_SANITIZE_STRING);
    $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
    $return_quantity = filter_input(INPUT_POST, 'quantity_to_return', FILTER_SANITIZE_NUMBER_INT); 

    if (!$order_id || !$product_id || !$request_type || !$reason || !$return_quantity || $return_quantity <= 0) {
        $response['message'] = 'All required fields (Order ID, Product ID, Type, Reason, and Quantity) must be provided.';
        echo json_encode($response);
        exit;
    }

    $reason = trim($reason);
    $proof_image_path = null;
    $upload_dir = '../images/rr_proofs/'; // Adjust this path as needed
    
    // 3. File Upload Handling
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['proof_image']['tmp_name'];
        $file_name = $_FILES['proof_image']['name'];
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // Check file size (max 5MB, adjust as necessary)
        if ($_FILES['proof_image']['size'] > 5 * 1024 * 1024) {
            $response['message'] = 'Proof image size exceeds the 5MB limit.';
            echo json_encode($response);
            exit;
        }

        // Check file type (allow only images)
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['proof_image']['type'], $allowed_mime_types)) {
             $response['message'] = 'Invalid file type. Only JPG, PNG, and GIF images are allowed.';
            echo json_encode($response);
            exit;
        }

        // Generate a unique file name
        $new_file_name = uniqid('rr_proof_', true) . '.' . $file_extension;
        $dest_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($file_tmp_path, $dest_path)) {
            $proof_image_path = $new_file_name; // Store only the file name
        } else {
            $response['message'] = 'Error uploading proof image.';
            error_log("File upload failed for " . $file_name . " to " . $dest_path);
            echo json_encode($response);
            exit;
        }
    }


    // 4. Database Insertion (Transaction)
    try {
        $conn->beginTransaction();

        // a. Check if an active request already exists for this order/product
        $stmt_check_active = $conn->prepare("
            SELECT id FROM returns_refunds 
            WHERE 
                user_id = ? 
                AND order_id = ? 
                AND product_id = ? 
                AND status IN ('Pending', 'Processing', 'ACCEPTED')
        ");
        $stmt_check_active->execute([$user_id, $order_id, $product_id]);
        if ($stmt_check_active->fetch()) {
            $conn->rollBack();
            $response['message'] = 'An active Return/Refund request already exists for this product in this order.';
            echo json_encode($response);
            exit;
        }

        // b. Check if the quantity requested is valid
        $stmt_qty = $conn->prepare("
            SELECT quantity FROM order_products_user WHERE order_id = ? AND product_id = ?
        ");
        $stmt_qty->execute([$order_id, $product_id]);
        $original_quantity = $stmt_qty->fetchColumn();

        if ($original_quantity === false) {
            $conn->rollBack();
            $response['message'] = 'Product not found in this order.';
            echo json_encode($response);
            exit;
        }

        if ($return_quantity > $original_quantity) {
             $conn->rollBack();
            $response['message'] = 'The quantity requested for return/refund (' . $return_quantity . ') exceeds the quantity purchased (' . $original_quantity . ').';
            echo json_encode($response);
            exit;
        }

        // c. Insert the new request
        $stmt_insert = $conn->prepare("
            INSERT INTO returns_refunds 
            (user_id, order_id, product_id, request_type, return_quantity, reason, proof_image_path, status, request_date) 
            VALUES 
            (:user_id, :order_id, :product_id, :request_type, :return_quantity, :reason, :proof_image, 'Pending', NOW())
        ");

        $stmt_insert->bindParam(':user_id', $user_id);
        $stmt_insert->bindParam(':order_id', $order_id);
        $stmt_insert->bindParam(':product_id', $product_id); 
        $stmt_insert->bindParam(':request_type', $request_type);
        $stmt_insert->bindParam(':return_quantity', $return_quantity); 
        $stmt_insert->bindParam(':reason', $reason);
        $stmt_insert->bindParam(':proof_image', $proof_image_path); 
        
        $stmt_insert->execute();

        $conn->commit();

        // Success Response
        $response['success'] = true;
        $response['message'] = 'Return/Refund request submitted successfully! Please wait for admin approval.';

    } catch (PDOException $e) {
        // Handle database errors
        if ($conn->inTransaction()) {
             $conn->rollBack();
        }
        error_log("Return/Refund Submission Error: " . $e->getMessage());
        $response['message'] = 'Database error: Could not submit the request. ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>