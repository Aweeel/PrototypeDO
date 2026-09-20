<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Log the logout action BEFORE destroying the session
$userEmail = $_SESSION['user']['email'] ?? '';

// Redirect to login page with success flag and user email
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Clear remember me token from database
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL, remember_token_expiry = NULL WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    
    // Log to audit_logs table
    logLogout($user_id);
}

// Clear remember me cookie
setcookie('remember_me_token', '', time() - 3600, '/', '', false, true);

// Clear all session data
$_SESSION = array();

// Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session entirely
session_destroy();

// Clear browser cache for security
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page with success flag
$redirectUrl = '/PrototypeDO/modules/login/login.php?logout=success&remember_me=1';
if (!empty($userEmail)) {
    $redirectUrl .= '&email=' . urlencode($userEmail);
}
header("Location: " . $redirectUrl);
exit;
?>