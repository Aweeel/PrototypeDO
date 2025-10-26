<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    try {
        // Get all students for dropdown
        if ($_POST['action'] === 'getStudents') {
            $students = getAllStudents();
            echo json_encode(['success' => true, 'students' => $students]);
            exit;
        }

        // Get cases with filters
        if ($_POST['action'] === 'getCases') {
            $filters = [
                'search' => $_POST['search'] ?? '',
                'type' => $_POST['type'] ?? '',
                'status' => $_POST['status'] ?? '',
                'archived' => isset($_POST['archived']) && $_POST['archived'] === 'true' ? true : false
            ];

            $cases = getAllCases($filters);

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
                    'severity' => $case['severity'] ?? 'Minor'
                ];
            }, $cases);

            echo json_encode(['success' => true, 'cases' => $formattedCases]);
            exit;
        }

        // Create new case
        if ($_POST['action'] === 'createCase') {
            $data = [
                'student_number' => $_POST['studentNumber'],
                'student_name' => $_POST['studentName'],
                'case_type' => $_POST['type'],
                'severity' => $_POST['severity'] ?? 'Minor',
                'status' => $_POST['status'] ?? 'Pending',
                'assigned_to' => $_SESSION['user_id'] ?? null,
                'reported_by' => $_SESSION['user_id'] ?? null,
                'description' => $_POST['description'],
                'notes' => $_POST['notes'] ?? ''
            ];

            $newCaseId = createCase($data);

            // Update student offense count
            updateStudentOffenseCount($data['student_number']);

            echo json_encode(['success' => true, 'caseId' => $newCaseId, 'message' => 'Case created successfully']);
            exit;
        }

        // Update existing case
        if ($_POST['action'] === 'updateCase') {
            $caseId = $_POST['caseId'];
            $data = [
                'case_type' => $_POST['type'],
                'severity' => $_POST['severity'] ?? 'Minor',
                'status' => $_POST['status'],
                'assigned_to' => $_SESSION['user_id'] ?? null,
                'description' => $_POST['description'],
                'notes' => $_POST['notes'] ?? ''
            ];

            updateCase($caseId, $data);

            // Get student_id from case
            $case = getCaseById($caseId);
            if ($case) {
                updateStudentOffenseCount($case['student_id']);
            }

            echo json_encode(['success' => true, 'message' => 'Case updated successfully']);
            exit;
        }

        // Archive case
        if ($_POST['action'] === 'archiveCase') {
            $caseId = $_POST['caseId'];
            archiveCase($caseId);

            echo json_encode(['success' => true, 'message' => 'Case archived successfully']);
            exit;
        }

        // Unarchive case
        if ($_POST['action'] === 'unarchiveCase') {
            $caseId = $_POST['caseId'];
            $sql = "UPDATE cases SET is_archived = 0, archived_at = NULL WHERE case_id = ?";
            executeQuery($sql, [$caseId]);

            logCaseHistory($caseId, $_SESSION['user_id'] ?? null, 'Unarchived', null, 'Case unarchived');

            echo json_encode(['success' => true, 'message' => 'Case restored successfully']);
            exit;
        }

    } catch (Exception $e) {
        error_log("Cases AJAX Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
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
</head>

<body
    class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <?php
            $pageTitle = "Cases Management";
            $adminName = $_SESSION['admin_name'] ?? 'Admin';
            include __DIR__ . '/../../includes/header.php';
            ?>

            <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                <!-- Top Bar -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="relative flex-1 max-w-md">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="searchInput" placeholder="Search cases..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none"
                            oninput="filterCases()">
                    </div>

                    <button onclick="addCase()"
                        class="ml-4 px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Case
                    </button>
                </div>

                <!-- Tabs and Filters -->
                <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
                    <div class="flex gap-2">
                        <button id="currentTab" onclick="switchTab('current')"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium">Current</button>
                        <button id="archivedTab" onclick="switchTab('archived')"
                            class="px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors">Archived</button>
                    </div>

                    <div class="flex gap-3 items-center flex-wrap">
                        <button onclick="toggleFilters()"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filters
                        </button>

                        <div id="filterSection" class="flex gap-3">
                            <select id="typeFilter" onchange="filterCases()"
                                class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                                <option value="">All Types</option>
                                <option value="Tardiness">Tardiness</option>
                                <option value="Dress Code">Dress Code</option>
                                <option value="Classroom Disruption">Classroom Disruption</option>
                                <option value="Academic Dishonesty">Academic Dishonesty</option>
                                <option value="Attendance">Attendance</option>
                            </select>

                            <select id="statusFilter" onchange="filterCases()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Under Review">Under Review</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Escalated">Escalated</option>
                            </select>

                            <select id="sortFilter" onchange="sortCases()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                                <option value="newest">Sort: Newest</option>
                                <option value="oldest">Sort: Oldest</option>
                                <option value="status">Sort: Status</option>
                            </select>
                        </div>
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
                                    Assigned To</th>
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
                        <!-- Populated by JS -->
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Load Scripts in correct order - NO INLINE SCRIPTS -->
    <script src="/PrototypeDO/assets/js/cases/data.js"></script>
    <script src="/PrototypeDO/assets/js/cases/filters.js"></script>
    <script src="/PrototypeDO/assets/js/cases/modals.js"></script>
    <script src="/PrototypeDO/assets/js/cases/pagination.js"></script>
    <script src="/PrototypeDO/assets/js/cases/main.js"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>

</html>