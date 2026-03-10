<?php
//---------------LAGAY SA SIMULA NG INSIDE PAGES/MODULES-------------------
/* <?php require_once __DIR__ . '/../../includes/auth_check.php'; ?> */
//^^^^^^^^ETO

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Prevent caching so back button won't load protected page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user has a valid session
if (isset($_SESSION['user']) && isset($_SESSION['user_id'])) {
    // Session is valid, continue
} elseif (isset($_COOKIE['remember_me_token'])) {
    // No session but remember me cookie exists - try to restore session
    $rememberToken = $_COOKIE['remember_me_token'];
    $tokenHash = hash('sha256', $rememberToken);
    
    $pdo = getDBConnection();
    if ($pdo) {
        // Find user by token
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND remember_token_expiry > CONVERT(datetime, GETDATE())");
        $stmt->execute([$tokenHash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Token is valid, restore session
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
            $_SESSION['last_activity'] = time();
            
            // Set display name - same logic as login handler
            if ($user['role'] === 'student') {
                $stmt = $pdo->prepare("SELECT first_name, last_name FROM students WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($student) {
                    $_SESSION['admin_name'] = $student['first_name'] . ' ' . $student['last_name'];
                } else {
                    $_SESSION['admin_name'] = $user['full_name'];
                }
            } else {
                $nameParts = explode(' ', trim($user['full_name']));
                if (count($nameParts) === 1) {
                    $_SESSION['admin_name'] = $nameParts[0];
                } elseif (count($nameParts) === 2) {
                    $_SESSION['admin_name'] = $nameParts[0] . ' ' . $nameParts[1];
                } else {
                    $_SESSION['admin_name'] = $nameParts[0] . ' ' . end($nameParts);
                }
            }
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = GETDATE() WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
        } else {
            // Token is invalid or expired, clear the cookie
            setcookie('remember_me_token', '', time() - 3600, '/', '', false, true);
            session_unset();
            session_destroy();
            header("Location: /PrototypeDO/modules/login/login.php?session=expired");
            exit;
        }
    } else {
        // Database error, redirect to login
        header("Location: /PrototypeDO/modules/login/login.php?error=db");
        exit;
    }
} else {
    // No valid session or remember me cookie, redirect to login
    session_unset();
    session_destroy();
    header("Location: /PrototypeDO/modules/login/login.php");
    exit;
}
?>