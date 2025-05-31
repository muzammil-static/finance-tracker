<?php
session_start();

require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's transactions from database
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

// First, let's check the table structure
$structure_query = "DESCRIBE transactions";
$structure_result = $conn->query($structure_query);

$transactions_query = "SELECT 
    user_id,
    amount,
    transaction_date as date,
    category,
    type
FROM transactions 
WHERE user_id = ? 
ORDER BY transaction_date DESC";

try {
    $stmt = $conn->prepare($transactions_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    
    // Convert transactions to JSON for JavaScript
    $transactions_json = json_encode($transactions, JSON_PRETTY_PRINT);
    if ($transactions_json === false) {
        throw new Exception("JSON encode failed: " . json_last_error_msg());
    }
    
} catch (Exception $e) {
    error_log("Error in reports.php: " . $e->getMessage());
    $transactions = [];
    $transactions_json = json_encode([]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="faviconFT.png">
    <title>Reports - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="reportStyle.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

    <section class="reports">
        <h1>Financial Reports</h1>

        <!-- Hidden inputs for JavaScript data -->
        <input type="hidden" id="transactions-data" value='<?php echo htmlspecialchars($transactions_json); ?>'>
        <input type="hidden" id="user-currency" value="<?php echo $currency; ?>">

        <div class="report-filters">
            <!-- Date Range Picker -->
            <div class="date-range-picker">
                <input type="text" id="date-range" placeholder="Select date range" readonly>
            </div>

            <!-- Quick Date Filters -->
            <div class="quick-filters">
                <button class="filter-btn" data-range="all">All Time</button>
                <button class="filter-btn" data-range="month">This Month</button>
                <button class="filter-btn" data-range="week">This Week</button>
                <button class="filter-btn" data-range="year">This Year</button>
            </div>
        </div>

        <!-- Income vs Expense Chart -->
        <div class="report-section">
            <div class="report-header">
                <h2>Income vs Expenses</h2>
            </div>
            <div class="report-content">
                <canvas id="income-expense-chart"></canvas>
            </div>
        </div>

        <!-- Category-wise Spending Chart -->
        <div class="report-section">
            <div class="report-header">
                <h2>Spending by Category</h2>
            </div>
            <div class="report-content">
                <canvas id="category-chart"></canvas>
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

    <script src="settings.js"></script>
    <script src="charts.js"></script>
    <script src="reportsScript.js"></script>
    <script src="nav.js"></script>
</body>
</html>
