// Immediate debug to check if script is loading
// console.log('Charts.js script loaded - Step 1');

// Get elements
// console.log('Charts.js script loaded - Step 2: Getting elements');
const dateRangePicker = document.getElementById("date-range");
const filterButtons = document.querySelectorAll(".filter-btn");

// Debug element existence
// console.log('Charts.js script loaded - Step 3: Element check results');
// console.log('Elements found:', {
//     dateRangePicker: !!dateRangePicker,
//     filterButtons: filterButtons.length,
//     transactionsData: !!document.getElementById('transactions-data'),
//     userCurrency: !!document.getElementById('user-currency'),
//     incomeExpenseChart: !!document.getElementById('income-expense-chart'),
//     categoryChart: !!document.getElementById('category-chart')
// });

// Check if elements were found before proceeding with listeners/initialization
if (!dateRangePicker || filterButtons.length === 0) {
    console.error('Charts.js failed to find required elements. Stopping initialization.');
} else {
    // console.log('Charts.js script loaded - Step 4: Required elements found, proceeding.');

    // Get transactions from PHP
    let transactions = [];
    try {
        const transactionsElement = document.getElementById('transactions-data');
        if (transactionsElement) {
            const transactionsValue = transactionsElement.value;
            transactions = JSON.parse(transactionsValue);
            
            // Validate transaction structure
            if (transactions.length > 0) {
                const firstTransaction = transactions[0];
                // console.log('First transaction structure:', {
                //     hasAmount: 'amount' in firstTransaction,
                //     hasDate: 'date' in firstTransaction,
                //     hasCategory: 'category' in firstTransaction,
                //     hasType: 'type' in firstTransaction,
                //     amount: firstTransaction.amount,
                //     date: firstTransaction.date,
                //     category: firstTransaction.category,
                //     type: firstTransaction.type
                // });
            }
        } else {
            console.error('transactions-data element not found');
        }
    } catch (error) {
        console.error('Error parsing transactions:', error);
        console.error('Error details:', {
            name: error.name,
            message: error.message,
            stack: error.stack
        });
    }

    let currency = document.getElementById('user-currency')?.value || "$";
    // console.log('Currency:', currency);

    // Initialize Flatpickr
    // console.log('Charts.js script loaded - Step 5: Initializing Flatpickr');
    let fp;
    try {
        fp = flatpickr(dateRangePicker, {
            mode: "range",
            dateFormat: "Y-m-d",
            maxDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                // Only render charts if two dates are selected
                if (selectedDates.length === 2) {
                    const startDate = selectedDates[0];
                    const endDate = selectedDates[1];
                    // Clear active state from quick filter buttons when date range is manually picked
                    filterButtons.forEach(btn => btn.classList.remove("active"));
                    // Force render with the selected date range
                    const filtered = filterTransactions(startDate, endDate);
                    renderChartsWithData(filtered);
                } else if (selectedDates.length === 0) {
                    // When date range is cleared, show all transactions
                    renderCharts(null, null);
                }
            }
        });
    } catch (error) {
        console.error('Error initializing Flatpickr:', error);
    }

    // Function to filter transactions by date range
    function filterTransactions(startDate = null, endDate = null) {
        if (!startDate || !endDate) {
            return transactions;
        }

        // Ensure dates are Date objects and valid
        const start = startDate instanceof Date && !isNaN(startDate) ? startDate : new Date(startDate);
        const end = endDate instanceof Date && !isNaN(endDate) ? endDate : new Date(endDate);

        // Set end date to end of day
        end.setHours(23, 59, 59, 999);
        
        return transactions.filter(t => {
            try {
                if (!t || !t.date) {
                    console.warn('Skipping transaction with missing date:', t);
                    return false;
                }
                const transDate = new Date(t.date);
                
                if (isNaN(transDate)) {
                    console.error('Invalid transaction date:', t.date, 'for transaction:', t);
                    return false;
                }

                return transDate >= start && transDate <= end;
            } catch (error) {
                console.error('Error processing transaction date:', error, t);
                return false;
            }
        });
    }

    // Function to get date range for quick filters
    function getDateRange(range) {
        const now = new Date();
        let startDate = new Date();
        let endDate = new Date();

        switch (range) {
            case "week":
                startDate = new Date(now);
                startDate.setDate(now.getDate() - now.getDay());
                startDate.setHours(0, 0, 0, 0);
                endDate = new Date(now);
                endDate.setHours(23, 59, 59, 999);
                break;
            case "month":
                startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                startDate.setHours(0, 0, 0, 0);
                endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                endDate.setHours(23, 59, 59, 999);
                break;
            case "year":
                startDate = new Date(now.getFullYear(), 0, 1);
                startDate.setHours(0, 0, 0, 0);
                endDate = new Date(now.getFullYear(), 11, 31);
                endDate.setHours(23, 59, 59, 999);
                break;
            case "all":
                return [null, null];
        }

        return [startDate, endDate];
    }

    // Function to calculate total income and expenses
    function calculateTotals(filtered) {
        let income = 0;
        let expenses = 0;
        
        filtered.forEach(t => {
            try {
                if (!t || typeof t.amount === 'undefined' || !t.type) {
                    console.warn('Skipping transaction with missing amount or type:', t);
                    return;
                }
                const amount = parseFloat(t.amount) || 0;
                if (t.type === "income") {
                    income += amount;
                } else if (t.type === "expense") {
                    expenses += amount;
                }
            } catch (error) {
                console.error('Error calculating total for transaction:', error, t);
            }
        });
        
        return { income, expenses };
    }

    // Function to calculate category-wise spending
    function calculateCategorySpending(filtered) {
        let categoryTotals = {};

        filtered.forEach(t => {
            try {
                if (!t || typeof t.amount === 'undefined' || t.type !== "expense") {
                     // Only process expense transactions here
                    return;
                }
                const category = t.category || "Uncategorized";
                const amount = parseFloat(t.amount) || 0;
                 if (isNaN(amount)) {
                    console.warn('Skipping transaction with invalid amount for category spending:', t);
                    return;
                }
                categoryTotals[category] = (categoryTotals[category] || 0) + amount;
            } catch (error) {
                 console.error('Error calculating category spending for transaction:', error, t);
            }
        });

        return categoryTotals;
    }

    // New function to render charts with specific data
    function renderChartsWithData(filteredTransactions) {
        const { income, expenses } = calculateTotals(filteredTransactions);
        const categoryData = calculateCategorySpending(filteredTransactions);

        // Update Income vs Expense Chart
        const incomeExpenseChart = document.getElementById("income-expense-chart");
        if (!incomeExpenseChart) {
            console.error('Income expense chart element not found');
            return;
        }

        if (window.incomeExpenseChartInstance) {
            window.incomeExpenseChartInstance.destroy();
        }

        window.incomeExpenseChartInstance = new Chart(incomeExpenseChart, {
            type: "bar",
            data: {
                labels: ["Income", "Expenses"],
                datasets: [{
                    label: `Amount (${currency})`,
                    data: [income, expenses],
                    backgroundColor: ["#4CAF50", "#F44336"],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "rgba(0, 0, 0, 0.1)"
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Update Category Spending Chart
        const categoryChart = document.getElementById("category-chart");
        if (!categoryChart) {
            console.error('Category chart element not found');
            return;
        }

        if (window.categoryChartInstance) {
            window.categoryChartInstance.destroy();
        }

        const categoryColors = [
            "#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF",
            "#FF9F40", "#FF6384", "#4CAF50", "#9C27B0", "#607D8B"
        ];

        window.categoryChartInstance = new Chart(categoryChart, {
            type: "doughnut",
            data: {
                labels: Object.keys(categoryData),
                datasets: [{
                    data: Object.values(categoryData),
                    backgroundColor: categoryColors.slice(0, Object.keys(categoryData).length),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "right",
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }

    // Modify the existing renderCharts function to use the new renderChartsWithData
    function renderCharts(startDate = null, endDate = null) {
        const filtered = filterTransactions(startDate, endDate);
        renderChartsWithData(filtered);
    }

    // Event listeners for quick filter buttons
    filterButtons.forEach(button => {
        button.addEventListener("click", () => {
            // Update active state
            filterButtons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");

            // Get and apply date range
            const [startDate, endDate] = getDateRange(button.dataset.range);
            renderCharts(startDate, endDate);
        });
    });

    // Load charts on page load
    document.addEventListener("DOMContentLoaded", async function() {
        // Load initial settings
        await loadSettings();

        // Initialize charts
        initializeCharts();
    });

    // Function to update currency symbol when changed in settings
    function updateCurrency(newCurrency) {
        currency = newCurrency;
        localStorage.setItem("currency", currency);
        renderCharts();
    }

    // Update charts when filter changes
    dateRangePicker.addEventListener("change", () => {
        const selectedDates = fp.selectedDates;
        if (selectedDates.length === 2) {
            renderCharts(selectedDates[0], selectedDates[1]);
        } else {
            renderCharts(null, null);
        }
    });

    function initializeTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.classList.remove('light-mode', 'dark-mode');
        document.body.classList.add(savedTheme === 'dark' ? 'dark-mode' : 'light-mode');
    }

    // Listen for theme changes from other pages
    window.addEventListener('themeChanged', (e) => {
        document.body.classList.remove('light-mode', 'dark-mode');
        document.body.classList.add(e.detail.theme === 'dark' ? 'dark-mode' : 'light-mode');
    });
    initializeTheme();

    // Load settings and initialize page
    async function initializeChartsPage() {
        try {
            const response = await fetch('get_settings.php');
            const settings = await response.json();
            
            // Apply theme
            document.body.classList.toggle('dark-mode', settings.theme === 'dark');
            
            // Initialize charts with currency symbol
            initializeCharts(settings.currency_symbol);
        } catch (error) {
            console.error('Error loading settings:', error);
        }
    }

    // Initialize all charts
    function initializeCharts(currencySymbol) {
        // Initialize all charts with the current currency
        initializeIncomeChart(currencySymbol);
        initializeExpenseChart(currencySymbol);
        initializeCategoryChart(currencySymbol);
    }

    // Initialize income chart
    function initializeIncomeChart(currencySymbol) {
        const ctx = document.getElementById('incomeChart');
        if (!ctx) return;

        fetch('get_income_data.php')
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Income',
                            data: data.values,
                            borderColor: '#28a745',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${currencySymbol}${context.raw}`;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error loading income data:', error));
    }

    // Initialize expense chart
    function initializeExpenseChart(currencySymbol) {
        const ctx = document.getElementById('expenseChart');
        if (!ctx) return;

        fetch('get_expense_data.php')
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Expenses',
                            data: data.values,
                            borderColor: '#dc3545',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${currencySymbol}${context.raw}`;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error loading expense data:', error));
    }

    // Initialize category chart
    function initializeCategoryChart(currencySymbol) {
        const ctx = document.getElementById('categoryChart');
        if (!ctx) return;

        fetch('get_category_data.php')
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: [
                                '#28a745',
                                '#dc3545',
                                '#ffc107',
                                '#17a2b8',
                                '#6c757d'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${context.label}: ${currencySymbol}${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error loading category data:', error));
    }

    // Function to show notifications
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}
