<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('database.php');
$user_id = $_SESSION['user_id'];

// Get settings from database
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

// Fetch user data for dashboard
// Fetch recent 5 transactions
$recent_sql = "SELECT * FROM transactions WHERE user_id=? ORDER BY transaction_date DESC LIMIT 5";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();

// Calculate totals
$total_income = 0;
$total_expense = 0;

$totals_sql = "SELECT type, COALESCE(SUM(amount), 0) AS total FROM transactions WHERE user_id=? GROUP BY type";
$totals_stmt = $conn->prepare($totals_sql);
$totals_stmt->bind_param("i", $user_id);
$totals_stmt->execute();
$totals_result = $totals_stmt->get_result();

while ($row = $totals_result->fetch_assoc()) {
    if ($row['type'] === 'income') {
        $total_income = floatval($row['total']);
    } elseif ($row['type'] === 'expense') {
        $total_expense = floatval($row['total']);
    }
}
$balance = $total_income - $total_expense;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="faviconFT.png">
    <title>Finance Tracker - Dashboard</title>
    <link rel="stylesheet" href="style.css">
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
    
    <!-- Dashboard Section -->
    <section class="dashboard">
        <!-- Summary Boxes -->
        <div class="summary">
            <div class="box">
                <h2>Total Balance<i class="fas fa-wallet"></i></h2>
                <p class="balance" data-amount="<?php echo number_format($balance, 2); ?>">
                    <?php echo $currency . number_format($balance, 2); ?>
                </p>
            </div>
            <div class="box">
                <h2>Total Income<i class="fas fa-hand-holding-usd"></i></h2>
                <p class="income" data-amount="<?php echo number_format($total_income, 2); ?>">
                    <?php echo $currency . number_format($total_income, 2); ?>
                </p>
            </div>
            <div class="box">
                <h2>Total Expenses<i class="fas fa-money-check-alt"></i></h2>
                <p class="expenses" data-amount="<?php echo number_format($total_expense, 2); ?>">
                    <?php echo $currency . number_format($total_expense, 2); ?>
                </p>
            </div>
        </div>

        <!-- Recent Transactions -->
        <h2 id="table-heading">Recent Transactions</h2>
        <table id="transaction-list">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody id="recent-transactions">
                <?php if ($recent_result->num_rows > 0): ?>
                    <?php while($row = $recent_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['transaction_date']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td class="transaction-amount" data-amount="<?php echo $row['amount']; ?>" style="color: <?php echo ($row['type'] === 'income' ? 'green' : 'red'); ?>">
                            <?php echo $currency . $row['amount']; ?>
                        </td>
                        <td><?php echo $row['type']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="4">No recent transactions found.</td>
                </tr>
                <?php endif; ?>
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
    <script>
        document.getElementById('theme').addEventListener('change', function() {
            document.body.className = this.value;
        });

        window.addEventListener('storage', function(event) {
            if (event.key === 'theme') {
                document.body.className = event.newValue;
            }
        });

        // On page load, apply theme from localStorage if available
        if (localStorage.getItem('theme')) {
            document.body.className = localStorage.getItem('theme');
        }
    </script>
    <script src="script.js"></script>
    <script src="nav.js"></script>
</body>
</html>
