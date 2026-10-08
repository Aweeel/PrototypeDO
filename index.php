<?php
// index.php — main entry point

// --- Includes ---
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/autoload.php';

// --- Redirect logic ---
if (!isset($_SESSION['user'], $_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/login/login.php');
    exit;
}

$role = $_SESSION['user_role'] ?? $_SESSION['user']['role'] ?? '';
$roleLandingPages = [
    'super_admin' => '/modules/super-admin/systemControl.php',
    'discipline_office' => '/modules/do/doDashboard.php',
    'do' => '/modules/do/doDashboard.php',
    'student' => '/modules/student/studentDashboard.php',
    'teacher' => '/modules/teacher-guard/studentReport.php',
    'security' => '/modules/teacher-guard/studentReport.php'
];

if (isset($roleLandingPages[$role])) {
    header('Location: ' . BASE_URL . $roleLandingPages[$role]);
} else {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/modules/login/login.php?session=invalid');
}
exit;
