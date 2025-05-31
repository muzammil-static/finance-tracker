// On page load, apply theme from localStorage if available
if (localStorage.getItem('theme')) {
    document.body.className = localStorage.getItem('theme');
}
// Function to fetch budget data and render the table
async function loadAndRenderBudgets() {
    try {
        // First fetch settings to get currency symbol
        const settingsResponse = await fetch('get_settings.php');
        if (!settingsResponse.ok) throw new Error('Failed to load settings');
        const settings = await settingsResponse.json();
        const currencySymbol = settings.currency_symbol || '$';

        // Then fetch budgets
        const response = await fetch('get_budget.php');
        if (!response.ok) throw new Error('Network response was not ok');
        const budgets = await response.json();

        renderBudgetTableFromJSON(budgets, currencySymbol);
    } catch (error) {
        console.error('Failed to load budgets:', error);
        document.getElementById('budget-list').innerHTML = `
            <tr><td colspan="6" style="text-align:center; color: red;">Failed to load budgets.</td></tr>
        `;
    }
}

// Function to render budgets inside the table body
function renderBudgetTableFromJSON(budgets, currency) {
    const budgetList = document.getElementById('budget-list');
    budgetList.innerHTML = ''; // Clear existing rows

    if (!budgets.length) {
        budgetList.innerHTML = `
            <tr><td colspan="6" style="text-align:center;">No budgets found.</td></tr>
        `;
        return;
    }

    budgets.forEach(budget => {
        const amount = parseFloat(budget.amount);
        const spent = parseFloat(budget.spent);
        const remaining = amount - spent;
        let percentage = (spent / amount) * 100;
        percentage = Math.min(100, Math.max(0, percentage));

        // Progress bar color logic
        let progressColor = 'green';
        if (percentage >= 90) progressColor = 'red';
        else if (percentage >= 70) progressColor = 'orange';

        // Create a new table row
        const row = document.createElement('tr');

        row.innerHTML = `
            <td>${escapeHtml(budget.category)}</td>
            <td class="currency-display" data-amount="${amount}">${currency}${amount.toFixed(2)}</td>
            <td class="currency-display" data-amount="${spent}">${currency}${spent.toFixed(2)}</td>
            <td class="currency-display" data-amount="${remaining}">${currency}${remaining.toFixed(2)}</td>
            <td>
                <div class="progress-bar" style="background: #e0e0e0; width: 100%; border-radius: 5px; position: relative;">
                    <div class="progress" style="width: ${percentage}%; background-color: ${progressColor}; height: 18px; border-radius: 5px;"></div>
                    <span style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); color: #000; font-weight: bold; text-shadow: 0 0 2px white;">${Math.round(percentage)}%</span>
                </div>
            </td>
            <td>
                <button onclick="editBudget(${budget.budget_id})" class="edit-btn">Edit</button>
                <button onclick="deleteBudget(${budget.budget_id})" class="delete-btn">Delete</button>
            </td>
        `;

        budgetList.appendChild(row);
    });
}

// Simple escape function to prevent XSS in category text
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Add event listener for theme changes
document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    loadAndRenderBudgets();

    // Listen for theme changes
    window.addEventListener('storage', function(e) {
        if (e.key === 'theme') {
            document.body.className = e.newValue;
        }
    });
});

// Placeholder functions for edit/delete buttons (you can fill these in)
function editBudget(budgetId) {
    fetch(`get_budget.php?id=${budgetId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('budget_id').value = data.budget_id;
            document.getElementById('category').value = data.category;
            document.getElementById('budget-amount').value = data.limit_amount;
            document.getElementById('submit-btn').textContent = 'Update Budget';
            document.getElementById('cancel-btn').style.display = 'inline-block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load budget details');
        });
}

function cancelEdit() {
    document.getElementById('budget-form').reset();
    document.getElementById('budget_id').value = '';
    document.getElementById('submit-btn').textContent = 'Set Budget';
    document.getElementById('cancel-btn').style.display = 'none';
}

function deleteBudget(id) {
    if (confirm('Are you sure you want to delete this budget?')) {
        // Example delete request
        fetch(`delete_budget.php?id=${id}`, {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Budget deleted.');
                loadAndRenderBudgets(); // refresh table
            } else {
                alert('Failed to delete budget.');
            }
        })
        .catch(() => alert('Error deleting budget.'));
    }
}
