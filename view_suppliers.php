<?php

// Start the session.
session_start();

// Get the user data from session.
$user = $_SESSION['user'] ?? null; 

// If user is not logged in, redirect to auth.php
if (!isset($user)) {
    header('Location: auth.php');
    exit;
}

// Include database connection
include('database/connect.php');

/**
 * Helper function to check if the current user has a specific permission.
 */
function hasPermission(array $userPermissions, string $module, string $action): bool {
    return isset($userPermissions[$module]) && in_array($action, $userPermissions[$module]);
}

// Get user's permissions from the session
$user_permissions = $user['permissions'] ?? [];

// Boolean checks for permissions
$canEdit = hasPermission($user_permissions, 'supplier', 'edit');
$canDelete = hasPermission($user_permissions, 'supplier', 'delete');
$showActionsColumn = ($canEdit || $canDelete);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="products.css"/>
    <link rel="stylesheet" href="tables.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .main { padding: 20px; width: 100%; }
        .products-table {
            width: 100% !important;
            border-collapse: collapse;
            margin-top: 10px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .products-table th {
            background-color: #284752;
            color: white;
            padding: 15px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            color: #333;
        }
        .action-group { display: flex; gap: 8px; }
        .btn-action {
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        .btn-edit { background-color: #e3f2fd; color: #1976d2; border: 1px solid #1976d2; }
        .btn-edit:hover { background-color: #1976d2; color: white; }
        .btn-delete { background-color: #ffebee; color: #d32f2f; border: 1px solid #d32f2f; }
        .btn-delete:hover { background-color: #d32f2f; color: white; }
        .view-details-btn {
            background-color: #f0f4f8;
            color: #284752;
            border: 1px solid #cfd8dc;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .view-details-btn:hover { background-color: #284752; color: white; }
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(3px);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 25px;
            border-radius: 12px;
            width: 400px;
            position: relative;
        }
        .search-sort-container {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .search-input-group { display: flex; gap: 10px; width: 100%; }
        .search-input-group input, .search-input-group select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        #supplierSearchInput { flex-grow: 1; }
        .btn-primary {
            background-color: #a93131;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <header>
        <a href="home.php" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
            <h3>&nbsp;&nbsp;Keepkit</h3>
        </a>
        <div class="right-element">
            <a href="database/logout.php"><img src="images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>

        <main class="main">
            <section id="suppliersPage" class="active">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Suppliers Information</h3>
                    <?php if (hasPermission($user_permissions, 'supplier', 'create')): ?>
                        <a href="add_suppliers.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Supplier</a>
                    <?php endif; ?>
                </div>

                <div class="search-sort-container">
                    <div class="search-input-group">
                        <select id="supplierSearchColumn">
                            <option value="supplier_name">Supplier Name</option>
                            <option value="supplier_id">ID</option>
                            <option value="email">Email</option>
                        </select>
                        <input type="text" id="supplierSearchInput" placeholder="Type to search suppliers...">
                    </div>
                </div>

                <table class="products-table" id="suppliersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier Name</th>
                            <th>Products</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Created By</th>
                            <?php if ($showActionsColumn): ?>
                                <th style="text-align: center;">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="<?= $showActionsColumn ? 8 : 7 ?>" style="text-align:center;">Loading suppliers...</td></tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" style="position: absolute; right: 20px; top: 15px; cursor: pointer; font-size: 24px; color: #999;">&times;</span>
            <h4 id="modalSupplierName" style="color: #284752; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;"></h4>
            <p style="font-weight: 600; font-size: 0.9rem; color: #666;">Supplied Items:</p>
            <ul id="modalProductList" style="padding-left: 20px; line-height: 2;"></ul>
        </div>
    </div>

    <script>
        function htmlspecialchars(str) {
            if (typeof str !== 'string') return str || '';
            let div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        $(document).ready(function() {
            let allSuppliers = [];
            const tableBody = $('#suppliersTable tbody');
            
            // I-pasa ang PHP variables sa JavaScript
            const canEdit = <?= json_encode($canEdit) ?>;
            const canDelete = <?= json_encode($canDelete) ?>;
            const showActions = <?= json_encode($showActionsColumn) ?>;

            async function loadData() {
                try {
                    const res = await fetch('api/fetch_suppliers.php');
                    const data = await res.json();
                    if (data.success) {
                        allSuppliers = data.suppliers;
                        renderTable(allSuppliers);
                    }
                } catch (e) {
                    tableBody.html(`<tr><td colspan="${showActions ? 8 : 7}" style="color:red; text-align:center;">Error loading data.</td></tr>`);
                }
            }

            function renderTable(data) {
                tableBody.empty();
                if (data.length === 0) {
                    tableBody.append(`<tr><td colspan="${showActions ? 8 : 7}" style="text-align:center;">No records found.</td></tr>`);
                    return;
                }

                data.forEach((s, idx) => {
                    let actionsTd = '';
                    
                    // Dito ang logic: Kung showActions is false, walang <td> na idadagdag
                    if (showActions) {
                        actionsTd = `<td class="action-group" style="justify-content: center;">`;
                        if (canEdit) actionsTd += `<a href="edit_supplier.php?id=${s.supplier_id}" class="btn-action btn-edit"><i class="fas fa-edit"></i> Edit</a>`;
                        if (canDelete) actionsTd += `<a href="edit_delete_suppliers/delete_supplier.php?id=${s.supplier_id}" class="btn-action btn-delete" onclick="return confirm('Delete this supplier?')"><i class="fas fa-trash"></i> Delete</a>`;
                        actionsTd += `</td>`;
                    }

                    tableBody.append(`
                        <tr>
                            <td><b>#${s.supplier_id}</b></td>
                            <td>${htmlspecialchars(s.supplier_name)}</td>
                            <td><button class="view-details-btn" onclick="openModal(${idx})"><i class="fas fa-list"></i> View Details</button></td>
                            <td>${htmlspecialchars(s.email)}</td>
                            <td>${htmlspecialchars(s.phone)}</td>
                            <td>${htmlspecialchars(s.supplier_address)}</td>
                            <td>${htmlspecialchars(s.created_by_first_name)}</td>
                            ${actionsTd}
                        </tr>
                    `);
                });
            }

            window.openModal = function(idx) {
                const s = allSuppliers[idx];
                $('#modalSupplierName').text(s.supplier_name);
                const list = $('#modalProductList').empty();
                if (s.products && s.products.length > 0) {
                    s.products.forEach(p => list.append(`<li>${htmlspecialchars(p.product_name)}</li>`));
                } else {
                    list.append('<li>No products found.</li>');
                }
                $('#productModal').fadeIn(200);
            };

            $('.close-modal').click(() => $('#productModal').fadeOut(200));
            
            $('#supplierSearchInput').on('input', function() {
                const term = $(this).val().toLowerCase();
                const col = $('#supplierSearchColumn').val();
                const filtered = allSuppliers.filter(s => String(s[col]).toLowerCase().includes(term));
                renderTable(filtered);
            });

            loadData();
        });
    </script>
</body>
</html>