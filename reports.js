// reports.js

// Global variables for Chart instances
let salesTrendChart;
let profitDistributionChart;

document.addEventListener('DOMContentLoaded', function() {
    const periodFilter = document.getElementById('report-period-filter');
    const customDateGroup = document.getElementById('customDateGroup');
    const startDateFilter = document.getElementById('startDateFilter');
    const endDateFilter = document.getElementById('endDateFilter');
    const applyCustomFilterButton = document.getElementById('applyCustomFilter');
    const exportSalesProfitButton = document.getElementById('exportSalesProfitButton');
    const exportInventoryReportButton = document.getElementById('exportInventoryReportButton');

    // Set default dates for the custom filter on load (e.g., last 7 days)
    const today = new Date();
    const lastWeek = new Date();
    lastWeek.setDate(today.getDate() - 7);
    startDateFilter.value = lastWeek.toISOString().slice(0, 10);
    endDateFilter.value = today.toISOString().slice(0, 10);

    // Initial load of data (default: monthly)
    fetchReportsData(periodFilter.value);

    // Event listener for fixed period selection
    periodFilter.addEventListener('change', function() {
        const selectedValue = this.value;
        if (selectedValue === 'custom') {
            customDateGroup.classList.remove('hidden');
        } else {
            customDateGroup.classList.add('hidden');
            fetchReportsData(selectedValue);
        }
    });

    // Event listener for the Apply button (custom period)
    applyCustomFilterButton.addEventListener('click', function() {
        const startDate = startDateFilter.value;
        const endDate = endDateFilter.value;

        if (startDate && endDate) {
            fetchReportsData(null, startDate, endDate); 
        } else {
            alert("Please select both a start and end date.");
        }
    });


    /**
     * Helper function to update the content of the summary cards.
     */
    function updateCards(data) {
        // Define period descriptions for the fixed options
        const periodDescriptions = {
            'daily': 'for today',
            'monthly': 'for this month',
            '1month': 'for the last 30 days',
            '3months': 'for the last 90 days',
            '6months': 'for the last 6 months',
            '12months': 'for the last 12 months',
            'yearly': 'for this year',
            'overall': 'for all time',
            'custom': `from ${startDateFilter.value} to ${endDateFilter.value}`
        };

        const periodDisplay = periodDescriptions[data.period] || `for the selected period`;

        // Helper to format currency
        const formatCurrency = (value) => {
            return new Intl.NumberFormat('en-PH', { 
                style: 'currency', 
                currency: 'PHP',
                minimumFractionDigits: 2
            }).format(value);
        };

        // Update Summary Cards
        document.getElementById('salesValue').textContent = formatCurrency(data.sales);
        document.getElementById('salesPeriodText').textContent = periodDisplay;
        document.getElementById('salesValue').classList.remove('loading');

        document.getElementById('profitValue').textContent = formatCurrency(data.profit);
        document.getElementById('profitPeriodText').textContent = periodDisplay;
        document.getElementById('profitValue').classList.remove('loading');

        document.getElementById('costValue').textContent = formatCurrency(data.total_cost);
        document.getElementById('costPeriodText').textContent = periodDisplay;
        document.getElementById('costValue').classList.remove('loading');

        // Render Charts
        renderSalesTrendChart(data.trend_data, data.period);
        renderProfitDistributionChart(data.profit_for_chart, data.cost_for_chart, data.sales);
    }

    /**
     * Renders the Sales Trend Line Chart.
     */
    function renderSalesTrendChart(trendData, period) {
        const canvasElement = document.getElementById('salesTrendChart');
        if (!canvasElement) return;

        const ctx = canvasElement.getContext('2d');

        // 1. Prepare Data
        let labels = trendData.map(item => item.date_label);
        let dataValues = trendData.map(item => parseFloat(item.daily_sales));
        
        // Simple date formatting for display
        if (labels.length > 0) {
             labels = labels.map(dateStr => {
                const date = new Date(dateStr);
                // Grouping by Month/Year for long periods
                if (period === 'yearly' || period === 'overall' || period === '6months' || period === '12months') {
                    return date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short' });
                }
                // Grouping by Day/Month for shorter periods
                return date.toLocaleDateString('en-PH', { day: 'numeric', month: 'short' });
            });
        }
        
        // Destroy existing chart instance if it exists
        if (salesTrendChart) {
            salesTrendChart.destroy();
        }

        // 2. Create Chart
        salesTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Sales (₱)',
                    data: dataValues,
                    backgroundColor: 'rgba(40, 167, 69, 0.4)', // Green
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    fill: 'start', 
                    tension: 0.3 
                }]
            },
            options: {
                responsive: true,
                // FIX: Set to FALSE for stable height
                maintainAspectRatio: false, 
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Sales Trend Over Selected Period'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Sales Amount (₱)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    }

    /**
     * Renders the Profit Distribution Pie Chart.
     */
    function renderProfitDistributionChart(profit, cost, sales) {
        const canvasElement = document.getElementById('profitDistributionChart');
        if (!canvasElement) return;

        const ctx = canvasElement.getContext('2d');
        
        const profitValue = parseFloat(profit);
        const costValue = parseFloat(cost);
        const salesValue = parseFloat(sales); 

        // Only show chart if there are sales
        if (salesValue <= 0) {
            if (profitDistributionChart) {
                profitDistributionChart.destroy();
            }
             // Display message on canvas
             ctx.clearRect(0, 0, canvasElement.width, canvasElement.height);
             ctx.font = "16px Inter";
             ctx.fillStyle = "#777";
             ctx.textAlign = "center";
             const textX = canvasElement.width / 2;
             const textY = canvasElement.height / 2;
             ctx.fillText("No sales data available for this period.", textX, textY);
             return;
        }

        // Calculate percentage for labels
        const total = profitValue + costValue;
        const costPercentOfTotal = (costValue / total) * 100;
        const profitPercentOfTotal = (profitValue / total) * 100;

        const chartData = [profitValue, costValue];
        const chartLabels = [`Gross Profit (${profitPercentOfTotal.toFixed(1)}%)`, `Total Cost (${costPercentOfTotal.toFixed(1)}%)`];
        
        // Destroy existing chart instance if it exists
        if (profitDistributionChart) {
            profitDistributionChart.destroy();
        }

        // 2. Create Chart
        profitDistributionChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.8)', // Yellow/Amber for Profit
                        'rgba(220, 53, 69, 0.8)'  // Red for Cost
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                // FIX: Set to FALSE for stable height
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Profit vs. Cost Breakdown'
                    },
                    tooltip: {
                         callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(context.parsed);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }


    /**
     * Fetches reports data from the PHP backend. (Sales & Profit Section)
     */
    function fetchReportsData(period = null, startDate = null, endDate = null) {
        // Show loading state
        document.querySelectorAll('.value').forEach(el => {
            el.textContent = '... Loading';
            el.classList.add('loading');
        });

        let url = 'fetch_reports_data.php?';

        if (period) {
            url += 'period=' + period;
        } else if (startDate && endDate) {
            url += `start_date=${startDate}&end_date=${endDate}`;
        } else {
            url += 'period=monthly';
        }

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    updateCards(result.data);
                } else {
                    alert('Error fetching report data: ' + result.message);
                    document.querySelectorAll('.value').forEach(el => el.textContent = '₱0.00');
                    if(salesTrendChart) salesTrendChart.destroy();
                    if(profitDistributionChart) profitDistributionChart.destroy();
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('A network or server error occurred. Check console for details.');
                document.querySelectorAll('.value').forEach(el => el.textContent = '₱0.00');
            });
    }


    // --- Inventory Report Section Handler ---
    const inventoryReportContent = document.getElementById('inventoryReportContent');
    const reportLinks = document.querySelectorAll('.report-navigation .report-link'); 

    reportLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active state from all links
            reportLinks.forEach(l => l.classList.remove('active'));
            // Add active state to clicked link
            this.classList.add('active');

            const reportType = this.getAttribute('data-report-type');
            let fetchUrl = '';

            switch (reportType) {
                case 'inventory-summary':
                    fetchUrl = 'fetch_inventory_summary.php'; 
                    break;
                case 'fifo-cost':
                    fetchUrl = 'fetch_fifo_cost_report.php';
                    break;
                default:
                    inventoryReportContent.innerHTML = '<p class="error-message">Unknown report type selected.</p>';
                    return;
            }
            
            // Show loading state
            inventoryReportContent.innerHTML = '<p class="placeholder-text loading">Loading report data...</p>';

            // Fetch the HTML content
            fetch(fetchUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.text();
                })
                .then(html => {
                    inventoryReportContent.innerHTML = html;
                })
                .catch(error => {
                    console.error('Inventory Report Fetch error:', error);
                    inventoryReportContent.innerHTML = `<p class="error-message">Error fetching report: Tiyakin na ang file na ${fetchUrl} ay nandiyan sa folder ng reports.php.</p>`;
                });
        });
    });
    
    // Automatically load the first inventory report on page load for better UX
    if (reportLinks.length > 0) {
        const inventorySummaryLink = document.querySelector('[data-report-type="inventory-summary"]');
        if (inventorySummaryLink) {
             inventorySummaryLink.click();
        }
    }
    
    // --- EXPORT FUNCTIONALITY ---

    /**
     * Sales & Profit Export Handler
     */
    exportSalesProfitButton.addEventListener('click', function() {
        // Get current filter values
        const period = periodFilter.value;
        const startDate = startDateFilter.value;
        const endDate = endDateFilter.value;
        
        let url = 'export_sales_profit.php?';

        if (period === 'custom' && startDate && endDate) {
            url += `period=custom&start_date=${startDate}&end_date=${endDate}`;
        } else {
            url += 'period=' + period;
        }

        // Trigger the download by navigating the window
        window.location.href = url;
    });


    /**
     * Inventory Report Export Handler
     */
    exportInventoryReportButton.addEventListener('click', function() {
        // Get the currently active report link
        const activeLink = document.querySelector('.report-navigation .report-link.active');
        
        if (!activeLink) {
            alert('Please select an Inventory Report first.');
            return;
        }

        const reportType = activeLink.getAttribute('data-report-type');
        let url = '';

        switch (reportType) {
            case 'inventory-summary':
                url = 'export_inventory_summary.php';
                break;
            case 'fifo-cost':
                url = 'export_fifo_cost_report.php';
                break;
            default:
                alert('Cannot determine report type for export.');
                return;
        }

        // Trigger the download
        window.location.href = url;
    });

});