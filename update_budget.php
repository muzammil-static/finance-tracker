<?php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $stmt = $conn->prepare("UPDATE budgets SET category = ?, limit_amount = ? WHERE budget_id = ? AND user_id = ?");
    $stmt->bind_param("sdii", $_POST['category'], $_POST['limit_amount'], $_GET['id'], $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        header("Location: budget.php?message=Budget updated successfully");
    } else {
        header("Location: budget.php?error=Failed to update budget");
    }
    exit();
}
?>