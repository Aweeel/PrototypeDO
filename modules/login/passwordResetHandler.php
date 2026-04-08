<?php
// modules/login/passwordResetHandler.php
// Handles password reset requests and notifies admin

require_once '../../includes/db_connect.php';
require_once '../../includes/logger.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$email = trim($_POST['email'] ?? '');

// Validate email
if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

try {
    // Check if user with this email exists
    $userSql = "SELECT user_id, full_name, email FROM users WHERE email = ?";
    $user = fetchOne($userSql, [$email]);
    
    if (!$user) {
        // For security, return generic message (don't reveal if email exists or not)
        echo json_encode([
            'success' => true,
            'message' => 'An admin will be notified to reset your password.'
        ]);
        
        // Log the attempt for non-existent email
        write_log("Password reset requested for non-existent email: $email", 'password_reset');
        exit;
    }
    
    // Get all admin users (super_admin and discipline_office roles)
    $adminSql = "SELECT user_id, email, full_name FROM users 
                 WHERE role IN ('super_admin', 'discipline_office') AND is_active = 1";
    $admins = fetchAll($adminSql);
    
    if (empty($admins)) {
        // No admins available - still return success but log the issue
        write_log("Password reset requested but no admins available for email: $email", 'password_reset');
        echo json_encode([
            'success' => true,
            'message' => 'An admin will be notified to reset your password.'
        ]);
        exit;
    }
    
    // Create notifications for each admin
    $notificationCount = 0;
    $failedNotifications = 0;
    
    foreach ($admins as $admin) {
        try {
            $notifSql = "INSERT INTO notifications (user_id, title, message, type, related_id, is_read, created_at)
                         VALUES (?, ?, ?, ?, ?, 0, GETDATE())";
            
            $title = "Password Reset Request";
            $message = "User {$user['full_name']} ({$user['email']}) has requested a password reset. Please take appropriate action.";
            $type = "password_reset_request";
            // Set related_id to 'password_reset' so notification routing knows this is not a case
            $relatedId = 'password_reset:' . $user['user_id'];
            
            executeQuery($notifSql, [$admin['user_id'], $title, $message, $type, $relatedId]);
            $notificationCount++;
        } catch (Exception $e) {
            $failedNotifications++;
            error_log("Failed to create notification for admin {$admin['user_id']}: " . $e->getMessage());
        }
    }
    
    // Log the password reset request
    write_log(
        "Password reset requested by user: {$user['full_name']} ({$user['email']}). " .
        "Notifications created: $notificationCount, Failed: $failedNotifications",
        'password_reset'
    );
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'An admin will be notified to reset your password.'
    ]);
    
} catch (Exception $e) {
    error_log("Password reset handler error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred. Please try again later.'
    ]);
}
?>
