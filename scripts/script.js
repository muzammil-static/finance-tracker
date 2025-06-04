// Sample transaction data
// let transactions = [
//     { date: "2025-03-01", description: "Salary", amount: 1000, type: "income" },
//     { date: "2025-03-02", description: "Groceries", amount: -200, type: "expense" },
//     { date: "2025-03-03", description: "Rent", amount: -500, type: "expense" }
// ];

// // function getTransactions() {
// //     return JSON.parse(localStorage.getItem("transactions")) || [];
// // }

// function getCurrencySymbol() {
//     return localStorage.getItem('currency') || '$'; // Default to USD symbol
// }

// function setCurrencySymbol(symbol) {
//     localStorage.setItem('currency', symbol);
//     updateDashboard(); // Update the dashboard to reflect the new currency
// }

// function updateDashboard() {
//     let transactions = getTransactions();
//     let currencySymbol = getCurrencySymbol();
    
//     let income = transactions.filter(t => t.type === "income")
//                            .reduce((sum, t) => sum + t.amount, 0);
    
//     let expenses = transactions.filter(t => t.type === "expense")
//                             .reduce((sum, t) => sum + t.amount, 0);

//     let balance = income - expenses;

//     // Update UI with the current currency symbol
//     document.getElementById("total-balance").textContent = `${currencySymbol}${balance.toFixed(2)}`;
//     document.getElementById("total-income").textContent = `${currencySymbol}${income.toFixed(2)}`;
//     document.getElementById("total-expenses").textContent = `${currencySymbol}${expenses.toFixed(2)}`;
// }

// function displayTransactions() {
//     let transactions = getTransactions();
//     let transactionTable = document.getElementById("recent-transactions");
//     let currencySymbol = getCurrencySymbol(); // Get the current currency symbol

//     transactionTable.innerHTML = ""; // Clear previous rows

//     transactions.slice(-5).forEach(transaction => {  // Show last 5 transactions
//         let row = document.createElement("tr");
        
//         // Format the date properly
//         let transactionDate = transaction.date ? new Date(transaction.date) : new Date();
//         let formattedDate = transactionDate.toLocaleDateString('en-US', {
//             year: 'numeric',
//             month: '2-digit',
//             day: '2-digit'
//         });

//         row.innerHTML = `
//             <td>${formattedDate}</td>
//             <td>${transaction.description}</td>
//             <td style="color: ${transaction.type === 'income' ? 'green' : 'red'};">
//                 ${currencySymbol}${transaction.amount.toFixed(2)}
//             </td>
//             <td>${transaction.type}</td>
//         `;

//         transactionTable.appendChild(row);
//     });
// }

// Theme Management
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);
}

function applyTheme(theme) {
    document.body.classList.remove('light-mode', 'dark-mode');
    document.body.classList.add(theme === 'dark' ? 'dark-mode' : 'light-mode');
    localStorage.setItem('theme', theme);
}

// Listen for theme changes from other pages
window.addEventListener('themeChanged', (e) => {
    applyTheme(e.detail.theme);
});

// Add storage event listener for theme changes
window.addEventListener("storage", (e) => {
    if (e.key === 'theme') {
        applyTheme(e.newValue);
    }
});

// Function to initialize the currency setting
function initializeCurrency() {
    const currencySelect = document.getElementById('currencySelect');
    if (currencySelect) {
        currencySelect.value = getCurrencySymbol();
        currencySelect.addEventListener('change', (event) => {
            setCurrencySymbol(event.target.value);
        });
    }
}

// Update initHomePage to include mobile menu initialization
function initHomePage() {
    // updateDashboard();
    // displayTransactions();
    initializeTheme();
    initializeCurrency();
    initMobileMenu(); // Add mobile menu initialization
}

// Initialize when the page loads
window.onload = initHomePage;

// Load settings and initialize page
async function initializePage() {
    try {
        const response = await fetch('get_settings.php');
        const settings = await response.json();
        
        // Apply theme
        applyTheme(settings.theme || 'light');
        
        // Update currency displays
        updateCurrencyDisplays(settings.currency_symbol);
        
        // Initialize other functionality
        initializeNavigation();
        loadDashboardData();
    } catch (error) {
        console.error('Error loading settings:', error);
    }
}

// Update all currency displays on the page
function updateCurrencyDisplays(currencySymbol) {
    // Update balance
    const balance = document.querySelector('.balance');
    if (balance) {
        const amount = balance.getAttribute('data-amount');
        if (amount) {
            balance.textContent = `${currencySymbol}${amount}`;
        }
    }

    // Update income
    const income = document.querySelector('.income');
    if (income) {
        const amount = income.getAttribute('data-amount');
        if (amount) {
            income.textContent = `${currencySymbol}${amount}`;
        }
    }

    // Update expenses
    const expenses = document.querySelector('.expenses');
    if (expenses) {
        const amount = expenses.getAttribute('data-amount');
        if (amount) {
            expenses.textContent = `${currencySymbol}${amount}`;
        }
    }

    // Update recent transactions
    document.querySelectorAll('.transaction-amount').forEach(el => {
        const amount = el.getAttribute('data-amount');
        if (amount) {
            el.textContent = `${currencySymbol}${amount}`;
        }
    });
}

// Initialize navigation
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', handleNavigation);
    });
}

// Handle navigation
function handleNavigation(event) {
    const target = event.target.getAttribute('data-target');
    if (target) {
        window.location.href = target;
    }
}

// Load dashboard data
async function loadDashboardData() {
    try {
        const response = await fetch('get_dashboard_data.php');
        const data = await response.json();
        updateDashboard(data);
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

// Update dashboard
function updateDashboard(data) {
    // Update summary cards
    const balance = document.querySelector('.balance');
    if (balance) {
        balance.setAttribute('data-amount', data.balance);
        balance.textContent = data.balance;
    }

    const income = document.querySelector('.income');
    if (income) {
        income.setAttribute('data-amount', data.income);
        income.textContent = data.income;
    }

    const expenses = document.querySelector('.expenses');
    if (expenses) {
        expenses.setAttribute('data-amount', data.expenses);
        expenses.textContent = data.expenses;
    }

    // Update recent transactions
    const transactionsList = document.querySelector('.recent-transactions');
    if (transactionsList) {
        transactionsList.innerHTML = '';
        data.recentTransactions.forEach(transaction => {
            const element = createTransactionElement(transaction);
            transactionsList.appendChild(element);
        });
    }
}

// Create transaction element
function createTransactionElement(transaction) {
    const div = document.createElement('div');
    div.className = 'transaction-item';
    div.innerHTML = `
        <span class="transaction-date">${transaction.date}</span>
        <span class="transaction-description">${transaction.description}</span>
        <span class="transaction-amount" data-amount="${transaction.amount}">
            ${transaction.amount}
        </span>
        <span class="transaction-type">${transaction.type}</span>
    `;
    return div;
}

// Initialize page when loaded
document.addEventListener('DOMContentLoaded', async function() {
    // Load initial settings
    await loadSettings();

    // Get dashboard elements
    const balanceAmount = document.getElementById('balance-amount');
    const incomeAmount = document.getElementById('income-amount');
    const expenseAmount = document.getElementById('expense-amount');
    const recentTransactions = document.getElementById('recent-transactions');

    // Update currency displays
    updateCurrencyDisplays();

    // Update recent transactions
    if (recentTransactions) {
        try {
            const response = await fetch('get_recent_transactions.php');
            const transactions = await response.json();
            
            if (transactions.length === 0) {
                recentTransactions.innerHTML = '<p class="no-data">No recent transactions</p>';
            } else {
                recentTransactions.innerHTML = transactions.map(transaction => `
                    <div class="transaction-item">
                        <div class="transaction-info">
                            <span class="transaction-title">${transaction.title}</span>
                            <span class="transaction-date">${transaction.date}</span>
                        </div>
                        <span class="transaction-amount ${transaction.type}">
                            ${transaction.type === 'income' ? '+' : '-'}${window.appSettings.currency_symbol}${transaction.amount}
                        </span>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Error fetching recent transactions:', error);
            recentTransactions.innerHTML = '<p class="error">Failed to load recent transactions</p>';
        }
    }
});

// Function to update all currency displays
function updateCurrencyDisplays() {
    const currencyElements = document.querySelectorAll('.currency-display');
    currencyElements.forEach(element => {
        const amount = element.getAttribute('data-amount');
        if (amount !== null) {
            element.textContent = `${window.appSettings.currency_symbol}${amount}`;
        }
    });
}