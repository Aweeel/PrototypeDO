<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// ============================================================
//  CSV DOWNLOAD — Community Service Check-In Report
// ============================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv' && isset($_GET['type']) && $_GET['type'] === 'checkin') {
    ensureCaseSanctionsDeadlineColumns();

    $caseId = $_GET['caseId'] ?? '';
    $studentName = $_GET['studentName'] ?? 'Student';
    $sanctionName = $_GET['sanctionName'] ?? 'Community Service';
    
    if (empty($caseId)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Case ID required']);
        exit;
    }

    // Fetch check-in data for this case
    $sql = "SELECT cs.*, s.sanction_name 
            FROM case_sanctions cs
            JOIN sanctions s ON cs.sanction_id = s.sanction_id
            WHERE cs.case_id = ? AND cs.duration_days > 0
            ORDER BY cs.applied_date DESC";
    $sanctions = fetchAll($sql, [$caseId]);

    if (empty($sanctions)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No check-in data found']);
        exit;
    }

    // Get check-in records
    $sanction = $sanctions[0];
    $totalDays = intval($sanction['duration_days']);
    $extraHours = max(0, intval($sanction['duration_extra_hours'] ?? 0));
    $totalHours = $totalDays > 0
        ? ($extraHours > 0 ? (($totalDays - 1) * 8) + $extraHours : ($totalDays * 8))
        : 0;
    $checkInSql = "WITH ranked AS (
                       SELECT *,
                              ROW_NUMBER() OVER (PARTITION BY day_number ORDER BY COALESCE(updated_at, created_at) DESC, checkin_id DESC) AS rn
                       FROM case_checkins
                       WHERE case_sanction_id = ?
                   )
                   SELECT * FROM ranked WHERE rn = 1
                   ORDER BY day_number ASC";
    $checkIns = fetchAll($checkInSql, [$sanction['case_sanction_id']]);
    
    // Build CSV data
    $filename = 'STI_CheckIn_Case_' . $caseId . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache');

    $out = fopen('php://output', 'w');
    // BOM for Excel UTF-8 compatibility
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header rows
    fputcsv($out, ['STI Discipline Office – Community Service Check-In Report']);
    fputcsv($out, ['Generated:', date('F d, Y H:i')]);
    fputcsv($out, []);
    fputcsv($out, ['Case ID:', $caseId]);
    fputcsv($out, ['Student:', $studentName]);
    fputcsv($out, ['Sanction Type:', $sanctionName]);
    fputcsv($out, ['Duration:', $totalHours . ' hours']);
    fputcsv($out, []);

    // Data headers
    fputcsv($out, ['Day Number', 'Check-In Time', 'Check-Out Time', 'Status']);

    // Data rows
    $completedHours = 0;
    for ($day = 1; $day <= $totalDays; $day++) {
        $dayData = null;
        foreach ($checkIns as $record) {
            if ($record['day_number'] == $day) {
                $dayData = $record;
                break;
            }
        }

        $inTime = '—';
        $outTime = '—';
        $status = 'Pending';

        if ($dayData) {
            if ($dayData['check_in_time']) {
                $inTime = date('h:i A', strtotime($dayData['check_in_time']));
            }
            if ($dayData['check_out_time']) {
                $outTime = date('h:i A', strtotime($dayData['check_out_time']));
            }

            if ($dayData['check_in_time'] && $dayData['check_out_time']) {
                $status = 'Completed';

                $inTs = strtotime($dayData['check_in_time']);
                $outTs = strtotime($dayData['check_out_time']);
                if ($inTs !== false && $outTs !== false && $outTs > $inTs) {
                    $hours = min(8, ($outTs - $inTs) / 3600);
                    $completedHours += $hours;
                }
            } else if ($dayData['check_in_time']) {
                $status = 'In Progress';
            }
        }

        fputcsv($out, [$day, $inTime, $outTime, $status]);
    }

    // Summary
    fputcsv($out, []);
    fputcsv($out, ['Summary']);
    fputcsv($out, ['Total Hours:', $totalHours]);
    fputcsv($out, ['Completed Hours:', number_format($completedHours, 2)]);
    fputcsv($out, ['Progress:', $totalHours > 0 ? round(($completedHours / $totalHours) * 100) . '%' : '0%']);

    fclose($out);
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['ajax']) || isset($_POST['action']))) {
    // Ensure AJAX endpoints always return clean JSON payloads.
    ini_set('display_errors', 0);

    if (!ob_get_level()) {
        ob_start();
    }

    header('Content-Type: application/json');
    ensureCommunityServiceSubmissionTable();

    $getMaxAllowedDayByDeadline = function ($caseSanctionId) {
        $meta = fetchOne(
            "SELECT applied_date, deadline
             FROM case_sanctions
             WHERE case_sanction_id = ?",
            [$caseSanctionId]
        );

        if (!$meta) {
            return ['ok' => false, 'error' => 'Case sanction not found'];
        }

        if (empty($meta['deadline'])) {
            return ['ok' => true, 'maxDay' => null];
        }

        try {
            $startDate = new DateTime($meta['applied_date'] ?? date('Y-m-d'));
            $startDate->setTime(0, 0, 0);

            $deadlineDate = new DateTime($meta['deadline']);
            $deadlineDate->setTime(0, 0, 0);

            if ($deadlineDate < $startDate) {
                return ['ok' => true, 'maxDay' => 0];
            }

            $maxDay = intval($startDate->diff($deadlineDate)->days);
            return ['ok' => true, 'maxDay' => max(1, $maxDay)];
        } catch (Exception $e) {
            return ['ok' => true, 'maxDay' => null];
        }
    };

    // Mark password warning as shown in this login session
    if (isset($_POST['action']) && $_POST['action'] === 'markPasswordWarningShown') {
        $_SESSION['password_warning_modal_shown'] = true;
        echo json_encode(['success' => true, 'message' => 'Password warning marked as shown']);
        exit;
    }

    // Mark notification as read
    if (isset($_POST['action']) && $_POST['action'] === 'markNotificationAsRead') {
        $notificationId = $_POST['notificationId'] ?? null;
        
        if ($notificationId) {
            try {
                markNotificationAsRead($notificationId);
                echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Notification ID required']);
        }
        exit;
    }

    try {
        // Get all students for dropdown
        if ($_POST['action'] === 'getStudents') {
            $students = getAllStudents();
            echo json_encode(['success' => true, 'students' => $students]);
            exit;
        }

        // Get cases with filters
        if ($_POST['action'] === 'getCases') {
            ensureCaseSanctionsDeadlineColumns();

            $filters = [
                'search' => $_POST['search'] ?? '',
                'type' => $_POST['type'] ?? '',
                'status' => $_POST['status'] ?? '',
                'archived' => isset($_POST['archived']) && $_POST['archived'] === 'true' ? true : false
            ];

            $cases = getAllCases($filters);

            $countSchoolDaysInclusive = function ($startDateStr, $endDateStr) {
                if (empty($startDateStr) || empty($endDateStr)) {
                    return 0;
                }

                $start = strtotime(date('Y-m-d', strtotime($startDateStr)));
                $end = strtotime(date('Y-m-d', strtotime($endDateStr)));

                if ($start === false || $end === false || $start > $end) {
                    return 0;
                }

                $days = 0;
                for ($ts = $start; $ts <= $end; $ts += 86400) {
                    // Monday=1 ... Saturday=6, Sunday=7
                    $isoDay = intval(date('N', $ts));
                    if ($isoDay >= 1 && $isoDay <= 6) {
                        $days++;
                    }
                }

                return $days;
            };

            $inferSanctionDurationDays = function ($durationValue, $sanctionName) {
                $stored = intval($durationValue);
                if ($stored > 0) {
                    return $stored;
                }

                $name = strtolower((string)$sanctionName);

                if (preg_match('/(\d+)\s*-\s*(\d+)\s*days?/i', $name, $rangeMatch)) {
                    $minDays = intval($rangeMatch[1]);
                    if ($minDays > 0) {
                        return $minDays;
                    }
                }

                if (preg_match('/(\d+)\s*days?/i', $name, $singleMatch)) {
                    $explicitDays = intval($singleMatch[1]);
                    if ($explicitDays > 0) {
                        return $explicitDays;
                    }
                }

                if (strpos($name, 'corrective reinforcement') !== false || strpos($name, 'suspension from class') !== false) {
                    return 3;
                }

                return 0;
            };

            // Format data for JavaScript
            $formattedCases = array_map(function ($case) use ($countSchoolDaysInclusive, $inferSanctionDurationDays) {
                $attachments = [];
                if (!empty($case['attachments'])) {
                    $attachments = json_decode($case['attachments'], true);
                    if (!is_array($attachments)) {
                        $attachments = [];
                    }
                }
                
                // Check if this case has a Corrective Reinforcement sanction applied
                $csCheckSql = "SELECT COUNT(*) as cnt FROM case_sanctions cs
                               JOIN sanctions s ON cs.sanction_id = s.sanction_id
                               WHERE cs.case_id = ? AND LOWER(s.sanction_name) LIKE '%corrective%'";
                $csCheck = fetchOne($csCheckSql, [$case['case_id']]);
                $hasCorrectiveService = ($csCheck && intval($csCheck['cnt']) > 0);

                // Check if this case has a Suspension from Class sanction applied.
                $suspensionCheckSql = "SELECT COUNT(*) as cnt FROM case_sanctions cs
                                      JOIN sanctions s ON cs.sanction_id = s.sanction_id
                                      WHERE cs.case_id = ? AND LOWER(s.sanction_name) LIKE '%suspension from class%'";
                $suspensionCheck = fetchOne($suspensionCheckSql, [$case['case_id']]);
                $hasSuspensionFromClass = ($suspensionCheck && intval($suspensionCheck['cnt']) > 0);

                // Compute whether corrective service is fully completed (for green icon state).
                $completionSql = "SELECT cs.case_sanction_id, cs.duration_days, cs.duration_extra_hours, s.sanction_name,
                                         (SELECT COALESCE(SUM(
                                            CASE
                                                WHEN cci.check_in_time IS NOT NULL
                                                 AND cci.check_out_time IS NOT NULL
                                                 AND DATEDIFF(MINUTE, cci.check_in_time, cci.check_out_time) > 0
                                                THEN CASE
                                                    WHEN DATEDIFF(MINUTE, cci.check_in_time, cci.check_out_time) > 480 THEN 8.0
                                                    ELSE CAST(DATEDIFF(MINUTE, cci.check_in_time, cci.check_out_time) AS FLOAT) / 60.0
                                                END
                                                ELSE 0
                                            END
                                         ), 0)
                                          FROM (
                                              SELECT check_in_time, check_out_time,
                                                     ROW_NUMBER() OVER (
                                                         PARTITION BY day_number
                                                         ORDER BY COALESCE(updated_at, created_at) DESC, checkin_id DESC
                                                     ) AS rn
                                              FROM case_checkins
                                              WHERE case_sanction_id = cs.case_sanction_id
                                          ) cci
                                          WHERE cci.rn = 1) AS completed_hours
                                  FROM case_sanctions cs
                                  JOIN sanctions s ON cs.sanction_id = s.sanction_id
                                  WHERE cs.case_id = ?
                                    AND LOWER(s.sanction_name) LIKE '%corrective%'";
                $completionRows = fetchAll($completionSql, [$case['case_id']]);
                $hasCorrectiveServiceCompleted = false;
                if (!empty($completionRows)) {
                    $hasCorrectiveServiceCompleted = true;
                    $hasTrackableCorrective = false;
                    foreach ($completionRows as $row) {
                        $requiredDays = $inferSanctionDurationDays($row['duration_days'] ?? null, $row['sanction_name'] ?? '');
                        if ($requiredDays <= 0) {
                            continue;
                        }

                        $extraHours = max(0, intval($row['duration_extra_hours'] ?? 0));
                        $required = $requiredDays > 0
                            ? ($extraHours > 0 ? (($requiredDays - 1) * 8) + $extraHours : ($requiredDays * 8))
                            : 0;
                        $hasTrackableCorrective = true;
                        $done = floatval($row['completed_hours'] ?? 0);
                        if ($required <= 0 || $done < $required) {
                            $hasCorrectiveServiceCompleted = false;
                            break;
                        }
                    }

                    if (!$hasTrackableCorrective) {
                        $hasCorrectiveServiceCompleted = false;
                    }
                }

                // Compute whether Suspension from Class is fully completed based on elapsed days.
                $suspensionCompletionSql = "SELECT cs.case_sanction_id, cs.duration_days, cs.applied_date, s.sanction_name
                                            FROM case_sanctions cs
                                            JOIN sanctions s ON cs.sanction_id = s.sanction_id
                                            WHERE cs.case_id = ?
                                              AND LOWER(s.sanction_name) LIKE '%suspension from class%'";
                $suspensionCompletionRows = fetchAll($suspensionCompletionSql, [$case['case_id']]);
                $hasSuspensionFromClassCompleted = false;
                if (!empty($suspensionCompletionRows)) {
                    $hasSuspensionFromClassCompleted = true;
                    $hasTrackableSuspension = false;
                    foreach ($suspensionCompletionRows as $row) {
                        $required = $inferSanctionDurationDays($row['duration_days'] ?? null, $row['sanction_name'] ?? '');
                        if ($required <= 0) {
                            continue;
                        }
                        $hasTrackableSuspension = true;
                        $elapsedDays = $countSchoolDaysInclusive(
                            $row['applied_date'] ?? null,
                            date('Y-m-d', strtotime('-1 day'))
                        );

                        $done = min($required, $elapsedDays);
                        if ($required <= 0 || $done < $required) {
                            $hasSuspensionFromClassCompleted = false;
                            break;
                        }
                    }

                    if (!$hasTrackableSuspension) {
                        $hasSuspensionFromClassCompleted = false;
                    }
                }

                $newSubmissionCountRow = fetchOne(
                    "SELECT COUNT(*) AS cnt
                     FROM community_service_submissions css
                     JOIN case_sanctions cs ON cs.case_sanction_id = css.case_sanction_id
                     JOIN sanctions s ON s.sanction_id = cs.sanction_id
                     WHERE css.case_id = ?
                       AND css.is_seen_by_do = 0
                       AND (
                            LOWER(s.sanction_name) LIKE '%corrective%'
                                                        OR LOWER(s.sanction_name) LIKE '%community service%'
                                                        OR LOWER(s.sanction_name) LIKE '%suspension from class%'
                       )",
                    [$case['case_id']]
                );
                $hasNewCommunityServiceSubmission = intval($newSubmissionCountRow['cnt'] ?? 0) > 0;

                return [
                    'id' => $case['case_id'],
                    'student' => $case['student_name'],
                    'studentId' => $case['student_id'],
                    'type' => $case['case_type'],
                    'date' => formatDate($case['date_reported']),
                    'status' => $case['status'],
                    'assignedTo' => $case['assigned_to_name'] ?? 'Unassigned',
                    'statusColor' => getStatusColor($case['status']),
                    'description' => $case['description'] ?? '',
                    'notes' => $case['notes'] ?? '',
                    'severity' => $case['severity'] ?? 'Minor',
                    'attachments' => $attachments,
                    'hasCorrectiveService' => $hasCorrectiveService,
                    'hasCorrectiveServiceCompleted' => $hasCorrectiveServiceCompleted,
                    'hasNewCommunityServiceSubmission' => $hasNewCommunityServiceSubmission,
                    'hasSuspensionFromClass' => $hasSuspensionFromClass,
                    'hasSuspensionFromClassCompleted' => $hasSuspensionFromClassCompleted
                ];
            }, $cases);

            $payload = ['success' => true, 'cases' => $formattedCases];
            $json = json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);

            if ($json === false) {
                throw new Exception('Failed to encode cases payload.');
            }

            if (ob_get_length()) {
                ob_clean();
            }

            echo $json;
            exit;
        }

        // Create new case
if ($_POST['action'] === 'createCase') {
    $data = [
        'student_number' => $_POST['studentNumber'],
        'student_name' => $_POST['studentName'],
        'case_type' => $_POST['type'],
        'severity' => $_POST['severity'] ?? 'Minor',
        'status' => 'Pending', // All new cases start as Pending
        'assigned_to' => $_SESSION['user_id'] ?? null,
        'reported_by' => $_SESSION['user_id'] ?? null,
        'description' => $_POST['description'],
        'notes' => $_POST['notes'] ?? ''
    ];

    $newCaseId = createCase($data);
    
    // Check if violation was prevented due to duplication
    if ($newCaseId === false) {
        echo json_encode([
            'success' => false, 
            'message' => 'Duplicate case: This violation has already been recorded for this student today.'
        ]);
        exit;
    }

    updateStudentOffenseCount($data['student_number']);
    
    // Notify the student that a case has been created for them
    notifyStudentOnNewCase(
        $data['student_number'],
        $newCaseId,
        $data['case_type'],
        $data['severity']
    );
    
    echo json_encode(['success' => true, 'caseId' => $newCaseId, 'message' => 'Case created successfully']);
    exit;
}


        // Get student by student number
if ($_POST['action'] === 'getStudentByNumber') {
    $studentNumber = $_POST['studentNumber'] ?? '';
    
    if (empty($studentNumber)) {
        echo json_encode(['success' => false, 'error' => 'Student number required']);
        exit;
    }
    
    $student = getStudentById($studentNumber);
    
    if ($student) {
        echo json_encode([
            'success' => true,
            'student' => [
                'student_id' => $student['student_id'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'full_name' => $student['first_name'] . ' ' . $student['last_name']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
    }
    exit;
}

        // Update existing case
if ($_POST['action'] === 'updateCase') {
    $caseId = $_POST['caseId'];
    $data = [
        'case_type' => $_POST['type'],
        'severity' => $_POST['severity'] ?? 'Minor',
        'status' => $_POST['status'],
        'date_reported' => $_POST['dateReported'] ?? null,
        'assigned_to' => $_SESSION['user_id'] ?? null,
        'description' => $_POST['description'],
        'notes' => $_POST['notes'] ?? ''
    ];

    // Get old data before update
    $oldData = sanitizeAuditData(getRecordForAudit('cases', 'case_id', $caseId));

    updateCase($caseId, $data);

 
    // Update student offense count
    $case = getCaseById($caseId);
    if ($case) updateStudentOffenseCount($case['student_id']);

    echo json_encode(['success' => true, 'message' => 'Case updated successfully']);
    exit;
}

        // Archive case
if ($_POST['action'] === 'archiveCase') {
    $caseId = $_POST['caseId'];
    $oldData = getRecordForAudit('cases', 'case_id', $caseId);
    $oldStatus = $oldData['status'] ?? 'Unknown';

    archiveCase($caseId);



    echo json_encode(['success' => true, 'message' => 'Case archived successfully']);
    exit;
}


        // Unarchive case
if ($_POST['action'] === 'unarchiveCase') {
    $caseId = $_POST['caseId'];
    $sql = "UPDATE cases SET is_archived = 0, archived_at = NULL, manually_restored = 1 WHERE case_id = ?";
    executeQuery($sql, [$caseId]);

    logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Unarchived', null, 'Case manually restored by user');

    // 🧾 Audit Log
    $oldData = getRecordForAudit('cases', 'case_id', $caseId);
    $oldStatus = $oldData['status'] ?? 'Archived';
    auditRestore('cases', $caseId, $oldStatus);

    echo json_encode(['success' => true, 'message' => 'Case restored successfully']);
    exit;
}

        // Unarchive multiple cases
if ($_POST['action'] === 'unarchiveCases') {
    $caseIds = $_POST['caseIds'] ?? [];
    
    if (empty($caseIds) || !is_array($caseIds)) {
        echo json_encode(['success' => false, 'error' => 'No cases selected']);
        exit;
    }

    $restoredCount = 0;
    $failedCount = 0;
    
    foreach ($caseIds as $caseId) {
        try {
            $sql = "UPDATE cases SET is_archived = 0, archived_at = NULL, manually_restored = 1 WHERE case_id = ? AND is_archived = 1";
            $stmt = executeQuery($sql, [$caseId]);
            $affected = $stmt->rowCount();

            if ($affected > 0) {
                logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Unarchived', null, 'Case bulk restored by user');

                // 🧾 Audit Log
                $oldData = getRecordForAudit('cases', 'case_id', $caseId);
                $oldStatus = $oldData['status'] ?? 'Archived';
                auditRestore('cases', $caseId, $oldStatus);
                
                $restoredCount++;
            } else {
                $failedCount++;
            }
        } catch (Exception $e) {
            error_log("Failed to restore case {$caseId}: " . $e->getMessage());
            $failedCount++;
        }
    }

    $message = "{$restoredCount} case(s) restored successfully";
    if ($failedCount > 0) {
        $message .= ", {$failedCount} failed";
    }

    echo json_encode(['success' => true, 'message' => $message, 'restored' => $restoredCount, 'failed' => $failedCount]);
    exit;
}

        // Update sanction
if ($_POST['action'] === 'updateSanction') {
    $caseSanctionId = $_POST['caseSanctionId'];
    $durationDays = $_POST['durationDays'] ?? null;
    $notes = $_POST['notes'] ?? '';

    // Get old data for audit comparison
    $oldSql = "SELECT * FROM case_sanctions WHERE case_sanction_id = ?";
    $oldData = fetchOne($oldSql, [$caseSanctionId]);

    $sql = "UPDATE case_sanctions SET duration_days = ?, notes = ? WHERE case_sanction_id = ?";
    executeQuery($sql, [$durationDays, $notes, $caseSanctionId]);

    // 🧾 Audit Log - Use specialized duration increase audit if duration changed
    if ($oldData && $oldData['duration_days'] != $durationDays) {
        $sanctionSql = "SELECT sanction_name FROM sanctions WHERE sanction_id = ?";
        $sanctionInfo = fetchOne($sanctionSql, [$oldData['sanction_id']]);
        $sanctionName = $sanctionInfo['sanction_name'] ?? 'Unknown Sanction';
        
        auditSanctionDurationIncreased($oldData['case_id'], $sanctionName, $oldData['duration_days'], $durationDays);
    } else {
        // Log as generic update if only notes changed
        auditUpdate('case_sanctions', $caseSanctionId, 
            ['notes' => $oldData['notes'] ?? null], 
            ['notes' => $notes]
        );
    }

    echo json_encode(['success' => true, 'message' => 'Sanction updated successfully']);
    exit;
}

        // Remove sanction
if ($_POST['action'] === 'removeSanction') {
    $caseSanctionId = $_POST['caseSanctionId'];

    // Get sanction info before deletion for logging
    $sql = "SELECT * FROM case_sanctions WHERE case_sanction_id = ?";
    $sanctionDataArray = fetchAll($sql, [$caseSanctionId]);
    $sanctionData = $sanctionDataArray[0] ?? null;

    if ($sanctionData) {
        $caseId = $sanctionData['case_id'];
        
        // Check latest row per day; allow removal only if no active check-in/check-out times remain.
        $checkInSql = "WITH ranked AS (
                           SELECT day_number, check_in_time, check_out_time,
                                  ROW_NUMBER() OVER (
                                      PARTITION BY day_number
                                      ORDER BY COALESCE(updated_at, created_at) DESC, checkin_id DESC
                                  ) AS rn
                           FROM case_checkins
                           WHERE case_sanction_id = ?
                       )
                       SELECT COUNT(*) as count
                       FROM ranked
                       WHERE rn = 1 AND (check_in_time IS NOT NULL OR check_out_time IS NOT NULL)";
        $checkInCount = fetchValue($checkInSql, [$caseSanctionId]);
        
        if ($checkInCount > 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Cannot remove this sanction because check-in records exist. The check-in process has already started.'
            ]);
            exit;
        }
        
        // If there was a scheduled event, try to delete it from calendar
        if (!empty($sanctionData['scheduled_date'])) {
            try {
                // Get case and sanction details to match event name
                $case = getCaseById($caseId);
                $sanctionSql = "SELECT sanction_name FROM sanctions WHERE sanction_id = ?";
                $sanction = fetchOne($sanctionSql, [$sanctionData['sanction_id']]);
                
                if ($case && $sanction) {
                    $studentName = $case['student_name'] ?? 'Student';
                    $sanctionName = $sanction['sanction_name'] ?? 'Sanction';
                    $eventName = "Sanction: {$sanctionName} - {$studentName} (Case {$caseId})";
                    
                    // Delete calendar event matching this name and date
                    $deleteEventSql = "DELETE FROM calendar_events 
                                      WHERE event_name = ? 
                                      AND event_date = ? 
                                      AND category = 'Hearing'";
                    executeQuery($deleteEventSql, [$eventName, $sanctionData['scheduled_date']]);
                    error_log("Calendar event deleted for removed sanction");
                }
            } catch (Exception $e) {
                error_log("Error deleting calendar event for removed sanction: " . $e->getMessage());
                // Continue with sanction removal even if calendar deletion fails
            }
        }

        // Delete all associated check-in records (including reverted ones with NULL times)
        $deleteCheckInsSql = "DELETE FROM case_checkins WHERE case_sanction_id = ?";
        executeQuery($deleteCheckInsSql, [$caseSanctionId]);

        // Delete the sanction
        $sql = "DELETE FROM case_sanctions WHERE case_sanction_id = ?";
        executeQuery($sql, [$caseSanctionId]);

        // Check if there are any remaining sanctions for this case
        $remainingSanctionsSql = "SELECT COUNT(*) as count FROM case_sanctions WHERE case_id = ?";
        $remainingCount = fetchValue($remainingSanctionsSql, [$caseId]);
        
        $newStatus = null;
        // If no more sanctions, change case status to Pending
        if ($remainingCount == 0) {
            $updateStatusSql = "UPDATE cases SET status = 'Pending' WHERE case_id = ?";
            executeQuery($updateStatusSql, [$caseId]);
            
            // Log the status change
            logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Status Changed', 'On Going', 'Pending - All sanctions removed');
            
            $newStatus = 'Pending';
            error_log("Case {$caseId} status changed to Pending (all sanctions removed)");
        }

        // 🧾 Audit Log - Use specialized sanction removal audit function
        $sanctionSql = "SELECT sanction_name FROM sanctions WHERE sanction_id = ?";
        $sanctionInfo = fetchOne($sanctionSql, [$sanctionData['sanction_id']]);
        $sanctionName = $sanctionInfo['sanction_name'] ?? 'Unknown Sanction';
        
        auditSanctionRemoved($caseId, $sanctionName, [
            'duration_days' => $sanctionData['duration_days'],
            'scheduled_date' => $sanctionData['scheduled_date'],
            'deadline' => $sanctionData['deadline']
        ]);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Sanction removed successfully',
        'statusChanged' => $newStatus !== null,
        'newStatus' => $newStatus
    ]);
    exit;
}

        // Get check-in history for a case
if ($_POST['action'] === 'getCheckInHistory') {
    ensureCaseSanctionsDeadlineColumns();

    $caseId = $_POST['caseId'] ?? null;
    
    if (!$caseId) {
        echo json_encode(['success' => false, 'error' => 'Case ID required']);
        exit;
    }

    $inferSanctionDurationDays = function ($durationValue, $sanctionName) {
        $stored = intval($durationValue);
        if ($stored > 0) {
            return $stored;
        }

        $name = strtolower((string)$sanctionName);

        if (preg_match('/(\d+)\s*-\s*(\d+)\s*days?/i', $name, $rangeMatch)) {
            $minDays = intval($rangeMatch[1]);
            if ($minDays > 0) {
                return $minDays;
            }
        }

        if (preg_match('/(\d+)\s*days?/i', $name, $singleMatch)) {
            $explicitDays = intval($singleMatch[1]);
            if ($explicitDays > 0) {
                return $explicitDays;
            }
        }

        if (strpos($name, 'corrective reinforcement') !== false || strpos($name, 'suspension from class') !== false) {
            return 3;
        }

        return 0;
    };

    // Get all sanctions for this case and infer duration for time-based sanctions.
    $sql = "SELECT cs.*, s.sanction_name 
            FROM case_sanctions cs
            JOIN sanctions s ON cs.sanction_id = s.sanction_id
            WHERE cs.case_id = ?
            ORDER BY cs.applied_date DESC";
    $sanctions = fetchAll($sql, [$caseId]);

    if (empty($sanctions)) {
        echo json_encode(['success' => true, 'sanctions' => []]);
        exit;
    }

    // For each sanction, get check-in data
    $result = [];
    $casePortfolioSubmissions = [];
    $caseNewPortfolioSubmissionCount = 0;
    foreach ($sanctions as $sanction) {
        $csId = $sanction['case_sanction_id'];
        $totalDays = $inferSanctionDurationDays($sanction['duration_days'] ?? null, $sanction['sanction_name'] ?? '');

        if ($totalDays <= 0) {
            continue;
        }

        if (intval($sanction['duration_days'] ?? 0) <= 0) {
            executeQuery("UPDATE case_sanctions SET duration_days = ? WHERE case_sanction_id = ?", [$totalDays, $csId]);
            $sanction['duration_days'] = $totalDays;
        }

        $sanctionNameLower = strtolower((string)($sanction['sanction_name'] ?? ''));
        if (strpos($sanctionNameLower, 'corrective reinforcement') !== false && empty($sanction['deadline'])) {
            $defaultDeadline = buildDefaultSanctionDeadline($sanction['applied_date'] ?? date('Y-m-d'), $totalDays);
            if (!empty($defaultDeadline)) {
                executeQuery("UPDATE case_sanctions SET deadline = ? WHERE case_sanction_id = ?", [$defaultDeadline, $csId]);
                $sanction['deadline'] = $defaultDeadline;
            }
        }

        $deadlineAllowsExtension = true;
        if (!empty($sanction['deadline'])) {
            try {
                $deadlineDate = new DateTime($sanction['deadline']);
                $deadlineDate->setTime(23, 59, 59);
                $deadlineAllowsExtension = $deadlineDate >= new DateTime('today');
            } catch (Exception $e) {
                $deadlineAllowsExtension = true;
            }
        }
        
        // Get latest check-in row per day for this sanction (avoids stale duplicate rows).
        $checkInSql = "WITH ranked AS (
                           SELECT *, ROW_NUMBER() OVER (
                               PARTITION BY day_number
                               ORDER BY COALESCE(updated_at, created_at) DESC, checkin_id DESC
                           ) AS rn
                           FROM case_checkins
                           WHERE case_sanction_id = ?
                       )
                       SELECT *
                       FROM ranked
                       WHERE rn = 1
                       ORDER BY day_number ASC";
        $checkIns = fetchAll($checkInSql, [$csId]);

        $maxRecordedDay = 0;
        $lastRecordedDayComplete = false;
        foreach ($checkIns as $checkInRow) {
            $recordedDay = intval($checkInRow['day_number'] ?? 0);
            if ($recordedDay > $maxRecordedDay) {
                $maxRecordedDay = $recordedDay;
            }
        }
        if ($maxRecordedDay > 0) {
            foreach ($checkIns as $checkInRow) {
                if (intval($checkInRow['day_number'] ?? 0) === $maxRecordedDay) {
                    $lastRecordedDayComplete = !empty($checkInRow['check_in_time']) && !empty($checkInRow['check_out_time']);
                    break;
                }
            }
        }

        $isCommunityServiceType = strpos($sanctionNameLower, 'corrective') !== false || strpos($sanctionNameLower, 'community service') !== false;
        $extraHours = max(0, intval($sanction['duration_extra_hours'] ?? 0));
        $requiredHours = max(0, $extraHours > 0 ? (($totalDays - 1) * 8) + $extraHours : ($totalDays * 8));
        $completedHours = 0.0;
        foreach ($checkIns as $checkInRow) {
            if (!empty($checkInRow['check_in_time']) && !empty($checkInRow['check_out_time'])) {
                try {
                    $inTime = new DateTime($checkInRow['check_in_time']);
                    $outTime = new DateTime($checkInRow['check_out_time']);
                    $secondsWorked = $outTime->getTimestamp() - $inTime->getTimestamp();
                    if ($secondsWorked > 0) {
                        $completedHours += ($secondsWorked / 3600);
                    }
                } catch (Exception $e) {
                    // Ignore malformed timestamps and continue with remaining rows.
                }
            }
        }
        $stillNeedsAdditionalDay = !$isCommunityServiceType || ($requiredHours > 0 && ($completedHours + 0.01) < $requiredHours);

        $maxDayByDeadlineWindow = null;
        if (!empty($sanction['deadline'])) {
            try {
                $appliedDate = new DateTime($sanction['applied_date'] ?? date('Y-m-d'));
                $appliedDate->setTime(0, 0, 0);

                $deadlineDate = new DateTime($sanction['deadline']);
                $deadlineDate->setTime(0, 0, 0);

                if ($deadlineDate < $appliedDate) {
                    $maxDayByDeadlineWindow = 0;
                } else {
                    $maxDayByDeadlineWindow = intval($appliedDate->diff($deadlineDate)->days);
                }
            } catch (Exception $e) {
                $maxDayByDeadlineWindow = null;
            }
        }

        $displayTotalDays = max($totalDays, $maxRecordedDay);
        $canStillAddDayByWindow = $maxDayByDeadlineWindow === null || $maxRecordedDay < intval($maxDayByDeadlineWindow);
        if ($deadlineAllowsExtension && $maxRecordedDay > 0 && $lastRecordedDayComplete && $stillNeedsAdditionalDay && $canStillAddDayByWindow) {
            $displayTotalDays = max($displayTotalDays, $maxRecordedDay + 1);
        }
        if ($maxDayByDeadlineWindow !== null) {
            $displayTotalDays = min($displayTotalDays, intval($maxDayByDeadlineWindow));
        }
        
        // Build day data
        $days = [];
        for ($d = 1; $d <= $displayTotalDays; $d++) {
            $dayCheckIn = null;
            foreach ($checkIns as $c) {
                if ($c['day_number'] == $d) {
                    $dayCheckIn = $c;
                    break;
                }
            }
            $days[$d] = [
                'day' => $d,
                'check_in_time' => $dayCheckIn ? $dayCheckIn['check_in_time'] : null,
                'check_out_time' => $dayCheckIn ? $dayCheckIn['check_out_time'] : null
            ];
        }

        $result[] = [
            'case_sanction_id' => $csId,
            'sanction_name' => $sanction['sanction_name'],
            'duration_days' => $totalDays,
            'duration_extra_hours' => intval($sanction['duration_extra_hours'] ?? 0),
            'deadline' => $sanction['deadline'],
            'max_day_by_deadline_window' => $maxDayByDeadlineWindow,
            'max_recorded_day' => $maxRecordedDay,
            'days' => $days,
            'portfolio_submissions' => [],
            'new_portfolio_submission_count' => 0
        ];

        $sanctionNameLower = strtolower((string)($sanction['sanction_name'] ?? ''));
        if (strpos($sanctionNameLower, 'corrective') !== false || strpos($sanctionNameLower, 'community service') !== false || strpos($sanctionNameLower, 'suspension from class') !== false) {
            $portfolioSubmissions = fetchAll(
                "SELECT submission_id, original_file_name, file_size_bytes, file_path, remarks, created_at, is_seen_by_do
                 FROM community_service_submissions
                 WHERE case_id = ? AND case_sanction_id = ?
                 ORDER BY created_at DESC, submission_id DESC",
                [$caseId, $csId]
            );

            $newSubmissionCountRow = fetchOne(
                "SELECT COUNT(*) AS cnt
                 FROM community_service_submissions
                 WHERE case_id = ? AND case_sanction_id = ? AND is_seen_by_do = 0",
                [$caseId, $csId]
            );

            if (!empty($portfolioSubmissions)) {
                $casePortfolioSubmissions = array_merge($casePortfolioSubmissions, $portfolioSubmissions);
            }
            $caseNewPortfolioSubmissionCount += intval($newSubmissionCountRow['cnt'] ?? 0);

            $result[count($result) - 1]['portfolio_submissions'] = $portfolioSubmissions;
            $result[count($result) - 1]['new_portfolio_submission_count'] = intval($newSubmissionCountRow['cnt'] ?? 0);
        }
    }

    echo json_encode([
        'success' => true,
        'sanctions' => $result,
        'case_portfolio_submissions' => $casePortfolioSubmissions,
        'case_new_portfolio_submission_count' => $caseNewPortfolioSubmissionCount
    ]);
    exit;
}

if ($_POST['action'] === 'markCommunityServiceSubmissionsViewed') {
    $caseId = trim($_POST['caseId'] ?? '');

    if ($caseId === '') {
        echo json_encode(['success' => false, 'error' => 'Case ID required']);
        exit;
    }

    $viewerId = $_SESSION['user_id'] ?? null;
    
    // Count submissions being marked as viewed for audit logging
    $countResult = fetchOne(
        "SELECT COUNT(*) AS cnt FROM community_service_submissions WHERE case_id = ? AND is_seen_by_do = 0",
        [$caseId]
    );
    $submissionCount = intval($countResult['cnt'] ?? 0);
    
    executeQuery(
        "UPDATE community_service_submissions
         SET is_seen_by_do = 1,
             seen_by_do_at = GETDATE(),
             seen_by_do_user_id = ?
         WHERE case_id = ? AND is_seen_by_do = 0",
        [$viewerId, $caseId]
    );

    // Audit log the portfolio viewing
    if ($submissionCount > 0) {
        auditPortfolioViewed($caseId, $submissionCount);
    }

    echo json_encode(['success' => true]);
    exit;
}

// ====== DEADLINE EXTENSION AND PENALTY MANAGEMENT ======

if ($_POST['action'] === 'extendSanctionDeadline') {
    $caseSanctionId = $_POST['caseSanctionId'] ?? null;
    $daysToAdd = intval($_POST['daysToAdd'] ?? 7);
    $extensionNotes = $_POST['extensionNotes'] ?? '';
    
    if (!$caseSanctionId) {
        echo json_encode(['success' => false, 'error' => 'Case Sanction ID required']);
        exit;
    }
    
    try {
        $success = extendSanctionDeadline($caseSanctionId, $daysToAdd, $extensionNotes);
        if ($success) {
            echo json_encode(['success' => true, 'message' => "Deadline extended by $daysToAdd days"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to extend deadline']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_POST['action'] === 'increaseSanctionDuration') {
    $caseSanctionId = $_POST['caseSanctionId'] ?? null;
    $additionalHours = intval($_POST['additionalHours'] ?? 8);
    $reason = $_POST['reason'] ?? 'Penalty for missed deadline';
    
    if (!$caseSanctionId) {
        echo json_encode(['success' => false, 'error' => 'Case Sanction ID required']);
        exit;
    }
    
    try {
        $success = increaseSanctionDuration($caseSanctionId, $additionalHours, $reason);
        if ($success) {
            echo json_encode(['success' => true, 'message' => "Duration increased by $additionalHours hour(s)"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to increase duration']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ====== Record check-in (student arrival) ======
if ($_POST['action'] === 'recordCheckIn') {
    $caseSanctionId = $_POST['caseSanctionId'] ?? null;
    $dayNumber = intval($_POST['dayNumber'] ?? 0);
    
    if (!$caseSanctionId || !$dayNumber) {
        echo json_encode(['success' => false, 'error' => 'Case Sanction ID and Day Number required']);
        exit;
    }

    $deadlineWindow = $getMaxAllowedDayByDeadline($caseSanctionId);
    if (!$deadlineWindow['ok']) {
        echo json_encode(['success' => false, 'error' => $deadlineWindow['error']]);
        exit;
    }
    if ($deadlineWindow['maxDay'] !== null && $dayNumber > intval($deadlineWindow['maxDay'])) {
        echo json_encode(['success' => false, 'error' => 'Cannot record beyond the sanction deadline window']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $displayTime = date('H:i'); // Format as HH:MM for display

    // Check if record exists for this day (latest by update time, regardless of date)
    $checkSql = "SELECT * FROM case_checkins 
                 WHERE case_sanction_id = ? AND day_number = ?
                 ORDER BY COALESCE(updated_at, created_at) DESC, checkin_id DESC";
    $existing = fetchOne($checkSql, [$caseSanctionId, $dayNumber]);

    if ($existing) {
        // Allow repeated check-ins by updating the latest row for the day.
        $sql = "UPDATE case_checkins SET check_in_time = ?, updated_at = ? 
                WHERE checkin_id = ?";
        executeQuery($sql, [$now, $now, $existing['checkin_id']]);
        
        // Get case_id for audit logging
        $sanctionSql = "SELECT case_id FROM case_sanctions WHERE case_sanction_id = ?";
        $sanctionInfo = fetchOne($sanctionSql, [$caseSanctionId]);
        if ($sanctionInfo) {
            auditCheckInRecorded($sanctionInfo['case_id'], $dayNumber, $now);
        }
        
        echo json_encode(['success' => true, 'message' => 'Check-in recorded', 'time' => $displayTime]);
    } else {
        // Insert new check-in record
        $sql = "INSERT INTO case_checkins (case_sanction_id, day_number, check_in_time, check_in_date) 
                VALUES (?, ?, ?, ?)";
        executeQuery($sql, [$caseSanctionId, $dayNumber, $now, date('Y-m-d')]);
        
        // Get case_id for audit logging
        $sanctionSql = "SELECT case_id FROM case_sanctions WHERE case_sanction_id = ?";
        $sanctionInfo = fetchOne($sanctionSql, [$caseSanctionId]);
        if ($sanctionInfo) {
            auditCheckInRecorded($sanctionInfo['case_id'], $dayNumber, $now);
        }
        
        echo json_encode(['success' => true, 'message' => 'Check-in recorded', 'time' => $displayTime]);
    }
    exit;
}

        // Record check-out (student departure)
if ($_POST['action'] === 'recordCheckOut') {
    $caseSanctionId = $_POST['caseSanctionId'] ?? null;
    $dayNumber = intval($_POST['dayNumber'] ?? 0);
    
    if (!$caseSanctionId || !$dayNumber) {
        echo json_encode(['success' => false, 'error' => 'Case Sanction ID and Day Number required']);
        exit;
    }

    $deadlineWindow = $getMaxAllowedDayByDeadline($caseSanctionId);
    if (!$deadlineWindow['ok']) {
        echo json_encode(['success' => false, 'error' => $deadlineWindow['error']]);
        exit;
    }
    if ($deadlineWindow['maxDay'] !== null && $dayNumber > intval($deadlineWindow['maxDay'])) {
        echo json_encode(['success' => false, 'error' => 'Cannot record beyond the sanction deadline window']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    // First check if a record exists for this day (latest by update time, regardless of date)
    $checkSql = "SELECT * FROM case_checkins 
                 WHERE case_sanction_id = ? AND day_number = ?
                 ORDER BY COALESCE(updated_at, created_at) DESC, checkin_id DESC";
    $existing = fetchOne($checkSql, [$caseSanctionId, $dayNumber]);

    if ($existing) {
        // Update check-out time on existing record
        $sql = "UPDATE case_checkins SET check_out_time = ?, updated_at = ? 
                WHERE checkin_id = ?";
        executeQuery($sql, [$now, $now, $existing['checkin_id']]);
        
        // Get case_id for audit logging
        $sanctionSql = "SELECT case_id FROM case_sanctions WHERE case_sanction_id = ?";
        $sanctionInfo = fetchOne($sanctionSql, [$caseSanctionId]);
        if ($sanctionInfo) {
            auditCheckOutRecorded($sanctionInfo['case_id'], $dayNumber, $now);
        }
        
        echo json_encode(['success' => true, 'message' => 'Check-out recorded', 'time' => date('H:i')]);
    } else {
        // Record doesn't exist - this shouldn't happen if check-in was recorded, but create one anyway
        $sql = "INSERT INTO case_checkins (case_sanction_id, day_number, check_in_time, check_out_time, check_in_date) 
                VALUES (?, ?, NULL, ?, ?)";
        executeQuery($sql, [$caseSanctionId, $dayNumber, $now, date('Y-m-d')]);
        
        // Get case_id for audit logging
        $sanctionSql = "SELECT case_id FROM case_sanctions WHERE case_sanction_id = ?";
        $sanctionInfo = fetchOne($sanctionSql, [$caseSanctionId]);
        if ($sanctionInfo) {
            auditCheckOutRecorded($sanctionInfo['case_id'], $dayNumber, $now);
        }
        
        echo json_encode(['success' => true, 'message' => 'Check-out recorded', 'time' => date('H:i')]);
    }
    exit;
}

// Manual check-in (record current time for a specific day)
if ($_POST['action'] === 'manualCheckIn') {
    $caseSanctionId = $_POST['caseSanctionId'] ?? null;
    $dayNumber = intval($_POST['dayNumber'] ?? 0);
    
    if (!$caseSanctionId || !$dayNumber) {
        echo json_encode(['success' => false, 'error' => 'Case Sanction ID and Day Number required']);
        exit;
    }

    $deadlineWindow = $getMaxAllowedDayByDeadline($caseSanctionId);
    if (!$deadlineWindow['ok']) {
        echo json_encode(['success' => false, 'error' => $deadlineWindow['error']]);
        exit;
    }
    if ($deadlineWindow['maxDay'] !== null && $dayNumber > intval($deadlineWindow['maxDay'])) {
        echo json_encode(['success' => false, 'error' => 'Cannot record beyond the sanction deadline window']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $displayTime = date('H:i');

    // Check if record exists for this day (latest by update time, regardless of date)
    $checkSql = "SELECT * FROM case_checkins 
                 WHERE case_sanction_id = ? AND day_number = ?
                 ORDER BY COALESCE(updated_at, created_at) DESC, checkin_id DESC";
    $existing = fetchOne($checkSql, [$caseSanctionId, $dayNumber]);

    if ($existing) {
        // A fresh check-in should invalidate any previous checkout on that same day row.
        $sql = "UPDATE case_checkins SET check_in_time = ?, check_out_time = NULL, updated_at = ? 
                WHERE checkin_id = ?";
        executeQuery($sql, [$now, $now, $existing['checkin_id']]);
    } else {
        // Create new check-in record
        $sql = "INSERT INTO case_checkins (case_sanction_id, day_number, check_in_time, check_in_date) 
                VALUES (?, ?, ?, ?)";
        executeQuery($sql, [$caseSanctionId, $dayNumber, $now, date('Y-m-d')]);
    }

    // 🧾 Audit Log
    $sanctionSql = "SELECT case_id FROM case_sanctions WHERE case_sanction_id = ?";
    $sanctionInfo = fetchOne($sanctionSql, [$caseSanctionId]);
    if ($sanctionInfo) {
        auditCheckInRecorded($sanctionInfo['case_id'], $dayNumber, $now);
    }

    error_log("Manual check-in: Case Sanction $caseSanctionId, Day $dayNumber, Time: $displayTime");
    echo json_encode(['success' => true, 'message' => 'Manual check-in recorded', 'time' => $displayTime]);
    exit;
}

// Manual check-out (record current time for a specific day)
if ($_POST['action'] === 'manualCheckOut') {
    $caseSanctionId = $_POST['caseSanctionId'] ?? null;
    $dayNumber = intval($_POST['dayNumber'] ?? 0);
    
    if (!$caseSanctionId || !$dayNumber) {
        echo json_encode(['success' => false, 'error' => 'Case Sanction ID and Day Number required']);
        exit;
    }

    $deadlineWindow = $getMaxAllowedDayByDeadline($caseSanctionId);
    if (!$deadlineWindow['ok']) {
        echo json_encode(['success' => false, 'error' => $deadlineWindow['error']]);
        exit;
    }
    if ($deadlineWindow['maxDay'] !== null && $dayNumber > intval($deadlineWindow['maxDay'])) {
        echo json_encode(['success' => false, 'error' => 'Cannot record beyond the sanction deadline window']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $displayTime = date('H:i');

    // Check if record exists for this day (latest by update time, regardless of date)
    $checkSql = "SELECT * FROM case_checkins 
                 WHERE case_sanction_id = ? AND day_number = ?
                 ORDER BY COALESCE(updated_at, created_at) DESC, checkin_id DESC";
    $existing = fetchOne($checkSql, [$caseSanctionId, $dayNumber]);

    if ($existing) {
        // Existing record - update check-out time
        $sql = "UPDATE case_checkins SET check_out_time = ?, updated_at = ? 
                WHERE checkin_id = ?";
        executeQuery($sql, [$now, $now, $existing['checkin_id']]);
    } else {
        // Create new record with only check-out time
        $sql = "INSERT INTO case_checkins (case_sanction_id, day_number, check_out_time, check_in_date) 
                VALUES (?, ?, ?, ?)";
        executeQuery($sql, [$caseSanctionId, $dayNumber, $now, date('Y-m-d')]);
    }

    error_log("Manual check-out: Case Sanction $caseSanctionId, Day $dayNumber, Time: $displayTime");
    echo json_encode(['success' => true, 'message' => 'Manual check-out recorded', 'time' => $displayTime]);
    exit;
}

// Correct/edit check-in or check-out time manually
if ($_POST['action'] === 'correctTime') {
    $caseSanctionId = $_POST['caseSanctionId'] ?? null;
    $dayNumber = intval($_POST['dayNumber'] ?? 0);
    $timeType = $_POST['timeType'] ?? null; // 'check_in' or 'check_out'
    $correctedTime = $_POST['correctedTime'] ?? null; // Format: HH:MM

    if (!$caseSanctionId || !$dayNumber || !$timeType || !$correctedTime) {
        echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
        exit;
    }

    // Validate time format
    if (!preg_match('/^\d{2}:\d{2}$/', $correctedTime)) {
        echo json_encode(['success' => false, 'error' => 'Invalid time format. Use HH:MM']);
        exit;
    }

    $today = date('Y-m-d');

    // Check if record exists for this day (latest by update time, regardless of date)
    $checkSql = "SELECT * FROM case_checkins 
                 WHERE case_sanction_id = ? AND day_number = ?
                 ORDER BY COALESCE(updated_at, created_at) DESC, checkin_id DESC";
    $existing = fetchOne($checkSql, [$caseSanctionId, $dayNumber]);

    if (!$existing) {
        echo json_encode(['success' => false, 'error' => 'No check-in record found for this day']);
        exit;
    }

    // Get old value for audit
    $oldTime = $timeType === 'check_in' ? $existing['check_in_time'] : $existing['check_out_time'];

    // Build the corrected time with today's date
    $correctedDateTime = $today . ' ' . $correctedTime . ':00';

    // Update the appropriate time field
    if ($timeType === 'check_in') {
        $sql = "UPDATE case_checkins SET check_in_time = ?, updated_at = ? 
                WHERE checkin_id = ?";
    } else if ($timeType === 'check_out') {
        $sql = "UPDATE case_checkins SET check_out_time = ?, updated_at = ? 
                WHERE checkin_id = ?";
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid time type']);
        exit;
    }

    executeQuery($sql, [$correctedDateTime, date('Y-m-d H:i:s'), $existing['checkin_id']]);
    
    // 🧾 Audit Log
    $sanctionSql = "SELECT case_id FROM case_sanctions WHERE case_sanction_id = ?";
    $sanctionInfo = fetchOne($sanctionSql, [$caseSanctionId]);
    if ($sanctionInfo) {
        auditTimeRecordCorrected($sanctionInfo['case_id'], $dayNumber, $oldTime, $correctedDateTime, $timeType);
    }
    
    error_log("Time correction: Case Sanction $caseSanctionId, Day $dayNumber, $timeType corrected to $correctedTime");
    
    echo json_encode(['success' => true, 'message' => 'Time corrected successfully', 'time' => $correctedTime]);
    exit;
}

// Revert check-in or check-out time
if ($_POST['action'] === 'revertTime') {
    $caseSanctionId = $_POST['caseSanctionId'] ?? null;
    $dayNumber = intval($_POST['dayNumber'] ?? 0);
    $timeType = $_POST['timeType'] ?? null; // 'check_in' or 'check_out'

    if (!$caseSanctionId || !$dayNumber || !$timeType) {
        echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
        exit;
    }

    if (!in_array($timeType, ['check_in', 'check_out'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid time type']);
        exit;
    }

    // Check if record exists for this day (latest by update time, regardless of date)
    $checkSql = "SELECT * FROM case_checkins 
                 WHERE case_sanction_id = ? AND day_number = ?
                 ORDER BY COALESCE(updated_at, created_at) DESC, checkin_id DESC";
    $existing = fetchOne($checkSql, [$caseSanctionId, $dayNumber]);

    if (!$existing) {
        echo json_encode(['success' => false, 'error' => 'No check-in record found for this day']);
        exit;
    }

    // Get the removed time for audit logging
    $removedTime = $timeType === 'check_in' ? $existing['check_in_time'] : $existing['check_out_time'];

    // Revert the appropriate time field to NULL.
    // For check_in revert, clear both fields to fully reset the day.
    if ($timeType === 'check_in') {
        $sql = "UPDATE case_checkins SET check_in_time = NULL, check_out_time = NULL, updated_at = ? 
                WHERE checkin_id = ?";
    } else if ($timeType === 'check_out') {
        $sql = "UPDATE case_checkins SET check_out_time = NULL, updated_at = ? 
                WHERE checkin_id = ?";
    }

    executeQuery($sql, [date('Y-m-d H:i:s'), $existing['checkin_id']]);
    
    // After reverting, check if both times are now NULL - if so, delete the record
    $checkAfterRevert = fetchOne("SELECT check_in_time, check_out_time FROM case_checkins 
                                  WHERE checkin_id = ?", 
                                 [$existing['checkin_id']]);
    
    // 🧾 Audit Log
    $sanctionSql = "SELECT case_id FROM case_sanctions WHERE case_sanction_id = ?";
    $sanctionInfo = fetchOne($sanctionSql, [$caseSanctionId]);
    if ($sanctionInfo) {
        auditTimeRecordReverted($sanctionInfo['case_id'], $dayNumber, $removedTime, $timeType);
    }
    
    if ($checkAfterRevert && $checkAfterRevert['check_in_time'] === null && $checkAfterRevert['check_out_time'] === null) {
        // Both times are NULL, delete the record entirely
        $deleteSql = "DELETE FROM case_checkins 
                      WHERE checkin_id = ?";
        executeQuery($deleteSql, [$existing['checkin_id']]);
        error_log("Time revert: Case Sanction $caseSanctionId, Day $dayNumber record deleted (both times NULL)");
    } else {
        error_log("Time revert: Case Sanction $caseSanctionId, Day $dayNumber, $timeType reverted");
    }
    
    echo json_encode(['success' => true, 'message' => 'Time reverted successfully']);
    exit;
}

// Set first day for suspension progress tracking
if ($_POST['action'] === 'setSuspensionStartDate') {
    $caseSanctionId = intval($_POST['caseSanctionId'] ?? 0);
    $startDate = $_POST['startDate'] ?? null; // YYYY-MM-DD

    if ($caseSanctionId <= 0 || !$startDate) {
        echo json_encode(['success' => false, 'error' => 'Case Sanction ID and start date are required']);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        echo json_encode(['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD']);
        exit;
    }

    $validatedDate = DateTime::createFromFormat('Y-m-d', $startDate);
    if (!$validatedDate || $validatedDate->format('Y-m-d') !== $startDate) {
        echo json_encode(['success' => false, 'error' => 'Invalid calendar date']);
        exit;
    }

    // Ensure this sanction is Suspension from Class before updating.
    $sanctionCheckSql = "SELECT cs.case_sanction_id
                         FROM case_sanctions cs
                         JOIN sanctions s ON cs.sanction_id = s.sanction_id
                         WHERE cs.case_sanction_id = {$caseSanctionId}
                           AND LOWER(s.sanction_name) LIKE '%suspension from class%'";
    $sanctionRow = fetchOne($sanctionCheckSql);

    if (!$sanctionRow) {
        echo json_encode(['success' => false, 'error' => 'Suspension sanction not found']);
        exit;
    }

    $updateSql = "UPDATE case_sanctions
                  SET applied_date = CAST(? AS DATE)
                  WHERE case_sanction_id = {$caseSanctionId}";
    executeQuery($updateSql, [$startDate]);

    echo json_encode(['success' => true, 'message' => 'First day updated successfully']);
    exit;
}

    } catch (Exception $e) {
        error_log("Cases AJAX Error: " . $e->getMessage());
        if (ob_get_length()) {
            ob_clean();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

    // Get offense types by category
    if ($_POST['action'] === 'getOffenseTypes') {
        $category = $_POST['category'] ?? '';
        if ($category) {
            $offenses = getOffenseTypesByCategory($category);
        } else {
            $offenses = getAllOffenseTypes();
        }
        echo json_encode(['success' => true, 'offenses' => $offenses]);
        exit;
    }

    // Get recommended sanction based on escalation algorithm
    if ($_POST['action'] === 'getRecommendedSanction') {
        $studentId = $_POST['studentId'] ?? '';
        $caseType = $_POST['caseType'] ?? '';
        $severity = $_POST['severity'] ?? 'Minor';
        $caseId = $_POST['caseId'] ?? null;
        
        if (empty($studentId) || empty($caseType)) {
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            exit;
        }
        
        $recommendation = getRecommendedSanction($studentId, $caseType, $severity, $caseId);
        echo json_encode(['success' => true, 'recommendation' => $recommendation]);
        exit;
    }

    // Get all sanctions
    if ($_POST['action'] === 'getSanctions') {
        $sanctions = getAllSanctions();
        echo json_encode(['success' => true, 'sanctions' => $sanctions]);
        exit;
    }

    // Mark case as resolved
if ($_POST['action'] === 'markResolved') {
            $caseId = trim((string)($_POST['caseId'] ?? ''));

            if ($caseId === '') {
            echo json_encode(['success' => false, 'error' => 'Invalid case ID']);
            exit;
        }

            $existingCase = getCaseById($caseId);
            if (!$existingCase) {
                echo json_encode(['success' => false, 'error' => 'Case not found']);
                exit;
            }

        $eligibility = getCaseResolutionEligibility($caseId);
        if (!$eligibility['can_resolve']) {
            echo json_encode(['success' => false, 'error' => $eligibility['error']]);
            exit;
        }

    markCaseAsResolved($caseId);

    // 🧾 Audit Log
    auditUpdate('cases', $caseId, ['status' => 'Pending'], ['status' => 'Resolved']);

    echo json_encode(['success' => true, 'message' => 'Case marked as resolved']);
    exit;
}


    // Apply sanction to case
if ($_POST['action'] === 'applySanction') {
    $caseId = $_POST['caseId'];
    $sanctionId = $_POST['sanctionId'];
    $durationDays = $_POST['durationDays'] ?? null;
    $notes = $_POST['notes'] ?? '';
    $scheduleDate = $_POST['scheduleDate'] ?? null;
    $scheduleTime = !empty(trim($_POST['scheduleTime'] ?? '')) ? trim($_POST['scheduleTime']) : null;
    $scheduleEndTime = !empty(trim($_POST['scheduleEndTime'] ?? '')) ? trim($_POST['scheduleEndTime']) : null;
    $scheduleNotes = $_POST['scheduleNotes'] ?? '';
    $deadlineDate = $_POST['deadlineDate'] ?? null;
    
    // Convert HH:MM to HH:MM:SS format for SQL Server TIME column
    if ($scheduleTime && strlen($scheduleTime) === 5 && substr_count($scheduleTime, ':') === 1) {
        $scheduleTime .= ':00';
    }
    
    if ($scheduleEndTime && strlen($scheduleEndTime) === 5 && substr_count($scheduleEndTime, ':') === 1) {
        $scheduleEndTime .= ':00';
    }

    try {
        applySanctionToCase($caseId, $sanctionId, $durationDays, $notes, $scheduleDate, $scheduleTime, $scheduleNotes, $scheduleEndTime, $deadlineDate);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    
    // Update case status to "On Going" when sanction is applied
    $sqlUpdateStatus = "UPDATE cases SET status = 'On Going' WHERE case_id = ?";
    executeQuery($sqlUpdateStatus, [$caseId]);

    // Notify student if schedule is set
    if (!empty($scheduleDate)) {
        // Get student's user_id from the case
        $case = getCaseById($caseId);
        if ($case && !empty($case['student_id'])) {
            $studentSql = "SELECT user_id FROM students WHERE student_id = ?";
            $student = fetchOne($studentSql, [$case['student_id']]);
            
            if ($student && !empty($student['user_id'])) {
                // Get sanction name
                $sanctionSql = "SELECT sanction_name FROM sanctions WHERE sanction_id = ?";
                $sanction = fetchOne($sanctionSql, [$sanctionId]);
                $sanctionName = $sanction['sanction_name'] ?? 'Sanction';
                
                // Format date and time for notification
                $scheduleDateTime = date('F j, Y', strtotime($scheduleDate));
                if (!empty($scheduleTime)) {
                    $scheduleDateTime .= ' at ' . date('g:i A', strtotime($scheduleTime));
                    if (!empty($scheduleEndTime)) {
                        $scheduleDateTime .= ' - ' . date('g:i A', strtotime($scheduleEndTime));
                    }
                }
                
                // Create notification
                $notificationTitle = "Scheduled Sanction: {$sanctionName}";
                $notificationMessage = "A sanction has been scheduled for your case {$caseId} on {$scheduleDateTime}.";
                if (!empty($scheduleNotes)) {
                    $notificationMessage .= " Note: {$scheduleNotes}";
                }
                
                createNotification(
                    $student['user_id'],
                    $notificationTitle,
                    $notificationMessage,
                    'sanction',
                    $caseId
                );
            }
        }
    }

    // 🧾 Audit Log - Use specialized sanction audit function
    $sanctionSql = "SELECT sanction_name FROM sanctions WHERE sanction_id = ?";
    $sanctionInfo = fetchOne($sanctionSql, [$sanctionId]);
    $sanctionName = $sanctionInfo['sanction_name'] ?? 'Unknown Sanction';
    
    auditSanctionApplied($caseId, $sanctionId, $sanctionName, [
        'duration_days' => $durationDays,
        'notes' => $notes,
        'scheduled_date' => $scheduleDate,
        'scheduled_time' => $scheduleTime,
        'schedule_notes' => $scheduleNotes,
        'deadline_date' => $deadlineDate
    ]);

    echo json_encode(['success' => true, 'message' => 'Sanction applied successfully']);
    exit;
}

    // Add these new handlers to your cases.php file (inside the existing AJAX handling section)

    // Remove case permanently
    if ($_POST['action'] === 'removeCase') {
        $caseId = $_POST['caseId'];

        // Get case info before deletion for logging
        $case = getCaseById($caseId);

        // Delete related records first (foreign key constraints)
        $sql1 = "DELETE FROM case_sanctions WHERE case_id = ?";
        executeQuery($sql1, [$caseId]);

        $sql2 = "DELETE FROM case_history WHERE case_id = ?";
        executeQuery($sql2, [$caseId]);

        // Delete the case
        $sql3 = "DELETE FROM cases WHERE case_id = ?";
        executeQuery($sql3, [$caseId]);

        // Update student offense count if student exists
        if ($case && $case['student_id']) {
            updateStudentOffenseCount($case['student_id']);
        }

        // Log the deletion
        logAudit($_SESSION['user_id'] ?? null, 'Case Deleted', 'cases', $caseId, json_encode($case), null);

        echo json_encode(['success' => true, 'message' => 'Case removed successfully']);
        exit;
    }

    // Get case sanctions
    if ($_POST['action'] === 'getCaseSanctions') {
        $caseId = $_POST['caseId'];
        $sanctions = getCaseSanctions($caseId);
        echo json_encode(['success' => true, 'sanctions' => $sanctions]);
        exit;
    }

    // Check for scheduling conflicts (real-time)
    if ($_POST['action'] === 'checkConflicts') {
        $scheduleDate = $_POST['scheduleDate'] ?? null;
        $scheduleTime = $_POST['scheduleTime'] ?? null;
        $scheduleEndTime = $_POST['scheduleEndTime'] ?? null;
        
        if (empty($scheduleDate) || empty($scheduleTime)) {
            echo json_encode(['success' => true, 'hasConflict' => false, 'conflicts' => []]);
            exit;
        }
        
        // Convert HH:MM to HH:MM:SS format for SQL Server TIME column
        if ($scheduleTime && strlen($scheduleTime) === 5 && substr_count($scheduleTime, ':') === 1) {
            $scheduleTime .= ':00';
        }
        
        if ($scheduleEndTime && strlen($scheduleEndTime) === 5 && substr_count($scheduleEndTime, ':') === 1) {
            $scheduleEndTime .= ':00';
        }
        
        try {
            $conflicts = checkSchedulingConflicts($scheduleDate, $scheduleTime, $scheduleEndTime);
            
            if (!empty($conflicts)) {
                // Format conflicts for display
                $formattedConflicts = array_map(function($conflict) {
                    $timeRange = date('g:i A', strtotime($conflict['event_time']));
                    if (!empty($conflict['event_end_time'])) {
                        $timeRange .= ' - ' . date('g:i A', strtotime($conflict['event_end_time']));
                    }
                    return [
                        'name' => $conflict['event_name'],
                        'time' => $timeRange,
                        'date' => date('M j, Y', strtotime($conflict['event_date']))
                    ];
                }, $conflicts);
                
                echo json_encode([
                    'success' => true,
                    'hasConflict' => true,
                    'conflicts' => $formattedConflicts
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'hasConflict' => false,
                    'conflicts' => []
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

?>

<?php
// Get the current user's name early for use in HTML meta tags
$adminName = getFormattedUserName() ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office - Cases Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }

        // Restore saved theme on page load
        if (localStorage.getItem("theme") === "dark") {
            document.documentElement.classList.add("dark");
        }

        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.toggle("dark");
            localStorage.setItem("theme", isDark ? "dark" : "light");
        }
    </script>
    <meta name="data-user-name" content="<?= isset($adminName) ? htmlspecialchars($adminName) : 'User' ?>">
    <script>
        // Set ADMIN_NAME for use in modals
        window.ADMIN_NAME = '<?= isset($adminName) ? htmlspecialchars(addslashes($adminName)) : 'User' ?>';
    </script>
    <style>
        #print-root { display: none; }
        @media print {
            body > * { display: none !important; }
            #print-root { display: block !important; font-family: Arial, sans-serif; font-size: 9pt; color: #111827; }
            @page { margin: 15mm 10mm; size: A4; }
        }
    </style>
</head>

<body
    class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">
    
    <!-- Hidden print root — only shown at @media print -->
    <div id="print-root" aria-hidden="true"></div>
    
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <?php
            $pageTitle = "Cases Management";
            include __DIR__ . '/../../includes/header.php';
            ?>

            <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                <!-- Top Bar -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="relative flex-1 max-w-md">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="searchInput" placeholder="Search cases..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none"
                            oninput="filterCases()">
                    </div>

                    <div class="ml-4 flex items-center gap-3">
                        <!-- Bulk Restore Button (Hidden by default, shown when cases are selected) -->
                        <button id="bulkRestoreBtn" onclick="bulkRestoreCases()" 
                            class="hidden px-4 py-2.5 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Restore <span class="count">0</span> Selected
                        </button>

                        <button onclick="addCase()"
                            class="px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Case
                        </button>
                    </div>
                </div>

                <!-- Tabs and Filters -->
                <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
                    <div class="flex gap-2 items-center">
                        <button id="currentTab" onclick="switchTab('current')"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium">Current</button>
                        <button id="resolvedTab" onclick="switchTab('resolved')"
                            class="px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">Resolved</button>
                        
                        <!-- Archived Icon Button -->
                        <button id="archivedTab" onclick="switchTab('archived')" title="View Archived Cases"
                            class="p-2 bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-gray-400 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors ml-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex gap-3 items-center flex-wrap">
                        <!-- Minor/Major Filter Buttons -->
                        <div class="flex gap-2 border-r border-gray-300 dark:border-slate-600 pr-3">
                            <button id="allOffensesBtn" onclick="filterByOffenseType('')"
                                class="px-4 py-2.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-sm hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">
                                All
                            </button>
                            <button id="minorBtn" onclick="filterByOffenseType('Minor')"
                                class="px-4 py-2.5 bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-500 text-yellow-700 dark:text-yellow-300 rounded-lg font-medium text-sm hover:bg-yellow-200 dark:hover:bg-yellow-900/40 transition-colors">
                                Minor
                            </button>
                            <button id="majorBtn" onclick="filterByOffenseType('Major')"
                                class="px-4 py-2.5 bg-red-100 dark:bg-red-900/20 border border-red-500 text-red-700 dark:text-red-300 rounded-lg font-medium text-sm hover:bg-red-200 dark:hover:bg-red-900/40 transition-colors">
                                Major
                            </button>
                        </div>

                        <!-- Advanced Filters Button -->
                        <button onclick="openAdvancedFilters()"
                            class="px-4 py-2.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-sm hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Advanced Filters
                        </button>

                        <!-- Sort Dropdown -->
                        <select id="sortFilter" onchange="sortCases()"
                            class="px-4 py-2.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-sm hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors cursor-pointer">
                            <option value="newest">Sort: Newest</option>
                            <option value="oldest">Sort: Oldest</option>
                            <option value="status">Sort: Status</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div
                    class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-100 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Case ID</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Student</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Type</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Date Reported</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody id="casesTableBody"
                            class="bg-white dark:bg-[#111827] divide-y divide-gray-200 dark:divide-slate-700">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex items-center justify-between">
                    <p id="paginationInfo" class="text-sm text-gray-600 dark:text-gray-400">Showing 1-8 of 24 cases</p>
                    <div id="paginationButtons" class="flex gap-2">
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Load Scripts -->
    <script src="/PrototypeDO/assets/js/notifications.js"></script>
    <script src="/PrototypeDO/assets/js/cases/data.js"></script>
    <script src="/PrototypeDO/assets/js/cases/filters.js"></script>
    <script src="/PrototypeDO/assets/js/cases/modals/core-utils.js"></script>
    <script src="/PrototypeDO/assets/js/cases/modals/advanced-filters.js"></script>
    <script src="/PrototypeDO/assets/js/cases/modals/view-case.js"></script>
    <script src="/PrototypeDO/assets/js/cases/modals/edit-case.js"></script>
    <script src="/PrototypeDO/assets/js/cases/modals/add-case.js"></script>
    <script src="/PrototypeDO/assets/js/cases/modals/sanctions.js"></script>
    <script src="/PrototypeDO/assets/js/cases/modals/archive.js"></script>
    <script src="/PrototypeDO/assets/js/cases/modals/notifications-and-recommendations.js"></script>
    <script src="/PrototypeDO/assets/js/cases/modals/checkin.js"></script>
    <script src="/PrototypeDO/assets/js/cases/pagination.js"></script>
    <script src="/PrototypeDO/assets/js/cases/main.js"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
    
    <!-- Auto-open case from notification -->
    <script>
        // Check if caseId is in URL params and open it
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const caseId = urlParams.get('caseId');
            
            if (caseId && urlParams.get('openPortfolio') !== '1' && urlParams.get('openCheckIn') !== '1') {
                // Wait for cases to be fully loaded, then open the specific case
                const checkInterval = setInterval(() => {
                    if (typeof allCases !== 'undefined' && allCases.length > 0 && typeof viewCase === 'function') {
                        viewCase(caseId);
                        clearInterval(checkInterval);
                        // Clean URL
                        window.history.replaceState({}, document.title, '/PrototypeDO/modules/do/cases.php');
                    }
                }, 100);
                
                // Timeout after 5 seconds to avoid infinite loop
                setTimeout(() => clearInterval(checkInterval), 5000);
            }
        });
    </script>
</body>

</html>