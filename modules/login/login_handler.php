<?php
// Load configuration and start a secure session
require_once __DIR__ . '/../../includes/config.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data safely
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Temporary authentication logic (for testing)
    if (!empty($email) && !empty($password)) {

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Store user info in session
        $_SESSION['user'] = [
            'email' => $email,
        ];

        // Set last activity timestamp (for session timeout tracking)
        $_SESSION['last_activity'] = time();

        // TEMPORARY: assign role manually (replace with DB role later)
        $_SESSION['user_role'] = 'do'; // or 'superAdmin', 'student', etc.

        // ✅ Default active page (for first login sidebar highlight)
        $_SESSION['active_page'] = 'doDashboard.php';

        // Redirect to dashboard
        header('Location: /PrototypeDO/modules/do/doDashboard.php');
        exit;
    } else {
        // Redirect back to login with error message
        header('Location: /PrototypeDO/modules/login/login.php?error=empty');
        exit;
    }
} else {
    // Invalid request method (direct access)
    header('Location: /PrototypeDO/modules/login/login.php');
    exit;
}
?>
