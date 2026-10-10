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
    $sessionUsername = $_SESSION['user']['username'] ?? '';
    if (!isSimultaneousLoginExempt($sessionUsername)
        && hasActiveSessionChanged($_SESSION['user_id'], session_id())) {
        setcookie('remember_me_token', '', time() - 3600, '/', '', false, true);
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/modules/login/simultaneous_logins_detected.php');
        exit;
    }
} elseif (isset($_COOKIE['remember_me_token'])) {
    // No session but remember me cookie exists - try to restore session
    $rememberToken = $_COOKIE['remember_me_token'];
    $tokenHash = hash('sha256', $rememberToken);
    
    $pdo = getDBConnection();
    if ($pdo) {
        // Find user by token
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND remember_token_expiry > CURRENT_TIMESTAMP AND COALESCE(is_archived, 0) = 0");
        $stmt->execute([$tokenHash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Token is valid, restore session
            session_regenerate_id(true);

            if (!isSimultaneousLoginExempt($user['username'])) {
                registerActiveSession($user['user_id'], session_id());
            }
            
            $_SESSION['user'] = [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'teacher_subrole' => $user['teacher_subrole'] ?? null,
                'program' => $user['program'] ?? null
            ];
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['teacher_subrole'] = $user['teacher_subrole'] ?? null;
            $_SESSION['program'] = $user['program'] ?? null;
            $_SESSION['last_activity'] = time();
            
            // Set display name - same logic as login handler
            if ($user['role'] === 'student') {
                $stmt = $pdo->prepare("SELECT first_name, middle_name, last_name FROM students WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($student) {
                    $_SESSION['admin_name'] = trim(implode(' ', array_filter([$student['first_name'], $student['middle_name'] ?? null, $student['last_name']], static fn($name) => trim((string)$name) !== '')));
                } else {
                    $_SESSION['admin_name'] = $user['full_name'];
                }
            } else {
                $_SESSION['admin_name'] = trim($user['full_name']);
            }
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
        } else {
            // Token is invalid or expired, clear the cookie
            setcookie('remember_me_token', '', time() - 3600, '/', '', false, true);
            session_unset();
            session_destroy();
            header("Location: " . BASE_URL . "/modules/login/login.php?session=expired");
            exit;
        }
    } else {
        // Database error, redirect to login
        header("Location: " . BASE_URL . "/modules/login/login.php?error=db");
        exit;
    }
} else {
    // No valid session or remember me cookie, redirect to login
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "/modules/login/login.php");
    exit;
}

$currentRole = $_SESSION['user_role'] ?? ($_SESSION['user']['role'] ?? '');
$currentPath = parse_url($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '', PHP_URL_PATH) ?: '';
$basePath = parse_url(BASE_URL, PHP_URL_PATH) ?: '';
if ($basePath !== '' && $basePath !== '/' && strpos($currentPath, $basePath) === 0) {
    $currentPath = substr($currentPath, strlen($basePath));
}
$currentPath = '/' . ltrim($currentPath, '/');

// Authorization is enforced here because every protected module includes this file.
$roleModulePrefixes = [
    'super_admin' => [
        '/modules/super-admin/',
        '/modules/shared/',
        '/modules/do/auditLog.php'
    ],
    'discipline_office' => [
        '/modules/do/',
        '/modules/shared/'
    ],
    'do' => [
        '/modules/do/',
        '/modules/shared/'
    ],
    'teacher' => [
        '/modules/teacher-guard/',
        '/modules/shared/',
        '/modules/do/calendar.php'
    ],
    'security' => [
        '/modules/teacher-guard/',
        '/modules/shared/'
    ],
    'student' => [
        '/modules/student/',
        '/modules/shared/'
    ]
];

$hasModuleAccess = false;
foreach ($roleModulePrefixes[$currentRole] ?? [] as $prefix) {
    $matchesPrefix = $prefix === '/modules/do/auditLog.php'
        ? $currentPath === $prefix
        : strpos($currentPath, $prefix) === 0;

    if ($matchesPrefix) {
        $hasModuleAccess = true;
        break;
    }
}

if ($hasModuleAccess
    && $currentRole === 'teacher'
    && $currentPath === '/modules/do/calendar.php'
    && ($_SESSION['teacher_subrole'] ?? ($_SESSION['user']['teacher_subrole'] ?? null)) !== 'department_head') {
    $hasModuleAccess = false;
}

if (!$hasModuleAccess && strpos($currentPath, '/modules/') === 0) {
    $isAjaxRequest = $_SERVER['REQUEST_METHOD'] === 'POST'
        && (isset($_POST['ajax']) || isset($_POST['action']));

    if ($isAjaxRequest) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Access denied']);
    } else {
        header('Location: ' . BASE_URL . '/index.php?error=access_denied');
    }
    exit;
}

if ($currentRole !== 'super_admin' && isMaintenanceModeEnabled()
    && basename($currentPath) !== 'maintenance.php') {
    header('Location: ' . BASE_URL . '/modules/shared/maintenance.php');
    exit;
}

// ===== Handle Terms of Service AJAX acceptance =====
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['ajax'])
    && ($_POST['action'] ?? '') === 'acceptTerms'
    && isset($_SESSION['user_id'])) {
    
    header('Content-Type: application/json');
    
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            // First, ensure the terms_accepted_date column exists
            try {
                $checkColSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                              WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'terms_accepted_date'";
                $colExists = $pdo->query($checkColSql)->fetch();
                
                if (!$colExists) {
                    // Column doesn't exist, add it
                    $pdo->exec("ALTER TABLE users ADD terms_accepted_date DATETIME NULL");
                    error_log("Added missing terms_accepted_date column to users table");
                }
            } catch (Exception $e) {
                error_log("Warning: Could not check/add terms_accepted_date column: " . $e->getMessage());
                // Continue anyway, might already exist
            }
            
            // Now update the terms acceptance
            $stmt = $pdo->prepare("UPDATE users SET terms_accepted_version = ?, terms_accepted_date = CURRENT_TIMESTAMP WHERE user_id = ?");
            
            // Explicitly bind parameters with type hints
            $stmt->bindValue(1, 2, PDO::PARAM_INT);
            $stmt->bindValue(2, (string)$_SESSION['user_id'], PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Database update failed");
            }
            
            // Audit log the terms acceptance
            auditTermsAccepted($_SESSION['user_id'], 2);
        }
        
        $_SESSION['tos_accepted'] = true;
        session_write_close();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>