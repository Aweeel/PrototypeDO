<?php
// includes/config.php

// Include database connection
require_once __DIR__ . '/db_connect.php';

date_default_timezone_set('Asia/Manila');

// Secure session setup — must happen before output
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Prevent browser caching (back-button protection)
if (!headers_sent()) {
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
}

// Resolve the correct public base URL for both local subfolder installs and root/Azure deployments.
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
$forwardedPrefix = $_SERVER['HTTP_X_FORWARDED_PREFIX'] ?? $_SERVER['X_FORWARDED_PREFIX'] ?? '';

$baseUrl = '';

if (!empty($forwardedPrefix)) {
    $baseUrl = rtrim($forwardedPrefix, '/');
} elseif (!empty($scriptName)) {
    $scriptDir = dirname($scriptName);
    $scriptDir = rtrim($scriptDir, '/');

    if ($scriptDir !== '' && $scriptDir !== '.' && $scriptDir !== '/') {
        $withoutModulesPath = preg_replace('#/modules(?:/.*)?$#', '', $scriptDir);
        if ($withoutModulesPath !== '' && $withoutModulesPath !== '/') {
            $baseUrl = $withoutModulesPath;
        }
    }

}

if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}

if ($baseUrl === '' && !empty($uriPath)) {
    $segments = explode('/', trim($uriPath, '/'));
    $modulesIndex = array_search('modules', $segments, true);
    if ($modulesIndex !== false && $modulesIndex > 0) {
        $baseUrl = '/' . implode('/', array_slice($segments, 0, $modulesIndex));
    }
}

define('BASE_URL', $baseUrl);
define('ASSETS_URL', BASE_URL . '/assets');

// Auto logout after inactivity (30 mins)
$timeout = 1800;
if (isset($_SESSION['user'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "/modules/login/login.php?session=expired");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880);

// Date/Time configuration
date_default_timezone_set('Asia/Manila');

// Error reporting (disable in production)
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>