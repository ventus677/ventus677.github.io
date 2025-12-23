<?php
session_start();
// Include database connection
include('database/connect.php');

// Redirect to login page if user is not logged in or session user data is empty
$user = (array) ($_SESSION['user'] ?? []);
if (empty($user)) {
    header('Location: index.php');
    exit;
}

// Fetch permissions for the logged-in user
$user_permissions = (array) ($user['permissions'] ?? []);

// Check if current user has permission to view reports
// Assuming 'reports' module has 'view' permission for this page
if (!isset($user_permissions['reports']) || !in_array('view', $user_permissions['reports'])) {
    $_SESSION['response'] = [
        'message' => 'You do not have permission to view the profit report.',
        'success' => false
    ];
    header('Location: home.php'); // Redirect to home or an unauthorized page
    exit;
}

// User data for sidebar display
$user = isset($_SESSION['user']) ? $_SESSION['user'] : ['first_name' => 'Guest', 'last_name' => ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit Report - Keepkit</title>
    <link rel="stylesheet" href="home.css">
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profit-report-container {
            padding: 20px;
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .profit-report-container h3 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .summary-card h4 {
            color: #555;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .summary-card p {
            font-size: 1.8em;
            font-weight: bold;
            color: #007bff;
            margin: 0;
        }
        .summary-card.sales p {
            color: #28a745; /* Green for sales */
        }
        .summary-card.profit p {
            color: #17a2b8; /* Teal for profit */
        }
        .loading-message, .error-message {
            text-align: center;
            padding: 20px;
            font-size: 1.1em;
            color: #777;
        }
        .error-message {
            color: #dc3545;
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
            <section id="profitReportPage" class="active">
                <div class="profit-report-container">
                    <h3>Sales and Profit Report</h3>
                    <div id="reportData">
                        <p class="loading-message">Loading sales and profit data...</p>
                        </div>
                </div>
            </section>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
    <script>
        $(document).ready(function() {
            function fetchProfitData() {
                $.ajax({
                    url: 'fetch_profit_data.php',
                    type: 'GET',
                    dataType: 'json', // Expect JSON response
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            let html = `
                                <div class="summary-grid">
                                    <div class="summary-card sales">
                                        <h4>Total Sales</h4>
                                        <p>₱${parseFloat(data.total_sales).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                                    </div>
                                    <div class="summary-card profit">
                                        <h4>Total Profit</h4>
                                        <p>₱${parseFloat(data.total_profit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                                    </div>
                                    <div class="summary-card sales">
                                        <h4>Sales This Month</h4>
                                        <p>₱${parseFloat(data.monthly_sales).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                                    </div>
                                    <div class="summary-card profit">
                                        <h4>Profit This Month</h4>
                                        <p>₱${parseFloat(data.monthly_profit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                                    </div>
                                </div>
                            `;
                            $('#reportData').html(html);
                        } else {
                            $('#reportData').html(`<p class="error-message">Error: ${response.message}</p>`);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#reportData').html('<p class="error-message">An error occurred while fetching data. Please try again.</p>');
                        console.error("AJAX Error: ", status, error, xhr.responseText);
                    }
                });
            }

            // Initial fetch of data
            fetchProfitData();

            // Set active sidebar link
            const currentPage = 'profit_report.php';
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