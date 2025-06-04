<?php
// budget.php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $category = $_POST['category'];
    $limit_amount = $_POST['limit_amount'];
    
    // Check if it's an update or new budget
    if (!empty($_POST['budget_id'])) {
        $stmt = $conn->prepare("UPDATE budgets SET category=?, limit_amount=? WHERE budget_id=? AND user_id=?");
        $stmt->bind_param("sdii", $category, $limit_amount, $_POST['budget_id'], $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO budgets (user_id, category, limit_amount, amount_spent, created_at) VALUES (?, ?, ?, 0, NOW())");
        $stmt->bind_param("isd", $user_id, $category, $limit_amount);
    }
    
    if ($stmt->execute()) {
        header("Location: budget.php?message=" . urlencode("Budget " . (empty($_POST['budget_id']) ? "added" : "updated") . " successfully"));
    } else {
        header("Location: budget.php?error=" . urlencode("Failed to " . (empty($_POST['budget_id']) ? "add" : "update") . " budget"));
    }
    exit();
}
?>