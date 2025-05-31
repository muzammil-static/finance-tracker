// Get all elements
const currencySelect = document.getElementById('currency');
const themeSelect = document.getElementById('theme');
const startWeekSelect = document.getElementById('start-week');
const budgetAlertsToggle = document.getElementById('budget-alerts');
const monthlyReportsToggle = document.getElementById('monthly-reports');
const exportDataBtn = document.getElementById('export-data');
const resetDataBtn = document.getElementById('reset-data');

// Load saved settings
async function loadSettings() {
    try {
        const response = await fetch('get_settings.php');
        const settings = await response.json();
        
        if (settings) {
            // Apply currency setting
            if (currencySelect) {
                currencySelect.value = settings.currency_symbol || '$';
            }

            // Apply theme
            if (themeSelect) {
                themeSelect.value = settings.theme || 'light';
            }
            applyTheme(settings.theme || 'light');

            // Apply notification settings
            if (budgetAlertsToggle) {
                budgetAlertsToggle.checked = settings.budget_alerts === '1';
            }
            if (monthlyReportsToggle) {
                monthlyReportsToggle.checked = settings.monthly_reports === '1';
            }
        }
    } catch (error) {
        console.error('Error loading settings:', error);
    }
}

// Apply theme to the page
function applyTheme(theme) {
    document.body.classList.remove('light-mode', 'dark-mode');
    document.body.classList.add(theme === 'dark' ? 'dark-mode' : 'light-mode');
    
    // Dispatch event for other pages
    window.dispatchEvent(new CustomEvent('themeChanged', {
        detail: { theme: theme }
    }));
}

// Save settings to database
async function saveSetting(key, value) {
    try {
        const formData = new FormData();
        formData.append('key', key);
        formData.append('value', value);

        const response = await fetch('update_setting.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            // If theme changed, apply it immediately
            if (key === 'theme') {
                applyTheme(value);
            }
            // If currency changed, reload the page to apply changes
            else if (key === 'currency_symbol') {
                window.location.reload();
            }
        } else {
            console.error('Error saving setting:', data.message);
        }
    } catch (error) {
        console.error('Error saving setting:', error);
    }
}

// Handle currency change
function handleCurrencyChange() {
    if (currencySelect) {
        saveSetting('currency_symbol', currencySelect.value);
    }
}

// Handle theme change
function handleThemeChange() {
    if (themeSelect) {
        saveSetting('theme', themeSelect.value);
    }
}

// Handle start of week change
function handleStartWeekChange() {
    if (startWeekSelect) {
        saveSetting('start_week', startWeekSelect.value);
    }
}

// Handle notification toggles
function handleNotificationToggles() {
    if (budgetAlertsToggle) {
        saveSetting('budget_alerts', budgetAlertsToggle.checked ? '1' : '0');
    }
    if (monthlyReportsToggle) {
        saveSetting('monthly_reports', monthlyReportsToggle.checked ? '1' : '0');
    }
}

// Export data as CSV
// Export Data functionality
document.getElementById('export-data').addEventListener('click', function() {
    window.location.href = 'export_data.php';
});

// Reset all data
// Reset Data functionality
document.getElementById('reset-data').addEventListener('click', async function() {
    if (confirm('Are you sure you want to reset all your data? This action cannot be undone!')) {
        try {
            const response = await fetch('reset_data.php');
            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                // Reload page to reflect changes
                window.location.reload();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            alert('Error resetting data: ' + error.message);
        }
    }
});

// Event listeners
document.addEventListener('DOMContentLoaded', async function() {
    // Load initial settings
    await loadSettings();

    // Get form elements
    const themeToggle = document.getElementById('theme-toggle');
    const currencySelect = document.getElementById('currency-select');
    const exportBtn = document.getElementById('export-btn');
    const resetBtn = document.getElementById('reset-btn');

    // Set initial values
    if (themeToggle) {
        themeToggle.checked = window.appSettings.theme === 'dark';
    }
    if (currencySelect) {
        currencySelect.value = window.appSettings.currency_symbol;
    }

    // Theme toggle event listener
    if (themeToggle) {
        themeToggle.addEventListener('change', async function() {
            const newTheme = this.checked ? 'dark' : 'light';
            const success = await saveSetting('theme', newTheme);
            if (success) {
                showNotification('Theme updated successfully');
            } else {
                showNotification('Failed to update theme', 'error');
            }
        });
    }

    // Currency select event listener
    if (currencySelect) {
        currencySelect.addEventListener('change', async function() {
            const success = await saveSetting('currency_symbol', this.value);
            if (success) {
                showNotification('Currency updated successfully');
            } else {
                showNotification('Failed to update currency', 'error');
            }
        });
    }

    // Export data event listener
    if (exportBtn) {
        exportBtn.addEventListener('click', async function() {
            try {
                const response = await fetch('export_data.php');
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'finance_data.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                showNotification('Data exported successfully');
            } catch (error) {
                console.error('Error exporting data:', error);
                showNotification('Failed to export data', 'error');
            }
        });
    }

    // Reset data event listener
    if (resetBtn) {
        resetBtn.addEventListener('click', async function() {
            if (confirm('Are you sure you want to reset all data? This action cannot be undone.')) {
                try {
                    const response = await fetch('reset_data.php', {
                        method: 'POST'
                    });
                    const result = await response.json();
                    if (result.success) {
                        showNotification('Data reset successfully');
                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        throw new Error(result.message || 'Failed to reset data');
                    }
                } catch (error) {
                    console.error('Error resetting data:', error);
                    showNotification('Failed to reset data', 'error');
                }
            }
        });
    }
});

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

// Request notification permission on page load
if ('Notification' in window) {
    Notification.requestPermission();
}

// Confirmation modal for reset data
document.addEventListener('DOMContentLoaded', function() {
    // Export Data Handler
    const exportBtn = document.getElementById('export-data');
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'export_data.php';
        });
    }

    // Reset Data Handler
    const resetBtn = document.getElementById('reset-data');
    const modal = document.getElementById('confirmation-modal');
    const confirmBtn = document.getElementById('confirm-reset');
    const cancelBtn = document.getElementById('cancel-reset');

    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = 'block';
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function() {
            try {
                const response = await fetch('reset_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    alert('All data has been reset successfully');
                    window.location.reload();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                alert('Error resetting data: ' + error.message);
            } finally {
                modal.style.display = 'none';
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Handle reset data functionality
document.addEventListener('DOMContentLoaded', function() {
    const resetBtn = document.getElementById('reset-data');
    const modal = document.getElementById('confirmation-modal');
    const confirmBtn = document.getElementById('confirm-reset');
    const cancelBtn = document.getElementById('cancel-reset');

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            modal.style.display = 'block';
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function() {
            try {
                const response = await fetch('reset_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    window.location.reload();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert('Error resetting data: ' + error.message);
            } finally {
                modal.style.display = 'none';
            }
        });
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const resetBtn = document.getElementById('reset-data');
    
    if (resetBtn) {
        resetBtn.addEventListener('click', async function() {
            if (confirm('Are you sure you want to reset all your data? This action cannot be undone!')) {
                try {
                    const response = await fetch('reset_data.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(result.message);
                        window.location.reload();
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    alert('Error resetting data: ' + error.message);
                }
            }
        });
    }
});
