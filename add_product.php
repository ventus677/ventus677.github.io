<?php
    //Start the session.
    session_start();
    $_SESSION['table'] = 'products';
    $user = $_SESSION['user'];
    if(!isset($_SESSION['user'])) {
        header('Location: index.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="products.css"/>
    <style>
        /* FOCUS SA DESIGN NG FORM LANG */
        #productsPage .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            max-width: 900px;
            margin: 20px auto;
        }

        .header {
            display: flex;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .plus-icon {
            font-size: 18px;
            margin-right: 15px;
            background-color: #007bff;
            color: white;
            border-radius: 8px;
            width: 35px;
            height: 35px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* GRID SYSTEM PARA SA FORM */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group { margin-bottom: 15px; }
        .full-width { grid-column: span 2; }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
            font-size: 13px;
            text-transform: uppercase;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e1e1e1;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        /* MODERN SEARCHABLE SUPPLIER LIST */
        .supplier-selection-container {
            border: 1.5px solid #e1e1e1;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        #supplierSearchInput {
            border: none;
            border-bottom: 1px solid #eee;
            border-radius: 0;
            background: #fafafa;
            margin-bottom: 0;
        }

        .supplier-scroll-area {
            max-height: 180px;
            overflow-y: auto;
            padding: 10px;
        }

        .supplier-option {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 6px;
            transition: 0.2s;
        }

        .supplier-option:hover { background: #f0f7ff; }

        .supplier-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 12px;
            cursor: pointer;
        }

        .supplier-option span { font-size: 14px; color: #333; }

        /* CREATE BUTTON */
        .create-button {
            background-color: #28a745;
            color: white;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            margin-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .create-button:hover { background-color: #218838; transform: translateY(-2px); }

        .responseMessage { margin-top: 20px; padding: 15px; border-radius: 8px; text-align: center; }
        .responseMessage_success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .responseMessage_error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>

<body>
    <header>
        <a href="index.php" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="Keepkit" height="50px">
            <h3>&nbsp;&nbsp;Keepkit</h3>
        </a>
        <div class="search-container">
            <input type="search" id="searchInput" placeholder="Search..." autocomplete="off">
            <div id="searchResults"></div>
        </div>
        <div class="right-element">
            <a href="database/logout.php"><img src="images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>
        <main class="main">
            <section id="productsPage" class="active">
                <div class="container">
                    <div class="header">
                        <span class="plus-icon"><i class="fas fa-plus"></i></span> Create Product
                    </div>
                    
                    <form action="database/add_products.php" method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="product_image">Product Image</label>
                                <input type="file" id="product_image" name="img">
                            </div>

                            <div class="form-group">
                                <label for="productName">Product Name</label>
                                <input type="text" id="productName" name="product_name" placeholder="Enter product name..." required>
                            </div>

                            <div class="form-group">
                                <label for="brandName">Brand Name</label>
                                <input type="text" id="brandName" name="brand_name">
                            </div>

                            <div class="form-group">
                                <label for="category">Category</label>
                                <input type="text" id="category" name="category">
                            </div>

                            <div class="form-group">
                                <label for="productType">Product Type</label>
                                <input type="text" id="productType" name="product_type">
                            </div>

                            <div class="form-group">
                                <label for="weight">Weight / Unit</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="number" id="weight" name="weight" step="any" placeholder="0.00">
                                    <select name="choose" style="width: 100px;">
                                        <option value="mL">mL</option>
                                        <option value="g">g</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="price">Price (SRP)</label>
                                <input type="number" id="price" name="price" step="any" placeholder="0.00">
                            </div>

                            <div class="form-group full-width">
                                <label>Suppliers</label>
                                <div class="supplier-selection-container">
                                    <input type="text" id="supplierSearchInput" placeholder="Search for a supplier..." onkeyup="filterSuppliers()">
                                    <div class="supplier-scroll-area" id="supplierList">
                                        <?php
                                            include('database/connect.php');
                                            $suppliers = include('database/get_suppliers.php');
                                            if ($suppliers) {
                                                foreach ($suppliers as $supplier) {
                                                    echo '<label class="supplier-option">
                                                            <input type="checkbox" name="suppliers[]" value="'.htmlspecialchars($supplier['supplier_id']).'">
                                                            <span>'.htmlspecialchars($supplier['supplier_name']).'</span>
                                                          </label>';
                                                }
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label for="ingredients">Ingredients</label>
                                <textarea id="ingredients" name="ingredients" rows="3"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="cost">Cost</label>
                                <input type="number" id="cost" name="cost" step="any" placeholder="0.00">
                            </div>
                        </div>

                        <button type="submit" class="create-button">
                            <i class="fas fa-check-circle"></i> Create Product
                        </button>
                    </form>

                    <?php
                        if (isset($_SESSION['response'])) {
                            $res = $_SESSION['response'];
                            $class = ($res['success'] ?? false) ? 'responseMessage_success' : 'responseMessage_error';
                            echo '<div class="responseMessage '.$class.'">'.htmlspecialchars($res['message']).'</div>';
                            unset($_SESSION['response']);
                        }
                    ?>
                </div>
            </section>
        </main>
    </div>

    <script src="script.js"></script>
    <script>
        function filterSuppliers() {
            let input = document.getElementById('supplierSearchInput');
            let filter = input.value.toLowerCase();
            let options = document.querySelectorAll('.supplier-option');

            options.forEach(opt => {
                let text = opt.querySelector('span').innerText.toLowerCase();
                opt.style.display = text.includes(filter) ? "flex" : "none";
            });
        }
    </script>
</body>
</html>