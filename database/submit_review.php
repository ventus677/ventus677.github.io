<?php
session_start();
include('connect.php'); 
header('Content-Type: application/json');

// Check if user is logged in (using 'user' session as per customers_order_history.php)
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Kailangan mo munang mag-login para makapag-review.']);
    exit;
}

$user_id = $_SESSION['user']['id'];

// --- 1. KUNIN AT I-VALIDATE ANG MGA KRITIKAL NA DATA MULA SA FORM ---
$order_product_id = $_POST['order_product_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = $_POST['comment'] ?? null;

// Validation Check
if (empty($order_product_id) || !is_numeric($order_product_id) || empty($rating) || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Error: Lahat ng fields (Order Item ID, Rating, at Comment) ay kailangan.']);
    exit;
}

$rating = max(1, min(5, (int)$rating)); 
$comment = trim($comment);

try {
    $conn->beginTransaction();

    // --- 2. Kuhanin ang product_id at I-CHECK kung ang order item ay pag-aari ng user ---
    // Gamit ang orders_user table base sa iyong requirements
    $stmt_check = $conn->prepare("
        SELECT 
            op.product_id 
        FROM 
            order_products_user op
        JOIN 
            orders_user o ON op.order_id = o.id
        WHERE 
            op.id = ? 
            AND o.user_id = ?
    ");
    $stmt_check->execute([$order_product_id, $user_id]);
    $order_item_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$order_item_data) {
        throw new Exception("Invalid Order Item ID o hindi mo pag-aari ang order na ito.");
    }
    $product_id = $order_item_data['product_id'];

    // CHECK kung may review na para sa specific order_product_id na ito
    $stmt_already_reviewed = $conn->prepare("SELECT id FROM product_reviews WHERE order_product_id = ?");
    $stmt_already_reviewed->execute([$order_product_id]);
    if ($stmt_already_reviewed->rowCount() > 0) {
        throw new Exception("Na-review na ang item na ito. Hindi na pwedeng mag-review ulit.");
    }

    // --- 3. I-INSERT ang bagong Review ---
    $stmt_insert = $conn->prepare("
        INSERT INTO product_reviews 
        (user_id, order_product_id, product_id, rating, comment, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt_insert->execute([$user_id, $order_product_id, $product_id, $rating, $comment]);
    $review_id = $conn->lastInsertId();
    
    // --- 4. I-HANDLE ang Image Upload ---
    if (!empty($_FILES['review_images']['name'][0])) {
        $upload_dir = '../uploads/review_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $max_files = 3;
        $file_count = count($_FILES['review_images']['name']);

        if ($file_count > $max_files) {
             throw new Exception("Maximum of " . $max_files . " images allowed.");
        }

        $stmt_insert_image = $conn->prepare("INSERT INTO review_images (review_id, image_path) VALUES (?, ?)");

        for ($i = 0; $i < $file_count; $i++) {
            $file_name = $_FILES['review_images']['name'][$i];
            $file_tmp = $_FILES['review_images']['tmp_name'][$i];
            $file_error = $_FILES['review_images']['error'][$i];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png'];

            if ($file_error !== 0) continue; 
            
            if (!in_array($file_ext, $allowed_ext)) {
                 throw new Exception("Invalid file type. JPG, JPEG, and PNG lang ang pwede.");
            }

            $new_file_name = uniqid('review_') . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $destination)) {
                $db_path = 'uploads/review_images/' . $new_file_name; 
                $stmt_insert_image->execute([$review_id, $db_path]);
            }
        }
    }

    // --- 5. I-UPDATE ang average rating ---
    $stmt_update_avg_rating = $conn->prepare("
        UPDATE products 
        SET 
            average_rating = (SELECT AVG(rating) FROM product_reviews WHERE product_id = ?),
            total_reviews = (SELECT COUNT(id) FROM product_reviews WHERE product_id = ?)
        WHERE id = ?
    ");
    $stmt_update_avg_rating->execute([$product_id, $product_id, $product_id]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Salamat sa iyong review!']);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>