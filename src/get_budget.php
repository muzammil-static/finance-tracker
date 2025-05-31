<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM budgets WHERE budget_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_GET['id'], $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_assoc());
} else {
    $query = "
        SELECT 
            b.budget_id, 
            b.category, 
            b.limit_amount as amount, 
            COALESCE(SUM(t.amount), 0) as spent,
            COALESCE(SUM(t.amount), 0) as amount_spent
        FROM budgets b
        LEFT JOIN transactions t 
            ON b.category = t.category AND t.type = 'expense' AND t.user_id = b.user_id
        WHERE b.user_id = ?
        GROUP BY b.budget_id, b.category, b.limit_amount
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $budgets = [];
    while ($row = $result->fetch_assoc()) {
        $budgets[] = $row;
    }

    echo json_encode($budgets);
}
?>
