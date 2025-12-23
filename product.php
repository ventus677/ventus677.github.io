<?php

    

    //Start the session.
    session_start();
    $_SESSION['table'] = 'products';
    $user = $_SESSION['user'];
     if(!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit; // Important: Exit after redirect
  
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
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="products.css"/>
    <style>
        .supplier-selection {
    margin-bottom: 15px;
}

#supplierSearchInput {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box; /* Ensures padding and border are included in the element's total width and height */
}

#suppliersSelect {
    width: 100%;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* Products Section (Container for the form) */
#productsPage .container {
    background-color: #ffffff;
    padding: 30px 40px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    max-width: 800px; /* Limit width for better readability */
    margin: 0 auto; /* Center the container */
}

#productsPage .header {
    display: flex;
    align-items: center;
    font-size: 2em;
    font-weight: bold;
    color: #007bff; /* Primary brand color for header */
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #eee;
}

.plus-icon {
    font-size: 1.2em;
    margin-right: 10px;
    background-color: #007bff;
    color: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Form Group Styling */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    color: #555;
    font-size: 1.1em;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="date"],
.form-group textarea,
.form-group select {
    width: calc(100% - 22px); /* Account for padding and border */
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1em;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    box-sizing: border-box; /* Include padding and border in the element's total width and height */
}

.form-group input[type="file"] {
    padding: 10px 0; /* Adjust padding for file input */
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #007bff;
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
    outline: none;
}

textarea {
    resize: vertical; /* Allow vertical resizing */
    min-height: 80px;
}

select#suppliersSelect {
    height: 120px; /* Adjust height for multi-select */
}

/* Create Button */
.create-button {
    background-color: #28a745; /* Success green */
    color: white;
    padding: 15px 25px;
    border: none;
    border-radius: 8px;
    font-size: 1.2em;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    width: 100%; /* Full width button */
    margin-top: 20px;
}

.create-button:hover {
    background-color: #218838; /* Darker green on hover */
    transform: translateY(-2px); /* Slight lift effect */
}

/* Response Messages */
.responseMessage {
    margin-top: 25px;
    padding: 15px;
    border-radius: 8px;
    font-size: 1.1em;
    font-weight: bold;
    text-align: center;
}

.responseMessage_success {
    background-color: #d4edda; /* Light green */
    color: #155724; /* Dark green */
    border: 1px solid #c3e6cb;
}

.responseMessage_error {
    background-color: #f8d7da; /* Light red */
    color: #721c24; /* Dark red */
    border: 1px solid #f5c6cb;
}

    .form-group input,
    .form-group textarea,
    .form-group select {
        font-size: 0.9em;
    }

    .create-button {
        font-size: 1.1em;
        padding: 12px 20px;
    }
    </style>
</head>

<body>
    <header>
        <a href="/" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
                &nbsp;&nbsp;Keepkit
        </a>
        <input type="search" id="searchInput" placeholder="Search in site" />
    </header>

     <div class="page" id="page">
        <?php include('sidebar.php'); ?>
        
        <main class="main">
            <section id="productsPage" class="active">
                   <div class="container">
                        <div class="header">
                            <span class="plus-icon">+</span> Create Product
                        </div>
                            <form action="database/add_products.php" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="product_image">PRODUCT Image</label>
                                <input type="file" id="produc_image" name="img" >
                            </div>
                            <div class="form-group">
                                <label for="productName">PRODUCT NAME</label>
                                <input type="text" id="productName" name="product_name" placeholder="Enter product name...">
                            </div>
                            <div class="form-group">
                                <label for="brandName">BRAND NAME</label>
                                <input type="text" id="brandName" name="brand_name">
                            </div>
                            <div class="form-group">
                                <label for="category">CATEGORY</label>
                                <input type="text" id="category" name="category">
                            </div>
                            <div class="form-group">
                                <label for="productType">PRODUCT TYPE</label>
                                <input type="text" id="productType" name="product_type">
                            </div>
                            <div class="form-group">
                                <label for="weight">WEIGHT</label>
                                <input type="number" id="weight" name="weight" step="any"> </div>
                            <div class="form-group">
                                <label for="description">DESCRIPTION</label>
                                <textarea id="description" name="description"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Suppliers</label>
                                    <?php
                                        include('database/connect.php');         // Connect to DB
                                        $suppliers = include('database/get_suppliers.php'); // This should return the data
                                    ?>

                                            <div class="supplier-selection">
                                <input type="text" id="supplierSearchInput" placeholder="Search supplier..." onkeyup="filterSuppliers()">
                                <select name="suppliers[]" id="suppliersSelect" multiple size="5">
                                    <option value="">Select Supplier</option>
                                    <?php
                                        // Assume $suppliers is an array of supplier data fetched from the database
                                        // Example: $suppliers = [['supplier_id' => 1, 'supplier_name' => 'Glow Beauty Co.'], ...]
                                        foreach ($suppliers as $supplier) {
                                            echo "<option value='" . htmlspecialchars($supplier['supplier_id']) . "'>" .
                                                htmlspecialchars($supplier['supplier_name']) . "</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                            </div>
                            <div class="form-group">
                                <label for="ingredients">INGREDIENTS</label>
                                <textarea id="ingredients" name="ingredients"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="price">PRICE</label>
                                <input type="number" id="price" name="price" step="any">
                            </div>
                            <button type="submit" class="create-button">Create Product</button>
                            </form>
                        <?php
                            if (isset($_SESSION['response'])) {
                                $response_message = $_SESSION['response']['message'];
                                //Corrected: Assuming 'success' key exists in $_SESSION['response']
                                $is_success = $_SESSION['response']['success'] ?? false; 

                                $class = $is_success ? 'responseMessage_success' : 'responseMessage_error';
                            ?>
                                <div class="responseMessage">
                                    <p class="<?= $class ?>"><?= htmlspecialchars($response_message) ?></p>
                                </div>
                            <?php
                                unset($_SESSION['response']); // Clean up the session variable.  No need for a separate function.
                            }
                        ?>
                    </div>
            </section>
        </main>
    </div>

 
        
    <script src="script.js"></script>
    <script>
        function filterSuppliers() {
    // Get the search input value and convert to lowercase for case-insensitive search
    var input = document.getElementById('supplierSearchInput');
    var filter = input.value.toLowerCase();

    // Get the select element and its options
    var select = document.getElementById('suppliersSelect');
    var options = select.getElementsByTagName('option');

    // Loop through all options, hiding those that don't match the search filter
    for (var i = 0; i < options.length; i++) {
        var option = options[i];
        var textValue = option.textContent || option.innerText; // Get the text content of the option

        // If the option's text contains the filter string (case-insensitive)
        if (textValue.toLowerCase().indexOf(filter) > -1) {
            option.style.display = ""; // Show the option
        } else {
            option.style.display = "none"; // Hide the option
        }
    }
}
    </script>

</body>
</html>