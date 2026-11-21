<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

ensureCalendarTargetUserColumn();
ensureCalendarRsvpTable();

$isDepartmentHead = (($_SESSION['user_role'] ?? '') === 'teacher')
    && (($_SESSION['user']['teacher_subrole'] ?? $_SESSION['teacher_subrole'] ?? null) === 'department_head');
$currentUserId = $_SESSION['user_id'] ?? null;

function validateCalendarDateTimeRange(string $eventDate, ?string $eventTime, ?string $eventEndTime): ?string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $eventDate);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $date->format('Y-m-d') !== $eventDate) {
        return 'Please provide a valid event date.';
    }

    if ($eventTime !== null && !preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $eventTime)) {
        return 'Please provide a valid start time.';
    }
    if ($eventEndTime !== null && !preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $eventEndTime)) {
        return 'Please provide a valid end time.';
    }
    if ($eventTime !== null && $eventEndTime !== null && $eventEndTime <= $eventTime) {
        return 'End time must be after start time.';
    }

    return null;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    // Mark password warning as shown in this login session
    if (isset($_POST['action']) && $_POST['action'] === 'markPasswordWarningShown') {
        $_SESSION['password_warning_modal_shown'] = true;
        echo json_encode(['success' => true, 'message' => 'Password warning marked as shown']);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'getUsers') {
        if ($isDepartmentHead) {
            echo json_encode(['success' => true, 'users' => []]);
            exit;
        }

        $users = fetchAll("SELECT user_id, full_name FROM users WHERE is_active = 1 AND role = 'discipline_office' ORDER BY full_name");
        echo json_encode(['success' => true, 'users' => $users]);
        exit;
    }

    try {
        // Get events
if ($_POST['action'] === 'getEvents') {
    $startDate = $_POST['startDate'] ?? null;
    $endDate = $_POST['endDate'] ?? null;
    $selectedUserId = $_POST['selectedUserId'] ?? null;
    $pendingEventId = trim((string)($_POST['pendingEventId'] ?? ''));
    $pendingCaseId = trim((string)($_POST['pendingCaseId'] ?? ''));

    if ($startDate && $endDate) {
        // Preferred path: exact date range, covers whatever the grid actually shows
        // (including leading/trailing days from adjacent months)
        $sql = "SELECT ce.event_id, ce.event_name, ce.event_date, ce.event_time, ce.event_end_time, 
                       ce.category, ce.description, ce.location, ce.created_by, ce.target_user_id,
               u.full_name as created_by_name,
               c.case_id, c.case_type, c.severity, c.status as case_status,
               c.description as case_description, c.action_taken, c.notes as case_notes,
               s.student_id, s.first_name, s.middle_name, s.last_name, s.track_course,
               ot.offense_name,
               (SELECT cer.response FROM calendar_event_rsvps cer
                WHERE cer.event_id = ce.event_id AND cer.user_id = " . (int)$currentUserId . "
                LIMIT 1) as rsvp_status,
               (SELECT COUNT(*)
                FROM calendar_event_rsvps cer
                INNER JOIN calendar_events invitation ON invitation.event_id = cer.event_id
                WHERE invitation.event_name = ce.event_name
                  AND invitation.event_date = ce.event_date
                  AND invitation.category = ce.category
                  AND invitation.target_user_id IS NOT NULL
                  AND cer.response = 'attend') as rsvp_attend_count,
               (SELECT COUNT(*)
                FROM calendar_event_rsvps cer
                INNER JOIN calendar_events invitation ON invitation.event_id = cer.event_id
                WHERE invitation.event_name = ce.event_name
                  AND invitation.event_date = ce.event_date
                  AND invitation.category = ce.category
                  AND invitation.target_user_id IS NOT NULL
                  AND cer.response = 'unavailable') as rsvp_unavailable_count,
               (SELECT GROUP_CONCAT(CONCAT(cer.response, ':', rsvp_user.full_name) SEPARATOR '|')
                FROM calendar_event_rsvps cer
                INNER JOIN calendar_events invitation ON invitation.event_id = cer.event_id
                INNER JOIN users rsvp_user ON rsvp_user.user_id = cer.user_id
                WHERE invitation.event_name = ce.event_name
                  AND invitation.event_date = ce.event_date
                  AND invitation.category = ce.category
                  AND invitation.target_user_id IS NOT NULL) as rsvp_responses
                FROM calendar_events ce
                LEFT JOIN users u ON ce.created_by = u.user_id
        LEFT JOIN cases c ON ce.category = 'Hearing'
            AND ce.event_name LIKE CONCAT('%Case ', c.case_id, '%')
        LEFT JOIN students s ON c.student_id = s.student_id
        LEFT JOIN offense_types ot ON c.offense_id = ot.offense_id
                WHERE (ce.event_date >= ? AND ce.event_date <= ?";
        $params = [$startDate, $endDate];
        if ($pendingEventId !== '' && ctype_digit($pendingEventId)) {
            $sql .= " OR ce.event_id = ?";
            $params[] = (int)$pendingEventId;
        }
        if ($pendingCaseId !== '') {
            $sql .= " OR (ce.category = 'Hearing' AND ce.event_name LIKE ?)";
            $params[] = "%Case {$pendingCaseId}%";
        }
        $sql .= ")";
        if ($isDepartmentHead) {
            $sql .= " AND ce.target_user_id = ?";
            $params[] = $currentUserId;
        } else {
            $sql .= " AND ce.target_user_id IS NULL";
        }
        if (!empty($selectedUserId)) {
            $sql .= " AND ce.created_by = ?";
            $params[] = $selectedUserId;
        }
        $sql .= " ORDER BY ce.event_date, ce.event_time";
        $events = fetchAll($sql, $params);
    } else {
        // Backward-compatible fallback if startDate/endDate aren't sent
        $month = $_POST['month'] ?? date('n');
        $year = $_POST['year'] ?? date('Y');

        $sql = "SELECT ce.event_id, ce.event_name, ce.event_date, ce.event_time, ce.event_end_time, 
                       ce.category, ce.description, ce.location, ce.created_by, ce.target_user_id,
               u.full_name as created_by_name,
               c.case_id, c.case_type, c.severity, c.status as case_status,
               c.description as case_description, c.action_taken, c.notes as case_notes,
               s.student_id, s.first_name, s.middle_name, s.last_name, s.track_course,
               ot.offense_name,
               (SELECT cer.response FROM calendar_event_rsvps cer
                WHERE cer.event_id = ce.event_id AND cer.user_id = " . (int)$currentUserId . "
                LIMIT 1) as rsvp_status,
               (SELECT COUNT(*)
                FROM calendar_event_rsvps cer
                INNER JOIN calendar_events invitation ON invitation.event_id = cer.event_id
                WHERE invitation.event_name = ce.event_name
                  AND invitation.event_date = ce.event_date
                  AND invitation.category = ce.category
                  AND invitation.target_user_id IS NOT NULL
                  AND cer.response = 'attend') as rsvp_attend_count,
               (SELECT COUNT(*)
                FROM calendar_event_rsvps cer
                INNER JOIN calendar_events invitation ON invitation.event_id = cer.event_id
                WHERE invitation.event_name = ce.event_name
                  AND invitation.event_date = ce.event_date
                  AND invitation.category = ce.category
                  AND invitation.target_user_id IS NOT NULL
                  AND cer.response = 'unavailable') as rsvp_unavailable_count,
               (SELECT GROUP_CONCAT(CONCAT(cer.response, ':', rsvp_user.full_name) SEPARATOR '|')
                FROM calendar_event_rsvps cer
                INNER JOIN calendar_events invitation ON invitation.event_id = cer.event_id
                INNER JOIN users rsvp_user ON rsvp_user.user_id = cer.user_id
                WHERE invitation.event_name = ce.event_name
                  AND invitation.event_date = ce.event_date
                  AND invitation.category = ce.category
                  AND invitation.target_user_id IS NOT NULL) as rsvp_responses
                FROM calendar_events ce
                LEFT JOIN users u ON ce.created_by = u.user_id
        LEFT JOIN cases c ON ce.category = 'Hearing'
            AND ce.event_name LIKE CONCAT('%Case ', c.case_id, '%')
        LEFT JOIN students s ON c.student_id = s.student_id
        LEFT JOIN offense_types ot ON c.offense_id = ot.offense_id
                WHERE MONTH(ce.event_date) = ? AND YEAR(ce.event_date) = ?";
        $params = [$month, $year];
        if ($isDepartmentHead) {
            $sql .= " AND ce.target_user_id = ?";
            $params[] = $currentUserId;
        } else {
            $sql .= " AND ce.target_user_id IS NULL";
        }
        if (!empty($selectedUserId)) {
            $sql .= " AND ce.created_by = ?";
            $params[] = $selectedUserId;
        }
        $sql .= " ORDER BY ce.event_date, ce.event_time";
        $events = fetchAll($sql, $params);
    }

    // Format events
    $formattedEvents = array_map(function($event) {
        $timeDisplay = null;
        if ($event['event_time']) {
            $timeDisplay = date('g:i A', strtotime($event['event_time']));
            if ($event['event_end_time']) {
                $timeDisplay .= ' - ' . date('g:i A', strtotime($event['event_end_time']));
            }
        }
        
        return [
            'id' => $event['event_id'],
            'name' => $event['event_name'],
            'date' => $event['event_date'],
            'time' => $timeDisplay,
            'category' => $event['category'],
            'description' => $event['description'],
            'location' => $event['location'],
            'createdBy' => $event['created_by_name'],
            'createdById' => $event['created_by'],
            'targetUserId' => $event['target_user_id'],
            'rsvpStatus' => $event['rsvp_status'],
            'rsvpSummary' => [
                'attend' => (int)($event['rsvp_attend_count'] ?? 0),
                'unavailable' => (int)($event['rsvp_unavailable_count'] ?? 0),
                'responses' => !empty($event['rsvp_responses'])
                    ? array_map(static function ($response) {
                        [$status, $name] = array_pad(explode(':', $response, 2), 2, '');
                        return ['status' => $status, 'name' => $name];
                    }, explode('|', $event['rsvp_responses']))
                    : []
            ],
            'caseDetails' => !empty($event['case_id']) ? [
                'caseId' => $event['case_id'],
                'caseType' => $event['case_type'],
                'severity' => $event['severity'],
                'status' => $event['case_status'],
                'offense' => $event['offense_name'],
                'description' => $event['case_description'],
                'actionTaken' => $event['action_taken'],
                'notes' => $event['case_notes'],
                'studentId' => $event['student_id'],
                'studentName' => trim(implode(' ', array_filter([
                    $event['first_name'],
                    $event['middle_name'],
                    $event['last_name']
                ]))),
                'trackCourse' => $event['track_course']
            ] : null,
            'color' => getCategoryColor($event['category'])
        ];
    }, $events);
    
    echo json_encode(['success' => true, 'events' => $formattedEvents]);
    exit;
}

        if ($_POST['action'] === 'rsvpEvent') {
            if (!$isDepartmentHead) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Only department heads can RSVP to invitations']);
                exit;
            }

            $eventId = filter_var($_POST['eventId'] ?? null, FILTER_VALIDATE_INT);
            $response = strtolower(trim((string)($_POST['response'] ?? '')));
            if (!$eventId || !in_array($response, ['attend', 'unavailable'], true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid RSVP response']);
                exit;
            }

            $event = fetchOne(
                "SELECT event_id FROM calendar_events WHERE event_id = ? AND target_user_id = ?",
                [$eventId, $currentUserId]
            );
            if (!$event) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Invitation not found']);
                exit;
            }

            executeQuery(
                "INSERT INTO calendar_event_rsvps (event_id, user_id, response)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE response = ?, updated_at = NOW()",
                [$eventId, $currentUserId, $response, $response]
            );

            echo json_encode(['success' => true, 'response' => $response]);
            exit;
        }

        // Get saved hearing schedule for a case
        if ($_POST['action'] === 'getCaseSchedule') {
            $caseId = $_POST['caseId'] ?? '';

            if (empty($caseId)) {
                echo json_encode(['success' => false, 'error' => 'Missing case id']);
                exit;
            }

            $sql = "SELECT event_id, event_name, event_date, event_time, event_end_time, description
                    FROM calendar_events
                    WHERE category = 'Hearing' AND event_name LIKE ?";
            $params = ["%Case {$caseId}%"];
            if ($isDepartmentHead) {
                $sql .= " AND target_user_id = ?";
                $params[] = $currentUserId;
            }
            $sql .= "
                    ORDER BY event_date DESC, event_id DESC LIMIT 1";

            $schedule = fetchOne($sql, $params);

            if (!$schedule) {
                echo json_encode(['success' => true, 'schedule' => null]);
                exit;
            }

            echo json_encode(['success' => true, 'schedule' => [
                'id' => $schedule['event_id'],
                'name' => $schedule['event_name'],
                'date' => $schedule['event_date'],
                'time' => $schedule['event_time'],
                'endTime' => $schedule['event_end_time'],
                'notes' => $schedule['description']
            ]]);
            exit;
        }

        // Create event
        if ($_POST['action'] === 'createEvent') {
            if ($isDepartmentHead) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Department heads can only view invited calendar events']);
                exit;
            }

            error_log("Creating event - Received data: " . print_r($_POST, true));
            
            $eventName = $_POST['eventName'] ?? '';
            $eventDate = $_POST['eventDate'] ?? '';
            $eventTime = isset($_POST['eventTime']) ? trim($_POST['eventTime']) : null;
            if ($eventTime === '') {
                $eventTime = null;
            } elseif ($eventTime !== null) {
                // Normalize to HH:MM:SS for MySQL TIME columns
                if (preg_match('/^\d{2}:\d{2}$/', $eventTime)) {
                    $eventTime .= ':00';
                }
            }
            
            $eventEndTime = isset($_POST['eventEndTime']) ? trim($_POST['eventEndTime']) : null;
            if ($eventEndTime === '') {
                $eventEndTime = null;
            } elseif ($eventEndTime !== null) {
                // Normalize to HH:MM:SS for MySQL TIME columns
                if (preg_match('/^\d{2}:\d{2}$/', $eventEndTime)) {
                    $eventEndTime .= ':00';
                }
            }
            
            $category = $_POST['category'] ?? '';
            $description = isset($_POST['description']) && !empty(trim($_POST['description'])) ? trim($_POST['description']) : null;
            $location = isset($_POST['location']) && !empty(trim($_POST['location'])) ? trim($_POST['location']) : null;
            $createdBy = $_SESSION['user_id'] ?? null;
            $targetUserId = null;
            
            // Validate required fields
            if (empty($eventName) || empty($eventDate) || empty($category)) {
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                exit;
            }
            $dateTimeError = validateCalendarDateTimeRange($eventDate, $eventTime, $eventEndTime);
            if ($dateTimeError !== null) {
                echo json_encode(['success' => false, 'error' => $dateTimeError]);
                exit;
            }

            $forceNew = ($_POST['forceNew'] ?? '0') === '1';
            $calendarCreateLockName = null;
            if ($category === 'Hearing' && !$forceNew && preg_match('/Case\s+([A-Za-z0-9-]+)/i', $eventName, $matches)) {
                $caseId = trim((string)($matches[1] ?? ''));
                $lockName = "hearing:{$caseId}:{$eventDate}";
                $lockAcquired = (int)fetchValue("SELECT GET_LOCK(?, 5)", [$lockName]);

                if ($lockAcquired === 1) {
                    $calendarCreateLockName = $lockName;
                    $existingEvent = fetchOne(
                        "SELECT event_id
                         FROM calendar_events
                         WHERE category = 'Hearing'
                           AND event_date = ?
                           AND event_name LIKE ?
                         ORDER BY (target_user_id IS NULL) DESC, event_id DESC
                         LIMIT 1",
                        [$eventDate, "%Case {$caseId}%"]
                    );

                    if ($existingEvent) {
                        $eventId = (int)$existingEvent['event_id'];
                        executeQuery(
                            "UPDATE calendar_events
                             SET event_name = ?, event_time = ?, event_end_time = ?, description = ?, location = ?, updated_at = NOW()
                             WHERE event_id = ?",
                            [$eventName, $eventTime, $eventEndTime, $description, $location, $eventId]
                        );
                        shareHearingEventWithDepartmentHeads($eventId, $eventName, $eventDate);
                        executeQuery("SELECT RELEASE_LOCK(?)", [$lockName]);
                        $calendarCreateLockName = null;

                        echo json_encode([
                            'success' => true,
                            'message' => 'Event created successfully',
                            'event_id' => $eventId
                        ]);
                        exit;
                    }
                }
            }

            // Build SQL based on whether time is provided
            if ($eventTime !== null) {
                $sql = "INSERT INTO calendar_events (event_name, event_date, event_time, event_end_time, category, description, location, created_by, target_user_id, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $params = [
                    $eventName,
                    $eventDate,
                    $eventTime,
                    $eventEndTime,
                    $category,
                    $description,
                    $location,
                    $createdBy,
                    $targetUserId
                ];
            } else {
                $sql = "INSERT INTO calendar_events (event_name, event_date, category, description, location, created_by, target_user_id, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $params = [
                    $eventName,
                    $eventDate,
                    $category,
                    $description,
                    $location,
                    $createdBy,
                    $targetUserId
                ];
            }
            
            error_log("SQL: $sql");
            error_log("Params: " . print_r($params, true));

            $eventId = insertAndGetId($sql, $params);
            $eventId = $eventId !== '' ? (int)$eventId : null;

            if ($calendarCreateLockName !== null) {
                executeQuery("SELECT RELEASE_LOCK(?)", [$calendarCreateLockName]);
                $calendarCreateLockName = null;
            }

            if ($category === 'Hearing' && preg_match('/Case\s+([A-Za-z0-9-]+)/i', $eventName, $matches)) {
                $caseId = trim((string)($matches[1] ?? ''));
                if ($caseId !== '') {
                    $case = getCaseById($caseId);
                    if ($case) {
                        $newStatus = (($case['severity'] ?? '') === 'Minor') ? 'Recorded' : 'On Going';
                        executeQuery(
                            "UPDATE cases SET status = ?, assigned_to = ?, updated_at = NOW() WHERE case_id = ?",
                            [$newStatus, $_SESSION['user_id'] ?? null, $caseId]
                        );
                    }
                }
            }

            // Audit and notifications must not turn a successful event insert into a
            // misleading scheduling error if either non-critical operation fails.
            try {
                auditCalendarEventCreated($eventId, $eventName, [
                    'event_date' => $eventDate,
                    'event_time' => $eventTime,
                    'category' => $category,
                    'description' => $description,
                    'location' => $location
                ]);
            } catch (Throwable $e) {
                error_log("Calendar event audit failed for {$eventId}: " . $e->getMessage());
            }

            if ($category === 'Hearing' && in_array($_SESSION['user_role'] ?? '', ['do', 'discipline_office', 'system_manager'], true)) {
                try {
                    shareHearingEventWithDepartmentHeads($eventId, $eventName, $eventDate);
                    notifyDepartmentHeadTeachersOfHearing($eventId, $eventName, $eventDate, $eventTime, $eventEndTime, $description, $location);
                } catch (Throwable $e) {
                    error_log("Hearing notification failed for {$eventId}: " . $e->getMessage());
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Event created successfully', 'event_id' => $eventId]);
            exit;
        }

        // Update event
        if ($_POST['action'] === 'updateEvent') {
            if ($isDepartmentHead) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Department heads can only view invited calendar events']);
                exit;
            }

            $eventId = $_POST['eventId'] ?? null;
            $eventName = $_POST['eventName'] ?? '';
            $eventDate = $_POST['eventDate'] ?? '';
            $eventTime = isset($_POST['eventTime']) && !empty(trim($_POST['eventTime'])) ? trim($_POST['eventTime']) : null;
            $eventEndTime = isset($_POST['eventEndTime']) && !empty(trim($_POST['eventEndTime'])) ? trim($_POST['eventEndTime']) : null;
            $category = $_POST['category'] ?? '';
            $description = isset($_POST['description']) && !empty(trim($_POST['description'])) ? trim($_POST['description']) : null;
            $location = isset($_POST['location']) && !empty(trim($_POST['location'])) ? trim($_POST['location']) : null;
            $targetUserId = null;
            
            if (empty($eventId)) {
                echo json_encode(['success' => false, 'error' => 'Event ID required']);
                exit;
            }
            
            // Get old data for audit comparison
            $oldEventSql = "SELECT * FROM calendar_events WHERE event_id = ?";
            $oldEvent = fetchOne($oldEventSql, [$eventId]);

            if ($category === 'Hearing' && preg_match('/Case\s+([A-Za-z0-9-]+)/i', $eventName, $matches)) {
                $caseId = trim((string)($matches[1] ?? ''));
                if ($caseId !== '') {
                    $case = getCaseById($caseId);
                    if ($case) {
                        $targetUserId = null;
                    }
                }
            } else {
                $targetUserId = $oldEvent['target_user_id'] ?? null;
            }
            
            // Normalize times to HH:MM:SS for MySQL TIME columns
            if ($eventTime !== null && preg_match('/^\d{2}:\d{2}$/', $eventTime)) {
                $eventTime .= ':00';
            }
            if ($eventEndTime !== null && preg_match('/^\d{2}:\d{2}$/', $eventEndTime)) {
                $eventEndTime .= ':00';
            }
            $dateTimeError = validateCalendarDateTimeRange($eventDate, $eventTime, $eventEndTime);
            if ($dateTimeError !== null) {
                echo json_encode(['success' => false, 'error' => $dateTimeError]);
                exit;
            }
            
                $sql = "UPDATE calendar_events 
                    SET event_name = ?, event_date = ?, event_time = ?, event_end_time = ?, category = ?, description = ?, location = ?, target_user_id = ?, updated_at = NOW()
                    WHERE event_id = ?";
            
            $params = [
                $eventName,
                $eventDate,
                $eventTime,
                $eventEndTime,
                $category,
                $description,
                $location,
                $targetUserId,
                $eventId
            ];
            
            executeQuery($sql, $params);

            if ($category === 'Hearing' && preg_match('/Case\s+([A-Za-z0-9-]+)/i', $eventName, $matches)) {
                $caseId = trim((string)($matches[1] ?? ''));
                if ($caseId !== '') {
                    $case = getCaseById($caseId);
                    if ($case) {
                        $newStatus = (($case['severity'] ?? '') === 'Minor') ? 'Recorded' : 'On Going';
                        executeQuery(
                            "UPDATE cases SET status = ?, assigned_to = ?, updated_at = NOW() WHERE case_id = ?",
                            [$newStatus, $_SESSION['user_id'] ?? null, $caseId]
                        );
                    }
                }
            }
            
            // 🧾 Audit Log - Use specialized calendar audit function
            if ($oldEvent) {
                auditCalendarEventUpdated($eventId,
                    [
                        'event_name' => $oldEvent['event_name'],
                        'event_date' => $oldEvent['event_date'],
                        'category' => $oldEvent['category'],
                        'description' => $oldEvent['description']
                    ],
                    [
                        'event_name' => $eventName,
                        'event_date' => $eventDate,
                        'category' => $category,
                        'description' => $description
                    ]
                );
            }

            if ($category === 'Hearing' && in_array($_SESSION['user_role'] ?? '', ['do', 'discipline_office', 'system_manager'], true)) {
                try {
                    notifyDepartmentHeadTeachersOfHearing(
                        (int)$eventId,
                        $eventName,
                        $eventDate,
                        $eventTime,
                        $eventEndTime,
                        $description,
                        $location
                    );
                } catch (Throwable $e) {
                    error_log("Hearing notification failed for updated event {$eventId}: " . $e->getMessage());
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Event updated successfully',
                'event_id' => (int)$eventId
            ]);
            exit;
        }

        // Delete event
        if ($_POST['action'] === 'deleteEvent') {
            if ($isDepartmentHead) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Department heads can only view invited calendar events']);
                exit;
            }

            $eventId = $_POST['eventId'] ?? null;
            
            if (empty($eventId)) {
                echo json_encode(['success' => false, 'error' => 'Event ID required']);
                exit;
            }
            
            // Get event details for audit logging
            $eventSql = "SELECT * FROM calendar_events WHERE event_id = ?";
            $event = fetchOne($eventSql, [$eventId]);
            
            $sql = "DELETE FROM calendar_events WHERE event_id = ?";
            executeQuery($sql, [$eventId]);

            if ($event && ($event['category'] ?? '') === 'Hearing') {
                if (preg_match('/Case\s+([A-Za-z0-9-]+)/i', (string)($event['event_name'] ?? ''), $matches)) {
                    $caseId = trim((string)($matches[1] ?? ''));
                    if ($caseId !== '') {
                        $case = getCaseById($caseId);
                        if ($case && ($case['severity'] ?? '') === 'Major') {
                            $hasSanction = fetchValue(
                                "SELECT COUNT(*) FROM case_sanctions WHERE case_id = ?",
                                [$caseId]
                            );
                            if ((int)$hasSanction === 0) {
                                executeQuery(
                                    "UPDATE cases SET status = 'Pending', updated_at = NOW() WHERE case_id = ?",
                                    [$caseId]
                                );
                            }
                        }
                    }
                }
            }
            
            // 🧾 Audit Log - Use specialized calendar audit function
            if ($event) {
                auditCalendarEventDeleted($eventId, $event['event_name']);
            }
            
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
            exit;
        }

        // Get event categories with counts
        if ($_POST['action'] === 'getCategories') {
            $categories = [
                ['name' => 'Meeting', 'count' => 0],
                ['name' => 'Conference', 'count' => 0],
                ['name' => 'Hearing', 'count' => 0],
                ['name' => 'Deadline', 'count' => 0],
                ['name' => 'Other', 'count' => 0]
            ];
            
            // Get counts
            $sql = "SELECT category, COUNT(*) as count FROM calendar_events";
            $params = [];
            if ($isDepartmentHead) {
                $sql .= " WHERE target_user_id = ?";
                $params[] = $currentUserId;
            } else {
                $sql .= " WHERE target_user_id IS NULL";
            }
            $sql .= " GROUP BY category";
            $counts = fetchAll($sql, $params) ?? [];
            
            foreach ($counts as $count) {
                foreach ($categories as &$cat) {
                    if ($cat['name'] === $count['category']) {
                        $cat['count'] = (int)$count['count'];
                    }
                }
            }
            
            echo json_encode(['success' => true, 'categories' => $categories]);
            exit;
        }

        // Unknown action
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    } catch (Exception $e) {
        error_log("Calendar AJAX Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

// Helper function
function getCategoryColor($category) {
    $colors = [
        'Meeting' => 'blue',
        'Conference' => 'green',
        'Hearing' => 'red',
        'Deadline' => 'yellow',
        'Other' => 'gray'
    ];
    return $colors[$category] ?? 'gray';
}

$pageTitle = "Calendar";
$adminName = getFormattedUserName();
$calendarView = $isDepartmentHead ? 'personal' : ($_GET['calendar_view'] ?? 'shared');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office - Calendar</title>
    <script>(function(){if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark');}})();</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
        if (localStorage.getItem("theme") === "dark") {
            document.documentElement.classList.add("dark");
        }
        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.toggle("dark");
            localStorage.setItem("theme", isDark ? "dark" : "light");
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="flex h-screen overflow-hidden">
        <div class="flex-1 overflow-y-auto ml-0 md:ml-64 transition-all duration-300">
            <?php include __DIR__ . '/../../includes/header.php'; ?>
            
            <main class="p-4 pt-20 md:p-8 md:pt-28 min-h-screen transition-colors duration-300">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Calendar Section (3 columns) -->
                    <div class="lg:col-span-3">
                        <!-- Calendar Header -->
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-6">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="flex flex-wrap items-center gap-3 md:gap-4">
                                    <button onclick="previousMonth()" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition-colors hover:bg-gray-100 dark:border-slate-700 dark:text-gray-300 dark:hover:bg-slate-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <h2 id="currentMonth" class="text-xl font-semibold text-gray-800 dark:text-gray-100 tracking-tight"></h2>
                                    <button onclick="nextMonth()" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition-colors hover:bg-gray-100 dark:border-slate-700 dark:text-gray-300 dark:hover:bg-slate-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                    <button onclick="goToToday()" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                                        Today
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                            <!-- Calendar Days Header -->
                            <div class="grid grid-cols-7 bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Sun</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Mon</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Tue</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Wed</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Thu</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Fri</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Sat</div>
                            </div>
                            
                            <!-- Calendar Days Grid -->
                            <div id="calendarGrid" class="grid grid-cols-7 divide-x divide-y divide-gray-200 dark:divide-slate-700">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar -->
                    <div class="space-y-6">
                        <?php if (!$isDepartmentHead) : ?>
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Quick Add Event</h3>
                            <button onclick="openAddEventModal()" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Event
                            </button>
                        </div>
                        <?php endif; ?>

                        <!-- Event Categories -->
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Event Categories</h3>
                            <div id="categoriesList" class="space-y-3">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>

                        <?php if ($calendarView !== 'personal') : ?>
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Scheduled By</h3>
                            <select id="userFilter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-200">
                                <option value="">All users</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Upcoming Events -->
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Upcoming Events</h3>
                            </div>
                            <div id="upcomingEventsList" class="space-y-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Loading events...</p>
                            </div>
                            <button onclick="openUpcomingEventsModal()" class="w-full mt-4 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                View All
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        window.CALENDAR_VIEW = <?= json_encode($calendarView) ?>;
    </script>
    <script src="<?= htmlspecialchars(ASSETS_URL) ?>/js/calendar/main.js"></script>
    <script src="<?= htmlspecialchars(ASSETS_URL) ?>/js/protect_pages.js"></script>
</body>
</html>