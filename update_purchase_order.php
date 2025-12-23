<?php
session_start();
header('Content-Type: application/json'); // Ito ang nagsasabi sa browser na JSON ang output

include('database/connect.php'); // Siguraduhin na tama ang path na ito

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data)) {
        $response['message'] = 'No data received for update.';
        echo json_encode($response);
        exit;
    }

    $id = $data['id'] ?? null;
    $newQuantityReceived = $data['quantity_received'] ?? null; // Bagong input mula sa user
    $newStatus = $data['status'] ?? null; // Bagong status na galing sa user (o auto-calculate)
    $receivedDate = $data['received_date'] ?? null;

    if (is_null($id) || is_null($newQuantityReceived) || !is_numeric($newQuantityReceived)) {
        $response['message'] = 'Invalid or missing data.';
        echo json_encode($response);
        exit;
    }

    try {
        $conn->beginTransaction(); // Simulan ang transaction para sa atomicity

        // Kumuha ng kasalukuyang quantity_ordered, quantity_received, at ang product ID para sa kalkulasyon
        $getOrderDataStmt = $conn->prepare("
            SELECT quantity_ordered, quantity_received, product
            FROM order_product
            WHERE id = :id
        ");
        $getOrderDataStmt->bindParam(':id', $id);
        $getOrderDataStmt->execute();
        $currentOrderData = $getOrderDataStmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentOrderData) {
            $response['message'] = 'Order item not found.';
            echo json_encode($response);
            exit;
        }

        $quantityOrdered = $currentOrderData['quantity_ordered'];
        $currentQtyReceived = $currentOrderData['quantity_received'];
        $productId = $currentOrderData['product'];

        // Calculate the total quantity received so far (new input)
        // Dito natin kino-consider ang 'newQuantityReceived' bilang ang *total* na quantity na ipinasok ng user
        $updatedTotalQtyReceived = $newQuantityReceived;

        // Validation: Ensure updated total received does not exceed quantity ordered
        if ($updatedTotalQtyReceived < 0 || $updatedTotalQtyReceived > $quantityOrdered) {
            $response['message'] = 'Invalid quantity received. It cannot be negative or exceed the quantity ordered.';
            $conn->rollBack();
            echo json_encode($response);
            exit;
        }

        // --- Simulan ang pagbabago dito: Auto-calculate ang status ---
        $calculatedStatus = '';
        if ($updatedTotalQtyReceived == $quantityOrdered) {
            $calculatedStatus = 'complete'; // All ordered quantity received
        } else if ($updatedTotalQtyReceived > 1 && $updatedTotalQtyReceived < $quantityOrdered) { // Strict: More than 1 received (e.g., 2, 3...) but not all
            $calculatedStatus = 'incomplete';
        } else { // Covers $updatedTotalQtyReceived == 0 OR $updatedTotalQtyReceived == 1, AND not complete
            $calculatedStatus = 'pending';
        }

        // Gamitin ang calculatedStatus.
        $statusToUse = $calculatedStatus;

        // Kung ang received_date ay empty string, gawin itong NULL para sa database.
        $receivedDateForDb = empty($receivedDate) ? NULL : $receivedDate;

        // Ihanda ang pangunahing update statement para sa order_product
        $updateOrderStmt = $conn->prepare("
            UPDATE order_product
            SET
                quantity_received = :updated_total_qty_received,
                remaining_quantity = quantity_ordered - :updated_total_qty_received,
                status = :status,
                received_date = :received_date
            WHERE id = :id
        ");
        $updateOrderStmt->bindParam(':updated_total_qty_received', $updatedTotalQtyReceived);
        $updateOrderStmt->bindParam(':status', $statusToUse); // Gamitin ang kinakalkulang status
        $updateOrderStmt->bindParam(':received_date', $receivedDateForDb);
        $updateOrderStmt->bindParam(':id', $id);
        $updateOrderStmt->execute();

        // I-update ang product stock sa products table (idagdag lamang ang actual na nadagdag)
        $actualQtyAddedToStock = $updatedTotalQtyReceived - $currentQtyReceived;

        // Siguraduhin na hindi tayo magdadagdag ng negatibong stock o 0 stock
        if ($actualQtyAddedToStock != 0) { // Only update if there was a change in received quantity
            // Kumuha ng product_id mula sa order_product table
            $getProductIdStmt = $conn->prepare("SELECT product FROM order_product WHERE id = :id");
            $getProductIdStmt->bindParam(':id', $id);
            $getProductIdStmt->execute();
            $productId = $getProductIdStmt->fetchColumn();

            if ($productId) {
                $updateProductStockStmt = $conn->prepare("
                    UPDATE products
                    SET stock = stock + :received_qty_increment
                    WHERE id = :product_id
                ");
                $updateProductStockStmt->bindParam(':received_qty_increment', $actualQtyAddedToStock);
                $updateProductStockStmt->bindParam(':product_id', $productId);
                $updateProductStockStmt->execute();
            }
        }

        $conn->commit(); // I-commit ang transaction
        $response['success'] = true;
        $response['message'] = 'Purchase Order updated successfully.';

    } catch (PDOException $e) {
        $conn->rollBack(); // I-rollback sa error
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        $conn->rollBack(); // I-rollback sa error
        $response['message'] = 'Application error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>