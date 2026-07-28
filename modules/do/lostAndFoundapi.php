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
            
            // Handle image upload if present
            $image_path = null;
            if (isset($_FILES['item_image']) && $_FILES['item_image']['size'] > 0) {
                // Generate temporary item ID for file naming (will use actual one after insert)
                $temp_item_id = 'LF-' . time();
                $upload_result = handleLostFoundImageUpload($_FILES['item_image'], $temp_item_id);
                
                if (!$upload_result['success']) {
                    echo json_encode(['success' => false, 'message' => $upload_result['error']]);
                    exit;
                }
                $image_path = $upload_result['path'];
            }
            
            $data = [
                'item_name' => $_POST['item_name'],
                'category' => $_POST['category'],
                'description' => !empty(trim($_POST['description'] ?? '')) ? trim($_POST['description']) : null,
                'location' => $_POST['location'],
                'date_found' => $_POST['date_found'],
                'time_found' => $time_found,
                'finder_name' => !empty(trim($_POST['finder_name'] ?? '')) ? trim($_POST['finder_name']) : null,
                'finder_student_id' => !empty(trim($_POST['finder_student_id'] ?? '')) ? trim($_POST['finder_student_id']) : null,
                'image_path' => $image_path
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
            
        case 'get_student':
            $student_id = $_GET['student_id'] ?? '';
            
            if (!$student_id) {
                echo json_encode(['success' => false, 'message' => 'Student ID required']);
                break;
            }
            
            // Query to get student information
            $sql = "SELECT student_id, first_name, last_name FROM students WHERE student_id = ?";
            
            try {
                $student = fetchOne($sql, [$student_id]);
                
                if ($student) {
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'student_id' => $student['student_id'],
                            'first_name' => $student['first_name'],
                            'last_name' => $student['last_name'],
                            'full_name' => $student['first_name'] . ' ' . $student['last_name']
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                }
            } catch (Exception $e) {
                error_log("get_student error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error fetching student']);
            }
            break;

        case 'lookup_claimer_id':
            $claimer_id = trim($_GET['claimer_id'] ?? '');

            if (!$claimer_id) {
                echo json_encode(['success' => false, 'message' => 'Claimer ID required']);
                break;
            }

            $sql = "
                SELECT TOP 1 id_type, claimer_id, full_name
                FROM (
                    SELECT 1 AS sort_order, 'student' AS id_type, student_id AS claimer_id, first_name + ' ' + last_name AS full_name
                    FROM students
                    WHERE student_id = ?

                    UNION ALL

                    SELECT 2 AS sort_order, 'teacher' AS id_type, teacher_id AS claimer_id, full_name
                    FROM users
                    WHERE teacher_id = ?

                    UNION ALL

                    SELECT 3 AS sort_order, 'discipline_office' AS id_type, do_id AS claimer_id, full_name
                    FROM users
                    WHERE do_id = ?
                ) claimer_matches
                ORDER BY sort_order
            ";

            try {
                $claimer = fetchOne($sql, [$claimer_id, $claimer_id, $claimer_id]);

                if ($claimer) {
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'id_type' => $claimer['id_type'],
                            'claimer_id' => $claimer['claimer_id'],
                            'full_name' => $claimer['full_name']
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Claimer ID not found']);
                }
            } catch (Exception $e) {
                error_log("lookup_claimer_id error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error fetching claimer ID']);
            }
            break;
            
        case 'add_category':
            $categoryName = $_POST['category_name'] ?? '';
            $description = $_POST['description'] ?? null;
            
            $result = addCategory($categoryName, $description);
            echo json_encode($result);
            break;
            
        case 'delete_category':
            $categoryId = $_POST['category_id'] ?? '';
            
            if (!$categoryId) {
                echo json_encode(['success' => false, 'message' => 'Category ID is required']);
                break;
            }
            
            $result = deleteCategory($categoryId);
            echo json_encode($result);
            break;
            
        case 'delete_category_by_name':
            $categoryName = $_POST['category_name'] ?? '';
            
            if (!$categoryName) {
                echo json_encode(['success' => false, 'message' => 'Category name is required']);
                break;
            }
            
            $result = deleteCategoryByName($categoryName);
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