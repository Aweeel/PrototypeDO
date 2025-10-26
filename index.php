<?php
// index.php — main entry point

// --- Session settings (set BEFORE session_start) ---
ini_set('session.cookie_lifetime', 0);  // Session ends when browser closes
ini_set('session.gc_maxlifetime', 0);   // Garbage collection for session

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Includes ---
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/autoload.php';

// --- Redirect logic ---
if (isset($_SESSION['user'])) {
    header('Location: modules/do/doDashboard.php');
    exit;
} else {
    header('Location: modules/login/login.php');
    exit;
}
