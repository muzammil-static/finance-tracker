<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="finance_data_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, ['Data Type', 'Category', 'Amount', 'Type', 'Date']);

try {
    // Get transactions data
    $trans_sql = "SELECT category, amount, type, transaction_date 
                  FROM transactions 
                  WHERE user_id = ?
                  ORDER BY transaction_date DESC";
    
    $trans_stmt = $conn->prepare($trans_sql);
    $trans_stmt->bind_param("i", $user_id);
    $trans_stmt->execute();
    $result = $trans_stmt->get_result();

    // Write transactions to CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            'Transaction',
            $row['category'],
            $row['amount'],
            $row['type'],
            $row['transaction_date']
        ]);
    }

    // Get budget data
    $budget_sql = "SELECT category, amount, created_at 
                   FROM budgets 
                   WHERE user_id = ?";
    
    $budget_stmt = $conn->prepare($budget_sql);
    $budget_stmt->bind_param("i", $user_id);
    $budget_stmt->execute();
    $result = $budget_stmt->get_result();

    // Write budgets to CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            'Budget',
            $row['category'],
            $row['amount'],
            'Budget Limit',
            $row['created_at']
        ]);
    }

} catch (Exception $e) {
    die("Error exporting data: " . $e->getMessage());
} finally {
    fclose($output);
    $conn->close();
}