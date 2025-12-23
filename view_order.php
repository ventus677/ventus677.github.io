<?php
session_start();
include('database/connect.php'); 

$user = isset($_SESSION['user']) ? $_SESSION['user'] : ['first_name' => 'Guest', 'last_name' => ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - Keepkit</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="tables.css">
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        :root {
            --primary-red: #a93131;
            --text-dark: #1e272e;
            --border-light: #f1f2f6;
            --bg-gray: #f8f9fa;
        }

        body { font-family: 'Inter', sans-serif; background-color: #fff; }

        /* Main Full-Width Container */
        .full-content-wrapper {
            width: 98%;
            margin: 0 auto;
            padding: 20px 0;
        }

        /* Enhanced Table Card */
        .table-container-full {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.02);
            overflow: hidden;
            width: 100%;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Ensures controlled column widths */
        }

        /* Table Header - Mas malaki at bold */
        .custom-table thead th {
            background: var(--bg-gray);
            color: #57606f;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            padding: 25px 20px;
            border-bottom: 2px solid var(--border-light);
            text-align: left;
        }

        /* Table Rows - Mas mataas (taller) para hindi siksikan */
        .custom-table tbody tr {
            transition: background 0.2s ease;
        }

        .custom-table tbody tr:hover {
            background-color: #fcfcfc;
        }

        .custom-table td {
            padding: 22px 20px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-dark);
            font-size: 15px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        /* Styling for Batch & Product */
        .batch-id {
            background: #2f3542;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .product-name-cell {
            font-weight: 700;
            font-size: 16px;
            color: var(--primary-red);
        }

        /* Action Buttons - Pinataas ang height */
        .btn-action {
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-view { background: #f1f2f6; color: #2f3542; }
        .btn-view:hover { background: #dfe4ea; }

        .btn-update { background: #fff5f5; color: var(--primary-red); border: 1px solid #ffc9c9; }
        .btn-update:hover { background: var(--primary-red); color: white; }

        /* Status Badges */
        .status-pill {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-ORDERED { background: #fff4e5; color: #d35400; }
        .status-Complete { background: #e6fffa; color: #27ae60; }

        /* Modal Layout */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(8px);
        }

        .modal-box {
            background: #fff;
            width: 600px;
            margin: 5% auto;
            border-radius: 20px;
            padding: 40px;
            position: relative;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-top: 20px;
        }

        .detail-item label {
            display: block;
            font-size: 12px;
            color: #a4b0be;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .detail-item span {
            font-size: 16px;
            font-weight: 700;
        }

        .input-full {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <header>
        <a href="index.php" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
            <h3>&nbsp;&nbsp;Keepkit</h3>
        </a>
        <div class="search-container">
            <input type="search" id="searchInput" placeholder="Search orders..." />
        </div>
        <div class="right-element">
            <a href="database/logout.php"><img src="images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>

        <main class="main">
            <div class="full-content-wrapper">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <div>
                        <h1 style="font-size: 28px; font-weight: 700; color: #2d3436;">Purchase Order Management</h1>
                        <p style="color: #636e72;">Review and update batch orders for your inventory.</p>
                    </div>
                    <div class="tablesMenu" style="display:flex; list-style:none; gap:10px;">
                        <li class="tablesItem"><a href="view_products.php" class="tablesLinks">Products</a></li>
                        <li class="tablesItem"><a href="view_order.php" class="tablesLinks" style="background: #151515; color: white;">Orders</a></li>
                    </div>
                </div>

                <div class="table-container-full">
                    <table class="custom-table">
                        <colgroup>
                            <col style="width: 10%;">
                            <col style="width: 30%;">
                            <col style="width: 20%;">
                            <col style="width: 15%;">
                            <col style="width: 25%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Batch</th>
                                <th>Product Information</th>
                                <th>Order Value</th>
                                <th>Status</th>
                                <th style="text-align: right; padding-right: 40px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $conn->prepare("
                                    SELECT p.product_name, p.cost, op.*, 
                                           u.first_name, u.last_name, s.supplier_name
                                    FROM order_product op
                                    JOIN suppliers s ON op.supplier = s.supplier_id
                                    JOIN products p ON op.product = p.id
                                    JOIN users u ON op.created_by = u.id
                                    ORDER BY op.created_at DESC
                                ");
                                $stmt->execute();
                                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                foreach($orders as $order): 
                                    $total = $order['quantity_ordered'] * $order['cost'];
                            ?>
                                <tr>
                                    <td><span class="batch-id">#<?= $order['batch'] ?></span></td>
                                    <td>
                                        <div class="product-name-cell"><?= htmlspecialchars($order['product_name']) ?></div>
                                        <small style="color:#a4b0be">Supplier: <?= htmlspecialchars($order['supplier_name']) ?></small>
                                    </td>
                                    <td><strong style="font-size:17px">₱<?= number_format($total, 2) ?></strong></td>
                                    <td>
                                        <span class="status-pill status-<?= $order['status'] ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; padding-right: 30px;">
                                        <div class="hidden-data" style="display:none;" 
                                             data-id="<?= $order['id'] ?>"
                                             data-name="<?= htmlspecialchars($order['product_name']) ?>"
                                             data-batch="<?= $order['batch'] ?>"
                                             data-ord="<?= $order['quantity_ordered'] ?>"
                                             data-rec="<?= $order['quantity_received'] ?>"
                                             data-rem="<?= $order['remaining_quantity'] ?>"
                                             data-mfg="<?= $order['manufactured_at'] ?: 'N/A' ?>"
                                             data-exp="<?= $order['expiration'] ?: 'N/A' ?>"
                                             data-sup="<?= htmlspecialchars($order['supplier_name']) ?>"
                                             data-by="<?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?>"
                                             data-status="<?= $order['status'] ?>">
                                        </div>
                                        <button class="btn-action btn-view viewDetailsBtn"><i class="fas fa-eye"></i> Details</button>
                                        <button class="btn-action btn-update updateOrderBtn"><i class="fas fa-edit"></i> Update</button>
                                    </td>
                                </tr>
                            <?php endforeach; 
                            } catch (PDOException $e) { echo "Error: " . $e->getMessage(); } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="viewModal" class="modal-overlay">
        <div class="modal-box">
            <h2 id="viewTitle" style="color:var(--primary-red); font-size:24px; margin-bottom:20px;">Product Info</h2>
            <div class="details-grid">
                <div class="detail-item"><label>Batch Number</label><span id="det-batch"></span></div>
                <div class="detail-item"><label>Supplier</label><span id="det-sup"></span></div>
                <div class="detail-item"><label>Quantity Ordered</label><span id="det-ord"></span></div>
                <div class="detail-item"><label>Quantity Received</label><span id="det-rec" style="color:green"></span></div>
                <div class="detail-item"><label>Quantity Remaining</label><span id="det-rem" style="color:red"></span></div>
                <div class="detail-item"><label>Manufacturing Date</label><span id="det-mfg"></span></div>
                <div class="detail-item"><label>Expiration Date</label><span id="det-exp"></span></div>
                <div class="detail-item"><label>Handled By</label><span id="det-by"></span></div>
            </div>
            <button class="btn-action btn-view" style="width:100%; margin-top:30px; justify-content:center;" onclick="closeModals()">Close Details</button>
        </div>
    </div>

    <div id="updateModal" class="modal-overlay">
        <div class="modal-box">
            <h2 id="upTitle" style="font-size:22px; margin-bottom:25px;">Update Order Status</h2>
            <div id="updateFormContent"></div>
            <div style="margin-top:30px; display:flex; gap:10px;">
                <button class="btn-action btn-view" style="flex:1; justify-content:center;" onclick="closeModals()">Cancel</button>
                <button id="saveUpdateBtn" class="btn-action btn-update" style="flex:1; justify-content:center; background:var(--primary-red); color:white;">Update Inventory</button>
            </div>
        </div>
    </div>

    <script>
        function closeModals() { document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none'); }

        document.addEventListener("click", function(e) {
            if (e.target.closest(".viewDetailsBtn")) {
                const data = e.target.closest("tr").querySelector('.hidden-data').dataset;
                document.getElementById('viewTitle').innerText = data.name;
                document.getElementById('det-batch').innerText = data.batch;
                document.getElementById('det-sup').innerText = data.sup;
                document.getElementById('det-ord').innerText = data.ord;
                document.getElementById('det-rec').innerText = data.rec;
                document.getElementById('det-rem').innerText = data.rem;
                document.getElementById('det-mfg').innerText = data.mfg;
                document.getElementById('det-exp').innerText = data.exp;
                document.getElementById('det-by').innerText = data.by;
                document.getElementById('viewModal').style.display = 'block';
            }

            if (e.target.closest(".updateOrderBtn")) {
                const data = e.target.closest("tr").querySelector('.hidden-data').dataset;
                document.getElementById('upTitle').innerText = "Update: " + data.name;
                document.getElementById('updateFormContent').innerHTML = `
                    <input type="hidden" id="up-id" value="${data.id}">
                    <div style="margin-bottom:15px">
                        <label style="font-size:12px; font-weight:700">Qty Received</label>
                        <input type="number" id="up-rec" class="input-full" value="${data.rec}">
                    </div>
                    <div style="display:flex; gap:15px; margin-bottom:15px">
                        <div style="flex:1">
                            <label style="font-size:12px; font-weight:700">Mfg Date</label>
                            <input type="date" id="up-mfg" class="input-full" value="${data.mfg === 'N/A' ? '' : data.mfg}">
                        </div>
                        <div style="flex:1">
                            <label style="font-size:12px; font-weight:700">Exp Date</label>
                            <input type="date" id="up-exp" class="input-full" value="${data.exp === 'N/A' ? '' : data.exp}">
                        </div>
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:700">Status</label>
                        <select id="up-status" class="input-full">
                            <option value="ORDERED" ${data.status === 'ORDERED' ? 'selected' : ''}>ORDERED</option>
                            <option value="Complete" ${data.status === 'Complete' ? 'selected' : ''}>Complete</option>
                        </select>
                    </div>`;
                document.getElementById('updateModal').style.display = 'block';
            }
        });

        document.getElementById('saveUpdateBtn').addEventListener('click', function() {
            const payload = [{
                id: document.getElementById('up-id').value,
                qtyReceived: document.getElementById('up-rec').value,
                manufacturedAt: document.getElementById('up-mfg').value,
                expiration: document.getElementById('up-exp').value,
                status: document.getElementById('up-status').value
            }];

            fetch('database/update_purchase_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(res => res.json()).then(data => {
                if(data.success) { location.reload(); } else { alert('Error: ' + data.message); }
            });
        });
    </script>
</body>
</html>