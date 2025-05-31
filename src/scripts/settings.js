// Function to load settings from the server
async function loadSettings() {
    try {
        const response = await fetch('get_settings.php');
        const settings = await response.json();
        
        // Apply theme
        applyTheme(settings.theme);
        
        // Update all currency symbols on the page
        document.querySelectorAll('.currency-symbol').forEach(el => {
            el.textContent = settings.currency_symbol;
        });

        // Store settings in a global variable for other scripts to use
        window.appSettings = settings;
        
        return settings;
    } catch (error) {
        console.error('Error loading settings:', error);
        // Apply default settings if there's an error
        window.appSettings = {
            currency_symbol: '$',
            theme: 'light'
        };
        return window.appSettings;
    }
}

// Function to update currency display
function updateCurrencyDisplay(element, amount) {
    if (element && amount !== undefined) {
        const settings = window.appSettings || { currency_symbol: '$' };
        element.textContent = settings.currency_symbol + parseFloat(amount).toFixed(2);
    }
}

// Function to apply theme
function applyTheme(theme) {
    // Remove existing theme classes
    document.body.classList.remove('light', 'dark');
    // Add new theme class
    document.body.classList.add(theme);
    // Store theme in localStorage for persistence
    localStorage.setItem('theme', theme);
}

// Function to update all currency displays on the page
function updateAllCurrencyDisplays() {
    const currencyElements = document.querySelectorAll('[data-amount]');
    currencyElements.forEach(element => {
        const amount = element.getAttribute('data-amount');
        updateCurrencyDisplay(element, amount);
    });
}

// Function to save settings
async function saveSetting(key, value) {
    try {
        const response = await fetch('update_setting.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                key: key,
                value: value
            })
        });

        if (!response.ok) {
            throw new Error('Failed to save setting');
        }

        const result = await response.json();
        
        if (result.success) {
            // Update local settings
            if (window.appSettings) {
                window.appSettings[key] = value;
            }

            // If theme or currency was changed, update the display
            if (key === 'theme') {
                applyTheme(value);
            } else if (key === 'currency_symbol') {
                updateAllCurrencyDisplays();
            }

            return true;
        } else {
            throw new Error(result.message || 'Failed to save setting');
        }
    } catch (error) {
        console.error('Error saving setting:', error);
        return false;
    }
}

// Initialize settings when the page loads
document.addEventListener('DOMContentLoaded', async function() {
    // Load settings from server
    await loadSettings();

    // Add event listeners for theme toggle if it exists
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('change', function() {
            const newTheme = this.checked ? 'dark' : 'light';
            saveSetting('theme', newTheme);
        });
    }

    // Add event listeners for currency select if it exists
    const currencySelect = document.getElementById('currency-select');
    if (currencySelect) {
        currencySelect.addEventListener('change', function() {
            saveSetting('currency_symbol', this.value);
        });
    }
}); 