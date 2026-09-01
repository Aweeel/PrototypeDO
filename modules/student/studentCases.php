<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Verify user is a student
if ($_SESSION['user_role'] !== 'student') {
    header('Location: /PrototypeDO/index.php');
    exit;
}

$pageTitle = "My Cases";

// Get the student record linked to this user
$student = getStudentRecordForUser($_SESSION['user_id'] ?? null);

if (!$student) {
    // No student record for this user account
    $studentId = null;
    $adminName = getFormattedUserName();
} else {
    $studentId = $student['student_id'];
    $adminName = getFormattedUserName();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    ensureCommunityServiceSubmissionTable();

    // Mark password warning as shown in this login session
    if ($_POST['action'] === 'markPasswordWarningShown') {
        $_SESSION['password_warning_modal_shown'] = true;
        echo json_encode(['success' => true, 'message' => 'Password warning marked as shown']);
        exit;
    }

    // Only allow viewing cases (read-only)
    if ($_POST['action'] === 'getCases') {
        if (!$studentId) {
            echo json_encode(['success' => false, 'error' => 'Student record not found', 'cases' => []]);
            exit;
        }

        // Get cases for only this student (including archived)
        $sql = "SELECT c.*, s.first_name, s.last_name, s.student_id,
                CONCAT(s.first_name, ' ', s.last_name) as student_name,
                u.full_name as assigned_to_name
                FROM cases c
                LEFT JOIN students s ON c.student_id = s.student_id
                LEFT JOIN users u ON c.assigned_to = u.user_id
                WHERE c.student_id = ?
                ORDER BY c.is_archived ASC, c.date_reported DESC, c.created_at DESC";
        
        $cases = fetchAll($sql, [$studentId]);

        // Format data for JavaScript
        $formattedCases = array_map(function ($case) {
            $portfolioSanction = fetchOne(
                "SELECT TOP 1 cs.case_sanction_id, cs.sanction_id, cs.duration_days, cs.duration_extra_hours, cs.deadline,
                        cs.applied_date, cs.is_completed, s.sanction_name
                 FROM case_sanctions cs
                 JOIN sanctions s ON cs.sanction_id = s.sanction_id
                 WHERE cs.case_id = ?
                   AND (
                        LOWER(s.sanction_name) LIKE '%corrective%'
                        OR LOWER(s.sanction_name) LIKE '%community service%'
                        OR LOWER(s.sanction_name) LIKE '%suspension from class%'
                   )
                 ORDER BY cs.applied_date DESC, cs.case_sanction_id DESC",
                [$case['case_id']]
            );
            $isSuspensionSanction = $portfolioSanction
                && strpos(strtolower((string)($portfolioSanction['sanction_name'] ?? '')), 'suspension from class') !== false;
            $portfolioCompletion = $portfolioSanction
                ? ($isSuspensionSanction
                    ? getSuspensionCompletionSnapshot($portfolioSanction['case_sanction_id'])
                    : getCommunityServiceCompletionSnapshot($portfolioSanction['case_sanction_id']))
                : null;

            return [
                'id' => $case['case_id'],
                'student' => $case['student_name'],
                'studentId' => $case['student_id'],
                'type' => $case['case_type'],
                'date' => formatDate($case['date_reported']),
                'status' => $case['status'],
                'assignedTo' => $case['assigned_to_name'] ?? 'Unassigned',
                'statusColor' => match ($case['status']) {
                    'Pending' => 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-500/30',
                    'On Going' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30',
                    'Resolved' => 'bg-green-500/10 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-500/30',
                    'Dismissed' => 'bg-gray-500/10 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-500/30',
                    'Recorded' => 'bg-green-500/10 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-500/30',
                    'Unrecorded' => 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-500/30',
                    default => 'bg-gray-500/10 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-500/30',
                },
                'description' => $case['description'] ?? '',
                'notes' => $case['notes'] ?? '',
                'severity' => $case['severity'] ?? 'Minor',
                'isArchived' => $case['is_archived'] == 1,
                'portfolioSanctionId' => $portfolioSanction['case_sanction_id'] ?? null,
                'hasProgressModal' => !empty($portfolioSanction['case_sanction_id']),
                'checkInCompleted' => !empty($portfolioCompletion['is_complete']),
                'isSuspension' => $isSuspensionSanction
            ];
        }, $cases);

        echo json_encode(['success' => true, 'cases' => $formattedCases]);
        exit;
    }

    // Get single case details (read-only view)
    if ($_POST['action'] === 'getCaseDetails') {
        $caseId = $_POST['caseId'] ?? null;
        
        if (!$caseId || !$studentId) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            exit;
        }

        // Get case but verify it belongs to the current student
        $sql = "SELECT c.*, s.first_name, s.last_name, s.student_id,
                CONCAT(s.first_name, ' ', s.last_name) as student_name,
                u.full_name as assigned_to_name
                FROM cases c
                LEFT JOIN students s ON c.student_id = s.student_id
                LEFT JOIN users u ON c.assigned_to = u.user_id
                WHERE c.case_id = ? AND c.student_id = ?";
        
        $case = fetchOne($sql, [$caseId, $studentId]);

        if (!$case) {
            echo json_encode(['success' => false, 'error' => 'Case not found']);
            exit;
        }

        $portfolioSanction = fetchOne(
            "SELECT TOP 1 cs.case_sanction_id, cs.sanction_id, cs.duration_days, cs.duration_extra_hours, cs.deadline,
                    cs.applied_date, cs.is_completed, s.sanction_name
             FROM case_sanctions cs
             JOIN sanctions s ON cs.sanction_id = s.sanction_id
             WHERE cs.case_id = ?
               AND (
                    LOWER(s.sanction_name) LIKE '%corrective%'
                    OR LOWER(s.sanction_name) LIKE '%community service%'
                    OR LOWER(s.sanction_name) LIKE '%suspension from class%'
               )
             ORDER BY cs.applied_date DESC, cs.case_sanction_id DESC",
            [$caseId]
        );

        $caseSanctions = fetchAll(
            "SELECT cs.case_sanction_id, cs.sanction_id, cs.duration_days, cs.duration_extra_hours, cs.deadline,
                    cs.applied_date, cs.is_completed, s.sanction_name
             FROM case_sanctions cs
             JOIN sanctions s ON cs.sanction_id = s.sanction_id
             WHERE cs.case_id = ?
             ORDER BY cs.applied_date DESC, cs.case_sanction_id DESC",
            [$caseId]
        );

        if ($portfolioSanction) {
            $completionSnapshot = getCommunityServiceCompletionSnapshot($portfolioSanction['case_sanction_id']);
            if ($completionSnapshot) {
                $portfolioSanction['is_completed'] = !empty($completionSnapshot['is_complete']);
                $portfolioSanction['completed_days'] = intval($completionSnapshot['completed_days'] ?? 0);
                $portfolioSanction['completed_hours'] = floatval($completionSnapshot['completed_hours'] ?? 0);
                $portfolioSanction['completion_percent'] = floatval($completionSnapshot['completion_percent'] ?? 0);
            }
        }

        $submissions = [];
        if ($portfolioSanction) {
            $submissions = fetchAll(
                "SELECT submission_id, original_file_name, file_size_bytes, file_path, remarks, created_at
                 FROM community_service_submissions
                 WHERE case_id = ? AND student_id = ?
                 ORDER BY created_at DESC, submission_id DESC",
                [$caseId, $studentId]
            );
        }

        $case['portfolio_sanction'] = $portfolioSanction ?: null;
        $case['community_service_sanction'] = $portfolioSanction ?: null;
        $case['suspension_sanction'] = $portfolioSanction ?: null;
        $case['case_sanctions'] = $caseSanctions;
        $case['community_service_submissions'] = $submissions;

        // Log this student viewing their case
        auditStudentCaseViewed($caseId);

        echo json_encode(['success' => true, 'case' => $case]);
        exit;
    }

    if ($_POST['action'] === 'getCheckInProgress') {
        if (!$studentId) {
            echo json_encode(['success' => false, 'error' => 'Student record not found']);
            exit;
        }

        $caseId = trim($_POST['caseId'] ?? '');
        $caseSanctionId = intval($_POST['caseSanctionId'] ?? 0);

        if ($caseId === '' || $caseSanctionId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            exit;
        }

        $ownedCase = fetchOne(
            "SELECT c.case_id, CONCAT(s.first_name, ' ', s.last_name) AS student_name
             FROM cases c
             JOIN students s ON s.student_id = c.student_id
             WHERE c.case_id = ? AND c.student_id = ?",
            [$caseId, $studentId]
        );

        if (!$ownedCase) {
            echo json_encode(['success' => false, 'error' => 'Case not found']);
            exit;
        }

        $sanction = fetchOne(
            "SELECT cs.case_sanction_id, cs.duration_days, cs.duration_extra_hours, cs.deadline, cs.applied_date, s.sanction_name
             FROM case_sanctions cs
             JOIN sanctions s ON s.sanction_id = cs.sanction_id
             WHERE cs.case_sanction_id = ?
               AND cs.case_id = ?
               AND (
                    LOWER(s.sanction_name) LIKE '%corrective%'
                    OR LOWER(s.sanction_name) LIKE '%community service%'
                    OR LOWER(s.sanction_name) LIKE '%suspension from class%'
               )",
            [$caseSanctionId, $caseId]
        );

        if (!$sanction) {
            echo json_encode(['success' => false, 'error' => 'Check-in progress not available for this case']);
            exit;
        }

        $isSuspension = strpos(strtolower((string)($sanction['sanction_name'] ?? '')), 'suspension from class') !== false;
        $snapshot = $isSuspension
            ? getSuspensionCompletionSnapshot($caseSanctionId)
            : getCommunityServiceCompletionSnapshot($caseSanctionId);
        if ($snapshot) {
            $sanction['is_completed'] = !empty($snapshot['is_complete']);
            $sanction['completed_hours'] = floatval($snapshot['completed_hours'] ?? 0);
            $sanction['completed_days'] = intval($snapshot['completed_days'] ?? 0);
            $sanction['progress_percent'] = floatval($snapshot['progress_percent'] ?? 0);
        }

        $inferDurationDays = function ($durationValue, $sanctionName) {
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

        $totalDays = $inferDurationDays($sanction['duration_days'] ?? null, $sanction['sanction_name'] ?? '');
        $extraHours = max(0, intval($sanction['duration_extra_hours'] ?? 0));
        $totalHours = max(0, $totalDays > 0 ? ($extraHours > 0 ? (($totalDays - 1) * 8) + $extraHours : ($totalDays * 8)) : 0);
        $isSuspension = strpos(strtolower((string)($sanction['sanction_name'] ?? '')), 'suspension from class') !== false;

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
        $checkIns = fetchAll($checkInSql, [$caseSanctionId]);

        $days = [];
        $maxRecordedDay = 0;
        foreach ($checkIns as $checkInRow) {
            $dayNumber = intval($checkInRow['day_number'] ?? 0);
            if ($dayNumber > $maxRecordedDay) {
                $maxRecordedDay = $dayNumber;
            }
            $days[] = [
                'day' => $dayNumber,
                'check_in_time' => $checkInRow['check_in_time'] ?? null,
                'check_out_time' => $checkInRow['check_out_time'] ?? null
            ];
        }

        $displayTotalDays = max(1, $maxRecordedDay);
        if ($maxRecordedDay === 0) {
            $displayTotalDays = 1;
        }

        $progressPercent = $isSuspension
            ? intval($sanction['progress_percent'] ?? ($totalDays > 0 ? min(100, round(((int)($sanction['completed_days'] ?? 0) / $totalDays) * 100)) : 0))
            : ($totalHours > 0 ? min(100, round(((float)($sanction['completed_hours'] ?? 0) / $totalHours) * 100)) : 0);

        $portfolioSubmissions = fetchAll(
            "SELECT submission_id, case_sanction_id, original_file_name, file_size_bytes, file_path, remarks, created_at
             FROM community_service_submissions
             WHERE case_id = ? AND case_sanction_id = ?
             ORDER BY created_at DESC, submission_id DESC",
            [$caseId, $caseSanctionId]
        );

        echo json_encode([
            'success' => true,
            'progress' => [
                'case_id' => $caseId,
                'case_sanction_id' => $caseSanctionId,
                'student_name' => $ownedCase['student_name'],
                'sanction_name' => $sanction['sanction_name'],
                'deadline' => $sanction['deadline'] ?? null,
                'applied_date' => $sanction['applied_date'] ?? null,
                'is_suspension' => $isSuspension,
                'total_days' => $totalDays,
                'total_hours' => $totalHours,
                'completed_days' => intval($sanction['completed_days'] ?? 0),
                'completed_hours' => floatval($sanction['completed_hours'] ?? 0),
                'progress_percent' => $progressPercent,
                'days' => $days,
                'display_total_days' => $displayTotalDays,
                'is_completed' => !empty($sanction['is_completed']),
                'portfolio_submissions' => $portfolioSubmissions
            ]
        ]);
        exit;
    }

    if ($_POST['action'] === 'uploadCommunityServicePortfolio') {
        if (!$studentId) {
            echo json_encode(['success' => false, 'error' => 'Student record not found']);
            exit;
        }

        $caseId = trim($_POST['caseId'] ?? '');
        $caseSanctionId = intval($_POST['caseSanctionId'] ?? 0);
        if ($caseId === '' || $caseSanctionId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid case or sanction']);
            exit;
        }

        $ownedCase = fetchOne(
            "SELECT c.case_id, CONCAT(s.first_name, ' ', s.last_name) AS student_name
             FROM cases c
             JOIN students s ON s.student_id = c.student_id
             WHERE c.case_id = ? AND c.student_id = ?",
            [$caseId, $studentId]
        );

        if (!$ownedCase) {
            echo json_encode(['success' => false, 'error' => 'You cannot upload to this case']);
            exit;
        }

        $sanction = fetchOne(
            "SELECT cs.case_sanction_id, s.sanction_name, cs.is_completed
             FROM case_sanctions cs
             JOIN sanctions s ON s.sanction_id = cs.sanction_id
             WHERE cs.case_sanction_id = ?
               AND cs.case_id = ?
               AND (
                   LOWER(s.sanction_name) LIKE '%corrective%'
                   OR LOWER(s.sanction_name) LIKE '%community service%'
                   OR LOWER(s.sanction_name) LIKE '%suspension from class%'
               )",
            [$caseSanctionId, $caseId]
        );

        if ($sanction) {
            $completionSnapshot = getCommunityServiceCompletionSnapshot($sanction['case_sanction_id']);
            if ($completionSnapshot) {
                $sanction['is_completed'] = !empty($completionSnapshot['is_complete']);
            }
        }

        if (!$sanction) {
            echo json_encode(['success' => false, 'error' => 'Portfolio-enabled sanction not found for this case']);
            exit;
        }

        // Check if sanction is completed
        if (!$sanction['is_completed']) {
            echo json_encode(['success' => false, 'error' => 'You can only upload files after the suspension or community service is complete']);
            exit;
        }

        if (!isset($_FILES['portfolioFile']) || !is_array($_FILES['portfolioFile'])) {
            echo json_encode(['success' => false, 'error' => 'Please select a file to upload']);
            exit;
        }

        $file = $_FILES['portfolioFile'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'File upload failed']);
            exit;
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        if (($file['size'] ?? 0) > $maxSize) {
            echo json_encode(['success' => false, 'error' => 'File is too large. Max size is 10MB']);
            exit;
        }

        $originalName = trim((string)($file['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
        if (!in_array($extension, $allowedExtensions, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: PDF, DOC, DOCX, PNG, JPG']);
            exit;
        }

        $uploadBaseDir = __DIR__ . '/../../assets/community_service_submissions';
        $caseDir = $uploadBaseDir . '/' . preg_replace('/[^A-Za-z0-9_-]/', '_', $caseId);
        if (!is_dir($caseDir) && !mkdir($caseDir, 0755, true) && !is_dir($caseDir)) {
            echo json_encode(['success' => false, 'error' => 'Unable to prepare upload directory']);
            exit;
        }

        $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $generatedFileName = date('Ymd_His') . '_' . uniqid('', true) . '_' . $safeBase . '.' . $extension;
        $absolutePath = $caseDir . '/' . $generatedFileName;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
            exit;
        }

        $publicPath = '/PrototypeDO/assets/community_service_submissions/'
            . rawurlencode(preg_replace('/[^A-Za-z0-9_-]/', '_', $caseId))
            . '/' . rawurlencode($generatedFileName);

        executeQuery(
            "INSERT INTO community_service_submissions
             (case_id, case_sanction_id, student_id, uploaded_by, file_name, original_file_name, file_path, file_size_bytes, mime_type, remarks, is_seen_by_do)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)",
            [
                $caseId,
                $caseSanctionId,
                $studentId,
                $_SESSION['user_id'] ?? null,
                $generatedFileName,
                $originalName,
                $publicPath,
                intval($file['size'] ?? 0),
                (string)($file['type'] ?? ''),
                null,
            ]
        );

        // Audit log the portfolio submission
        auditPortfolioSubmitted($caseId, $studentId, $sanction['sanction_name'] ?? 'Unknown', $originalName);

        notifyDOOnCommunityServicePortfolioSubmission(
            $caseId,
            $ownedCase['student_name'] ?? 'Student',
            $originalName,
            $sanction['sanction_name'] ?? ''
        );

        echo json_encode([
            'success' => true,
            'message' => 'Completion report uploaded successfully'
        ]);
        exit;
    }

    // Reject any edit/update/delete attempts
    if (in_array($_POST['action'], ['updateCase', 'deleteCase', 'archiveCase', 'createCase'])) {
        echo json_encode(['success' => false, 'error' => 'You do not have permission to modify cases']);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office - <?php echo htmlspecialchars($pageTitle); ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };

        // Restore dark mode
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

    <!-- Mobile Overlay Backdrop -->
    <div id="sidebarOverlay" 
         onclick="toggleSidebar()" 
         class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity"></div>

    <div class="flex h-screen overflow-hidden">
        <div class="flex-1 overflow-y-auto ml-0 md:ml-64 transition-all duration-300">
            <!-- Mobile Navigation Header Bar -->
            <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white dark:bg-[#111827] border-b border-gray-200 dark:border-slate-700 flex items-center justify-between px-4 z-20">
                <button type="button" 
                        onclick="toggleSidebar()" 
                        class="p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white rounded-lg focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="font-bold text-gray-900 dark:text-gray-100 text-sm">STI Discipline Office</span>
                <div class="w-6"></div>
            </div>

            <!-- Fixed Header -->
            <?php include __DIR__ . '/../../includes/header.php'; ?>

            <!-- Page Content -->
            <main class="p-4 pt-20 md:p-8 md:pt-28 min-h-screen transition-colors duration-300">
                <!-- Page Title -->
                <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100">My Cases</h1>
                        <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 mt-1 sm:mt-2">View all the discipline cases you are involved in</p>
                    </div>
                    <button id="toggleArchivedBtn" onclick="toggleArchivedCases()" class="w-full sm:w-auto px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors font-medium flex items-center justify-center gap-2 text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        <span id="toggleArchivedText">Show Archived</span>
                    </button>
                </div>

                <!-- Cases Table -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto min-w-[640px]">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50">
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase w-32">Case ID</th>
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase w-44">Type</th>
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase w-32">Date Reported</th>
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase w-28">Severity</th>
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase w-28">Status</th>
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase w-44">Assigned To</th>
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase w-36">Action</th>
                                </tr>
                            </thead>
                            <tbody id="casesTableBody" class="divide-y divide-gray-200 dark:divide-slate-700">
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="inline-block">
                                            <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent mb-3"></div>
                                            <p class="text-gray-500 dark:text-gray-400">Loading cases...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div id="emptyState" class="hidden p-8 md:p-12 text-center">
                        <svg class="w-12 h-12 md:w-16 md:h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400 text-base md:text-lg">No cases found</p>
                        <p class="text-xs md:text-sm text-gray-400 dark:text-gray-500 mt-2">You are not involved in any discipline cases at this time</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Case Details Modal -->
    <div id="caseModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#111827] rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-slate-700">
            <!-- Modal Header -->
            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 px-4 md:px-6 py-4 flex items-center justify-between border-b border-blue-800 dark:border-blue-900">
                <h2 id="modalTitle" class="text-lg md:text-xl font-bold text-white">Case Details</h2>
                <button onclick="closeCaseModal()" class="text-white hover:bg-blue-800 rounded p-1 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Content -->
            <div id="modalContent" class="p-4 md:p-6 space-y-4 md:space-y-6">
                <div class="inline-block">
                    <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-gray-200 dark:border-slate-700 px-4 md:px-6 py-4 flex justify-end gap-3">
                <button onclick="closeCaseModal()" class="w-full sm:w-auto px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors font-medium text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle Handler for Mobile Viewports
        function toggleSidebar() {
            const sidebar = document.querySelector('aside') || document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar) {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            }
        }

        let allCases = [];
        let showArchived = false;

        // Load cases on page load
        document.addEventListener('DOMContentLoaded', function () {
            loadCases();

            // Check if a specific case should be opened from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const caseId = urlParams.get('case_id');
            
            if (caseId) {
                // Remove the case_id parameter from URL to prevent modal from opening again on reload
                const newUrl = new URL(window.location);
                newUrl.searchParams.delete('case_id');
                window.history.replaceState({}, '', newUrl);
                
                // Wait a bit for cases to load, then open the specific case
                setTimeout(() => {
                    viewCaseDetails(caseId);
                }, 500);
            }
        });

        async function loadCases() {
            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', 'getCases');

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    allCases = data.cases;
                    renderCases();
                } else {
                    showEmptyState();
                }
            } catch (error) {
                console.error('Error loading cases:', error);
                showEmptyState();
            }
        }

        function renderCases() {
            const tbody = document.getElementById('casesTableBody');
            const emptyState = document.getElementById('emptyState');

            // Filter cases based on showArchived flag
            const filteredCases = showArchived ? allCases : allCases.filter(c => !c.isArchived);

            if (filteredCases.length === 0) {
                tbody.innerHTML = '';
                emptyState.classList.remove('hidden');
                return;
            }

            emptyState.classList.add('hidden');
            tbody.innerHTML = filteredCases.map(caseItem => `
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors ${caseItem.isArchived ? 'opacity-70' : ''}">
                    <td class="px-4 md:px-6 py-3 md:py-4 text-xs md:text-sm font-semibold text-gray-900 dark:text-gray-100 w-32 align-middle">
                        <div class="truncate">${escapeHtml(caseItem.id)}</div>
                        ${caseItem.isArchived ? '<span class="ml-1 md:ml-2 px-2 py-0.5 rounded-full text-[10px] md:text-xs font-semibold bg-gray-500/10 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600">Archived</span>' : ''}
                    </td>
                    <td class="px-4 md:px-6 py-3 md:py-4 text-xs md:text-sm text-gray-600 dark:text-gray-400 w-44"><div class="truncate">${escapeHtml(caseItem.type)}</div></td>
                    <td class="px-4 md:px-6 py-3 md:py-4 text-xs md:text-sm text-gray-600 dark:text-gray-400 w-32"><div class="truncate">${escapeHtml(caseItem.date)}</div></td>
                    <td class="px-4 md:px-6 py-3 md:py-4 text-xs md:text-sm w-28 align-middle">
                        <div class="inline-flex items-center justify-center">
                            <span class="inline-flex items-center justify-center rounded-full text-xs font-semibold leading-none px-2.5 py-1 ${getSeverityClass(caseItem.severity)}">
                                ${escapeHtml(caseItem.severity)}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 md:px-6 py-3 md:py-4 text-xs md:text-sm w-28 align-middle">
                        <div class="inline-flex items-center justify-center">
                            <span class="inline-flex items-center justify-center rounded-full text-xs font-semibold leading-none px-2.5 py-1 ${caseItem.statusColor}">
                                ${escapeHtml(caseItem.status)}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 md:px-6 py-3 md:py-4 text-xs md:text-sm text-gray-600 dark:text-gray-400 w-44"><div class="truncate">${escapeHtml(caseItem.assignedTo)}</div></td>
                    <td class="px-4 md:px-6 py-3 md:py-4 text-xs md:text-sm w-36 whitespace-nowrap align-middle">
                        <div class="flex items-center gap-1.5 whitespace-nowrap flex-nowrap">
                            <button onclick="viewCaseDetails('${escapeHtml(caseItem.id)}')" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition-colors whitespace-nowrap text-xs md:text-sm">
                                View Details
                            </button>
                            ${caseItem.hasProgressModal ? '<span class="text-gray-400 dark:text-gray-500 text-xs md:text-sm font-medium select-none">|</span>' : ''}
                            ${caseItem.hasProgressModal ? `
                                <button type="button" onclick="openCheckInProgressModal('${escapeHtml(caseItem.id)}', '${escapeHtml(caseItem.portfolioSanctionId)}')" data-case-checkin-icon="true" data-case-checkin-type="${caseItem.isSuspension ? 'suspension' : 'corrective'}" data-case-id="${escapeHtml(caseItem.id)}" class="${caseItem.checkInCompleted ? 'text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300' : 'text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300'} font-medium transition-colors whitespace-nowrap text-xs md:text-sm" title="${caseItem.isSuspension ? 'Suspension Progress' : 'Check-In Progress'}" aria-label="${caseItem.isSuspension ? 'Suspension Progress' : 'Check-In Progress'}">
                                    ${caseItem.isSuspension ? 'Suspension Progress' : 'Check-In Progress'}
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        async function viewCaseDetails(caseId) {
            const modal = document.getElementById('caseModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');

            // Show loading state
            modalTitle.textContent = 'Loading...';
            modalContent.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div></div>';
            modal.classList.remove('hidden');

            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', 'getCaseDetails');
                formData.append('caseId', caseId);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    const caseData = data.case;
                    const isArchived = caseData.is_archived == 1;
                    modalTitle.innerHTML = `Case ${escapeHtml(caseData.case_id)} Details ${isArchived ? '<span class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-500/20 text-gray-200 border border-gray-400">Archived</span>' : ''}`;
                    
                    modalContent.innerHTML = `
                        <div class="space-y-4">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Case ID</label>
                                    <p class="text-base md:text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">${escapeHtml(caseData.case_id)}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</label>
                                    <p class="text-base md:text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${getStatusColorClass(caseData.status)}">
                                            ${escapeHtml(caseData.status)}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Case Type</label>
                                        <p class="text-sm md:text-base text-gray-900 dark:text-gray-100 mt-1">${escapeHtml(caseData.case_type)}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Severity</label>
                                        <p class="text-sm md:text-base text-gray-900 dark:text-gray-100 mt-1">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold ${getSeverityColorClass(caseData.severity)}">
                                                ${escapeHtml(caseData.severity)}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Date Reported</label>
                                        <p class="text-sm md:text-base text-gray-900 dark:text-gray-100 mt-1">${escapeHtml(formatDisplayDate(caseData.date_reported))}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Assigned To</label>
                                        <p class="text-sm md:text-base text-gray-900 dark:text-gray-100 mt-1">${escapeHtml(caseData.assigned_to_name || 'Unassigned')}</p>
                                    </div>
                                </div>
                            </div>

                            ${caseData.description ? `
                                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Description</label>
                                    <p class="text-sm md:text-base text-gray-900 dark:text-gray-100 mt-2 whitespace-pre-wrap">${escapeHtml(caseData.description)}</p>
                                </div>
                            ` : ''}

                            ${caseData.notes ? `
                                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Notes</label>
                                    <p class="text-sm md:text-base text-gray-900 dark:text-gray-100 mt-2 whitespace-pre-wrap">${escapeHtml(caseData.notes)}</p>
                                </div>
                            ` : ''}

                            ${Array.isArray(caseData.case_sanctions) && caseData.case_sanctions.length ? `
                                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Sanction Given</label>
                                    <div class="mt-3 space-y-3">
                                        ${caseData.case_sanctions.map((sanction) => {
                                            const durationParts = [];
                                            const durationDays = Number(sanction.duration_days || 0);
                                            const durationExtraHours = Number(sanction.duration_extra_hours || 0);
                                            if (durationDays > 0) {
                                                durationParts.push(`${durationDays} ${durationDays === 1 ? 'day' : 'days'}`);
                                            }
                                            if (durationExtraHours > 0) {
                                                durationParts.push(`${durationExtraHours} ${durationExtraHours === 1 ? 'hour' : 'hours'}`);
                                            }
                                            const durationLabel = durationParts.length > 0 ? durationParts.join(' • ') : 'No fixed duration';
                                            const appliedDate = sanction.applied_date ? formatDisplayDate(sanction.applied_date) : 'Unknown date';
                                            const deadline = sanction.deadline ? formatDisplayDate(sanction.deadline) : '';
                                            const isCompleted = !!sanction.is_completed;

                                            return `
                                                <div class="rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/40 p-3 md:p-4">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div>
                                                            <p class="text-sm md:text-base font-semibold text-gray-900 dark:text-gray-100">${escapeHtml(sanction.sanction_name || 'Sanction')}</p>
                                                            <p class="text-xs md:text-sm text-gray-600 dark:text-gray-300 mt-1">${escapeHtml(durationLabel)}</p>
                                                        </div>
                                                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${isCompleted ? 'bg-green-500/10 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-500/30' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30'}">${isCompleted ? 'Completed' : 'Active'}</span>
                                                    </div>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 text-xs md:text-sm">
                                                        <div>
                                                            <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Applied Date</span>
                                                            <span class="text-gray-900 dark:text-gray-100">${escapeHtml(appliedDate)}</span>
                                                        </div>
                                                        ${deadline ? `
                                                            <div>
                                                                <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Deadline</span>
                                                                <span class="text-gray-900 dark:text-gray-100">${escapeHtml(deadline)}</span>
                                                            </div>
                                                        ` : ''}
                                                    </div>
                                                </div>
                                            `;
                                        }).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            ${caseData.location ? `
                                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Location</label>
                                    <p class="text-sm md:text-base text-gray-900 dark:text-gray-100 mt-1">${escapeHtml(caseData.location)}</p>
                                </div>
                            ` : ''}

                            ${caseData.action_taken ? `
                                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Action Taken</label>
                                    <p class="text-sm md:text-base text-gray-900 dark:text-gray-100 mt-2 whitespace-pre-wrap">${escapeHtml(caseData.action_taken)}</p>
                                </div>
                            ` : ''}
                        </div>
                    `;
                } else {
                    modalContent.innerHTML = `<p class="text-red-600 dark:text-red-400 text-sm">${escapeHtml(data.error || 'Error loading case details')}</p>`;
                }
            } catch (error) {
                console.error('Error loading case details:', error);
                modalContent.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">Error loading case details</p>';
            }
        }

        function closeCaseModal() {
            document.getElementById('caseModal').classList.add('hidden');
        }

        function toggleArchivedCases() {
            showArchived = !showArchived;
            const toggleBtn = document.getElementById('toggleArchivedText');
            toggleBtn.textContent = showArchived ? 'Hide Archived' : 'Show Archived';
            renderCases();
        }

        function showEmptyState() {
            const tbody = document.getElementById('casesTableBody');
            const emptyState = document.getElementById('emptyState');
            tbody.innerHTML = '';
            emptyState.classList.remove('hidden');
        }

        function getSeverityClass(severity) {
            switch (severity) {
                case 'Major':
                    return 'bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400';
                case 'Minor':
                    return 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400';
                default:
                    return 'bg-gray-100 dark:bg-gray-500/10 text-gray-700 dark:text-gray-400';
            }
        }

        function getSeverityColorClass(severity) {
            switch (severity) {
                case 'Major':
                    return 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30';
                case 'Minor':
                    return 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-500/30';
                default:
                    return 'bg-gray-500/10 text-gray-700 dark:text-gray-400 border border-gray-200 dark:border-gray-500/30';
            }
        }

        function getStatusColorClass(status) {
            switch (status) {
                case 'Pending':
                    return 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-500/30';
                case 'On Going':
                    return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30';
                case 'Resolved':
                    return 'bg-green-500/10 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-500/30';
                case 'Dismissed':
                    return 'bg-gray-500/10 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-500/30';
                case 'Recorded':
                    return 'bg-green-500/10 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-500/30';
                case 'Unrecorded':
                    return 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-500/30';
                default:
                    return 'bg-gray-500/10 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-500/30';
            }
        }

        function formatFileSize(sizeBytes) {
            const size = Number(sizeBytes || 0);
            if (!Number.isFinite(size) || size <= 0) return 'Unknown size';
            if (size < 1024) return `${size} B`;
            if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
            return `${(size / (1024 * 1024)).toFixed(2)} MB`;
        }

        function renderCommunityServiceSubmissions(submissions) {
            if (!Array.isArray(submissions) || submissions.length === 0) {
                return '<div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">No submissions yet.</div>';
            }

            return submissions.map((submission) => {
                const createdAt = submission.created_at ? formatDisplayDate(submission.created_at) : 'Unknown date';
                const filePath = submission.file_path ? escapeHtml(submission.file_path) : '#';
                const fileName = escapeHtml(submission.original_file_name || 'Submitted file');
                const remarks = submission.remarks ? `<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${escapeHtml(submission.remarks)}</p>` : '';

                return `
                    <div class="px-3 md:px-4 py-3 border-b border-gray-200 dark:border-slate-700 last:border-b-0 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs md:text-sm font-medium text-gray-900 dark:text-gray-100 truncate">${fileName}</p>
                            <p class="text-[10px] md:text-xs text-gray-500 dark:text-gray-400 mt-0.5">${createdAt} • ${formatFileSize(submission.file_size_bytes)}</p>
                            ${remarks}
                        </div>
                        <a href="${filePath}" target="_blank" rel="noopener" class="px-2.5 py-1 md:px-3 md:py-1.5 text-xs font-semibold rounded-md border border-blue-200 dark:border-blue-500/40 text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-colors">View</a>
                    </div>
                `;
            }).join('');
        }

        async function uploadCommunityServicePortfolio(caseId, caseSanctionId) {
            const fileInput = document.getElementById('portfolioFileInput');
            const statusEl = document.getElementById('portfolioUploadStatus');

            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                if (statusEl) {
                    statusEl.textContent = 'Please choose a file before submitting.';
                    statusEl.className = 'text-xs text-red-600 dark:text-red-400';
                }
                return;
            }

            const payload = new FormData();
            payload.append('ajax', '1');
            payload.append('action', 'uploadCommunityServicePortfolio');
            payload.append('caseId', caseId);
            payload.append('caseSanctionId', caseSanctionId);
            payload.append('portfolioFile', fileInput.files[0]);

            if (statusEl) {
                statusEl.textContent = 'Uploading...';
                statusEl.className = 'text-xs text-blue-600 dark:text-blue-400';
            }

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: payload
                });

                const result = await response.json();
                if (!result.success) {
                    if (statusEl) {
                        statusEl.textContent = result.error || 'Upload failed.';
                        statusEl.className = 'text-xs text-red-600 dark:text-red-400';
                    }
                    return;
                }

                if (statusEl) {
                    statusEl.textContent = result.message || 'Upload successful.';
                    statusEl.className = 'text-xs text-green-600 dark:text-green-400';
                }

                if (typeof showNotification === 'function') {
                    showNotification(result.message || 'Portfolio submitted successfully', 'success');
                }

                fileInput.value = '';
                await openCheckInProgressModal(caseId, caseSanctionId);
            } catch (error) {
                console.error('Portfolio upload error:', error);
                if (statusEl) {
                    statusEl.textContent = 'Upload failed due to a network error.';
                    statusEl.className = 'text-xs text-red-600 dark:text-red-400';
                }

                if (typeof showNotification === 'function') {
                    showNotification('Upload failed due to a network error.', 'error');
                }
            }
        }

        async function openCheckInProgressModal(caseId, caseSanctionId) {
            document.querySelectorAll('[data-checkin-progress-modal="true"]').forEach((el) => el.remove());

            const overlay = document.createElement('div');
            overlay.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-[70] p-4';
            overlay.setAttribute('data-checkin-progress-modal', 'true');
            overlay.innerHTML = `
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-slate-700">
                    <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 px-4 md:px-6 py-4 flex items-center justify-between border-b border-blue-800 dark:border-blue-900">
                        <div>
                            <h2 class="text-lg md:text-xl font-bold text-white">${caseId && caseSanctionId ? 'Loading...' : 'Check-In Progress'}</h2>
                        </div>
                        <button type="button" class="text-white hover:bg-blue-800 rounded p-1 transition-colors" onclick="closeCheckInProgressModal()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-4 md:p-6" data-checkin-progress-content>
                        <div class="flex items-center justify-center py-10">
                            <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
                        </div>
                    </div>
                </div>
            `;

            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) {
                    overlay.remove();
                }
            });

            document.body.appendChild(overlay);

            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', 'getCheckInProgress');
                formData.append('caseId', caseId);
                formData.append('caseSanctionId', caseSanctionId);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                const content = overlay.querySelector('[data-checkin-progress-content]');

                if (!data.success || !data.progress) {
                    content.innerHTML = `<p class="text-red-600 dark:text-red-400 text-sm">${escapeHtml(data.error || 'Unable to load check-in progress')}</p>`;
                    return;
                }

                const title = data.progress.is_suspension ? 'Suspension Progress' : 'Check-In Progress';
                const modalTitle = overlay.querySelector('h2');
                if (modalTitle) {
                    modalTitle.textContent = title;
                }

                renderCheckInProgressModal(content, data.progress);
            } catch (error) {
                console.error('Error loading check-in progress:', error);
                const content = overlay.querySelector('[data-checkin-progress-content]');
                if (content) {
                    content.innerHTML = '<p class="text-red-600 dark:text-red-400 text-sm">Error loading check-in progress</p>';
                }
            }
        }

        function closeCheckInProgressModal() {
            document.querySelectorAll('[data-checkin-progress-modal="true"]').forEach((el) => el.remove());
        }

        function renderCheckInProgressModal(container, progress) {
            const isSuspension = !!progress.is_suspension;
            const totalValue = isSuspension ? Number(progress.total_days || 0) : Number(progress.total_hours || 0);
            const completedValue = isSuspension ? Number(progress.completed_days || 0) : Number(progress.completed_hours || 0);
            const progressPercent = Number(progress.progress_percent || 0);
            const dayCards = Array.isArray(progress.days) ? progress.days : [];
            const displayDays = Math.max(1, Number(progress.display_total_days || dayCards.length || 1));
            const dayMap = new Map(dayCards.map((item) => [Number(item.day), item]));
            const canUploadPortfolio = !!progress.is_completed;
            const portfolioSubmissions = Array.isArray(progress.portfolio_submissions) ? progress.portfolio_submissions : [];

            let dayCardsHtml = '';

            if (isSuspension) {
                // Suspension progress is day-based: show days served vs upcoming days
                const totalDaysForDisplay = Math.max(1, Number(progress.total_days || displayDays));
                const completedDaysCount = Number(progress.completed_days || 0);

                dayCardsHtml = Array.from({ length: totalDaysForDisplay }, (_, index) => {
                    const dayNumber = index + 1;
                    const isDone = dayNumber <= completedDaysCount;

                    if (isDone) {
                        return `
                            <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Day ${dayNumber}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Served</p>
                                </div>
                                <span class="text-xs font-semibold text-green-600 dark:text-green-400 flex-shrink-0">Completed</span>
                            </div>
                        `;
                    }

                    return `
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-700/40 rounded-lg border border-gray-200 dark:border-slate-600 opacity-60">
                            <div class="w-8 h-8 bg-gray-300 dark:bg-slate-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-gray-500 dark:text-gray-400 text-xs font-bold">${dayNumber}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Day ${dayNumber}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Upcoming</p>
                            </div>
                            <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">—</span>
                        </div>
                    `;
                }).join('');
            } else {
                dayCardsHtml = Array.from({ length: displayDays }, (_, index) => {
                    const dayNumber = index + 1;
                    const dayData = dayMap.get(dayNumber) || {};
                    const hasCheckIn = !!dayData.check_in_time;
                    const hasCheckOut = !!dayData.check_out_time;

                    if (hasCheckIn && hasCheckOut) {
                        return `
                            <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Day ${dayNumber}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs text-gray-500 dark:text-gray-400"><span class="font-medium text-green-600 dark:text-green-400">In:</span> ${escapeHtml(formatTimeOnly(dayData.check_in_time))}</span>
                                        <span class="text-xs text-gray-300 dark:text-gray-600">|</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400"><span class="font-medium text-green-600 dark:text-green-400">Out:</span> ${escapeHtml(formatTimeOnly(dayData.check_out_time))}</span>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-green-600 dark:text-green-400 flex-shrink-0">Completed</span>
                            </div>
                        `;
                    }

                    if (hasCheckIn) {
                        return `
                            <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-400 dark:border-blue-500">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-white text-xs font-bold">${dayNumber}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Day ${dayNumber}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs text-gray-500 dark:text-gray-400"><span class="font-medium text-blue-500">In:</span> ${escapeHtml(formatTimeOnly(dayData.check_in_time))}</span>
                                        <span class="text-xs text-gray-300 dark:text-gray-600">|</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400"><span class="font-medium text-blue-500">Out:</span> <span class="italic text-gray-400 dark:text-gray-500">In progress</span></span>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 flex-shrink-0">In progress</span>
                            </div>
                        `;
                    }

                    return `
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-700/40 rounded-lg border border-gray-200 dark:border-slate-600 opacity-60">
                            <div class="w-8 h-8 bg-gray-300 dark:bg-slate-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-gray-500 dark:text-gray-400 text-xs font-bold">${dayNumber}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Day ${dayNumber}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Upcoming</p>
                            </div>
                            <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">—</span>
                        </div>
                    `;
                }).join('');
            }

            container.innerHTML = `
                <div class="space-y-4">
                    <div class="bg-gray-50 dark:bg-slate-700/60 rounded-lg p-3 md:p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Case</p>
                                <p class="text-base md:text-lg font-semibold text-gray-900 dark:text-gray-100">${escapeHtml(progress.case_id)}</p>
                                <p class="text-xs md:text-sm text-gray-600 dark:text-gray-300 mt-1">${escapeHtml(progress.sanction_name || (isSuspension ? 'Suspension' : 'Community Service'))}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100">${escapeHtml(formatProgressValue(totalValue, isSuspension))}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">${isSuspension ? 'days total' : 'hours total'}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Progress</span>
                            <span class="text-xs font-bold text-gray-900 dark:text-gray-100">${escapeHtml(formatProgressValue(completedValue, isSuspension))} / ${escapeHtml(formatProgressValue(totalValue, isSuspension))} ${isSuspension ? 'days completed' : 'hours completed'}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-slate-600 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: ${progressPercent}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">${progressPercent}% done</p>
                    </div>

                    ${progress.deadline ? `
                        <div class="rounded-lg border border-gray-200 dark:border-slate-700 p-3 text-xs md:text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">Deadline:</span> ${escapeHtml(formatDisplayDate(progress.deadline))}
                        </div>
                    ` : ''}

                    <div class="space-y-2 max-h-[48vh] overflow-y-auto pr-1">
                        ${dayCardsHtml}
                    </div>

                    <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                        ${isSuspension ? '' : `
                            <div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Completion Report</label>
                                        <p class="text-xs md:text-sm text-gray-600 dark:text-gray-300 mt-0.5">Submit documented accomplishments, reflections, and lessons learned.</p>
                                    </div>
                                    <span class="self-start sm:self-auto px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30">Required</span>
                                </div>

                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Upload File</label>
                                        <input id="portfolioFileInput" type="file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" ${!canUploadPortfolio ? 'disabled' : ''} class="w-full text-xs md:text-sm text-gray-700 dark:text-gray-200 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-white ${canUploadPortfolio ? 'file:bg-blue-600 hover:file:bg-blue-700' : 'file:bg-gray-400 cursor-not-allowed opacity-50'}" />
                                        <p class="text-[10px] md:text-xs text-gray-500 dark:text-gray-400 mt-1">Allowed: PDF, DOC, DOCX, PNG, JPG. Max size: 10MB.</p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                                        <p id="portfolioUploadStatus" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                        <button onclick="uploadCommunityServicePortfolio('${escapeHtml(progress.case_id)}', '${escapeHtml(progress.case_sanction_id)}')" ${!canUploadPortfolio ? 'disabled' : ''} class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-xs md:text-sm prevent-double ${!canUploadPortfolio ? 'opacity-50 cursor-not-allowed' : ''}">
                                            Submit Completion Report
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Submitted Files</label>
                                    <div class="rounded-lg border border-gray-200 dark:border-slate-700 overflow-hidden">
                                        ${renderCommunityServiceSubmissions(portfolioSubmissions)}
                                    </div>
                                </div>
                            </div>
                        `}
                    </div>
                </div>
            `;
        }

        function formatTimeOnly(dateTimeValue) {
            if (!dateTimeValue) return 'Not recorded yet';

            const date = new Date(dateTimeValue);
            if (Number.isNaN(date.getTime())) {
                return 'Not recorded yet';
            }

            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function formatProgressValue(value, isSuspension) {
            if (isSuspension) {
                return `${Number(value || 0)}`;
            }

            const numericValue = Number(value || 0);
            return numericValue % 1 === 0 ? numericValue.toFixed(0) : numericValue.toFixed(2);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDisplayDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    </script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>

</html>