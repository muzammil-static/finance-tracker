// Get elements
const form = document.getElementById("transaction-form");
const transactionsList = document.getElementById("transactions-list");

// Function to handle edit button click
function editTransaction(id, category, amount, type) {
    // Fill the form with transaction data
    document.getElementById('transaction_id').value = id;
    document.getElementById('category').value = category;
    document.getElementById('amount').value = amount;
    document.getElementById('type').value = type;
    
    // Change button text
    document.getElementById('submit-btn').textContent = 'Update Transaction';
    
    // Show cancel button
    document.getElementById('cancel-btn').style.display = 'inline-block';
    
    // Scroll to form
    document.getElementById('transaction-form').scrollIntoView();
}

function cancelEdit() {
    // Reset form
    document.getElementById('transaction-form').reset();
    document.getElementById('transaction_id').value = '';
    document.getElementById('submit-btn').textContent = 'Add Transaction';
    document.getElementById('cancel-btn').style.display = 'none';
}

// Mobile Menu Functions
function initMobileMenu() {
    const menuBtn = document.getElementById('menu-btn');
    const closeBtn = document.getElementById('close-btn');
    const navLinks = document.getElementById('nav-links');

    if (menuBtn && closeBtn && navLinks) {
        menuBtn.addEventListener('click', () => {
            navLinks.classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
        });

        closeBtn.addEventListener('click', () => {
            navLinks.classList.remove('show');
            document.body.style.overflow = ''; // Restore scrolling
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (navLinks.classList.contains('show') && 
                !navLinks.contains(e.target) && 
                !menuBtn.contains(e.target)) {
                navLinks.classList.remove('show');
                document.body.style.overflow = '';
            }
        });

        // Close menu when pressing Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && navLinks.classList.contains('show')) {
                navLinks.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    }
}

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

// Initialize theme and mobile menu
initializeTheme();
initMobileMenu();

// Add event listener for form submission
document.getElementById('transaction-form').addEventListener('submit', function(e) {
    // Add any form validation here if needed
    return true;
});

// Load settings and initialize page
async function initializeTransactionPage() {
    try {
        const response = await fetch('get_settings.php');
        const settings = await response.json();
        
        // Apply theme
        applyTheme(settings.theme || 'light');
        
        // Update currency displays
        updateCurrencyDisplays(settings.currency_symbol);
        
        // Initialize other transaction functionality
        initializeTransactionForm();
        loadTransactions();
    } catch (error) {
        console.error('Error loading settings:', error);
    }
}

// Apply theme to the page
function applyTheme(theme) {
    document.body.classList.remove('light-mode', 'dark-mode');
    document.body.classList.add(theme === 'dark' ? 'dark-mode' : 'light-mode');
}

// Listen for theme changes from other pages
window.addEventListener('themeChanged', (e) => {
    applyTheme(e.detail.theme);
});

// Update all currency displays on the page
function updateCurrencyDisplays(currencySymbol) {
    // Update transaction amounts
    document.querySelectorAll('.transaction-amount').forEach(el => {
        const amount = el.getAttribute('data-amount');
        if (amount) {
            el.textContent = `${currencySymbol}${amount}`;
        }
    });

    // Update totals
    const incomeTotal = document.querySelector('.income-total');
    if (incomeTotal) {
        const amount = incomeTotal.getAttribute('data-amount');
        if (amount) {
            incomeTotal.textContent = `${currencySymbol}${amount}`;
        }
    }

    const expenseTotal = document.querySelector('.expense-total');
    if (expenseTotal) {
        const amount = expenseTotal.getAttribute('data-amount');
        if (amount) {
            expenseTotal.textContent = `${currencySymbol}${amount}`;
        }
    }

    const balance = document.querySelector('.balance');
    if (balance) {
        const amount = balance.getAttribute('data-amount');
        if (amount) {
            balance.textContent = `${currencySymbol}${amount}`;
        }
    }
}

// Initialize transaction form
function initializeTransactionForm() {
    const form = document.getElementById('transaction-form');
    if (form) {
        form.addEventListener('submit', handleTransactionSubmit);
    }
}

// Handle transaction form submission
async function handleTransactionSubmit(event) {
    event.preventDefault();
    // Your existing transaction submission code
}

// Load transactions
async function loadTransactions() {
    try {
        const response = await fetch('get_transactions.php');
        const transactions = await response.json();
        displayTransactions(transactions);
    } catch (error) {
        console.error('Error loading transactions:', error);
    }
}

// Display transactions
function displayTransactions(transactions) {
    const container = document.querySelector('.transactions-list');
    if (!container) return;

    container.innerHTML = '';
    transactions.forEach(transaction => {
        const element = createTransactionElement(transaction);
        container.appendChild(element);
    });
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

// Replace the form submission event listener with this updated version
document.getElementById('transaction-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    try {
        const response = await fetch('add_transaction.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message);
            this.reset();
            document.getElementById('transaction_id').value = '';
            document.getElementById('submit-btn').textContent = 'Add Transaction';
            document.getElementById('cancel-btn').style.display = 'none';
            await loadTransactions(); // Reload the transactions list
        } else {
            throw new Error(result.message || 'Failed to process transaction');
        }
    } catch (error) {
        console.error('Error processing transaction:', error);
        showNotification(error.message, 'error');
    }
});

// Initialize page when loaded
document.addEventListener('DOMContentLoaded', async function() {
    // Load initial settings
    await loadSettings();

    // Get transaction form elements
    const transactionForm = document.getElementById('transaction-form');
    const transactionList = document.getElementById('transaction-list');
    const transactionType = document.getElementById('transaction-type');
    const amountInput = document.getElementById('amount');
    const descriptionInput = document.getElementById('description');
    const dateInput = document.getElementById('date');

    // Update currency displays
    updateCurrencyDisplays();

    // Load transactions
    loadTransactions();

    // Form submit event listener
    if (transactionForm) {
        transactionForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            try {
                const response = await fetch('add_transaction.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Transaction added successfully');
                    this.reset();
                    loadTransactions();
                } else {
                    throw new Error(result.message || 'Failed to add transaction');
                }
            } catch (error) {
                console.error('Error adding transaction:', error);
                showNotification('Failed to add transaction', 'error');
            }
        });
    }
});

// Function to load transactions
async function loadTransactions() {
    const transactionList = document.getElementById('transaction-list');
    if (!transactionList) return;

    try {
        const response = await fetch('get_transactions.php');
        const transactions = await response.json();
        
        if (transactions.length === 0) {
            transactionList.innerHTML = '<p class="no-data">No transactions found</p>';
        } else {
            transactionList.innerHTML = transactions.map(transaction => `
                <div class="transaction-item">
                    <div class="transaction-info">
                        <span class="transaction-title">${transaction.description}</span>
                        <span class="transaction-date">${transaction.date}</span>
                    </div>
                    <div class="transaction-actions">
                        <span class="transaction-amount ${transaction.type}">
                            ${transaction.type === 'income' ? '+' : '-'}${window.appSettings.currency_symbol}${transaction.amount}
                        </span>
                        <button class="delete-btn" data-id="${transaction.id}">Delete</button>
                    </div>
                </div>
            `).join('');

            // Add delete event listeners
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', async function() {
                    const id = this.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this transaction?')) {
                        try {
                            const response = await fetch('delete_transaction.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ id })
                            });
                            const result = await response.json();
                            
                            if (result.success) {
                                showNotification('Transaction deleted successfully');
                                loadTransactions();
                            } else {
                                throw new Error(result.message || 'Failed to delete transaction');
                            }
                        } catch (error) {
                            console.error('Error deleting transaction:', error);
                            showNotification('Failed to delete transaction', 'error');
                        }
                    }
                });
            });
        }
    } catch (error) {
        console.error('Error loading transactions:', error);
        transactionList.innerHTML = '<p class="error">Failed to load transactions</p>';
    }
}

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
