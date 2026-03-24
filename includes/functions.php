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

/**
 * Get the full name of a user for consistent display
 * Format: First Name Last Name (without middle name)
 * For students: Uses first_name last_name from students table
 * For others: Extracts first and last from full_name in users table
 */
function getFormattedUserName($userId = null) {
    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    if (!$userId) {
        return 'User';
    }
    
    // Check if user is a student and get their name from students table
    $role = $_SESSION['user_role'] ?? null;
    if ($role === 'student') {
        $sql = "SELECT first_name, last_name FROM students WHERE user_id = ?";
        $student = fetchOne($sql, [$userId]);
        if ($student) {
            return $student['first_name'] . ' ' . $student['last_name'];
        }
    }
    
    // For non-students, extract first and last name from full_name field
    $user = getUserById($userId);
    if ($user && !empty($user['full_name'])) {
        $nameParts = explode(' ', trim($user['full_name']));
        
        if (count($nameParts) === 1) {
            // Single name, return as-is
            return $nameParts[0];
        } elseif (count($nameParts) === 2) {
            // First and Last name
            return $nameParts[0] . ' ' . $nameParts[1];
        } else {
            // Multiple names - take first and last, skip middle names
            return $nameParts[0] . ' ' . end($nameParts);
        }
    }
    
    return 'User';
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
        // Check if search contains space (full name search)
        if (strpos($filters['search'], ' ') !== false) {
            // Split the search term
            $parts = explode(' ', trim($filters['search']));
            $firstName = $parts[0];
            $lastName = isset($parts[1]) ? $parts[1] : '';
            
            // Search for first name + last name combination
            $sql .= " AND (c.case_id LIKE ? OR (s.first_name LIKE ? AND s.last_name LIKE ?) OR s.first_name LIKE ? OR s.last_name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $firstNameTerm = '%' . $firstName . '%';
            $lastNameTerm = '%' . $lastName . '%';
            $params[] = $searchTerm;
            $params[] = $firstNameTerm;
            $params[] = $lastNameTerm;
            $params[] = $searchTerm; // Also search for full term in first_name
            $params[] = $searchTerm; // Also search for full term in last_name
        } else {
            // Single word search (matching original behavior)
            $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR c.case_id LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
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
    
    // Check for duplicate violation on the same day
    $today = date('Y-m-d');
    $duplicateCheck = fetchOne(
        "SELECT case_id FROM cases WHERE student_id = ? AND case_type = ? AND CAST(date_reported AS DATE) = ?",
        [$studentId, $data['case_type'], $today]
    );
    
    if ($duplicateCheck) {
        error_log("Duplicate violation prevented: Student $studentId, Type: " . $data['case_type'] . ", Date: $today");
        return false; // Return false to indicate duplicate prevention
    }
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
    
    // Urgent cases (Major offenses that are not resolved)
    $stats['urgent_cases'] = fetchValue(
        "SELECT COUNT(*) FROM cases WHERE severity = 'Major' AND status != 'Resolved' AND is_archived = 0"
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

/**
 * Categorize a major offense into Category A, B, C, or D based on STI Handbook
 * @param string $offenseName The name of the offense
 * @return string Category A, B, C, or D
 */
function categorizeMajorOffense($offenseName) {
    // Category A - Lighter major offenses
    $categoryA = [
        'Repeated Minor Offenses',
        'Lending/Borrowing ID',
        'Smoking/Vaping on Campus',
        'Intoxication',
        'Allowing Non-STI Entry',
        'Cheating',
        'Plagiarism'
    ];
    
    // Category B - Property/image damage
    $categoryB = [
        'Vandalism',
        'Cyberbullying/Defamation',
        'Privacy Violation',
        'Wearing Uniform in Ill Repute Places',
        'False Testimony',
        'Use of Profane Language'
    ];
    
    // Category C - Serious offenses
    $categoryC = [
        'Hacking',
        'Forgery',
        'Theft',
        'Unauthorized Material Distribution',
        'Embezzlement',
        'Illegal Assembly',
        'Immorality',
        'Bullying',
        'Physical Assault',
        'Drug Use',
        'False Alarms',
        'Misuse of Fire Equipment'
    ];
    
    // Category D - Criminal offenses
    $categoryD = [
        'Drug Possession/Sale',
        'Repeated Drug Use',
        'Weapons Possession',
        'Fraternity/Sorority Membership',
        'Hazing',
        'Moral Turpitude',
        'Sexual Harassment',
        'Subversion/Sedition'
    ];
    
    if (in_array($offenseName, $categoryA)) return 'A';
    if (in_array($offenseName, $categoryB)) return 'B';
    if (in_array($offenseName, $categoryC)) return 'C';
    if (in_array($offenseName, $categoryD)) return 'D';
    
    // Default to Category A if not found
    return 'A';
}

/**
 * Get recommended sanction based on student's offense history for the SAME offense type
 * Following STI Student Handbook escalation rules:
 * - Escalation only triggers for repeated offenses of the same type
 * - 3 occurrences of the same minor offense escalates to Major (Repeated Minor Offenses)
 *
 * @param string $studentId The student ID
 * @param string $currentOffenseType The current offense type (matches cases.case_type)
 * @param string $severity Either "Minor" or "Major"
 * @return array Recommended sanction information
 */
function getRecommendedSanction($studentId, $currentOffenseType, $severity) {
    $student = getStudentById($studentId);

    if (!$student) {
        return [
            'sanction_name' => 'Verbal/Oral Warning',
            'reason' => 'New student - first offense',
            'offense_count' => 1,
            'category' => $severity,
            'subcategory' => null,
            'duration_days' => null
        ];
    }

    // Count active (non-archived) cases of the SAME offense type for this student
    // Only count On Going and Resolved — Pending cases haven't been processed yet
    $sameTypeCountSql = "SELECT COUNT(*) FROM cases 
                         WHERE student_id = ? 
                           AND case_type = ? 
                           AND severity = ?
                           AND status IN ('On Going', 'Resolved')
                           AND is_archived = 0";
    $sameTypeCount = (int) fetchValue($sameTypeCountSql, [$studentId, $currentOffenseType, $severity]);
    // Add 1 for the current case (which is still Pending and not counted above)
    $sameTypeCount = $sameTypeCount + 1;

    // Count archived cases of the same offense type (for informational note only)
    $archivedCountSql = "SELECT COUNT(*) FROM cases 
                         WHERE student_id = ? 
                           AND case_type = ? 
                           AND severity = ?
                           AND is_archived = 1";
    $archivedSameTypeCount = (int) fetchValue($archivedCountSql, [$studentId, $currentOffenseType, $severity]);

    // For Minor Offenses — escalate based on same-type repeat count
    if ($severity === 'Minor') {
        if ($sameTypeCount === 1) {
            return [
                'sanction_name' => 'Verbal/Oral Warning',
                'reason' => 'First offense of this type',
                'offense_count' => 1,
                'category' => 'Minor',
                'subcategory' => null,
                'duration_days' => null,
                'archived_same_type_count' => $archivedSameTypeCount
            ];
        } elseif ($sameTypeCount === 2) {
            return [
                'sanction_name' => 'Written Reprimand',
                'reason' => 'Second offense of the same type',
                'offense_count' => 2,
                'category' => 'Minor',
                'subcategory' => null,
                'duration_days' => null,
                'archived_same_type_count' => $archivedSameTypeCount
            ];
        } else {
            // 3rd or more of the same minor offense → escalates to Major (Repeated Minor Offenses)
            return [
                'sanction_name' => 'Corrective Reinforcement (3-7 days)',
                'reason' => 'Third or more offense of the same type — escalates to Major (Repeated Minor Offenses)',
                'offense_count' => $sameTypeCount,
                'category' => 'Major',
                'subcategory' => 'A',
                'duration_days' => 3,
                'duration_range' => '3-7 days',
                'escalated_to_major' => true,
                'archived_same_type_count' => $archivedSameTypeCount
            ];
        }
    }

    // For Major Offenses — count same-type major cases
    if ($severity === 'Major') {
        $category = categorizeMajorOffense($currentOffenseType);
        $offenseNumber = $sameTypeCount;

        // Category A: Lighter major offenses
        if ($category === 'A') {
            if ($offenseNumber === 1) {
                return [
                    'sanction_name' => 'Corrective Reinforcement (3-7 days)',
                    'reason' => 'First offense of this type (Category A major)',
                    'offense_count' => 1,
                    'category' => 'Major',
                    'subcategory' => 'A',
                    'duration_days' => 3,
                    'duration_range' => '3-7 days',
                    'archived_same_type_count' => $archivedSameTypeCount
                ];
            } elseif ($offenseNumber === 2) {
                return [
                    'sanction_name' => 'Suspension from Class',
                    'reason' => 'Second offense of the same type (Category A major)',
                    'offense_count' => 2,
                    'category' => 'Major',
                    'subcategory' => 'A',
                    'duration_days' => 3,
                    'duration_range' => '3-7 days',
                    'archived_same_type_count' => $archivedSameTypeCount
                ];
            } else {
                return [
                    'sanction_name' => 'Non-readmission',
                    'reason' => 'Third or more offense of the same type (Category A major)',
                    'offense_count' => $offenseNumber,
                    'category' => 'Major',
                    'subcategory' => 'A',
                    'duration_days' => null,
                    'archived_same_type_count' => $archivedSameTypeCount
                ];
            }
        }

        // Category B: Property/image damage
        if ($category === 'B') {
            if ($offenseNumber === 1) {
                return [
                    'sanction_name' => 'Suspension from Class',
                    'reason' => 'First offense of this type (Category B major)',
                    'offense_count' => 1,
                    'category' => 'Major',
                    'subcategory' => 'B',
                    'duration_days' => 3,
                    'duration_range' => '3-7 days',
                    'archived_same_type_count' => $archivedSameTypeCount
                ];
            } else {
                return [
                    'sanction_name' => 'Non-readmission',
                    'reason' => 'Second or more offense of the same type (Category B major)',
                    'offense_count' => $offenseNumber,
                    'category' => 'Major',
                    'subcategory' => 'B',
                    'duration_days' => null,
                    'archived_same_type_count' => $archivedSameTypeCount
                ];
            }
        }

        // Category C: Serious offenses
        if ($category === 'C') {
            if ($offenseNumber === 1) {
                return [
                    'sanction_name' => 'Suspension from Class',
                    'reason' => 'First offense of this type (Category C major)',
                    'offense_count' => 1,
                    'category' => 'Major',
                    'subcategory' => 'C',
                    'duration_days' => 8,
                    'duration_range' => '7-10 days',
                    'archived_same_type_count' => $archivedSameTypeCount
                ];
            } else {
                return [
                    'sanction_name' => 'Non-readmission',
                    'reason' => 'Second or more offense of the same type (Category C major)',
                    'offense_count' => $offenseNumber,
                    'category' => 'Major',
                    'subcategory' => 'C',
                    'duration_days' => null,
                    'archived_same_type_count' => $archivedSameTypeCount
                ];
            }
        }

        // Category D: Criminal offenses — immediate exclusion regardless of count
        if ($category === 'D') {
            return [
                'sanction_name' => 'Exclusion',
                'reason' => 'Criminal offense of this type (Category D) — immediate exclusion/expulsion',
                'offense_count' => $offenseNumber,
                'category' => 'Major',
                'subcategory' => 'D',
                'duration_days' => null,
                'requires_ched_approval' => true,
                'archived_same_type_count' => $archivedSameTypeCount
            ];
        }
    }

    // Fallback
    return [
        'sanction_name' => 'Verbal/Oral Warning',
        'reason' => 'Unable to determine appropriate sanction',
        'offense_count' => 1,
        'category' => $severity,
        'subcategory' => null,
        'duration_days' => null,
        'archived_same_type_count' => $archivedSameTypeCount ?? 0
    ];
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

/**
 * Notify all DO (Discipline Office) users of a new report submission
 * 
 * @param string $caseId The case ID of the new report
 * @param string $studentName The student's name
 * @param string $caseType The type of case
 * @param string $severity The severity level (Major/Minor)
 * @return int Number of notifications sent
 */
function notifyDOOnNewReport($caseId, $studentName, $caseType, $severity) {
    try {
        // Get all DO and super_admin users
        $sql = "SELECT user_id, full_name FROM users WHERE (role = 'discipline_office' OR role = 'do' OR role = 'super_admin') AND is_active = 1";
        $doUsers = fetchAll($sql);
        
        if (empty($doUsers)) {
            return 0;
        }
        
        $count = 0;
        $title = "New Report Submitted - " . $severity;
        
        foreach ($doUsers as $doUser) {
            $message = "New incident report submitted for $studentName. Case Type: $caseType. Severity: $severity. Case ID: $caseId";
            createNotification($doUser['user_id'], $title, $message, 'report_submitted', $caseId);
            $count++;
        }
        
        return $count;
    } catch (Exception $e) {
        error_log("Error in notifyDOOnNewReport: " . $e->getMessage());
        return 0;
    }
}

/**
 * Notify a student when a case is created/reported against them
 * 
 * @param string $studentId The student ID
 * @param string $caseId The case ID
 * @param string $caseType The type of case
 * @param string $severity The severity level (Major/Minor)
 * @return bool True if notification was sent
 */
function notifyStudentOnNewCase($studentId, $caseId, $caseType, $severity) {
    try {
        // Get student info
        $sql = "SELECT user_id, first_name, last_name, student_id FROM students WHERE student_id = ?";
        $student = fetchOne($sql, [$studentId]);
        
        if (!$student) {
            error_log("notifyStudentOnNewCase: Student not found - $studentId");
            return false;
        }
        
        $userId = $student['user_id'];
        
        // If user_id is not linked, try to find it by matching username patterns
        if (!$userId) {
            error_log("notifyStudentOnNewCase: Student user_id is NULL, searching for matching user account");
            
            // Try to find user by searching for username containing student_id or matching name pattern
            $searchUsername = '%' . substr($studentId, -4) . '%'; // Last 4 digits of student ID
            $searchNamePattern = strtolower($student['first_name']) . '%';
            
            $sql = "SELECT user_id, role FROM users WHERE (
                        username LIKE ? 
                        OR username LIKE ?
                        OR email LIKE ?
                    ) LIMIT 1";
            $foundUser = fetchOne($sql, [$searchUsername, $searchNamePattern, $searchUsername]);
            
            if ($foundUser) {
                $userId = $foundUser['user_id'];
                error_log("notifyStudentOnNewCase: Found matching user - user_id $userId with role {$foundUser['role']}");
                
                // Update the student record to link to this user
                try {
                    $updateSql = "UPDATE students SET user_id = ? WHERE student_id = ?";
                    executeQuery($updateSql, [$userId, $studentId]);
                    error_log("notifyStudentOnNewCase: Linked student $studentId to user_id $userId");
                } catch (Exception $e) {
                    error_log("notifyStudentOnNewCase: Could not update student record - " . $e->getMessage());
                }
                
                // If the user's role is not 'student', update it to 'student'
                if ($foundUser['role'] !== 'student') {
                    try {
                        $roleUpdateSql = "UPDATE users SET role = 'student' WHERE user_id = ?";
                        executeQuery($roleUpdateSql, [$userId]);
                        error_log("notifyStudentOnNewCase: Updated user role to 'student' for user_id $userId (was: {$foundUser['role']})");
                    } catch (Exception $roleEx) {
                        error_log("notifyStudentOnNewCase: Could not update user role - " . $roleEx->getMessage());
                    }
                }
            } else {
                error_log("notifyStudentOnNewCase: No matching user found, will create new account");
                
                // Create new user account as fallback
                try {
                    $tempPassword = password_hash('TempPassword123!', PASSWORD_BCRYPT);
                    $username = strtolower(str_replace(' ', '.', $student['first_name'] . '.' . $student['last_name'] . '.' . substr($studentId, -4)));
                    $email = 'student.' . $studentId . '@sti.edu.ph';
                    
                    $sql = "INSERT INTO users (username, password_hash, email, full_name, role, is_active)
                            VALUES (?, ?, ?, ?, ?, 1)";
                    executeQuery($sql, [
                        $username,
                        $tempPassword,
                        $email,
                        $student['first_name'] . ' ' . $student['last_name'],
                        'student'
                    ]);
                    
                    $newUser = fetchOne("SELECT user_id FROM users WHERE username = ?", [$username]);
                    if ($newUser) {
                        $userId = $newUser['user_id'];
                        
                        $updateSql = "UPDATE students SET user_id = ? WHERE student_id = ?";
                        executeQuery($updateSql, [$userId, $studentId]);
                        
                        error_log("notifyStudentOnNewCase: Created new user account - user_id $userId for student $studentId");
                    }
                } catch (Exception $createUserEx) {
                    error_log("notifyStudentOnNewCase: Failed to create user account - " . $createUserEx->getMessage());
                    return false;
                }
            }
        }
        
        if (!$userId) {
            error_log("notifyStudentOnNewCase: Still no user_id available");
            return false;
        }
        
        $title = "New Case - " . $severity;
        $message = "You have been reported for: $caseType. Severity: $severity. Case ID: $caseId. Please check the case details for more information.";
        
        createNotification($userId, $title, $message, 'case_reported', $caseId);
        
        error_log("notifyStudentOnNewCase: Notification sent to user_id {$userId} for case $caseId");
        
        return true;
    } catch (Exception $e) {
        error_log("Error in notifyStudentOnNewCase: " . $e->getMessage());
        return false;
    }
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
        'On Going' => 'blue',
        'Resolved' => 'green',
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
                'label' => 'Statistics & Reports',
                'path' => '/PrototypeDO/modules/do/statistics.php',
                'icon' => 'statistics-icon.png'
            ],
            [
                'label' => 'Lost & Found',
                'path' => '/PrototypeDO/modules/do/lostAndFound.php',
                'icon' => 'Lost-and-found-icon.png'
            ],
            [
                'label' => 'Student List',
                'path' => '/PrototypeDO/modules/do/studentHistory.php',
                'icon' => 'student-history-icon.png'
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
                'label' => 'Statistics & Reports',
                'path' => '/PrototypeDO/modules/do/statistics.php',
                'icon' => 'statistics-icon.png'
            ],
            [
                'label' => 'Lost & Found',
                'path' => '/PrototypeDO/modules/do/lostAndFound.php',
                'icon' => 'Lost-and-found-icon.png'
            ],
            [
                'label' => 'Student List',
                'path' => '/PrototypeDO/modules/do/studentHistory.php',
                'icon' => 'student-history-icon.png'
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
                'label' => 'Audit Log',
                'path' => '/PrototypeDO/modules/do/auditLog.php',
                'icon' => 'Audit-log-icon.png'
            ]
        ];
    } elseif ($role === 'student') {
        $items = [
            [
                'label' => 'Dashboard',
                'path' => '/PrototypeDO/modules/student/studentDashboard.php',
                'icon' => 'dashboard-icon.png'
            ],
            [
                'label' => 'My Cases',
                'path' => '/PrototypeDO/modules/student/studentCases.php',
                'icon' => 'cases-icon.png'
            ],  
            [
                'label' => 'Lost & Found',
                'path' => '/PrototypeDO/modules/shared/searchLostAndFound.php',
                'icon' => 'Lost-and-found-icon.png'
            ],
            [
                'label' => 'Handbook',
                'path' => '/PrototypeDO/modules/shared/studentHandbook.php',
                'icon' => 'Student-handbook-icon.png'
            ],
        ];
    } elseif ($role === 'teacher' || $role === 'security') {
        $items = [
            [
                'label' => 'Report Student',
                'path' => '/PrototypeDO/modules/teacher-guard/studentReport.php',
                'icon' => 'Reports-icon.png'
            ],
            [
                'label' => 'Lost & Found',
                'path' => '/PrototypeDO/modules/shared/searchLostAndFound.php',
                'icon' => 'Lost-and-found-icon.png'
            ],
            [
                'label' => 'Handbook',
                'path' => '/PrototypeDO/modules/shared/studentHandbook.php',
                'icon' => 'Student-handbook-icon.png'
            ],
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

/**
 * Check for scheduling conflicts
 * @param string $scheduleDate - Date in YYYY-MM-DD format
 * @param string $scheduleStartTime - Start time in HH:MM:SS format
 * @param string $scheduleEndTime - End time in HH:MM:SS format (optional)
 * @param int $excludeEventId - Event ID to exclude from conflict check (for updates)
 * @return array - Array of conflicting events or empty array if no conflicts
 */
function checkSchedulingConflicts($scheduleDate, $scheduleStartTime, $scheduleEndTime = null, $excludeEventId = null) {
    if (empty($scheduleDate) || empty($scheduleStartTime)) {
        return [];
    }
    
    // If no end time provided, assume 1-hour duration
    if (empty($scheduleEndTime)) {
        $scheduleEndTime = date('H:i:s', strtotime($scheduleStartTime) + 3600);
    }
    
    // Only check calendar_events table since all scheduled sanctions create calendar events
    // This prevents duplicate conflict messages
    // Filter by current user (DO) - each DO has their own schedule
    $currentUserId = $_SESSION['user_id'] ?? null;
    
    $sql = "SELECT 
                event_id,
                event_name,
                event_date,
                event_time,
                event_end_time,
                category
            FROM calendar_events 
            WHERE event_date = ?
            AND category = 'Hearing'
            AND event_time IS NOT NULL
            AND created_by = ?";
    
    $params = [$scheduleDate, $currentUserId];
    
    if ($excludeEventId !== null) {
        $sql .= " AND event_id != ?";
        $params[] = $excludeEventId;
    }
    
    $events = fetchAll($sql, $params);
    $conflicts = [];
    
    foreach ($events as $event) {
        $eventStart = $event['event_time'];
        $eventEnd = $event['event_end_time'] ?? date('H:i:s', strtotime($eventStart) + 3600);
        
        // Check if time ranges overlap
        // Overlap occurs if: (StartA < EndB) AND (EndA > StartB)
        if (($scheduleStartTime < $eventEnd) && ($scheduleEndTime > $eventStart)) {
            $conflicts[] = [
                'event_name' => $event['event_name'],
                'event_date' => $event['event_date'],
                'event_time' => $eventStart,
                'event_end_time' => $eventEnd,
                'type' => 'calendar_event'
            ];
        }
    }
    
    return $conflicts;
}

function applySanctionToCase($caseId, $sanctionId, $durationDays = null, $notes = '', $scheduleDate = null, $scheduleTime = null, $scheduleNotes = '', $scheduleEndTime = null) {
    // Check for scheduling conflicts if date and time are provided
    if (!empty($scheduleDate) && !empty($scheduleTime)) {
        $conflicts = checkSchedulingConflicts($scheduleDate, $scheduleTime, $scheduleEndTime);
        if (!empty($conflicts)) {
            throw new Exception('Scheduling conflict detected: ' . $conflicts[0]['event_name'] . ' is already scheduled at this time.');
        }
    }
    
    // scheduleTime should already be in HH:MM:SS format from cases.php
    $sql = "INSERT INTO case_sanctions (case_id, sanction_id, duration_days, notes, scheduled_date, scheduled_time, scheduled_end_time, schedule_notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    executeQuery($sql, [$caseId, $sanctionId, $durationDays, $notes, $scheduleDate, $scheduleTime, $scheduleEndTime, $scheduleNotes]);
    
    // Log the sanction
    logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Sanction Applied', null, "Sanction ID: $sanctionId applied");
    
    // If a schedule date is provided, create a calendar event
    if ($scheduleDate) {
        try {
            // Get case and sanction details for the event name
            $case = getCaseById($caseId);
            $sanctionSql = "SELECT sanction_name FROM sanctions WHERE sanction_id = ?";
            $sanction = fetchOne($sanctionSql, [$sanctionId]);
            
            $studentName = $case['student_name'] ?? 'Student';
            $sanctionName = $sanction['sanction_name'] ?? 'Sanction';
            
            $eventName = "Sanction: {$sanctionName} - {$studentName} (Case {$caseId})";
            $description = $scheduleNotes ?: "Scheduled sanction event for Case {$caseId}";
            
            // Create calendar event
            if ($scheduleTime) {
                $eventSql = "INSERT INTO calendar_events (event_name, event_date, event_time, event_end_time, category, description, location, created_by, created_at)
                            VALUES (?, ?, ?, ?, 'Hearing', ?, ?, ?, GETDATE())";
                executeQuery($eventSql, [
                    $eventName,
                    $scheduleDate,
                    $scheduleTime,
                    $scheduleEndTime,
                    $description,
                    'Discipline Office',
                    $_SESSION['user_id'] ?? null
                ]);
            } else {
                $eventSql = "INSERT INTO calendar_events (event_name, event_date, category, description, location, created_by, created_at)
                            VALUES (?, ?, 'Hearing', ?, ?, ?, GETDATE())";
                executeQuery($eventSql, [
                    $eventName,
                    $scheduleDate,
                    $description,
                    'Discipline Office',
                    $_SESSION['user_id'] ?? null
                ]);
            }
            
            error_log("Calendar event created for sanction schedule on {$scheduleDate}");
        } catch (Exception $e) {
            error_log("Error creating calendar event for sanction: " . $e->getMessage());
            // Don't fail the sanction application if calendar event fails
        }
    }
}

function getCaseSanctions($caseId) {
    // First get the basic sanction info
    $sql = "SELECT cs.*, s.sanction_name, s.severity_level, s.description
            FROM case_sanctions cs
            JOIN sanctions s ON cs.sanction_id = s.sanction_id
            WHERE cs.case_id = ?
            ORDER BY cs.applied_date DESC";
    
    $sanctions = fetchAll($sql, [$caseId]);
    
    // For each sanction with a schedule, try to find the corresponding calendar event
    foreach ($sanctions as &$sanction) {
        if ($sanction['scheduled_date']) {
            $eventSql = "SELECT TOP 1 u.full_name as scheduled_by_name
                        FROM calendar_events ce
                        JOIN users u ON ce.created_by = u.user_id
                        WHERE ce.category = 'Hearing'
                        AND ce.event_date = ?
                        AND ce.event_name LIKE ?
                        ORDER BY ce.created_at DESC";
            
            $eventPattern = '%Case ' . $caseId . ')%';
            $eventData = fetchOne($eventSql, [$sanction['scheduled_date'], $eventPattern]);
            
            if ($eventData) {
                $sanction['scheduled_by_name'] = $eventData['scheduled_by_name'];
            }
        }
    }
    
    return $sanctions;
}

// ==========================================
// CASE FUNCTIONS - UPDATED
// ==========================================

function markCaseAsResolved($caseId) {
    $sql = "UPDATE cases SET status = 'Resolved', resolved_date = CAST(GETDATE() AS DATE), updated_at = GETDATE() WHERE case_id = ?";
    executeQuery($sql, [$caseId]);
    
    logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Resolved', 'Previous Status', 'Case marked as resolved');
}

// ==========================================
// CASE ATTACHMENTS/IMAGES FUNCTIONS
// ==========================================

function saveAttachmentForCase($caseId, $file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Create attachments directory if it doesn't exist
    $attachmentsDir = __DIR__ . '/../assets/case_attachments';
    if (!is_dir($attachmentsDir)) {
        mkdir($attachmentsDir, 0755, true);
    }
    
    // Validate file is an image
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowedMimes)) {
        return false;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Generate unique filename
    $filename = uniqid('case_' . $caseId . '_') . '_' . pathinfo($file['name'], PATHINFO_FILENAME) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filepath = $attachmentsDir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return false;
    }
    
    // Return relative path for storage in database
    return '/PrototypeDO/assets/case_attachments/' . $filename;
}

function addCaseAttachments($caseId, $attachmentPaths) {
    // Get current attachments
    $case = getCaseById($caseId);
    $currentAttachments = !empty($case['attachments']) ? json_decode($case['attachments'], true) : [];
    
    // Add new attachments
    if (is_array($attachmentPaths)) {
        $currentAttachments = array_merge($currentAttachments, $attachmentPaths);
    } else {
        $currentAttachments[] = $attachmentPaths;
    }
    
    // Update case with new attachments
    $attachmentsJson = json_encode(array_unique($currentAttachments));
    $sql = "UPDATE cases SET attachments = ?, updated_at = GETDATE() WHERE case_id = ?";
    executeQuery($sql, [$attachmentsJson, $caseId]);
    
    return true;
}

function getCaseAttachments($caseId) {
    $case = getCaseById($caseId);
    if (empty($case['attachments'])) {
        return [];
    }
    
    $attachments = json_decode($case['attachments'], true);
    return is_array($attachments) ? $attachments : [];
}
?>