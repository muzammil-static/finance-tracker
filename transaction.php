<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('database.php');
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
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $transaction_date = date('Y-m-d H:i:s');
    
    if (!empty($_POST['transaction_id'])) {
        // Update existing transaction
        $stmt = $conn->prepare("UPDATE transactions SET amount=?, type=?, category=? WHERE transaction_id=? AND user_id=?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $transaction_id = $_POST['transaction_id'];
        $stmt->bind_param("dssii", $amount, $type, $category, $transaction_id, $user_id);
    } else {
        // Insert new transaction
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, category, transaction_date) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("idsss", $user_id, $amount, $type, $category, $transaction_date);
    }

    if ($stmt->execute()) {
        header("Location: transaction.php?message=" . urlencode($message));
        exit();
    } else {
        $message = "Error: " . $stmt->error;
        header("Location: transaction.php?message=" . urlencode($message));
        exit();
    }
    $stmt->close();
}

// Fetch transactions
$sql = "SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="faviconFT.png">
    <title>Transactions - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="transStyle.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        #cancel-btn{
            color: white;
            margin-top: 10px;
            background-color: #f44336;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        #cancel-btn:hover{
            background-color: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(244, 67, 54, 0.2);
        }
    </style>    
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

    <section class="transactions">
        <h1>Manage Transactions</h1>

        <?php if (isset($_GET['message'])): ?>
            <div class="message"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>

        <!-- Transaction Form -->
        <form action="transaction.php" method="POST" id="transaction-form">
            <input type="hidden" id="transaction_id" name="transaction_id" value="">
            <input type="text" id="category" name="category" placeholder="Category" required>
            <input type="number" id="amount" name="amount" placeholder="Amount" step="0.01" required>
            <select id="type" name="type" required>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>
            <div class="button-group">
                <button type="submit" id="submit-btn">Add Transaction</button>
                <button type="button" id="cancel-btn" style="display:none;" onclick="cancelEdit()">Cancel</button>
            </div>
        </form>

        <!-- Transactions Table -->
        <h2>Transaction History</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="transactions-list">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td style="color: <?php echo ($row['type'] === 'income' ? 'green' : 'red'); ?>"><?php echo $currency . number_format($row['amount'], 2); ?></td>
                                <td><?php echo ucfirst($row['type']);?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="edit" onclick="editTransaction(<?= $row['transaction_id'] ?>, '<?= htmlspecialchars($row['category']) ?>', <?= $row['amount'] ?>, '<?= $row['type'] ?>')">Edit</button>
                                        <form action="delete_transaction.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="delete_id" value="<?= $row['transaction_id'] ?>">
                                            <button class="delete" type="submit" onclick="return confirm('Are you sure you want to delete this transaction?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

    <script src="transScript.js"></script>
    <script src="nav.js"></script>
</body>
</html>
