<?php
session_start();
require_once 'database.php';
require_once 'get_settings.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$settings_sql = "SELECT * FROM settings WHERE user_id = ?";
$settings_stmt = $conn->prepare($settings_sql);
$settings_stmt->bind_param("i", $user_id);
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();
$settings = $settings_result->fetch_assoc();

// Set default values if settings don't exist
$theme = $settings['theme'] ?? 'light';
$currency = $settings['currency_symbol'] ?? '$';

// Update session variables
$_SESSION['theme'] = $theme;
$_SESSION['currency_symbol'] = $currency;
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currency = $_POST['currency_symbol'];
    $theme = $_POST['theme'];
    
    $stmt = $conn->prepare("UPDATE settings SET currency_symbol=?, theme=?, updated_at=NOW() WHERE user_id=?");
    $stmt->bind_param("ssi", $currency, $theme, $user_id);
    $stmt->execute();

    // Update session for cross-page use
    $_SESSION['theme'] = $theme;
    $_SESSION['currency_symbol'] = $currency;

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Ensure $settings is an array with default values
$settings = $settings ?? [
    'currency_symbol' => '$',
    'theme' => 'light'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="faviconFT.png">
    <title>Settings - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="setStyle.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?php echo $theme; ?>">

    <nav class="navbar">
        <div class="nav-title">
            <img src="LogoFT33.png">
            <h2>Finance Tracker</h2>
        </div>
        
        <!-- Hamburger Menu Button -->
        <button id="menu-btn" aria-label="Toggle menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        
        <!-- Sidebar Menu -->
        <ul class="nav-links" id="nav-links">
            <button id="close-btn" aria-label="Close menu">×</button>
            <li><i class="fas fa-tachometer-alt"></i><a href="dashboard.php">Dashboard</a></li>
            <li><i class="fa-solid fa-paper-plane"></i><a href="transaction.php">Transaction</a></li>
            <li><i class="fa-solid fa-briefcase"></i><a href="budget.php">Budget</a></li>
            <li><i class="fa-solid fa-chart-column"></i><a href="reports.php">Reports</a></li>
            <li><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
            <li><i class="fas fa-right-to-bracket"></i><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <section class="settings-container">
        <h1><i class="fas fa-cog" id="set"></i> Settings</h1>

        <form method="POST" action="settings.php">
            <!-- Profile Settings -->
            <div class="settings-card">
                <div class="settings-header">
                    <i class="fas fa-user"></i>
                    <h2>Profile Settings</h2>
                </div>
                <div class="settings-content">
                    <a href="profile_update.php" class="btn btn-secondary">Update Profile</a>
                </div>
            </div>

            <!-- Preferences -->
            <div class="settings-card">
                <div class="settings-header">
                    <i class="fas fa-sliders-h"></i>
                    <h2>Preferences</h2>
                </div>
                <div class="settings-content">
                    <div class="form-group">
                        <label for="currency">Currency</label>
                        <select id="currency" name="currency_symbol">
                            <option value="Rs." <?php echo ($settings['currency_symbol'] ?? '') === 'Rs.' ? 'selected' : ''; ?>>Rs - PKR</option>
                            <option value="$" <?php echo ($settings['currency_symbol'] ?? '') === '$' ? 'selected' : ''; ?>>$ - USD</option>
                            <option value="€" <?php echo ($settings['currency_symbol'] ?? '') === '€' ? 'selected' : ''; ?>>€ - EUR</option>
                            <option value="₹" <?php echo ($settings['currency_symbol'] ?? '') === '₹' ? 'selected' : ''; ?>>₹ - INR</option>
                            <option value="£" <?php echo ($settings['currency_symbol'] ?? '') === '£' ? 'selected' : ''; ?>>£ - GBP</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Appearance -->
            <div class="settings-card">
                <div class="settings-header">
                    <i class="fas fa-paint-brush"></i>
                    <h2>Appearance</h2>
                </div>
                <div class="settings-content">
                    <div class="theme-toggle">
                        <span>Theme</span>
                        <div class="toggle-wrapper">
                            <select name="theme" id="theme">
                                <option value="light" <?php echo ($settings['theme'] ?? '') === 'light' ? 'selected' : ''; ?>>Light</option>
                                <option value="dark" <?php echo ($settings['theme'] ?? '') === 'dark' ? 'selected' : ''; ?>>Dark</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>

        <!-- Data Management -->
        <div class="settings-card danger-zone">
            <div class="settings-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Data Management</h2>
            </div>
            <div class="settings-content">
                <div class="danger-action">
                    <div class="danger-text">
                        <h3>Export Data</h3>
                        <p>Download all your financial data as CSV</p>
                    </div>
                    <form method="POST" action="export_data.php">
                        <button type="submit" id="export-data" class="btn btn-secondary">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </form>
                </div>
                <div class="danger-action">
                    <div class="danger-text">
                        <h3>Reset Data</h3>
                        <p>Delete all your transactions and budgets</p>
                    </div>
                    <button type="button" id="reset-data" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Reset All Data
                    </button>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer" id="contact">
        <p>&copy; 2025 Muzammil Faisal. All rights reserved.</p>
        <div class="social-links">
            <a href="#"><i class="fab fa-github"></i></a>
            <a href="#"><i class="fab fa-linkedin"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
        </div>
    </footer>
    
    <script>
        document.getElementById('reset-data').addEventListener('click', async function() {
            if (confirm('Are you sure you want to reset all your data? This action cannot be undone!')) {
                try {
                    const response = await fetch('reset_data.php', {
                        method: 'POST'
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

        document.getElementById('theme').addEventListener('change', function() {
            var newTheme = this.value;
            document.body.className = newTheme;
            localStorage.setItem('theme', newTheme);
        });
    </script>
    <script src="setScript.js"></script>
    <script src="nav.js"></script>
</body>
</html>
