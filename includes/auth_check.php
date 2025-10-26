<?php

//---------------LAGAY SA SIMULA NG INSIDE PAGES/MODULES-------------------
/* <?php require_once __DIR__ . '/../../includes/auth_check.php'; ?> */
//^^^^^^^^ETO


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// Prevent caching so back button won't load protected page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: /PrototypeDO/modules/login/login.php");
    exit;
}
?>