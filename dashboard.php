<?php
//Start the session.
session_start();
$user = $_SESSION['user'] ?? null; // Use null coalescing to prevent undefined index error if session is not set
if(!isset($user)) {
    header('Location: index.php');
    exit; // Important: Exit after redirect
}

// Include database connection here.
// It's crucial for the PHP part that fetches initial inventory data and for get_permissions.
include_once('database/connect.php'); // Ensure this is available and provides a $conn PDO object
include_once('database/get_permissions.php'); // Assuming you have this file for permissions

$user_permissions = getPermissions($user['role'] ?? 'guest');

// Check if current user has permission to view the dashboard
if (!isset($user_permissions['dashboard']) || !in_array('view', $user_permissions['dashboard'])) {
    $_SESSION['response'] = [
        'message' => 'You do not have permission to view the dashboard.',
        'success' => false
    ];
    header('Location: home.php'); // Redirect to home or an unauthorized page
    exit;
}

// PHP code to fetch initial inventory data (used for the "Aggregated Inventory Statistics")
// This is synchronous data fetched directly during page load, not via AJAX.
// NOTE: These are product counts, not stock quantities. Total Products vs Total Stock Items.
$totalProducts = 0;
$highestStock = 0;
$lowestStock = 0;
$outOfStock = 0;

try {
    if (!($conn instanceof PDO)) {
        throw new Exception("Database connection object is not a PDO instance in dashboard.php.");
    }

    $stmt = $conn->prepare("
        SELECT
            COUNT(id) AS totalProducts,
            SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) AS outOfStock,
            MAX(stock) AS highestStock,
            MIN(stock) AS lowestStock
        FROM products
    ");
    $stmt->execute();
    $inventory_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalProducts = $inventory_data['totalProducts'] ?? 0;
    $highestStock = $inventory_data['highestStock'] ?? 0;
    $lowestStock = $inventory_data['lowestStock'] ?? 0;
    $outOfStock = $inventory_data['outOfStock'] ?? 0;

} catch (Exception $e) {
    error_log("Dashboard PHP Inventory Data Error (Initial Load): " . $e->getMessage());
    // Display a fallback message on the dashboard if this fails
    echo "<p style='color: red;'>Error loading initial inventory stats: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="stylesheet" href="dashboard.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        .summary-section {
            margin-bottom: 30px;
        }

        .summary-cards {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        .dashcard {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            min-width: 200px;
            flex: 1;
            max-width: 280px;
            display: flex; /* Use flexbox for vertical centering of content */
            flex-direction: column;
            justify-content: center; /* Center content vertically */
            align-items: center; /* Center content horizontally */
        }

        .dashcard strong {
            font-size: 1.1em;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }

        .dashcard .value {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }

        /* Specific colors for sales/profit cards */
        #totalSales .value, #salesThisMonth .value { color: #28a745; } /* Green */
        #totalProfit .value, #grossProfitThisMonth .value { color: #17a2b8; } /* Teal */

        .chart-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
            height: 400px;
            position: relative;
        }

        canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .dashmain {
            padding: 20px;
            flex-grow: 1;
        }

        @media (max-width: 768px) {
            .summary-cards {
                flex-direction: column;
                align-items: center;
            }

            .dashcard {
                width: 90%;
                max-width: unset;
            }

            .chart-card {
                height: 300px;
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

        /* Flex container for the boxes */
        .dashboard-stats {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <header>
        <a href="home.php" id="navbar__logo">
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

        <main class="dashmain">
            <section class="summary-section">
                <h2>Inventory Summary</h2>
                <div class="summary-cards">
                    <div id="totalStocks" class="dashcard"><strong>Total Stock Items</strong><br /><span class="value">Loading...</span></div>
                    <div id="lowStocks" class="dashcard"><strong>Low Stock Items</strong><br /><span class="value">Loading...</span></div>
                    <div id="totalSales" class="dashcard"><strong>Overall Sales</strong><br /><span class="value">Loading...</span></div>
                    <div id="totalProfit" class="dashcard"><strong>Overall Profit</strong><br /><span class="value">Loading...</span></div>
                </div>

                <h2>Aggregated Inventory Statistics (Product Counts)</h2>
                <div class="dashboard-stats summary-cards">
                    <div id="totalProducts" class="dashcard">
                        <strong>Total Products</strong>
                        <span class="value"><?= $totalProducts ?> <?= ($totalProducts <= 1) ? 'pc' : 'pcs' ?></span>
                    </div>
                    <div id="highestStock" class="dashcard">
                        <strong>Highest Stock (Single Product)</strong>
                        <span class="value"><?= $highestStock ?> <?= ($highestStock <= 1) ? 'pc' : 'pcs' ?></span>
                    </div>
                    <div id="lowestStock" class="dashcard">
                        <strong>Lowest Stock (Excl. Zero)</strong>
                        <span class="value"><?= $lowestStock ?> <?= ($lowestStock <= 1) ? 'pc' : 'pcs' ?></span>
                    </div>
                    <div id="outOfStock" class="dashcard">
                        <strong>Out of Stock Products</strong>
                        <span class="value"><?= $outOfStock ?> <?= ($outOfStock <= 1) ? 'pc' : 'pcs' ?></span>
                    </div>
                    <div id="salesThisMonth" class="dashcard"><strong>Sales This Month</strong><br /><span class="value">Loading...</span></div>
                    <div id="grossProfitThisMonth" class="dashcard"><strong>Gross Profit This Month</strong><br /><span class="value">Loading...</span></div>
                </div>

                <div class="chart-card" style="margin-top: 20px;">
                    <h3>Low Stock Items (Threshold: 10)</h3>
                    <ul id="lowStockList">
                        <li>Loading low stock items...</li>
                    </ul>
                </div>

                <div class="chart-card" style="margin-top: 20px;">
                    <h3>Product Stock Distribution</h3>
                    <canvas id="stockChart"></canvas>
                </div>

                <div class="chart-card" style="margin-top: 20px;">
                    <h3>Sales Trend Over Last 6 Months (Line Chart)</h3>
                    <canvas id="salesTrendChart"></canvas>
                </div>

                <div class="chart-card" style="margin-top: 20px;">
                    <h3>Sales by Product Category (Bar Chart)</h3>
                    <canvas id="salesByCategoryChart"></canvas>
                </div>

                <div class="chart-card" style="margin-top: 20px;">
                    <h3>Product Category Distribution</h3>
                    <canvas id="pieChart"></canvas>
                </div>
            </section>
        </main>

        <script src="script.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Generic function to fetch JSON data
                async function fetchJSON(url) {
                    try {
                        console.log(`Attempting to fetch data from: ${url}`);
                        const response = await fetch(url);
                        if (!response.ok) {
                            const errorText = await response.text();
                            console.error(`HTTP error for ${url}! Status: ${response.status}, Message: ${errorText}`);
                            throw new Error(`HTTP error! Status: ${response.status}, Message: ${errorText}`);
                        }
                        const data = await response.json();
                        console.log(`Successfully fetched data from ${url}:`, data);
                        return data;
                    } catch (error) {
                        console.error(`Error fetching ${url}:`, error);
                        // Return a structure indicating failure for consistent handling in loadDashboard
                        return { success: false, message: `Failed to fetch data from ${url}. ${error.message}`, data: {} };
                    }
                }

                async function loadDashboard() {
                    console.log('Starting dashboard data load...');

                    // Fetch all necessary data concurrently
                    const [stocksAggregatedData, dashboardAggregatedData, profitData] = await Promise.all([
                        fetchJSON('fetch_stocks.php?json=true'), // This will now provide aggregated stock data
                        fetchJSON('get_dashboard_data.php'), // This will provide data for sales trend and category charts
                        fetchJSON('fetch_profit_data.php') // This provides overall and monthly sales/profit figures
                    ]);

                    console.log('All data fetch operations completed.');
                    console.log('stocksAggregatedData:', stocksAggregatedData);
                    console.log('profitData:', profitData);
                    console.log('dashboardAggregatedData (for charts):', dashboardAggregatedData);

                    // --- Update Dashboard Cards (Inventory Summary from fetch_stocks.php) ---
                    if (stocksAggregatedData && stocksAggregatedData.success) {
                        console.log('Processing aggregated stock data...');
                        const totalStockItems = stocksAggregatedData.total_stock_items ?? 0;
                        const lowStockCount = stocksAggregatedData.low_stock_count ?? 0;
                        const lowStockItemsList = stocksAggregatedData.low_stock_items_list ?? [];

                        document.getElementById('totalStocks').querySelector('.value').textContent = totalStockItems.toLocaleString() + (totalStockItems === 1 ? ' pc' : ' pcs');
                        document.getElementById('lowStocks').querySelector('.value').textContent = lowStockCount.toLocaleString() + (lowStockCount === 1 ? ' pc' : ' pcs');

                        // Populate Low Stock Items List
                        const lowStockListUl = document.getElementById('lowStockList');
                        if (lowStockListUl) {
                            lowStockListUl.innerHTML = ''; // Clear previous loading message
                            if (lowStockItemsList.length === 0) {
                                const li = document.createElement('li');
                                li.textContent = 'No low-stock items 🎉';
                                lowStockListUl.appendChild(li);
                            } else {
                                lowStockItemsList.forEach(item => {
                                    const li = document.createElement('li');
                                    li.className = 'low-stock-item';
                                    const unit = (item.stock === 1) ? 'pc' : 'pcs';
                                    li.textContent = `${item.name} (${item.stock} ${unit})`;
                                    lowStockListUl.appendChild(li);
                                });
                            }
                        }

                        // Also use data for the stock chart if available in stocksAggregatedData.data
                        const stockNames = [];
                        const stockQuantities = [];
                        if (Array.isArray(stocksAggregatedData.data)) {
                             stocksAggregatedData.data.forEach(item => {
                                 stockNames.push(item.product_name);
                                 stockQuantities.push(parseInt(item.stock));
                             });
                        }
                        const stockChartCtx = document.getElementById('stockChart')?.getContext('2d');
                        if (stockChartCtx) {
                            if (window.stockChartInstance) { window.stockChartInstance.destroy(); }
                            window.stockChartInstance = new Chart(stockChartCtx, {
                                type: 'bar',
                                data: {
                                    labels: stockNames,
                                    datasets: [{
                                        label: 'Current Stock Level',
                                        data: stockQuantities,
                                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                        borderColor: 'rgba(75, 192, 192, 1)',
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false,
                                    scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantity in Stock (pcs)' } } },
                                    plugins: { legend: { display: false }, title: { display: true, text: 'Product Stock Distribution' } }
                                }
                            });
                        }

                    } else {
                        console.error('Failed to fetch aggregated stock data or data is invalid:', stocksAggregatedData ? stocksAggregatedData.message : 'Unknown error or no response/data property');
                        document.getElementById('totalStocks').querySelector('.value').textContent = 'N/A';
                        document.getElementById('lowStocks').querySelector('.value').textContent = 'N/A';
                        document.getElementById('lowStockList').innerHTML = '<li>Error loading low stock items.</li>';
                    }


                    // --- Update Dashboard Cards (Sales and Profit from fetch_profit_data.php) ---
                    if (profitData && profitData.success && profitData.data) {
                        console.log('Processing profit data...');
                        const salesData = profitData.data;

                        const totalSalesVal = parseFloat(salesData.total_sales);
                        const totalProfitVal = parseFloat(salesData.total_profit);
                        const monthlySalesVal = parseFloat(salesData.monthly_sales);
                        const monthlyProfitVal = parseFloat(salesData.monthly_profit);

                        document.getElementById('totalSales').querySelector('.value').textContent = `₱${isNaN(totalSalesVal) ? '0.00' : totalSalesVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        document.getElementById('totalProfit').querySelector('.value').textContent = `₱${isNaN(totalProfitVal) ? '0.00' : totalProfitVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        document.getElementById('salesThisMonth').querySelector('.value').textContent = `₱${isNaN(monthlySalesVal) ? '0.00' : monthlySalesVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        document.getElementById('grossProfitThisMonth').querySelector('.value').textContent = `₱${isNaN(monthlyProfitVal) ? '0.00' : monthlyProfitVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        console.log('Profit data updated on dashboard cards.');
                    } else {
                        console.error('Failed to fetch profit data or data is invalid:', profitData ? profitData.message : 'Unknown error or no response/data property');
                        const defaultCurrency = '₱0.00';
                        document.getElementById('totalSales').querySelector('.value').textContent = defaultCurrency;
                        document.getElementById('totalProfit').querySelector('.value').textContent = defaultCurrency;
                        document.getElementById('salesThisMonth').querySelector('.value').textContent = defaultCurrency;
                        document.getElementById('grossProfitThisMonth').querySelector('.value').textContent = defaultCurrency;
                    }

                    // --- Render Charts (from get_dashboard_data.php) ---
                    // Sales Trend Chart (Line Chart)
                    const salesTrendChartCtx = document.getElementById('salesTrendChart')?.getContext('2d');
                    if (salesTrendChartCtx) {
                        if (window.salesTrendChartInstance) { window.salesTrendChartInstance.destroy(); }
                        let monthlyLabels = [];
                        let monthlySalesData = [];
                        if (dashboardAggregatedData && dashboardAggregatedData.success && dashboardAggregatedData.lineChart && dashboardAggregatedData.lineChart.labels && dashboardAggregatedData.lineChart.data) {
                            monthlyLabels = dashboardAggregatedData.lineChart.labels;
                            monthlySalesData = dashboardAggregatedData.lineChart.data;
                            console.log('Sales Trend Chart Data:', monthlyLabels, monthlySalesData);
                        } else {
                             console.warn('No valid sales trend data received for line chart from get_dashboard_data.php. Displaying default.');
                             monthlyLabels = ['No Data'];
                             monthlySalesData = [0];
                        }
                        window.salesTrendChartInstance = new Chart(salesTrendChartCtx, {
                            type: 'line',
                            data: {
                                labels: monthlyLabels,
                                datasets: [{
                                    label: 'Monthly Sales (PHP)',
                                    data: monthlySalesData,
                                    borderColor: 'rgb(54, 162, 235)', tension: 0, fill: false,
                                    pointBackgroundColor: 'rgb(54, 162, 235)', pointBorderColor: '#fff',
                                    pointHoverBackgroundColor: '#fff', pointHoverBorderColor: 'rgb(54, 162, 235)',
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                scales: { y: { beginAtZero: true, title: { display: true, text: 'Sales Amount (PHP)' } }, x: { title: { display: true, text: 'Month' } } },
                                plugins: { legend: { display: true, position: 'top' }, title: { display: true, text: 'Sales Trend Over Last 6 Months' } }
                            }
                        });
                    }

                    // Sales by Product Category (Bar Chart)
                    const salesByCategoryChartCtx = document.getElementById('salesByCategoryChart')?.getContext('2d');
                    if (salesByCategoryChartCtx) {
                        if (window.salesByCategoryChartInstance) { window.salesByCategoryChartInstance.destroy(); }
                        let categoryLabels = [];
                        let categorySalesData = [];

                        if (dashboardAggregatedData && dashboardAggregatedData.success && dashboardAggregatedData.barChart && dashboardAggregatedData.barChart.labels && dashboardAggregatedData.barChart.data) {
                             categoryLabels = dashboardAggregatedData.barChart.labels;
                             categorySalesData = dashboardAggregatedData.barChart.data;
                             console.log('Sales by Category Chart Data:', categoryLabels, categorySalesData);
                        } else {
                            console.warn('No valid sales by category data received for bar chart from get_dashboard_data.php. Displaying default.');
                            categoryLabels = ['No Data'];
                            categorySalesData = [0];
                        }

                        window.salesByCategoryChartInstance = new Chart(salesByCategoryChartCtx, {
                            type: 'bar',
                            data: {
                                labels: categoryLabels,
                                datasets: [{
                                    label: 'Sales by Category (PHP)',
                                    data: categorySalesData,
                                    backgroundColor: ['rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)', 'rgba(75, 192, 192, 0.6)', 'rgba(153, 102, 255, 0.6)', 'rgba(255, 159, 64, 0.6)'],
                                    borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)'],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                scales: { y: { beginAtZero: true, title: { display: true, text: 'Sales Amount (PHP)' } }, x: { title: { display: true, text: 'Product Category' } } },
                                plugins: { legend: { display: false }, title: { display: true, text: 'Sales by Product Category' } }
                            }
                        });
                    }

                    // Pie Chart
                    const pieCtx = document.getElementById('pieChart')?.getContext('2d');
                    if (pieCtx && dashboardAggregatedData && dashboardAggregatedData.success && dashboardAggregatedData.pieChart && dashboardAggregatedData.pieChart.labels && dashboardAggregatedData.pieChart.data) {
                        if (window.pieChartInstance) { window.pieChartInstance.destroy(); }
                        window.pieChartInstance = new Chart(pieCtx, {
                            type: 'pie',
                            data: {
                                labels: dashboardAggregatedData.pieChart.labels,
                                datasets: [{
                                    label: 'Categories',
                                    data: dashboardAggregatedData.pieChart.data,
                                    backgroundColor: [ '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40' ]
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Product Category Distribution' } } }
                        });
                        console.log('Pie Chart Data:', dashboardAggregatedData.pieChart.labels, dashboardAggregatedData.pieChart.data);
                    } else {
                        console.warn('No valid pie chart data received from get_dashboard_data.php. Displaying default.');
                    }
                }

                // --- Low Stock Card Clickable ---
                const lowStocksCard = document.getElementById('lowStocks');
                if (lowStocksCard) {
                    lowStocksCard.addEventListener('click', function() {
                        // This will now point to your view_stocks.php, possibly with a filter for low stock
                        window.location.href = 'view_stock.php?search_column=stock&search_term=low';
                    });
                }

                loadDashboard(); // Initial load of data and charts

                // Set active sidebar link
                const currentPage = 'dashboard.php';
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
    </div>

</body>
</html>