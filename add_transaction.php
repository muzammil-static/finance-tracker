<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include('database.php');

try {
    $user_id = $_SESSION['user_id'];
    $category = $_POST['category'] ?? '';
    $amount = floatval($_POST['amount']) ?? 0;
    $type = $_POST['type'] ?? '';
    $transaction_date = date('Y-m-d H:i:s');
    
    if (empty($_POST['transaction_id'])) {
        // Insert new transaction
        $sql = "INSERT INTO transactions (user_id, amount, type, category, transaction_date) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idsss", $user_id, $amount, $type, $category, $transaction_date);
    } else {
        // Update existing transaction
        $transaction_id = $_POST['transaction_id'];
        $sql = "UPDATE transactions 
                SET amount = ?, type = ?, category = ? 
                WHERE transaction_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dssii", $amount, $type, $category, $transaction_id, $user_id);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => empty($_POST['transaction_id']) ? 'Transaction added successfully' : 'Transaction updated successfully'
        ]);
    } else {
        throw new Exception($stmt->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();