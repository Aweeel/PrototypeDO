<?php
//lostAndFoundapi.php
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
            // Convert empty strings to null for proper SQL Server handling
            $time_found = !empty(trim($_POST['time_found'] ?? '')) ? trim($_POST['time_found']) : null;
            // Convert HH:MM to HH:MM:SS format for SQL Server TIME column
            if ($time_found && strlen($time_found) === 5 && substr_count($time_found, ':') === 1) {
                $time_found .= ':00';
            }
            
            $data = [
                'item_name' => $_POST['item_name'],
                'category' => $_POST['category'],
                'description' => !empty(trim($_POST['description'] ?? '')) ? trim($_POST['description']) : null,
                'location' => $_POST['location'],
                'date_found' => $_POST['date_found'],
                'time_found' => $time_found,
                'finder_name' => !empty(trim($_POST['finder_name'] ?? '')) ? trim($_POST['finder_name']) : null,
                'finder_student_id' => !empty(trim($_POST['finder_student_id'] ?? '')) ? trim($_POST['finder_student_id']) : null
            ];
            
            // Debug logging
            error_log("Lost & Found Add - Data: " . print_r($data, true));
            
            $result = addLostFoundItem($data);
            echo json_encode($result);
            break;
            
        case 'get':
            $item_id = $_GET['item_id'];
            $item = getItemById($item_id);
            
            if ($item) {
                echo json_encode(['success' => true, 'data' => $item]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Item not found']);
            }
            break;
            
        case 'update':
            $item_id = $_POST['item_id'];
            // Convert empty strings to null for proper SQL Server handling
            $time_found = !empty(trim($_POST['time_found'] ?? '')) ? trim($_POST['time_found']) : null;
            // Convert HH:MM to HH:MM:SS format for SQL Server TIME column
            if ($time_found && strlen($time_found) === 5 && substr_count($time_found, ':') === 1) {
                $time_found .= ':00';
            }
            
            $data = [
                'item_name' => $_POST['item_name'],
                'category' => $_POST['category'],
                'description' => !empty(trim($_POST['description'] ?? '')) ? trim($_POST['description']) : null,
                'location' => $_POST['location'],
                'date_found' => $_POST['date_found'],
                'time_found' => $time_found,
                'finder_name' => !empty(trim($_POST['finder_name'] ?? '')) ? trim($_POST['finder_name']) : null,
                'finder_student_id' => !empty(trim($_POST['finder_student_id'] ?? '')) ? trim($_POST['finder_student_id']) : null
            ];
            
            // Debug logging
            error_log("Lost & Found Update - Item ID: $item_id, Data: " . print_r($data, true));
            
            $result = updateItem($item_id, $data);
            echo json_encode($result);
            break;
            
        case 'mark_claimed':
            $item_id = $_POST['item_id'];
            $claimer_data = [
                'claimer_name' => $_POST['claimer_name'],
                'claimer_student_id' => $_POST['claimer_student_id'] ?? null
            ];
            
            $result = markAsClaimed($item_id, $claimer_data);
            echo json_encode($result);
            break;
            
        case 'mark_unclaimed':
            $item_id = $_POST['item_id'];
            $result = markAsUnclaimed($item_id);
            echo json_encode($result);
            break;
            
        case 'archive':
            $item_id = $_POST['item_id'];
            $result = archiveItem($item_id);
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