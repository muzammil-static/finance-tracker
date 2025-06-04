<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

include('database.php');
$user_id = $_SESSION['user_id'];

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

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'balance' => $balance,
    'total_income' => $total_income,
    'total_expense' => $total_expense
]);
?> 