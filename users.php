<?php
error_reporting(E_ALL); // Enable all error reporting
ini_set('display_errors', 1); // Display errors directly on the page for debugging
session_start(); // MAKE SURE THIS IS THE VERY FIRST LINE AFTER ERROR REPORTING

// Use null coalescing operator and explicit array cast for safer access
$user = (array) ($_SESSION['user'] ?? []);

// Redirect to login page if user is not logged in or session user data is empty
if (empty($user)) {
    header('Location: index.php'); // Assuming index.php is your login page
    exit;
}

// Fetch users
// Assuming database/show_users.php returns an array of users.
// Make sure show_users.php includes connection.php correctly and fetches ALL users.
$users = include('database/show_users.php'); // This should return an array of user data

// Fetch permissions for the logged-in user DIRECTLY FROM THE SESSION
$user_permissions = (array) ($user['permissions'] ?? []);

// Check if current user has permission to view the users page itself
// This is your first line of defense for page access
// Make sure 'user' key exists and 'view' is present in its array
if (!isset($user_permissions['user']) || !in_array('view', $user_permissions['user'])) {
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
    <title>Users - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="table.css"/>
    <link rel="stylesheet" href="users.css"/> <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Specific styles for the filter/search section within usersPage */
        /* These styles are added/modified specifically for the new filter/search functionality */
        .user-search-filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center; /* Align items vertically */
        }

        .user-search-filter-container label {
            font-weight: bold;
            color: #555;
        }

        #userSearchInput,
        #userRoleFilter {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
            flex: 1; /* Allow them to grow */
        }

        #userRoleFilter {
            max-width: 200px; /* Limit width of dropdown */
            background-color: #f9f9f9;
        }

        /* Styles for the table sorting icons */
        .user table th {
            cursor: pointer; /* Indicate sortable */
            position: relative;
        }

        .user table th .sort-icon {
            margin-left: 8px;
            color: #888;
        }

        .user table th.sorted-asc .sort-icon.fa-sort-up,
        .user table th.sorted-desc .sort-icon.fa-sort-down {
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
            <section id="usersPage" class="active">
                <h3>List of Users <br><p>Monitor every account that accesses the system.</p></h3><br>
                <?php if (isset($user_permissions['user']) && in_array('create', $user_permissions['user'])): ?>
                <button class="add-button" id="showUserFormBtn">Create Account</button><br>
                <?php endif; ?>
                <div class="section_content">
                    <div class="user-search-filter-container">
                        <label for="userRoleFilter">Filter by Role:</label>
                        <select id="userRoleFilter">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                        <input type="text" id="userSearchInput" placeholder="Search users...">
                    </div>
                    <div class="user">
                        <table>
                            <thead>
                                <tr>
                                    <th data-column="id">ID <span class="sort-icon fas fa-sort"></span></th>
                                    <th data-column="first_name">First Name <span class="sort-icon fas fa-sort"></span></th>
                                    <th data-column="last_name">Last Name <span class="sort-icon fas fa-sort"></span></th>
                                    <th data-column="email">Email <span class="sort-icon fas fa-sort"></span></th>
                                    <th data-column="role">Role <span class="sort-icon fas fa-sort"></span></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                                </tbody>
                        </table>
                        <br>
                    </div>
                </div>

                <?php if (isset($user_permissions['user']) && in_array('create', $user_permissions['user'])): ?>
                <div id="formModal" class="modal-overlay">
                    <div id="userAddFormContainer">
                        <form class="appForm" action="Adduser.php" method="POST">
                            <span class="close-button">&times;</span>
                            <h2 style="text-align: center;">Create New Account</h2><br>
                            <div class="appFormInputContainer">
                                <label for="first_name_create">First Name</label>
                                <input type="text" class="appFormInput" id="first_name_create" name="first_name" required />
                            </div>
                            <div class="appFormInputContainer">
                                <label for="last_name_create">Last Name</label>
                                <input type="text" class="appFormInput" id="last_name_create" name="last_name" required />
                            </div>
                            <div class="appFormInputContainer">
                                <label for="email_create">Email</label>
                                <input type="email" class="appFormInput" id="email_create" name="email" required />
                            </div>
                            <div class="appFormInputContainer">
                                <label for="password_create">Password</label>
                                <input type="password" class="appFormInput" id="password_create" name="password" required />
                            </div>
                            <div class="appFormInputContainer">
                                <label for="confirm_password_create">Confirm Password</label>
                                <input type="password" class="appFormInput" id="confirm_password_create" name="confirm_password" required />
                            </div>
                            <div class="appFormInputContainer">
                                <label for="role">Role</label>
                                <select class="appFormInput" id="role" name="role" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="button-create"><i class="fa fa-plus"></i> Add User</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($user_permissions['user']) && in_array('edit', $user_permissions['user'])): ?>
                <div id="editUserModal" class="modal-overlay">
                    <div id="userEditFormContainer">
                        <form class="appForm" action="database/edit_user.php" method="POST">
                            <span class="close-button-edit">&times;</span>
                            <h2 style="text-align: center;">Edit Account</h2><br>
                            <input type="hidden" id="edit_user_id" name="id">
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
                            <div class="appFormInputContainer">
                                <label for="edit_role">Role</label>
                                <select class="appFormInput" id="edit_role" name="role" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="button-update"><i class="fa fa-save"></i> Update User</button>
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
            const showUserFormBtn = document.getElementById('showUserFormBtn');
            const formModal = document.getElementById('formModal');
            const closeButton = formModal ? formModal.querySelector('.close-button') : null;

            const editUserModal = document.getElementById('editUserModal');
            const closeButtonEdit = editUserModal ? editUserModal.querySelector('.close-button-edit') : null;
            
            // Re-query edit buttons after initial render as renderTable will create new ones
            let editButtons = document.querySelectorAll('.edit-button'); 

            // Show Add User Modal
            if (showUserFormBtn && formModal) {
                showUserFormBtn.addEventListener('click', function() {
                    formModal.style.display = 'flex';
                });
            }

            // Hide Add User Modal
            if (closeButton && formModal) {
                closeButton.addEventListener('click', function() {
                    formModal.style.display = 'none';
                    document.getElementById('first_name_create').value = '';
                    document.getElementById('last_name_create').value = '';
                    document.getElementById('email_create').value = '';
                    document.getElementById('password_create').value = '';
                    document.getElementById('confirm_password_create').value = '';
                    document.getElementById('role').value = 'user';
                });
            }

            // Hide Add User Modal when clicking outside the form
            if (formModal) {
                window.addEventListener('click', function(event) {
                    if (event.target === formModal) {
                        formModal.style.display = 'none';
                        document.getElementById('first_name_create').value = '';
                        document.getElementById('last_name_create').value = '';
                        document.getElementById('email_create').value = '';
                        document.getElementById('password_create').value = '';
                        document.getElementById('confirm_password_create').value = '';
                        document.getElementById('role').value = 'user';
                    }
                });
            }

            // Function to attach edit button listeners (called after rendering)
            function attachEditButtonListeners() {
                editButtons = document.querySelectorAll('.edit-button'); // Re-query all edit buttons
                editButtons.forEach(button => {
                    button.onclick = function() { // Use onclick to allow re-assignment
                        const id = this.dataset.id;
                        const firstName = this.dataset.firstName;
                        const lastName = this.dataset.lastName;
                        const email = this.dataset.email;
                        const role = this.dataset.role;

                        document.getElementById('edit_user_id').value = id;
                        document.getElementById('edit_first_name').value = firstName;
                        document.getElementById('edit_last_name').value = lastName;
                        document.getElementById('edit_email').value = email;
                        document.getElementById('edit_role').value = role;

                        if (editUserModal) {
                            editUserModal.style.display = 'flex';
                        }
                    };
                });
            }

            // Hide Edit User Modal
            if (closeButtonEdit && editUserModal) {
                closeButtonEdit.addEventListener('click', function() {
                    editUserModal.style.display = 'none';
                });
            }

            // Hide Edit User Modal when clicking outside the form
            if (editUserModal) {
                window.addEventListener('click', function(event) {
                    if (event.target === editUserModal) {
                        editUserModal.style.display = 'none';
                    }
                });
            }

            // Function to attach delete button listeners (called after rendering)
            function attachDeleteButtonListeners() {
                const deleteButtons = document.querySelectorAll('.delete-button'); // Re-query all delete buttons
                deleteButtons.forEach(button => {
                    button.onclick = function() { // Use onclick to allow re-assignment
                        const userId = this.dataset.id;
                        if (confirm('Are you sure you want to delete this user?')) {
                            window.location.href = 'database/delete_user.php?id=' + userId;
                        }
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

            // Client-side search and sort for the user table
            const userSearchInput = document.getElementById('userSearchInput');
            const userRoleFilter = document.getElementById('userRoleFilter'); // New filter
            const userTableBody = document.getElementById('userTableBody');
            const tableHeaders = document.querySelectorAll('#usersPage table th[data-column]');

            let currentSortColumn = null;
            let currentSortDirection = 'asc';

            const usersData = <?= json_encode($users) ?>;

            function renderTable(data) {
                userTableBody.innerHTML = '';
                if (data.length === 0) {
                    userTableBody.innerHTML = '<tr><td colspan="6">No users found.</td></tr>';
                    return;
                }

                data.forEach(user_data => {
                    const row = userTableBody.insertRow();
                    row.insertCell().textContent = user_data.id;
                    row.insertCell().textContent = user_data.first_name;
                    row.insertCell().textContent = user_data.last_name;
                    row.insertCell().textContent = user_data.email;
                    row.insertCell().textContent = user_data.role.charAt(0).toUpperCase() + user_data.role.slice(1);

                    const actionCell = row.insertCell();
                    
                    const userPermissions = <?= json_encode($user_permissions) ?>;

                    if (userPermissions.user && userPermissions.user.includes('edit')) {
                        const editButton = document.createElement('button');
                        editButton.className = 'action-button edit-button';
                        editButton.innerHTML = '<i class="fa fa-edit"></i> Edit';
                        editButton.dataset.id = user_data.id;
                        editButton.dataset.firstName = user_data.first_name;
                        editButton.dataset.lastName = user_data.last_name;
                        editButton.dataset.email = user_data.email;
                        editButton.dataset.role = user_data.role;
                        actionCell.appendChild(editButton);
                    }

                    if (userPermissions.user && userPermissions.user.includes('delete')) {
                        const deleteButton = document.createElement('button');
                        deleteButton.className = 'action-button delete-button';
                        deleteButton.innerHTML = '<i class="fa fa-trash"></i> Delete';
                        deleteButton.dataset.id = user_data.id;
                        actionCell.appendChild(deleteButton);
                    }

                    if (!((userPermissions.user && userPermissions.user.includes('edit')) || (userPermissions.user && userPermissions.user.includes('delete')))) {
                        if (actionCell.children.length === 0) {
                            actionCell.textContent = 'No actions';
                        }
                    }
                });

                // Re-attach listeners after rendering the table with new buttons
                attachEditButtonListeners();
                attachDeleteButtonListeners();
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

                const currentHeader = document.querySelector(`#usersPage table th[data-column="${column}"]`);
                if (currentHeader) {
                    const icon = currentHeader.querySelector('.sort-icon');
                    if (icon) {
                        icon.classList.remove('fa-sort');
                        icon.classList.add(currentSortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
                    }
                    currentHeader.classList.add(currentSortDirection === 'asc' ? 'sorted-asc' : 'sorted-desc');
                }

                const filteredAndSortedData = [...usersData].filter(user_data => {
                    const searchTerm = userSearchInput.value.toLowerCase();
                    const selectedRole = userRoleFilter.value.toLowerCase();

                    const matchesSearch = Object.values(user_data).some(value =>
                        String(value).toLowerCase().includes(searchTerm)
                    );
                    const matchesRole = selectedRole === '' || user_data.role.toLowerCase() === selectedRole;

                    return matchesSearch && matchesRole;
                }).sort((a, b) => {
                    let valA = a[column];
                    let valB = b[column];

                    if (column === 'id') {
                        valA = parseInt(valA);
                        valB = parseInt(valB);
                    } else {
                        valA = String(valA).toLowerCase();
                        valB = String(valB).toLowerCase();
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

            function filterAndSearchUsers() {
                const searchTerm = userSearchInput.value.toLowerCase();
                const selectedRole = userRoleFilter.value.toLowerCase();

                const filteredUsers = usersData.filter(user_data => {
                    const matchesSearch = Object.values(user_data).some(value =>
                        String(value).toLowerCase().includes(searchTerm)
                    );
                    const matchesRole = selectedRole === '' || user_data.role.toLowerCase() === selectedRole;
                    return matchesSearch && matchesRole;
                });
                renderTable(filteredUsers);
            }

            // Initial render of the table
            renderTable(usersData);

            // Add event listener for user search input
            userSearchInput.addEventListener('keyup', filterAndSearchUsers);

            // Add event listener for role filter dropdown
            userRoleFilter.addEventListener('change', filterAndSearchUsers);

            // Add event listeners for sorting
            tableHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const column = this.dataset.column;
                    sortTable(column);
                });
            });

            // Set active sidebar link based on current page
            const currentPage = 'users.php'; // Assuming this is the current page
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