<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/logger.php'; // ✅ Add this

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'email' => $email,
        ];

        $_SESSION['last_activity'] = time();
        $_SESSION['user_role'] = 'do';
        $_SESSION['active_page'] = 'doDashboard.php';

        // ✅ Log the successful login
        write_log("LOGIN SUCCESS: $email", 'login');

        header('Location: /PrototypeDO/modules/do/doDashboard.php');
        exit;
    } else {
        // ✅ Log failed attempt
        write_log("LOGIN FAILED (missing fields) from IP: " . $_SERVER['REMOTE_ADDR'], 'login');
        header('Location: /PrototypeDO/modules/login/login.php?error=empty');
        exit;
    }
} else {
    write_log("INVALID ACCESS to login_handler.php from IP: " . $_SERVER['REMOTE_ADDR'], 'system');
    header('Location: /PrototypeDO/modules/login/login.php');
    exit;
}
?>
