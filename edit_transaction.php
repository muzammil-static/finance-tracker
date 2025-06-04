<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = $_POST['edit_id'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $user_id = $_SESSION['user_id'];

    // Verify that the transaction belongs to the current user
    $check_sql = "SELECT user_id FROM transactions WHERE transaction_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $transaction_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        if ($row['user_id'] == $user_id) {
            // Update the transaction
            $update_sql = "UPDATE transactions SET description = ?, amount = ?, type = ? WHERE transaction_id = ? AND user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sdsii", $description, $amount, $type, $transaction_id, $user_id);
            
            if ($update_stmt->execute()) {
                $message = "Transaction updated successfully.";
            } else {
                $message = "Error updating transaction: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $message = "Unauthorized access to transaction.";
        }
    } else {
        $message = "Transaction not found.";
    }
    $check_stmt->close();
}

$conn->close();
header("Location: transaction.php" . (isset($message) ? "?message=" . urlencode($message) : ""));
exit();
?> 