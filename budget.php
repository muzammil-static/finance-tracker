<?php
session_start();

require_once 'database.php';
// Check if user is logged in
if(!isset($_SESSION['user_id'])){
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
// Fetch user budget data
$stmt = $conn->prepare("
    SELECT 
        b.*,
        COALESCE(SUM(t.amount), 0) as amount_spent
    FROM budgets b
    LEFT JOIN transactions t ON b.category = t.category AND b.user_id = t.user_id
    WHERE b.user_id = ?
    GROUP BY b.budget_id, b.category, b.limit_amount, b.user_id
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="faviconFT.png">
    <title>Budget - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="budgStyle.css">
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

    <section class="budget">
        <h1>Manage Budget</h1>

        <?php if (isset($_GET['message'])): ?>
            <div class="message"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>

        <!-- Budget Form -->
        <form id="budget-form" action="add_budget.php" method="POST">
            <input type="hidden" id="budget_id" name="budget_id" value="">
            <input type="text" id="category" name="category" placeholder="Category (e.g., Food, Rent)" required>
            <input type="number" id="budget-amount" name="limit_amount" placeholder="Budget Amount" step="0.01" required>
            <button type="submit" id="submit-btn">Set Budget</button>
            <button type="button" id="cancel-btn" style="display:none" onclick="cancelEdit()">Cancel</button>
        </form>

        <!-- Budget Table -->
        <h2>Budget Overview</h2>
        <table>
    <thead>
        <tr>
            <th>Category</th>
            <th>Budget Limit</th>
            <th>Amount Spent</th>
            <th>Remaining</th>
            <th>Progress</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="budget-list">
        <!-- Rows will be dynamically inserted here -->
    </tbody>
</table>
    </section>
    <footer class="footer" id="contact">
        <p>&copy; 2025 Muzammil Faisal. All rights reserved.</p>
        <div class="social-links">
            <a href="#"><i class="fab fa-github"></i></a>
            <a href="#"><i class="fab fa-linkedin"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
        </div>
    </footer>

    <script src="budgScript.js"></script>
    <script src="nav.js"></script>
</body>
</html>
