<?php
// includes/functions.php
// Database Helper Functions

require_once __DIR__ . '/db_connect.php';

// ==========================================
// USER FUNCTIONS
// ==========================================

function getUserById($userId) {
    $sql = "SELECT * FROM users WHERE user_id = ?";
    return fetchOne($sql, [$userId]);
}

function getUserByUsername($username) {
    $sql = "SELECT * FROM users WHERE username = ?";
    return fetchOne($sql, [$username]);
}

function authenticateUser($username, $password) {
    $user = getUserByUsername($username);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Update last login
        $sql = "UPDATE users SET last_login = GETDATE() WHERE user_id = ?";
        executeQuery($sql, [$user['user_id']]);
        
        return $user;
    }
    
    return false;
}

// ==========================================
// CASE FUNCTIONS
// ==========================================

function getAllCases($filters = []) {
    $sql = "SELECT c.*, s.first_name, s.last_name, s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            u.full_name as assigned_to_name
            FROM cases c
            LEFT JOIN students s ON c.student_id = s.student_id
            LEFT JOIN users u ON c.assigned_to = u.user_id
            WHERE c.is_archived = 0";
    
    $params = [];
    
    // Apply filters
    if (!empty($filters['search'])) {
        $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR c.case_id LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($filters['type'])) {
        $sql .= " AND c.case_type = ?";
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND c.status = ?";
        $params[] = $filters['status'];
    }
    
    $sql .= " ORDER BY c.date_reported DESC, c.created_at DESC";
    
    return fetchAll($sql, $params);
}

function getCaseById($caseId) {
    $sql = "SELECT c.*, s.first_name, s.last_name, s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            u.full_name as assigned_to_name
            FROM cases c
            LEFT JOIN students s ON c.student_id = s.student_id
            LEFT JOIN users u ON c.assigned_to = u.user_id
            WHERE c.case_id = ?";
    
    return fetchOne($sql, [$caseId]);
}

function getRecentCases($limit = 5) {
    $sql = "SELECT TOP (?) c.*, s.first_name, s.last_name,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            u.full_name as assigned_to_name
            FROM cases c
            LEFT JOIN students s ON c.student_id = s.student_id
            LEFT JOIN users u ON c.assigned_to = u.user_id
            WHERE c.is_archived = 0
            ORDER BY c.date_reported DESC, c.created_at DESC";
    
    return fetchAll($sql, [$limit]);
}

function createCase($data) {
    // Generate new case ID
    $lastCase = fetchOne("SELECT TOP 1 case_id FROM cases ORDER BY case_id DESC");
    $lastNum = $lastCase ? intval(substr($lastCase['case_id'], 2)) : 1000;
    $newCaseId = 'C-' . ($lastNum + 1);
    
    // Check if student exists, if not create
    $studentId = $data['student_number'];
    $existingStudent = getStudentById($studentId);
    
    if (!$existingStudent) {
        // Parse student name
        $nameParts = explode(' ', trim($data['student_name']));
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';
        
        // Create new student record
        $sql = "INSERT INTO students (student_id, first_name, last_name, grade_year, track_course, student_type)
                VALUES (?, ?, ?, 'N/A', 'N/A', 'College')";
        
        executeQuery($sql, [$studentId, $firstName, $lastName]);
    }
    
    // Create case
    $sql = "INSERT INTO cases (case_id, student_id, offense_id, case_type, severity, 
            status, date_reported, reported_by, assigned_to, description, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $newCaseId,
        $studentId,
        $data['offense_id'] ?? null,
        $data['case_type'],
        $data['severity'],
        $data['status'] ?? 'Pending',
        $data['date_reported'] ?? date('Y-m-d'),
        $data['reported_by'] ?? null,
        $data['assigned_to'] ?? null,
        $data['description'],
        $data['notes'] ?? ''
    ];
    
    executeQuery($sql, $params);
    
    // Log case creation
    logCaseHistory($newCaseId, $_SESSION['user_id'] ?? null, 'Created', null, 'Case created');
    
    return $newCaseId;
}

function updateCase($caseId, $data) {
    $sql = "UPDATE cases SET 
            case_type = ?, 
            severity = ?, 
            status = ?, 
            assigned_to = ?,
            description = ?, 
            notes = ?,
            updated_at = GETDATE()
            WHERE case_id = ?";
    
    $params = [
        $data['case_type'],
        $data['severity'],
        $data['status'],
        $data['assigned_to'] ?? null,
        $data['description'],
        $data['notes'] ?? '',
        $caseId
    ];
    
    executeQuery($sql, $params);
    
    // Log case update
    logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Updated', null, 'Case updated');
    
    return true;
}

function archiveCase($caseId) {
    $sql = "UPDATE cases SET is_archived = 1, archived_at = GETDATE() WHERE case_id = ?";
    executeQuery($sql, [$caseId]);
    
    logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Archived', null, 'Case archived');
}

function logCaseHistory($caseId, $userId, $action, $oldValue, $newValue) {
    $sql = "INSERT INTO case_history (case_id, changed_by, action, old_value, new_value)
            VALUES (?, ?, ?, ?, ?)";
    
    executeQuery($sql, [$caseId, $userId, $action, $oldValue, $newValue]);
}

// ==========================================
// STATISTICS FUNCTIONS
// ==========================================

function getCaseStatistics() {
    $stats = [
        'total_active' => 0,
        'pending_review' => 0,
        'urgent_cases' => 0,
        'resolved' => 0
    ];
    
    // Total active cases
    $stats['total_active'] = fetchValue(
        "SELECT COUNT(*) FROM cases WHERE is_archived = 0 AND status != 'Resolved'"
    );
    
    // Pending review
    $stats['pending_review'] = fetchValue(
        "SELECT COUNT(*) FROM cases WHERE status = 'Pending' AND is_archived = 0"
    );
    
    // Urgent cases (Escalated or Major offenses)
    $stats['urgent_cases'] = fetchValue(
        "SELECT COUNT(*) FROM cases WHERE (status = 'Escalated' OR severity = 'Major') AND is_archived = 0"
    );
    
    // Resolved cases
    $stats['resolved'] = fetchValue(
        "SELECT COUNT(*) FROM cases WHERE status = 'Resolved' AND is_archived = 0"
    );
    
    return $stats;
}

function getCaseTypeDistribution() {
    $sql = "SELECT case_type, COUNT(*) as count,
            CAST(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM cases WHERE is_archived = 0) AS DECIMAL(5,2)) as percentage
            FROM cases
            WHERE is_archived = 0
            GROUP BY case_type
            ORDER BY count DESC";
    
    return fetchAll($sql);
}

// ==========================================
// LOST & FOUND FUNCTIONS
// ==========================================

function getAllLostFoundItems($filters = []) {
    $sql = "SELECT * FROM lost_found_items WHERE is_archived = 0";
    
    $params = [];
    
    if (!empty($filters['search'])) {
        $sql .= " AND item_name LIKE ?";
        $params[] = '%' . $filters['search'] . '%';
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }
    
    $sql .= " ORDER BY date_found DESC";
    
    return fetchAll($sql, $params);
}

function getRecentLostFoundItems($limit = 4) {
    $sql = "SELECT TOP (?) * FROM lost_found_items 
            WHERE is_archived = 0 
            ORDER BY date_found DESC";
    
    return fetchAll($sql, [$limit]);
}

function getLostFoundStatistics() {
    $stats = [
        'total_unclaimed' => 0,
        'total_claimed' => 0
    ];
    
    $stats['total_unclaimed'] = fetchValue(
        "SELECT COUNT(*) FROM lost_found_items WHERE status = 'Unclaimed' AND is_archived = 0"
    );
    
    $stats['total_claimed'] = fetchValue(
        "SELECT COUNT(*) FROM lost_found_items WHERE status = 'Claimed' AND is_archived = 0"
    );
    
    return $stats;
}

// ==========================================
// STUDENT FUNCTIONS
// ==========================================

function getStudentById($studentId) {
    $sql = "SELECT * FROM students WHERE student_id = ?";
    return fetchOne($sql, [$studentId]);
}

function getAllStudents() {
    $sql = "SELECT * FROM students ORDER BY last_name, first_name";
    return fetchAll($sql);
}

function updateStudentOffenseCount($studentId) {
    $sql = "UPDATE students SET 
            total_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = ? AND is_archived = 0),
            major_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = ? AND severity = 'Major' AND is_archived = 0),
            minor_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = ? AND severity = 'Minor' AND is_archived = 0),
            last_incident_date = (SELECT MAX(date_reported) FROM cases WHERE student_id = ?)
            WHERE student_id = ?";
    
    executeQuery($sql, [$studentId, $studentId, $studentId, $studentId, $studentId]);
}

// ==========================================
// NOTIFICATION FUNCTIONS
// ==========================================

function createNotification($userId, $title, $message, $type = 'system', $relatedId = null) {
    $sql = "INSERT INTO notifications (user_id, title, message, type, related_id)
            VALUES (?, ?, ?, ?, ?)";
    
    executeQuery($sql, [$userId, $title, $message, $type, $relatedId]);
}

function getUnreadNotifications($userId) {
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC";
    
    return fetchAll($sql, [$userId]);
}

function markNotificationAsRead($notificationId) {
    $sql = "UPDATE notifications SET is_read = 1, read_at = GETDATE() WHERE notification_id = ?";
    executeQuery($sql, [$notificationId]);
}

// ==========================================
// AUDIT LOG FUNCTIONS
// ==========================================

function logAudit($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    $sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $userId,
        $action,
        $tableName,
        $recordId,
        $oldValues ? json_encode($oldValues) : null,
        $newValues ? json_encode($newValues) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    executeQuery($sql, $params);
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function getStatusColor($status) {
    $colors = [
        'Pending' => 'yellow',
        'Under Review' => 'blue',
        'Resolved' => 'green',
        'Escalated' => 'red',
        'Dismissed' => 'gray'
    ];
    
    return $colors[$status] ?? 'gray';
}

// ==========================================
// SIDEBAR FUNCTIONS
// ==========================================

function get_sidebar_items($role) {
    $items = [];
    
    if ($role === 'super_admin') {
        $items = [
            [
                'label' => 'Dashboard',
                'path' => '/PrototypeDO/modules/do/doDashboard.php',
                'icon' => 'dashboard-icon.png'
            ],
            [
                'label' => 'Cases',
                'path' => '/PrototypeDO/modules/do/cases.php',
                'icon' => 'cases-icon.png'
            ],
            [
                'label' => 'Statistics',
                'path' => '/PrototypeDO/modules/do/statistics.php',
                'icon' => 'statistics-icon.png'
            ],
            [
                'label' => 'Lost & Found',
                'path' => '/PrototypeDO/modules/do/lostAndFound.php',
                'icon' => 'Lost-and-found-icon.png'
            ],
            [
                'label' => 'Student History',
                'path' => '/PrototypeDO/modules/do/studentHistory.php',
                'icon' => 'student-history-icon.png'
            ],
            [
                'label' => 'Reports',
                'path' => '/PrototypeDO/modules/do/reports.php',
                'icon' => 'reports-icon.png'
            ],
            [
                'label' => 'Calendar',
                'path' => '/PrototypeDO/modules/do/calendar.php',
                'icon' => 'calendar-icon.png'
            ],
            [
                'label' => 'Handbook',
                'path' => '/PrototypeDO/modules/shared/studentHandbook.php',
                'icon' => 'Student-handbook-icon.png'
            ],
            [
                'label' => 'Users',
                'path' => '/PrototypeDO/modules/super-admin/adminUsers.php',
                'icon' => 'users-icon.png'
            ],
            [
                'label' => 'Audit Log',
                'path' => '/PrototypeDO/modules/do/auditLog.php',
                'icon' => 'Audit-log-icon.png'
            ]
        ];
    } elseif ($role === 'discipline_office' || $role === 'do') {
        $items = [
            [
                'label' => 'Dashboard',
                'path' => '/PrototypeDO/modules/do/doDashboard.php',
                'icon' => 'dashboard-icon.png'
            ],
            [
                'label' => 'Cases',
                'path' => '/PrototypeDO/modules/do/cases.php',
                'icon' => 'cases-icon.png'
            ],
            [
                'label' => 'Statistics',
                'path' => '/PrototypeDO/modules/do/statistics.php',
                'icon' => 'statistics-icon.png'
            ],
            [
                'label' => 'Lost & Found',
                'path' => '/PrototypeDO/modules/do/lostAndFound.php',
                'icon' => 'Lost-and-found-icon.png'
            ],
            [
                'label' => 'Student History',
                'path' => '/PrototypeDO/modules/do/studentHistory.php',
                'icon' => 'student-history-icon.png'
            ],
            [
                'label' => 'Reports',
                'path' => '/PrototypeDO/modules/do/reports.php',
                'icon' => 'reports-icon.png'
            ],
            [
                'label' => 'Calendar',
                'path' => '/PrototypeDO/modules/do/calendar.php',
                'icon' => 'calendar-icon.png'
            ],
            [
                'label' => 'Handbook',
                'path' => '/PrototypeDO/modules/shared/studentHandbook.php',
                'icon' => 'Student-handbook-icon.png'
            ]
        ];
    } elseif ($role === 'student') {
        $items = [
            [
                'label' => 'Dashboard',
                'path' => '/PrototypeDO/modules/do/shared/studentHandbook.php',
                'icon' => 'dashboard-icon.png'
            ],
            [
                'label' => 'My Cases',
                'path' => '/PrototypeDO/modules/student/myCases.php',
                'icon' => 'cases-icon.png'
            ],
            [
                'label' => 'Handbook',
                'path' => '/PrototypeDO/modules/shared/studentHandbook.php',
                'icon' => 'handbook-icon.png'
            ]
        ];
    } elseif ($role === 'teacher' || $role === 'security') {
        $items = [
            [
                'label' => 'Dashboard',
                'path' => '/PrototypeDO/modules/teacher/teacherDashboard.php',
                'icon' => 'dashboard-icon.png'
            ],
            [
                'label' => 'Report Student',
                'path' => '/PrototypeDO/modules/teacher/reportStudent.php',
                'icon' => 'report-icon.png'
            ],
            [
                'label' => 'My Reports',
                'path' => '/PrototypeDO/modules/teacher/myReports.php',
                'icon' => 'cases-icon.png'
            ],
            [
                'label' => 'Handbook',
                'path' => 'PrototypeDO/modules/shared/studentHandbook.php',
                'icon' => 'Student-handbook-icon.png'
            ]
        ];
    }
    
    return $items;
}
?>