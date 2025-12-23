<?php
error_reporting(E_ALL); // Enable all error reporting
ini_set('display_errors', 1); // Display errors directly on the page for debugging
session_start(); // MAKE SURE THIS IS THE VERY FIRST LINE AFTER ERROR REPORTING

// Use null coalescing operator and explicit array cast for safer access
$user = (array) ($_SESSION['user'] ?? []); // Assuming only admin users can view this page

// Redirect to login page if user is not logged in or session user data is empty
if (empty($user)) {
    header('Location: index.php'); // Assuming index.php is your login page
    exit;
}

// Fetch customers/users from the users table
include('database/connect.php'); 

$customers = [];
try {
    // UPDATED SQL: Selecting from users table where role is 'user' or 'customer' based on your schema
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, profile_picture, Created_AT as created_at, Updated_AT as updated_at, role FROM users WHERE role IN ('user', 'customer') ORDER BY id DESC");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching customer data: " . $e->getMessage());
    $_SESSION['response'] = [
        'message' => 'Error fetching customer data. Please try again later.',
        'success' => false
    ];
}

// Fetch permissions for the logged-in user DIRECTLY FROM THE SESSION
$user_permissions = (array) ($user['permissions'] ?? []);
$user_role = strtolower($user['role'] ?? ''); // Get user's role

// Check if current user has permission to view this customers page itself
if (!isset($user_permissions['customer']) || !in_array('view', $user_permissions['customer'])) {
    $_SESSION['response'] = [
        'message' => 'You do not have permission to view this page.',
        'success' => false
    ];
    header('Location: home.php'); // Redirect to home or an unauthorized page
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="table.css"/>
    <link rel="stylesheet" href="users.css"/> <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Specific styles for the filter/search section within customersPage */
        .customer-search-filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center; /* Align items vertically */
        }

        .customer-search-filter-container label {
            font-weight: bold;
            color: #555;
        }

        #customerSearchInput {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
            flex: 1; /* Allow them to grow */
        }

        /* Styles for the table sorting icons */
        .customer-table th {
            cursor: pointer; /* Indicate sortable */
            position: relative;
        }

        .customer-table th .sort-icon {
            margin-left: 8px;
            color: #888;
        }

        .customer-table th.sorted-asc .sort-icon.fa-sort-up,
        .customer-table th.sorted-desc .sort-icon.fa-sort-down {
            color: #3498db; /* Highlight active sort icon */
        }

        /* Styles for the response message (unchanged from previous, adjust if needed) */
        .responseMessage {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 300px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            opacity: 0; /* Hidden by default */
            transition: opacity 0.5s ease-in-out;
            z-index: 1000;
        }

        .responseMessage p {
            padding: 15px 20px;
            margin: 0;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .responseMessage_success {
            color: #28a745;
            border-left: 5px solid #28a745;
        }

        .responseMessage_error {
            color: #dc3545;
            border-left: 5px solid #dc3545;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        /* Existing user-name styles from users.php (adjust if customer sidebar is separate) */
        .user-name {
            background-color: #343a40;
            padding: 15px 10px;
            border-bottom: 1px solid #444;
            margin-bottom: 20px;
            text-align: left;
            display: flex;
            align-items: center;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }

        .user-name:hover {
            background-color: #495057;
        }

        .user-name a.profile-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #f8f9fa;
            font-size: 1.15rem;
            font-weight: 600;
            width: 100%;
        }

        .user-name img.profile-pic-icon {
            height: 35px;
            width: 35px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
            border: 2px solid #ffc107;
            box-shadow: 0 0 5px rgba(0,0,0,0.3);
        }

        .user-name span.first-name,
        .user-name span.last-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Styles for profile pictures in table */
        .customer-table .customer-profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #ddd;
        }

        /* Style for the new "View Orders" button */
        .action-button.view-orders-btn {
            background-color: #007bff; /* A blue color for visibility */
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            transition: background-color 0.2s ease;
            margin-right: 5px; /* Space between buttons if multiple actions */
        }

        .action-button.view-orders-btn:hover {
            background-color: #0056b3;
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
            <input type="search" id="searchInput" placeholder="Search..." autocomplete="off">
            <div id="searchResults"></div>
        </div>
        <div class="right-element">
            <a href="database/logout.php" ><img src= "images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>

        <main class="main">
            <section id="customersPage" class="active">
                <h3>List of Accounts <br><p>Monitor all user and customer accounts in the system.</p></h3><br>
                <div class="section_content">
                    <div class="customer-search-filter-container">
                        <input type="text" id="customerSearchInput" placeholder="Search accounts...">
                    </div>
                    <div class="customer-table-container">
                        <table class="customer-table">
                            <thead>
                                <tr>
                                    <th data-column="id">ID <span class="sort-icon fas fa-sort"></span></th>
                                    <th>Profile Picture</th>
                                    <th data-column="first_name">First Name <span class="sort-icon fas fa-sort"></span></th>
                                    <th data-column="last_name">Last Name <span class="sort-icon fas fa-sort"></span></th>
                                    <th data-column="email">Email <span class="sort-icon fas fa-sort"></span></th>
                                    <th data-column="role">Role <span class="sort-icon fas fa-sort"></span></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="customerTableBody">
                                </tbody>
                        </table>
                        <br>
                    </div>
                </div>

                <?php if (isset($user_permissions['customer']) && in_array('edit', $user_permissions['customer']) && $user_role === 'admin'): ?>
                <div id="editCustomerModal" class="modal-overlay">
                    <div id="customerEditFormContainer">
                        <form class="appForm" action="database/edit_customer.php" method="POST">
                            <span class="close-button-edit">&times;</span>
                            <h2 style="text-align: center;">Edit Account</h2><br>
                            <input type="hidden" id="edit_customer_id" name="id">
                            <div class="appFormInputContainer">
                                <label for="edit_first_name">First Name</label>
                                <input type="text" class="appFormInput" id="edit_first_name" name="first_name" required />
                            </div>
                            <div class="appFormInputContainer">
                                <label for="edit_last_name">Last Name</label>
                                <input type="text" class="appFormInput" id="edit_last_name" name="last_name" required />
                            </div>
                            <div class="appFormInputContainer">
                                <label for="edit_email">Email</label>
                                <input type="email" class="appFormInput" id="edit_email" name="email" required />
                            </div>
                            <button type="submit" class="button-update"><i class="fa fa-save"></i> Update Account</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                    if (isset($_SESSION['response'])) {
                        $response_message = $_SESSION['response']['message'];
                        $is_success = $_SESSION['response']['success'] ?? false;

                        $class = $is_success ? 'responseMessage_success' : 'responseMessage_error';
                    ?>
                        <div class="responseMessage">
                            <p class="<?= $class ?>"><?= htmlspecialchars($response_message) ?></p>
                        </div>
                    <?php
                        unset($_SESSION['response']); // Clean up the session variable.
                    }
                ?>
            </section>
        </main>
    </div>

    <script src="script.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const editCustomerModal = document.getElementById('editCustomerModal');
            const closeButtonEdit = editCustomerModal ? editCustomerModal.querySelector('.close-button-edit') : null;
            
            let editButtons = document.querySelectorAll('.edit-button'); 

            // Function to attach edit button listeners (called after rendering)
            function attachEditButtonListeners() {
                editButtons = document.querySelectorAll('.edit-button'); // Re-query all edit buttons
                editButtons.forEach(button => {
                    button.onclick = function() { // Use onclick to allow re-assignment
                        const id = this.dataset.id;
                        const firstName = this.dataset.firstName;
                        const lastName = this.dataset.lastName;
                        const email = this.dataset.email;

                        document.getElementById('edit_customer_id').value = id;
                        document.getElementById('edit_first_name').value = firstName;
                        document.getElementById('edit_last_name').value = lastName;
                        document.getElementById('edit_email').value = email;

                        if (editCustomerModal) {
                            editCustomerModal.style.display = 'flex';
                        }
                    };
                });
            }

            // Hide Edit Customer Modal
            if (closeButtonEdit && editCustomerModal) {
                closeButtonEdit.addEventListener('click', function() {
                    editCustomerModal.style.display = 'none';
                });
            }

            // Hide Edit Customer Modal when clicking outside the form
            if (editCustomerModal) {
                window.addEventListener('click', function(event) {
                    if (event.target === editCustomerModal) {
                        editCustomerModal.style.display = 'none';
                    }
                });
            }

            // Function to attach delete button listeners (called after rendering)
            function attachDeleteButtonListeners() {
                const deleteButtons = document.querySelectorAll('.delete-button'); // Re-query all delete buttons
                deleteButtons.forEach(button => {
                    button.onclick = function() { // Use onclick to allow re-assignment
                        const customerId = this.dataset.id;
                        if (confirm('Are you sure you want to delete this account?')) {
                            window.location.href = 'database/delete_customer.php?id=' + customerId;
                        }
                    };
                });
            }

            // Function to attach view orders button listeners (NEW)
            function attachViewOrdersButtonListeners() {
                const viewOrdersButtons = document.querySelectorAll('.view-orders-btn');
                viewOrdersButtons.forEach(button => {
                    button.onclick = function() {
                        const customerId = this.dataset.id;
                        window.location.href = 'customer_order_history.php?customer_id=' + customerId;
                    };
                });
            }


            // Handle response message display
            const responseMessageContainer = document.querySelector('.responseMessage');
            if (responseMessageContainer) {
                const responseMessageP = responseMessageContainer.querySelector('p');
                if (responseMessageP && responseMessageP.textContent.trim() !== '') {
                    responseMessageContainer.style.opacity = '1';
                    setTimeout(() => {
                        responseMessageContainer.style.opacity = '0';
                        setTimeout(() => {
                            responseMessageContainer.style.display = 'none';
                        }, 500);
                    }, 3000);
                }
            }

            // Client-side search and sort for the customer table
            const customerSearchInput = document.getElementById('customerSearchInput');
            const customerTableBody = document.getElementById('customerTableBody');
            const tableHeaders = document.querySelectorAll('#customersPage table th[data-column]');

            let currentSortColumn = null;
            let currentSortDirection = 'asc';

            const customersData = <?= json_encode($customers) ?>; // PHP data to JS

            function renderTable(data) {
                customerTableBody.innerHTML = '';
                if (data.length === 0) {
                    customerTableBody.innerHTML = '<tr><td colspan="9">No accounts found.</td></tr>';
                    return;
                }

                data.forEach(customer_data => {
                    const row = customerTableBody.insertRow();
                    row.insertCell().textContent = customer_data.id;
                    
                    const profilePicCell = row.insertCell();
                    const img = document.createElement('img');
                    img.classList.add('customer-profile-pic');
                    const profilePicPath = customer_data.profile_picture ? `uploads/profiles/${customer_data.profile_picture}` : 'images/iconUser.png';
                    img.src = profilePicPath;
                    img.alt = 'Profile';
                    profilePicCell.appendChild(img);

                    row.insertCell().textContent = customer_data.first_name;
                    row.insertCell().textContent = customer_data.last_name;
                    row.insertCell().textContent = customer_data.email;
                    row.insertCell().textContent = customer_data.role;

                    const actionCell = row.insertCell();
                    
                    const userPermissions = <?= json_encode($user_permissions) ?>;
                    const userRole = '<?= htmlspecialchars($user_role) ?>'; // Pass user role to JS

                    // View Orders button (accessible by any user with 'view' permission for customer)
                    if (userPermissions.customer && userPermissions.customer.includes('view')) {
                        const viewOrdersButton = document.createElement('button');
                        viewOrdersButton.className = 'action-button view-orders-btn';
                        viewOrdersButton.innerHTML = '<i class="fas fa-receipt"></i> View Orders'; // Using a receipt icon
                        viewOrdersButton.dataset.id = customer_data.id;
                        actionCell.appendChild(viewOrdersButton);
                    }

                    if (userPermissions.customer && userPermissions.customer.includes('edit') && userRole === 'admin') {
                        const editButton = document.createElement('button');
                        editButton.className = 'action-button edit-button';
                        editButton.innerHTML = '<i class="fa fa-edit"></i> Edit';
                        editButton.dataset.id = customer_data.id;
                        editButton.dataset.firstName = customer_data.first_name;
                        editButton.dataset.lastName = customer_data.last_name;
                        editButton.dataset.email = customer_data.email;
                        actionCell.appendChild(editButton);
                    }

                    if (userPermissions.customer && userPermissions.customer.includes('delete') && userRole === 'admin') {
                        const deleteButton = document.createElement('button');
                        deleteButton.className = 'action-button delete-button';
                        deleteButton.innerHTML = '<i class="fa fa-trash"></i> Delete';
                        deleteButton.dataset.id = customer_data.id;
                        actionCell.appendChild(deleteButton);
                    }

                    if (actionCell.children.length === 0) {
                        actionCell.textContent = 'No actions';
                    }
                });

                // Re-attach listeners after rendering the table with new buttons
                attachEditButtonListeners();
                attachDeleteButtonListeners();
                attachViewOrdersButtonListeners(); // Attach listener for new button
            }

            function sortTable(column) {
                if (currentSortColumn === column) {
                    currentSortDirection = (currentSortDirection === 'asc') ? 'desc' : 'asc';
                } else {
                    currentSortColumn = column;
                    currentSortDirection = 'asc';
                }

                tableHeaders.forEach(header => {
                    const icon = header.querySelector('.sort-icon');
                    if (icon) {
                        icon.classList.remove('fa-sort-up', 'fa-sort-down');
                        icon.classList.add('fa-sort');
                        header.classList.remove('sorted-asc', 'sorted-desc');
                    }
                });

                const currentHeader = document.querySelector(`#customersPage table th[data-column="${column}"]`);
                if (currentHeader) {
                    const icon = currentHeader.querySelector('.sort-icon');
                    if (icon) {
                        icon.classList.remove('fa-sort');
                        icon.classList.add(currentSortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
                    }
                    currentHeader.classList.add(currentSortDirection === 'asc' ? 'sorted-asc' : 'sorted-desc');
                }

                const filteredAndSortedData = [...customersData].filter(customer_data => {
                    const searchTerm = customerSearchInput.value.toLowerCase();
                    const matchesSearch = Object.values(customer_data).some(value =>
                        String(value).toLowerCase().includes(searchTerm)
                    );
                    return matchesSearch;
                }).sort((a, b) => {
                    let valA = a[column];
                    let valB = b[column];

                    if (column === 'id') {
                        valA = parseInt(valA);
                        valB = parseInt(valB);
                    } else if (column === 'created_at' || column === 'updated_at') {
                        valA = new Date(valA);
                        valB = new Date(valB);
                    } else {
                        valA = String(valA || '').toLowerCase();
                        valB = String(valB || '').toLowerCase();
                    }

                    if (valA < valB) {
                        return currentSortDirection === 'asc' ? -1 : 1;
                    }
                    if (valA > valB) {
                        return currentSortDirection === 'asc' ? 1 : -1;
                    }
                    return 0;
                });
                renderTable(filteredAndSortedData);
            }

            function filterAndSearchCustomers() {
                const searchTerm = customerSearchInput.value.toLowerCase();
                const filteredCustomers = customersData.filter(customer_data => {
                    const matchesSearch = Object.values(customer_data).some(value =>
                        String(value).toLowerCase().includes(searchTerm)
                    );
                    return matchesSearch;
                });
                renderTable(filteredCustomers);
            }

            // Initial render of the table
            renderTable(customersData);

            // Add event listener for customer search input
            customerSearchInput.addEventListener('keyup', filterAndSearchCustomers);

            // Add event listeners for sorting
            tableHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const column = this.dataset.column
                    sortTable(column);
                });
            });

            // Set active sidebar link based on current page
            const currentPage = 'customer_list.php'; 
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                if (link.dataset.page === currentPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>