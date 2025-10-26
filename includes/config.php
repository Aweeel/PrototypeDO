<?php
// includes/config.php

// --- Secure Session Settings ---
if (session_status() === PHP_SESSION_NONE) {
    // Configure cookie BEFORE starting session
    session_set_cookie_params([
        'lifetime' => 0,                // Ends when browser closes
        'path' => '/',                  // Available across entire site
        'secure' => isset($_SERVER['HTTPS']), // Only secure if HTTPS
        'httponly' => true,             // Not accessible via JS
        'samesite' => 'Lax'             // Prevents CSRF via cross-site requests
    ]);

    session_start();
}

// --- Cache Control ---
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// --- Auto Logout After Inactivity (Optional) ---
$timeout = 1800; // 30 minutes of inactivity

if (isset($_SESSION['user'])) {
    // Check if user has a last_activity timestamp
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Session expired — destroy it
        session_unset();
        session_destroy();
        header("Location: /PrototypeDO/modules/login/login.php?session=expired");
        exit();
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
}
?>
