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
// AUTO-ARCHIVE FUNCTIONS
// ==========================================

/**
 * Automatically archive cases that are 1 year or older
 * based on date_reported
 */
function autoArchiveOldCases() {
    $sql = "UPDATE cases 
            SET is_archived = 1, 
                archived_at = GETDATE(),
                notes = CASE 
                    WHEN notes IS NULL OR notes = '' THEN '[Auto-archived after 1 year]'
                    ELSE CONCAT(notes, ' [Auto-archived after 1 year]')
                END
            WHERE is_archived = 0 
            AND DATEDIFF(year, date_reported, GETDATE()) >= 1
            AND date_reported IS NOT NULL
            AND (manually_restored = 0 OR manually_restored IS NULL)";
    
    try {
        executeQuery($sql);
        
        $countSql = "SELECT @@ROWCOUNT as archived_count";
        $count = fetchValue($countSql);
        
        if ($count > 0) {
            error_log("Auto-archived $count old cases (1+ years old)");
        }
        
        return $count;
    } catch (Exception $e) {
        error_log("Error in autoArchiveOldCases: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check and archive old cases - call this before loading cases
 * This ensures old cases are automatically moved to archive
 * Updated to run less frequently (once per day per user)
 */
function checkAndArchiveOldCases() {
    // Check if auto-archive was already run today for this session
    $today = date('Y-m-d');
    
    if (!isset($_SESSION['auto_archive_date']) || $_SESSION['auto_archive_date'] !== $today) {
        $archivedCount = autoArchiveOldCases();
        $_SESSION['auto_archive_date'] = $today;
        $_SESSION['auto_archive_count'] = $archivedCount;
        return $archivedCount;
    }
    
    return 0;
}

// ==========================================
// CASE FUNCTIONS
// ==========================================

function getAllCases($filters = []) {
    // Auto-archive old cases first (only once per session)
    checkAndArchiveOldCases();
    
    $sql = "SELECT c.*, s.first_name, s.last_name, s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            u.full_name as assigned_to_name
            FROM cases c
            LEFT JOIN students s ON c.student_id = s.student_id
            LEFT JOIN users u ON c.assigned_to = u.user_id
            WHERE 1=1";
    
    $params = [];
    
    // Handle archived filter
    if (isset($filters['archived']) && $filters['archived'] === true) {
        $sql .= " AND c.is_archived = 1";
    } else {
        $sql .= " AND c.is_archived = 0";
    }
    
    // Apply other filters
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

    // Check if student exists
    $studentId = $data['student_number'];
    $existingStudent = getStudentById($studentId);

    if (!$existingStudent) {
        // Parse student name
        $nameParts = explode(' ', trim($data['student_name']));
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';

        // Create new student record
        $sql = "INSERT INTO students (student_id, first_name, last_name, grade_year, track_course, student_type, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        executeQuery($sql, [
            $studentId, 
            $firstName, 
            $lastName, 
            'N/A', 
            'N/A', 
            'College',
            'Good Standing'
        ]);

        error_log("Created new student: $studentId - $firstName $lastName");
    }

    // Try to find matching offense_id
    $offenseId = null;
    $offenseQuery = "SELECT offense_id FROM offense_types WHERE offense_name = ?";
    $offense = fetchOne($offenseQuery, [$data['case_type']]);
    if ($offense) {
        $offenseId = $offense['offense_id'];
    }

    // Create case
    $sql = "INSERT INTO cases (case_id, student_id, offense_id, case_type, severity, 
            status, date_reported, reported_by, assigned_to, description, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $params = [
        $newCaseId,
        $studentId,
        $offenseId,
        $data['case_type'],
        $data['severity'],
        $data['status'] ?? 'Pending',
        date('Y-m-d'),
        $data['reported_by'] ?? null,
        $data['assigned_to'] ?? null,
        $data['description'],
        $data['notes'] ?? ''
    ];
    executeQuery($sql, $params);

    // ✅ Log separately (only once each)
    logCaseHistory($newCaseId, $_SESSION['user_id'] ?? null, 'Created', null, 'Case created');
    auditCreate('cases', $newCaseId, sanitizeAuditData($data));

    return $newCaseId;
}

function updateCase($caseId, $data) {
    // 🧩 Fetch old record for audit before updating
    $oldData = getRecordForAudit('cases', 'case_id', $caseId);
    $oldData = sanitizeAuditData($oldData);

    // Build SQL update query
    $sql = "UPDATE cases SET 
            case_type = ?, 
            severity = ?, 
            status = ?, 
            assigned_to = ?,
            description = ?, 
            notes = ?,
            updated_at = GETDATE()";
    
    $params = [
        $data['case_type'],
        $data['severity'],
        $data['status'],
        $data['assigned_to'] ?? null,
        $data['description'],
        $data['notes'] ?? ''
    ];
    
    // Add date_reported if provided
    if (isset($data['date_reported']) && !empty($data['date_reported'])) {
        $sql .= ", date_reported = ?";
        $params[] = $data['date_reported'];
    }
    
    $sql .= " WHERE case_id = ?";
    $params[] = $caseId;

    // Execute the update
    executeQuery($sql, $params);

    //  Fetch new data after update
    $newData = getRecordForAudit('cases', 'case_id', $caseId);
    $newData = sanitizeAuditData($newData);

    //  Log the change to Audit Log
    auditUpdate('cases', $caseId, $oldData, $newData);

    //  Still log the change in case history
    logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Updated', null, 'Case updated');

    return true;
}


function archiveCase($caseId) {
    //  Get old case data before archiving (for audit)
    $oldData = getRecordForAudit('cases', 'case_id', $caseId);
    $oldData = sanitizeAuditData($oldData);
    $oldStatus = $oldData['status'] ?? 'Unknown';

    //  Archive the case
    $sql = "UPDATE cases SET is_archived = 1, archived_at = GETDATE() WHERE case_id = ?";
    executeQuery($sql, [$caseId]);

    //  Get new data after update (for audit comparison)
    $newData = getRecordForAudit('cases', 'case_id', $caseId);
    $newData = sanitizeAuditData($newData);

    //  Log to Audit Log
    auditArchive('cases', $caseId, $oldStatus);

    //  Also log to Case History
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

/**
 * Get record data before modification (for logging old values)
 * 
 * @param string $tableName The table name
 * @param string $primaryKey The primary key column name
 * @param mixed $recordId The record ID
 * @return array|null The record data or null if not found
 */
function getRecordForAudit($tableName, $primaryKey, $recordId) {
    try {
        $sql = "SELECT * FROM " . $tableName . " WHERE " . $primaryKey . " = ?";
        
        $result = fetchAll($sql, [$recordId]);
        return $result[0] ?? null;
        
    } catch (Exception $e) {
        error_log("Get Record for Audit Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Sanitize data for audit logging (remove sensitive fields)
 * 
 * @param array $data The data to sanitize
 * @return array The sanitized data
 */
function sanitizeAuditData($data) {
    if (!is_array($data)) {
        return $data;
    }
    
    $sensitiveFields = ['password', 'password_hash', 'token', 'secret', 'api_key', 'access_token', 'refresh_token'];
    
    foreach ($sensitiveFields as $field) {
        if (isset($data[$field])) {
            $data[$field] = '[REDACTED]';
        }
    }
    
    return $data;
}

/**
 * Log user login
 * 
 * @param int $userId The user ID
 * @return void
 */
function logLogin($userId) {
    logAudit($userId, 'Login', 'users', $userId, null, ['login_time' => date('Y-m-d H:i:s')]);
}

/**
 * Log user logout
 * 
 * @param int $userId The user ID
 * @return void
 */
function logLogout($userId) {
    logAudit($userId, 'Logout', 'users', $userId, null, ['logout_time' => date('Y-m-d H:i:s')]);
}

/**
 * Log failed login attempt
 * 
 * @param string $username The attempted username
 * @param string $reason The failure reason
 * @return void
 */
function logFailedLogin($username, $reason = 'Invalid credentials') {
    logAudit(null, 'Failed Login', 'users', null, null, [
        'username' => $username,
        'reason' => $reason,
        'attempt_time' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Quick audit helper for CREATE operations
 * 
 * @param string $tableName The table name
 * @param mixed $recordId The record ID
 * @param array $data The new data
 * @return void
 */
function auditCreate($tableName, $recordId, $data) {
    $userId = $_SESSION['user_id'] ?? null;
    logAudit($userId, 'Created', $tableName, $recordId, null, $data);
}

/**
 * Quick audit helper for UPDATE operations
 * 
 * @param string $tableName The table name
 * @param mixed $recordId The record ID
 * @param array $oldData The old data
 * @param array $newData The new data
 * @return void
 */
function auditUpdate($tableName, $recordId, $oldData, $newData) {
    $userId = $_SESSION['user_id'] ?? null;
    logAudit($userId, 'Updated', $tableName, $recordId, $oldData, $newData);
}

/**
 * Quick audit helper for DELETE operations
 * 
 * @param string $tableName The table name
 * @param mixed $recordId The record ID
 * @param array $oldData The old data
 * @return void
 */
function auditDelete($tableName, $recordId, $oldData) {
    $userId = $_SESSION['user_id'] ?? null;
    logAudit($userId, 'Deleted', $tableName, $recordId, $oldData, null);
}

/**
 * Quick audit helper for ARCHIVE operations
 * 
 * @param string $tableName The table name
 * @param mixed $recordId The record ID
 * @param string $oldStatus The old status
 * @return void
 */
function auditArchive($tableName, $recordId, $oldStatus) {
    $userId = $_SESSION['user_id'] ?? null;
    logAudit($userId, 'Archived', $tableName, $recordId, 
        ['status' => $oldStatus], 
        ['status' => 'Archived', 'archived_date' => date('Y-m-d H:i:s')]
    );
}

/**
 * Quick audit helper for RESTORE operations
 * 
 * @param string $tableName The table name
 * @param mixed $recordId The record ID
 * @param string $oldStatus The old status
 * @return void
 */
function auditRestore($tableName, $recordId, $oldStatus) {
    $userId = $_SESSION['user_id'] ?? null;
    logAudit($userId, 'Restored', $tableName, $recordId, 
        ['status' => $oldStatus], 
        ['status' => 'Active', 'restored_date' => date('Y-m-d H:i:s')]
    );
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
// ==========================================
// OFFENSE TYPES FUNCTIONS
// ==========================================

function getOffenseTypesByCategory($category) {
    $sql = "SELECT offense_id, offense_name, description 
            FROM offense_types 
            WHERE category = ? AND is_active = 1 
            ORDER BY offense_name";
    return fetchAll($sql, [$category]);
}

function getAllOffenseTypes() {
    $sql = "SELECT offense_id, offense_name, category, description 
            FROM offense_types 
            WHERE is_active = 1 
            ORDER BY category, offense_name";
    return fetchAll($sql);
}

// ==========================================
// SANCTIONS FUNCTIONS
// ==========================================

function getAllSanctions() {
    $sql = "SELECT * FROM sanctions WHERE is_active = 1 ORDER BY severity_level, sanction_name";
    return fetchAll($sql);
}

function applySanctionToCase($caseId, $sanctionId, $durationDays = null, $notes = '') {
    $sql = "INSERT INTO case_sanctions (case_id, sanction_id, duration_days, notes)
            VALUES (?, ?, ?, ?)";
    
    executeQuery($sql, [$caseId, $sanctionId, $durationDays, $notes]);
    
    // Log the sanction
    logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Sanction Applied', null, "Sanction ID: $sanctionId applied");
}

function getCaseSanctions($caseId) {
    $sql = "SELECT cs.*, s.sanction_name, s.severity_level, s.description
            FROM case_sanctions cs
            JOIN sanctions s ON cs.sanction_id = s.sanction_id
            WHERE cs.case_id = ?
            ORDER BY cs.applied_date DESC";
    
    return fetchAll($sql, [$caseId]);
}

// ==========================================
// CASE FUNCTIONS - UPDATED
// ==========================================

function markCaseAsResolved($caseId) {
    $sql = "UPDATE cases SET status = 'Resolved', resolved_date = CAST(GETDATE() AS DATE), updated_at = GETDATE() WHERE case_id = ?";
    executeQuery($sql, [$caseId]);
    
    logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Resolved', 'Previous Status', 'Case marked as resolved');
}
?>