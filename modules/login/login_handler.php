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
            
            // Check if user is using default password and set warning flag
            $_SESSION['has_default_password'] = userHasDefaultPassword($user['user_id']);

            // Set display name - always use First Name Last Name format (without middle names)
            if ($user['role'] === 'student') {
                $pdo = getDBConnection();
                if ($pdo) {
                    $stmt = $pdo->prepare("SELECT first_name, last_name FROM students WHERE user_id = ?");
                    $stmt->execute([$user['user_id']]);
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($student) {
                        $_SESSION['admin_name'] = $student['first_name'] . ' ' . $student['last_name'];
                    } else {
                        $_SESSION['admin_name'] = $user['full_name'];
                    }
                } else {
                    $_SESSION['admin_name'] = $user['full_name'];
                }
            } else {
                // Extract first and last name from full_name (skip middle names)
                $nameParts = explode(' ', trim($user['full_name']));
                if (count($nameParts) === 1) {
                    $_SESSION['admin_name'] = $nameParts[0];
                } elseif (count($nameParts) === 2) {
                    $_SESSION['admin_name'] = $nameParts[0] . ' ' . $nameParts[1];
                } else {
                    $_SESSION['admin_name'] = $nameParts[0] . ' ' . end($nameParts);
                }
            }
            
            $_SESSION['last_activity'] = time();

            // Handle "Remember Me" checkbox
            if (isset($_POST['remember_me'])) {
                // Create a secure token for "remember me" functionality
                $rememberToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rememberToken);
                $expiryDate = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Store token in database
                $pdo = getDBConnection();
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_token_expiry = ? WHERE user_id = ?");
                        $stmt->execute([$tokenHash, $expiryDate, $user['user_id']]);
                    } catch (Exception $e) {
                        write_log("Remember Me Error: " . $e->getMessage(), 'error');
                    }
                }
                
                // Set the cookie with the actual token (expires in 30 days)
                setcookie('remember_me_token', $rememberToken, time() + (30 * 24 * 60 * 60), '/', '', false, true);
            }

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