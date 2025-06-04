<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM budgets WHERE budget_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_GET['id'], $_SESSION['user_id']);
    
    echo json_encode(['success' => $stmt->execute()]);
}
?>