// dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    // Function to fetch JSON data
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

    // Function to update card with value and trend
    function updateCard(cardId, value, trendPercentage, valuePrefix = '', valueSuffix = '', periodText = 'vs previous 30 days') {
        const cardElement = document.getElementById(cardId);
        if (cardElement) {
            cardElement.querySelector('.value').textContent = `${valuePrefix}${value.toLocaleString()}${valueSuffix}`;
            const trendElement = cardElement.querySelector('.trend');
            if (trendElement) {
                const arrowIcon = trendElement.querySelector('.fas');
                const percentageSpan = trendElement.querySelector('span');
                const periodSpan = trendElement.querySelector('.period');

                if (trendPercentage > 0) {
                    trendElement.className = 'trend positive';
                    arrowIcon.className = 'fas fa-arrow-up';
                } else if (trendPercentage < 0) {
                    trendElement.className = 'trend negative';
                    arrowIcon.className = 'fas fa-arrow-down';
                } else {
                    trendElement.className = 'trend'; // Neutral
                    arrowIcon.className = 'fas fa-arrow-right'; // Or no arrow
                }
                percentageSpan.textContent = `${Math.abs(trendPercentage).toFixed(2)}%`;
                periodSpan.textContent = periodText;
            }
        }
    }


    async function loadDashboard() {
        console.log('Starting dashboard data load...');

        // Fetch all necessary data concurrently
        // NOTE: stocksAggregatedData, dashboardAggregatedData, profitData are your original fetches.
        // I am assuming you will modify these PHP files to return the new data needed.
        const [stocksAggregatedData, dashboardAggregatedData, profitData] = await Promise.all([
            fetchJSON('fetch_stocks.php?json=true'), // Existing stock data
            fetchJSON('fetch_reports_data.php') // Existing sales/profit data
        ]);

        console.log('All data fetch operations completed.');
        console.log('stocksAggregatedData:', stocksAggregatedData);
        console.log('profitData:', profitData);
        console.log('dashboardAggregatedData (for charts):', dashboardAggregatedData);

        // --- Update Dashboard Cards (New Summary Cards like in the image) ---
        // Placeholder data for new summary cards
        // You MUST replace this with actual data from your PHP backend
        const totalAccountsValue = 2104;
        const totalAccountsTrend = 20; // %
        const ordersPerMonthValue = 37;
        const ordersPerMonthTrend = 15;
        const averageContractValue = 1553;
        const averageContractTrend = 7.3;
        const growthRateValue = 8.29; // %
        const growthRateTrend = 1.3;

        updateCard('totalAccounts', totalAccountsValue, totalAccountsTrend);
        updateCard('ordersPerMonth', ordersPerMonthValue, ordersPerMonthTrend);
        updateCard('averageContract', averageContractValue, averageContractTrend, '$'); // Prefix with $
        updateCard('growthRate', growthRateValue, growthRateTrend, '', '%'); // Suffix with %


        // --- Original Dashboard Cards (Inventory Summary from fetch_stocks.php) ---
        // If you still need these, ensure their IDs are unique and they are in dashboard.php
        if (stocksAggregatedData && stocksAggregatedData.success) {
            console.log('Processing aggregated stock data...');
            const totalStockItems = stocksAggregatedData.total_stock_items ?? 0;
            const lowStockCount = stocksAggregatedData.low_stock_count ?? 0;
            const lowStockItemsList = stocksAggregatedData.low_stock_items_list ?? [];

            // Example: If you want to keep the total stocks card
            // document.getElementById('totalStocks').querySelector('.value').textContent = totalStockItems.toLocaleString();
            // document.getElementById('lowStocks').querySelector('.value').textContent = lowStockCount.toLocaleString();

            // Populate Low Stock Items List (if still visible)
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
                        li.textContent = `${item.name} (${item.stock})`;
                        lowStockListUl.appendChild(li);
                    });
                }
            }

            // Also use data for the stock chart if available in stocksAggregatedData.data (if still visible)
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
                            backgroundColor: 'rgba(75, 192, 192, 0.6)', // Greenish-blue
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantity in Stock' } } },
                        plugins: { legend: { display: false }, title: { display: true, text: 'Product Stock Distribution' } }
                    }
                });
            }

        } else {
            console.error('Failed to fetch aggregated stock data or data is invalid:', stocksAggregatedData ? stocksAggregatedData.message : 'Unknown error or no response/data property');
            // document.getElementById('totalStocks').querySelector('.value').textContent = 'N/A';
            // document.getElementById('lowStocks').querySelector('.value').textContent = 'N/A';
            if (document.getElementById('lowStockList')) { // Only update if element exists
                document.getElementById('lowStockList').innerHTML = '<li>Error loading low stock items.</li>';
            }
        }


        // --- Original Dashboard Cards (Sales and Profit from fetch_profit_data.php) ---
        // If you still need these, ensure their IDs are unique and they are in dashboard.php
        if (profitData && profitData.success && profitData.data) {
            console.log('Processing profit data...');
            const salesData = profitData.data;

            const totalSalesVal = parseFloat(salesData.total_sales);
            const totalProfitVal = parseFloat(salesData.total_profit);
            const monthlySalesVal = parseFloat(salesData.monthly_sales);
            const monthlyProfitVal = parseFloat(salesData.monthly_profit);

            // Example: If you want to keep these cards
            // document.getElementById('totalSales').querySelector('.value').textContent = `₱${isNaN(totalSalesVal) ? '0.00' : totalSalesVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            // document.getElementById('totalProfit').querySelector('.value').textContent = `₱${isNaN(totalProfitVal) ? '0.00' : totalProfitVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            // document.getElementById('salesThisMonth').querySelector('.value').textContent = `₱${isNaN(monthlySalesVal) ? '0.00' : monthlySalesVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            // document.getElementById('grossProfitThisMonth').querySelector('.value').textContent = `₱${isNaN(monthlyProfitVal) ? '0.00' : monthlyProfitVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            console.log('Profit data updated on dashboard cards.');
        } else {
            console.error('Failed to fetch profit data or data is invalid:', profitData ? profitData.message : 'Unknown error or no response/data property');
            // const defaultCurrency = '₱0.00';
            // document.getElementById('totalSales').querySelector('.value').textContent = defaultCurrency;
            // document.getElementById('totalProfit').querySelector('.value').textContent = defaultCurrency;
            // document.getElementById('salesThisMonth').querySelector('.value').textContent = defaultCurrency;
            // document.getElementById('grossProfitThisMonth').querySelector('.value').textContent = defaultCurrency;
        }

        // --- Render Charts (from get_dashboard_data.php and new placeholders) ---

        // Sales Growth by Market Segment (Line Chart)
        const salesGrowthByMarketSegmentChartCtx = document.getElementById('salesGrowthByMarketSegmentChart')?.getContext('2d');
        if (salesGrowthByMarketSegmentChartCtx) {
            if (window.salesGrowthByMarketSegmentChartInstance) { window.salesGrowthByMarketSegmentChartInstance.destroy(); }

            // Placeholder data for Sales Growth by Market Segment
            const marketSegmentLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const segmentAData = [100, 120, 150, 130, 170, 190, 220, 200, 240, 260, 280, 300];
            const segmentBData = [80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190];
            const segmentCData = [50, 60, 70, 65, 75, 80, 90, 85, 95, 100, 110, 120];

            // You would typically get this data from dashboardAggregatedData.lineChart.datasets
            // For example:
            // let chartData = { labels: [], datasets: [] };
            // if (dashboardAggregatedData && dashboardAggregatedData.success && dashboardAggregatedData.lineChart) {
            //     chartData.labels = dashboardAggregatedData.lineChart.labels;
            //     chartData.datasets = dashboardAggregatedData.lineChart.datasets;
            // }

            window.salesGrowthByMarketSegmentChartInstance = new Chart(salesGrowthByMarketSegmentChartCtx, {
                type: 'line',
                data: {
                    labels: marketSegmentLabels, // Use actual labels from data
                    datasets: [
                        {
                            label: 'Segment A',
                            data: segmentAData, // Use actual data
                            borderColor: '#82E0AA', // Light Green
                            backgroundColor: 'rgba(130, 224, 170, 0.2)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 3,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Segment B',
                            data: segmentBData, // Use actual data
                            borderColor: '#A569BD', // Purple
                            backgroundColor: 'rgba(165, 105, 189, 0.2)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 3,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Segment C',
                            data: segmentCData, // Use actual data
                            borderColor: '#F7DC6F', // Yellow
                            backgroundColor: 'rgba(247, 220, 111, 0.2)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 3,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Sales Amount ($)'
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        title: {
                            display: false, // Title is in h3 in HTML
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    }
                }
            });
        }

        // Sales per Rep (Bar Chart with multiple bars and optional line)
        const salesPerRepChartCtx = document.getElementById('salesPerRepChart')?.getContext('2d');
        if (salesPerRepChartCtx) {
            if (window.salesPerRepChartInstance) { window.salesPerRepChartInstance.destroy(); }

            // Placeholder data for Sales per Rep
            const repLabels = ['Rep A', 'Rep B', 'Rep C', 'Rep D', 'Rep E'];
            const currentMonthSales = [300, 450, 200, 500, 350];
            const previousMonthSales = [250, 400, 220, 480, 300];
            const salesTargetLine = [400, 400, 400, 400, 400]; // Example sales target line

            // You would get this data from your backend
            // Example:
            // let salesPerRepData = { labels: [], current: [], previous: [], target: [] };
            // if (dashboardAggregatedData && dashboardAggregatedData.salesPerRepChart) {
            //     salesPerRepData.labels = dashboardAggregatedData.salesPerRepChart.labels;
            //     salesPerRepData.current = dashboardAggregatedData.salesPerRepChart.currentMonthSales;
            //     salesPerRepData.previous = dashboardAggregatedData.salesPerRepChart.previousMonthSales;
            //     salesPerRepData.target = dashboardAggregatedData.salesPerRepChart.salesTargetLine;
            // }


            window.salesPerRepChartInstance = new Chart(salesPerRepChartCtx, {
                type: 'bar',
                data: {
                    labels: repLabels,
                    datasets: [
                        {
                            label: 'Current Month',
                            data: currentMonthSales,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)', // Blue bars
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            categoryPercentage: 0.6, // Adjust bar width
                            barPercentage: 0.8
                        },
                        {
                            label: 'Previous Month',
                            data: previousMonthSales,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)', // Greenish-blue bars
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            categoryPercentage: 0.6,
                            barPercentage: 0.8
                        },
                        {
                            label: 'Sales Target',
                            data: salesTargetLine,
                            type: 'line', // This makes it a line on top of the bars
                            borderColor: 'rgba(255, 99, 132, 1)', // Red line
                            backgroundColor: 'transparent',
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            borderWidth: 2,
                            tension: 0 // Straight line
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Sales Amount ($)' },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            title: { display: true, text: 'Sales Representative' },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: { display: true, position: 'bottom' },
                        title: { display: false } // Title is in h3 in HTML
                    }
                }
            });
        }


        // Sales by Product Category (Bar Chart) - Original Chart, kept if needed
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

        // Pie Chart - Original Chart, kept if needed
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
            window.location.href = 'view_stock.php?search_column=stock&search_term=low';
        });
    }

    // Call the function to fetch data when the page loads
    loadDashboard();

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