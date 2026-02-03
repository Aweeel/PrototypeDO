<?php
// API Handler for Lost & Found AJAX requests
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/lostAndFoundFunctions.php';

// FIXED: Use user_role instead of role
if (!in_array($_SESSION['user_role'], ['discipline_office', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $data = [
                'item_name' => $_POST['item_name'],
                'category' => $_POST['category'],
                'description' => $_POST['description'] ?? '',
                'location' => $_POST['location'],
                'date_found' => $_POST['date_found'],
                'time_found' => $_POST['time_found'] ?? null,
                'finder_name' => $_POST['finder_name'] ?? null,
                'finder_student_id' => $_POST['finder_student_id'] ?? null
            ];
            
            $result = addLostFoundItem($conn, $data);
            echo json_encode($result);
            break;
            
        case 'get':
            $item_id = $_GET['item_id'];
            $item = getItemById($conn, $item_id);
            
            if ($item) {
                echo json_encode(['success' => true, 'data' => $item]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Item not found']);
            }
            break;
            
        case 'update':
            $item_id = $_POST['item_id'];
            $data = [
                'item_name' => $_POST['item_name'],
                'category' => $_POST['category'],
                'description' => $_POST['description'] ?? '',
                'location' => $_POST['location'],
                'date_found' => $_POST['date_found'],
                'time_found' => $_POST['time_found'] ?? null,
                'finder_name' => $_POST['finder_name'] ?? null,
                'finder_student_id' => $_POST['finder_student_id'] ?? null
            ];
            
            $result = updateItem($conn, $item_id, $data);
            echo json_encode($result);
            break;
            
        case 'mark_claimed':
            $item_id = $_POST['item_id'];
            $claimer_data = [
                'claimer_name' => $_POST['claimer_name'],
                'claimer_student_id' => $_POST['claimer_student_id'] ?? null
            ];
            
            $result = markAsClaimed($conn, $item_id, $claimer_data);
            echo json_encode($result);
            break;
            
        case 'mark_unclaimed':
            $item_id = $_POST['item_id'];
            $result = markAsUnclaimed($conn, $item_id);
            echo json_encode($result);
            break;
            
        case 'archive':
            $item_id = $_POST['item_id'];
            $result = archiveItem($conn, $item_id);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>