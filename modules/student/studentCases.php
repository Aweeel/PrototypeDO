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
                'isArchived' => $case['is_archived'] == 1
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
                    cs.applied_date, s.sanction_name
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
        $case['community_service_submissions'] = $submissions;

        echo json_encode(['success' => true, 'case' => $case]);
        exit;
    }

    if ($_POST['action'] === 'uploadCommunityServicePortfolio') {
        if (!$studentId) {
            echo json_encode(['success' => false, 'error' => 'Student record not found']);
            exit;
        }

        $caseId = trim($_POST['caseId'] ?? '');
        $caseSanctionId = intval($_POST['caseSanctionId'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

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
            "SELECT cs.case_sanction_id, s.sanction_name
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
            echo json_encode(['success' => false, 'error' => 'Portfolio-enabled sanction not found for this case']);
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
                $remarks !== '' ? $remarks : null,
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
            'message' => 'Portfolio/completion report uploaded successfully'
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

<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <!-- Fixed Header -->
            <?php include __DIR__ . '/../../includes/header.php'; ?>

            <!-- Page Content -->
            <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                <!-- Page Title -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">My Cases</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">View all the discipline cases you are involved in</p>
                    </div>
                    <button id="toggleArchivedBtn" onclick="toggleArchivedCases()" class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        <span id="toggleArchivedText">Show Archived</span>
                    </button>
                </div>

                <!-- Cases Table -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50">
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Case ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Date Reported</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Severity</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Assigned To</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Action</th>
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
                    <div id="emptyState" class="hidden p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">No cases found</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">You are not involved in any discipline cases at this time</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Case Details Modal -->
    <div id="caseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#111827] rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-slate-700">
            <!-- Modal Header -->
            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex items-center justify-between border-b border-blue-800 dark:border-blue-900">
                <h2 id="modalTitle" class="text-xl font-bold text-white">Case Details</h2>
                <button onclick="closeCaseModal()" class="text-white hover:bg-blue-800 rounded p-1 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Content -->
            <div id="modalContent" class="p-6 space-y-6">
                <div class="inline-block">
                    <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-gray-200 dark:border-slate-700 px-6 py-4 flex justify-end gap-3">
                <button onclick="closeCaseModal()" class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors font-medium">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        let allCases = [];
        let showArchived = false;

        // Load cases on page load
        document.addEventListener('DOMContentLoaded', function () {
            loadCases();

            // Check if a specific case should be opened from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const caseId = urlParams.get('case_id');
            
            if (caseId) {
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
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                        ${escapeHtml(caseItem.id)}
                        ${caseItem.isArchived ? '<span class="ml-2 px-2 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600">Archived</span>' : ''}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${escapeHtml(caseItem.type)}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${escapeHtml(caseItem.date)}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${getSeverityClass(caseItem.severity)}">
                            ${escapeHtml(caseItem.severity)}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${caseItem.statusColor}">
                            ${escapeHtml(caseItem.status)}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${escapeHtml(caseItem.assignedTo)}</td>
                    <td class="px-6 py-4 text-sm">
                        <button onclick="viewCaseDetails('${escapeHtml(caseItem.id)}')" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition-colors">
                            View
                        </button>
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
                    modalTitle.innerHTML = `Case ${escapeHtml(caseData.case_id)} Details ${isArchived ? '<span class="ml-2 px-2 py-1 rounded-full text-xs font-semibold bg-gray-500/20 text-gray-200 border border-gray-400">Archived</span>' : ''}`;
                    
                    modalContent.innerHTML = `
                        <div class="space-y-4">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Case ID</label>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">${escapeHtml(caseData.case_id)}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</label>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${getStatusColorClass(caseData.status)}">
                                            ${escapeHtml(caseData.status)}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Case Type</label>
                                        <p class="text-gray-900 dark:text-gray-100 mt-1">${escapeHtml(caseData.case_type)}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Severity</label>
                                        <p class="text-gray-900 dark:text-gray-100 mt-1">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold ${getSeverityColorClass(caseData.severity)}">
                                                ${escapeHtml(caseData.severity)}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Date Reported</label>
                                        <p class="text-gray-900 dark:text-gray-100 mt-1">${escapeHtml(formatDisplayDate(caseData.date_reported))}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Assigned To</label>
                                        <p class="text-gray-900 dark:text-gray-100 mt-1">${escapeHtml(caseData.assigned_to_name || 'Unassigned')}</p>
                                    </div>
                                </div>
                            </div>

                            ${caseData.description ? `
                                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Description</label>
                                    <p class="text-gray-900 dark:text-gray-100 mt-2 whitespace-pre-wrap">${escapeHtml(caseData.description)}</p>
                                </div>
                            ` : ''}

                            ${caseData.notes ? `
                                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Notes</label>
                                    <p class="text-gray-900 dark:text-gray-100 mt-2 whitespace-pre-wrap">${escapeHtml(caseData.notes)}</p>
                                </div>
                            ` : ''}

                            ${caseData.location ? `
                                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Location</label>
                                    <p class="text-gray-900 dark:text-gray-100 mt-1">${escapeHtml(caseData.location)}</p>
                                </div>
                            ` : ''}

                            ${caseData.portfolio_sanction ? `
                                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                    <div class="flex items-center justify-between gap-3 mb-3">
                                        <div>
                                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Portfolio / Completion Report</label>
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Submit documented accomplishments, reflections, and lessons learned.</p>
                                        </div>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30">Required</span>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Upload File</label>
                                            <input id="portfolioFileInput" type="file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" class="w-full text-sm text-gray-700 dark:text-gray-200 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700" />
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Allowed: PDF, DOC, DOCX, PNG, JPG. Max size: 10MB.</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Remarks (Optional)</label>
                                            <textarea id="portfolioRemarksInput" rows="3" placeholder="Add a brief summary of your submission" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"></textarea>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <p id="portfolioUploadStatus" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                            <button onclick="uploadCommunityServicePortfolio('${escapeHtml(caseData.case_id)}', '${escapeHtml(caseData.portfolio_sanction.case_sanction_id)}')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
                                                Submit Portfolio
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Submitted Files</label>
                                        <div class="rounded-lg border border-gray-200 dark:border-slate-700 overflow-hidden">
                                            ${renderCommunityServiceSubmissions(caseData.community_service_submissions || [])}
                                        </div>
                                    </div>
                                </div>
                            ` : ''}

                            ${caseData.action_taken ? `
                                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Action Taken</label>
                                    <p class="text-gray-900 dark:text-gray-100 mt-2 whitespace-pre-wrap">${escapeHtml(caseData.action_taken)}</p>
                                </div>
                            ` : ''}
                        </div>
                    `;
                } else {
                    modalContent.innerHTML = `<p class="text-red-600 dark:text-red-400">${escapeHtml(data.error || 'Error loading case details')}</p>`;
                }
            } catch (error) {
                console.error('Error loading case details:', error);
                modalContent.innerHTML = '<p class="text-red-600 dark:text-red-400">Error loading case details</p>';
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
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700 last:border-b-0 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">${fileName}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${createdAt} • ${formatFileSize(submission.file_size_bytes)}</p>
                            ${remarks}
                        </div>
                        <a href="${filePath}" target="_blank" rel="noopener" class="px-3 py-1.5 text-xs font-semibold rounded-md border border-blue-200 dark:border-blue-500/40 text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-colors">View</a>
                    </div>
                `;
            }).join('');
        }

        async function uploadCommunityServicePortfolio(caseId, caseSanctionId) {
            const fileInput = document.getElementById('portfolioFileInput');
            const remarksInput = document.getElementById('portfolioRemarksInput');
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
            payload.append('remarks', remarksInput ? remarksInput.value.trim() : '');
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

                if (remarksInput) remarksInput.value = '';
                fileInput.value = '';
                await viewCaseDetails(caseId);
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
</body>

</html>
