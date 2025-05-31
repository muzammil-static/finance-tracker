<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database.php';

// Only set JSON header if this file is being accessed directly
if (basename($_SERVER['PHP_SELF']) === 'get_settings.php') {
    header('Content-Type: application/json');
}

try {
    $stmt = $conn->prepare("SELECT * FROM settings WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();
    
    if ($settings) {
        if (basename($_SERVER['PHP_SELF']) === 'get_settings.php') {
            echo json_encode($settings);
        }
    } else {
        // Return default settings if none found
        $settings = [
            'currency_symbol' => '$',
            'theme' => 'light'
        ];
        
        if (basename($_SERVER['PHP_SELF']) === 'get_settings.php') {
            echo json_encode($settings);
        }
    }
} catch(Exception $e) {
    if (basename($_SERVER['PHP_SELF']) === 'get_settings.php') {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?> 