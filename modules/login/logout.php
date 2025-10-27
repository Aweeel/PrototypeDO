<?php
session_start();

// Log the logout action if user exists
if (isset($_SESSION['user_id']) && isset($_SESSION['user'])) {
    require_once __DIR__ . '/../../includes/db_connect.php';
    require_once __DIR__ . '/../../includes/logger.php';
    
    $username = $_SESSION['user']['username'] ?? 'Unknown';
    write_log("LOGOUT: $username", 'login');
}

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear browser cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login
header("Location: /PrototypeDO/modules/login/login.php?logout=success");
exit;
?>