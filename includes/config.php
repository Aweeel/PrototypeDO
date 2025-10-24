<?php
// includes/config.php

// 🧱 Secure session setup — must happen before output
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,                // Ends when browser closes
        'path' => '/',                  // Valid site-wide
        'secure' => false,              // XAMPP = usually no HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// 🚫 Prevent browser caching (back-button protection)
if (!headers_sent()) {
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
}

// ⏱ Auto logout after inactivity (30 mins)
$timeout = 1800; // 30 minutes
if (isset($_SESSION['user'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header("Location: /PrototypeDO/modules/login/login.php?session=expired");
        exit();
    }
    $_SESSION['last_activity'] = time();
}
?>
