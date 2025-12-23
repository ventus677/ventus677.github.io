<?php
// reports.php
session_start();
$user = $_SESSION['user'] ?? null;

if(!isset($user)) {
    header('Location: index.php');
    exit;
}

// NOTE: I'm assuming you have $conn and a way to populate $user details here.
// For consistency, I'll replicate the user display logic from home.php.
// If you don't have $user populated, you might see errors.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Keepkit</title>
    <link rel="stylesheet" href="home.css"/> <link rel="stylesheet" href="reports.css"/> <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <header>
        <a href="index.php" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
            <h3>&nbsp;&nbsp;Keepkit</h3>
        </a>
        <div class="right-element">
            <a href="database/logout.php" ><img src= "images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>

        <main class="main">
            <div class="main-content">
                <div class="report-title-group">
                    <h1 class="main-content-title">Sales & Profit Reports</h1>
                    <button class="export-button sales-export-button" id="exportSalesProfitButton">
                        <i class="fas fa-file-excel"></i> Export Sales Data
                    </button>
                </div>
                <p style="text-align: center; margin-bottom: 40px; font-size: clamp(14px, 1vw, 16px); color: #151515;">Review your key financial metrics.</p>

                <div class="filters-container">
                    <div class="filter-group">
                        <label for="report-period-filter">Select Period:</label>
                        <select id="report-period-filter">
                            <option value="monthly">This Month (Current)</option>
                            <option value="daily">Today (This Day)</option>
                            <option value="1month">Last 30 Days (1 Month)</option>
                            <option value="3months">Last 90 Days (3 Months)</option>
                            <option value="6months">Last 180 Days (6 Months)</option>
                            <option value="12months">Last 365 Days (12 Months)</option>
                            <option value="yearly">This Year</option>
                            <option value="overall">Overall (All Time)</option>
                            <option value="custom">Custom Date Range</option> 
                        </select>
                    </div>

                    <div class="custom-date-group hidden" id="customDateGroup">
                        <label>Start Date:</label>
                        <input type="date" id="startDateFilter">
                        <label>End Date:</label>
                        <input type="date" id="endDateFilter">
                        <button id="applyCustomFilter">Apply</button>
                    </div>
                </div>

                <div class="summary-cards">
                    <div class="card sales-card" id="totalSalesCard">
                        <div class="card-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="card-content">
                            <span class="card-title">Total Sales (Benta)</span>
                            <div class="value loading" id="salesValue">₱0.00</div>
                            <p class="period-text" id="salesPeriodText">for this month</p>
                        </div>
                    </div>

                    <div class="card profit-card" id="totalProfitCard">
                        <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="card-content">
                            <span class="card-title">Gross Profit (Kita)</span>
                            <div class="value loading" id="profitValue">₱0.00</div>
                            <p class="period-text" id="profitPeriodText">for this month</p>
                        </div>
                    </div>

                    <div class="card cost-card" id="totalCostCard">
                        <div class="card-icon"><i class="fas fa-sack-dollar"></i></div>
                        <div class="card-content">
                            <span class="card-title">Total Cost (Puhunan)</span>
                            <div class="value loading" id="costValue">₱0.00</div>
                            <p class="period-text" id="costPeriodText">for this month</p>
                        </div>
                    </div>
                </div>

                <div class="detailed-reports">
                    <div class="chart-container">
                        <h2>Sales Trend</h2>
                        <canvas id="salesTrendChart"></canvas>
                    </div>

                    <div class="chart-container">
                        <h2>Profit Distribution</h2>
                        <canvas id="profitDistributionChart"></canvas>
                    </div>
                </div>
                
                <div class="report-title-group">
                    <h1 class="main-content-title inventory-title">Inventory Reports</h1>
                    <button class="export-button inventory-export-button" id="exportInventoryReportButton">
                        <i class="fas fa-file-excel"></i> Export Current Report
                    </button>
                </div>
                <p style="text-align: center; margin-bottom: 30px; font-size: clamp(14px, 1vw, 16px); color: #151515;">Detailed stock tracking and valuation.</p>

                <div class="inventory-reports-container">
                    <div class="report-navigation">
                        <a href="#" class="report-link active" data-report-type="inventory-summary">Inventory Summary</a>
                        <a href="#" class="report-link" data-report-type="fifo-cost">FIFO Cost Lot Tracking</a>
                    </div>
                    
                    <div class="inventory-report-content" id="inventoryReportContent">
                        <p class="placeholder-text">Loading Inventory Summary...</p>
                    </div>
                </div>
                </div>
        </main>
    </div>
    <script src="reports.js"></script>
</body>
</html>