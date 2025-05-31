<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear any cookies
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, '/');
}
if (isset($_COOKIE['email'])) {
    setcookie('email', '', time() - 3600, '/');
}

// Redirect to login page
header("Location: index.html");
exit();
?> 