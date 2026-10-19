<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'getRealtimeState') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unsupported request.']);
    exit;
}

$role = $_SESSION['user_role'] ?? '';
if ($role === 'security' || ($role === 'teacher' && ($_SESSION['teacher_subrole'] ?? null) !== 'department_head')) {
    echo json_encode(['success' => true, 'notifications' => []]);
    exit;
}

$notifications = getUnreadNotifications($_SESSION['user_id'] ?? null) ?: [];
echo json_encode([
    'success' => true,
    'notifications' => array_slice($notifications, 0, 20),
]);
