<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? $_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        
        // Authenticate user against database
        $user = authenticateUser($username, $password);
        
        if ($user) {
            // Authentication successful
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ];



            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['last_activity'] = time();

            // Log successful login to file
            write_log("LOGIN SUCCESS: {$user['username']} ({$user['role']})", 'login');
            
            // Log to audit_logs database table
            logLogin($user['user_id']);

            // Redirect based on role
            if ($user['role'] === 'super_admin' || $user['role'] === 'discipline_office') {
                header('Location: /PrototypeDO/modules/do/doDashboard.php');
            } elseif ($user['role'] === 'student') {
                header('Location: /PrototypeDO/modules/student/studentDashboard.php');
            } elseif ($user['role'] === 'teacher' || $user['role'] === 'security') {
                header('Location: /PrototypeDO/modules/teacher-guard/studentReport.php');
            } else {
                header('Location: /PrototypeDO/modules/do/doDashboard.php');
            }
            exit;
            
        } else {
            // Authentication failed
            write_log("LOGIN FAILED: Invalid credentials for '$username' from IP: " . $_SERVER['REMOTE_ADDR'], 'login');
            
            // Log failed login attempt to audit_logs
            logFailedLogin($username, 'Invalid credentials');
            
            header('Location: /PrototypeDO/modules/login/login.php?error=invalid');
            exit;
        }
        
    } else {
        write_log("LOGIN FAILED: Missing fields from IP: " . $_SERVER['REMOTE_ADDR'], 'login');
        header('Location: /PrototypeDO/modules/login/login.php?error=empty');
        exit;
    }
} else {
    write_log("INVALID ACCESS to login_handler.php from IP: " . $_SERVER['REMOTE_ADDR'], 'system');
    header('Location: /PrototypeDO/modules/login/login.php');
    exit;
}
?>