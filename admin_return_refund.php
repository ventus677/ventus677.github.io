<?php
session_start();
// Tiyakin na tama ang path papunta sa user data at connect.php
$user = $_SESSION['user'] ?? null;

if(!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: index.php');
    exit;
}

// I-check kung Admin ang role
if (strtolower($user['role'] ?? '') !== 'admin') {
    header('Location: home.php'); 
    exit;
}

// Siguraduhin na tama ang path ng 'connect.php'
include('database/connect.php');

$message = '';
$user_id = $user['id'] ?? null;

// --- 1. HANDLE ACCEPT/DECLINE ACTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rr_id'], $_POST['action'])) {
    $rr_id = filter_input(INPUT_POST, 'rr_id', FILTER_SANITIZE_NUMBER_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    $new_status = '';
    
    $admin_notes = filter_input(INPUT_POST, 'admin_notes', FILTER_SANITIZE_STRING) ?? null;

    if ($action === 'accept') {
        $new_status = 'ACCEPTED';
    } elseif ($action === 'decline') {
        $new_status = 'DECLINED';
    }

    if ($new_status && $rr_id && $user_id) {
        $update_query = "
            UPDATE returns_refunds 
            SET 
                status = :new_status, 
                admin_notes = :admin_notes, 
                processed_by = :user_id, 
                processed_at = NOW() 
            WHERE id = :rr_id
        ";
        
        try {
            $stmt = $conn->prepare($update_query);
            $stmt->bindParam(':new_status', $new_status);
            $stmt->bindParam(':admin_notes', $admin_notes);
            $stmt->bindParam(':user_id', $user_id); 
            $stmt->bindParam(':rr_id', $rr_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $message = "Return/Refund Request #{$rr_id} has been **{$new_status}** successfully.";
            } else {
                $message = "WARNING: Request #{$rr_id} was not updated.";
            }
        } catch (PDOException $e) {
            $message = "DATABASE ERROR: " . $e->getMessage();
        }
    }
}

// --- 2. FETCH ALL RETURN/REFUND REQUESTS ---
$requests = [];
// FIXED QUERY: Pinalitan ang 'JOIN users c' ng 'JOIN users u' para gumana ang u.first_name at u.last_name
$fetch_query = "
    SELECT 
        rr.*, 
        u.first_name AS user_fname, 
        u.last_name AS user_lname,  
        p.product_name
    FROM 
        returns_refunds rr
    JOIN 
        users u ON rr.user_id = u.id
    LEFT JOIN
        products p ON rr.product_id = p.id
    ORDER BY 
        rr.status ASC, rr.request_date DESC  
";

try {
    $stmt_fetch = $conn->query($fetch_query);
    $requests = $stmt_fetch->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Database Error: Could not retrieve requests. " . $e->getMessage();
}

$base_url_for_images = '/Keepkit/images/rr_proofs/';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Returns and Refunds</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="tables.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .rr-container { width: 100%; max-width: 1200px; margin: 0 auto; }
        .rr-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .rr-table th, .rr-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .rr-table th { background-color: #f2f2f2; font-weight: bold; }
        .status-Pending { color: #ff9800; font-weight: bold; }
        .status-ACCEPTED { color: #4CAF50; font-weight: bold; }
        .status-DECLINED { color: #f44336; font-weight: bold; }
        .accept-btn { background-color: #4CAF50; color: white; border: none; padding: 8px; cursor: pointer; border-radius: 4px; }
        .decline-btn { background-color: #f44336; color: white; border: none; padding: 8px; cursor: pointer; border-radius: 4px; }
        .proof-link { color: #007bff; text-decoration: underline; cursor: pointer; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.9);
            align-items: center; 
            justify-content: center; 
        }

        .modal-content {
            max-width: 90%; 
            max-height: 90vh; 
            object-fit: contain; 
        }

        .close-btn {
            position: absolute; top: 15px; right: 35px;
            color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer;
        }
    </style>
</head>

<body>
    <header>
        <a href="index.php" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
            <h3>&nbsp;&nbsp;Keepkit</h3>
        </a>
        <div class="right-element">
            <a href="admin_return_refund.php" class="notification-link"><i class="fas fa-bell"></i></a>
            <a href="database/logout.php" ><img src= "images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>
        <main class="main">
            <section class="rr-container">
                <h1>Return/Refund Management</h1>
                <p>Reviewing requests from Users and Customers.</p>
                
                <?php if ($message): ?>
                    <div class="alert <?= (strpos($message, 'ERROR') !== false) ? 'alert-error' : 'alert-success' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($requests)): ?>
                    <p style="text-align: center;">No return or refund requests found.</p>
                <?php else: ?>
                    <table class="rr-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Qty</th> 
                                <th>Reason</th>
                                <th>Proof</th> 
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars($request['id']) ?></td>
                                    <td><?= htmlspecialchars($request['user_fname'] . ' ' . $request['user_lname']) ?></td>
                                    <td><?= htmlspecialchars($request['product_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars(ucfirst($request['request_type'])) ?></td>
                                    <td><?= htmlspecialchars($request['return_quantity']) ?></td> 
                                    <td><?= htmlspecialchars($request['reason']) ?></td>
                                    <td>
                                        <?php if ($request['proof_image_path']): ?>
                                            <span class="proof-link" onclick="openImageModal('<?= $base_url_for_images . htmlspecialchars($request['proof_image_path']) ?>')">View</span>
                                        <?php else: ?> N/A <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($request['request_date']))) ?></td>
                                    <td class="status-<?= htmlspecialchars($request['status']) ?>"><?= htmlspecialchars($request['status']) ?></td>
                                    <td>
                                        <?php if (strtoupper($request['status']) === 'PENDING'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="rr_id" value="<?= $request['id'] ?>">
                                                <textarea name="admin_notes" placeholder="Notes" rows="1" style="width: 100%;"></textarea>
                                                <div style="display:flex; gap: 5px; margin-top:5px;">
                                                    <button type="submit" name="action" value="accept" class="accept-btn">Accept</button>
                                                    <button type="submit" name="action" value="decline" class="decline-btn">Decline</button>
                                                </div>
                                            </form>
                                        <?php else: ?> <span>Processed</span> <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>
    
    <div id="imageModal" class="modal">
        <span class="close-btn">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        var modal = document.getElementById("imageModal");
        var modalImg = document.getElementById("modalImage");
        function openImageModal(imageUrl) { modal.style.display = "flex"; modalImg.src = imageUrl; }
        document.querySelector(".close-btn").onclick = function() { modal.style.display = "none"; }
        window.onclick = function(event) { if (event.target == modal) modal.style.display = "none"; }
    </script>
</body>
</html>