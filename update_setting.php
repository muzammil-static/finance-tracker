<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_POST['key']) || !isset($_POST['value'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$key = $_POST['key'];
$value = $_POST['value'];
$user_id = $_SESSION['user_id'];

try {
    // First check if setting exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM settings WHERE user_id = ? AND setting_key = ?");
    $stmt->bind_param("is", $user_id, $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $exists = $row['count'] > 0;

    if ($exists) {
        // Update existing setting
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE user_id = ? AND setting_key = ?");
        $stmt->bind_param("sis", $value, $user_id, $key);
        $stmt->execute();
    } else {
        // Insert new setting
        $stmt = $conn->prepare("INSERT INTO settings (user_id, setting_key, setting_value) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $key, $value);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 