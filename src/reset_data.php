<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

require_once 'database.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    // Delete transactions
    $trans_sql = "DELETE FROM transactions WHERE user_id = ?";
    $trans_stmt = $conn->prepare($trans_sql);
    $trans_stmt->bind_param("i", $user_id);
    $trans_stmt->execute();

    // Delete budgets
    $budget_sql = "DELETE FROM budgets WHERE user_id = ?";
    $budget_stmt = $conn->prepare($budget_sql);
    $budget_stmt->bind_param("i", $user_id);
    $budget_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'All data has been reset successfully'
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
    
$conn->close();