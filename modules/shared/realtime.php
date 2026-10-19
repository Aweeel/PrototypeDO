<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$role = $_SESSION['user_role'] ?? '';
$showNotifications = $role !== 'security'
    && ($role !== 'teacher' || ($_SESSION['teacher_subrole'] ?? $_SESSION['user']['teacher_subrole'] ?? null) === 'department_head');

if (!$showNotifications || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'notifications' => [], 'count' => 0]);
    exit;
}

try {
    $notifications = getUnreadNotifications($_SESSION['user_id']) ?? [];
    echo json_encode([
        'success' => true,
        'notifications' => array_map(static function ($notification) {
            return [
                'id' => (int)($notification['notification_id'] ?? 0),
                'title' => (string)($notification['title'] ?? ''),
                'message' => (string)($notification['message'] ?? ''),
                'createdAt' => (string)($notification['created_at'] ?? ''),
                'relatedId' => (string)($notification['related_id'] ?? '')
            ];
        }, $notifications),
        'count' => count($notifications)
    ]);
} catch (Throwable $e) {
    error_log('Realtime notification lookup failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load notifications']);
}
