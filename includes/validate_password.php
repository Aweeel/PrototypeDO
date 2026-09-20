<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['valid' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['current_password'])) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'Missing password']);
    exit;
}

$currentPassword = $input['current_password'];
$userId = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    
    // Fetch user's password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['valid' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Verify password
    $isValid = password_verify($currentPassword, $user['password_hash']);
    
    echo json_encode(['valid' => $isValid]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'error' => 'Server error']);
    exit;
}
