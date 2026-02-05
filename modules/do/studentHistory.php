<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    try {
        // Get students with filters
        if ($_POST['action'] === 'getStudents') {
            $search = $_POST['search'] ?? '';
            $grade = $_POST['grade'] ?? '';
            $status = $_POST['status'] ?? '';

            // Build SQL query with filters
            $sql = "SELECT 
                        s.student_id,
                        s.first_name,
                        s.last_name,
                        s.grade_year,
                        s.track_course,
                        s.total_offenses,
                        s.major_offenses,
                        s.minor_offenses,
                        s.last_incident_date,
                        s.status
                    FROM students s
                    WHERE 1=1";
            
            $params = [];

            // Search filter
            if (!empty($search)) {
                $sql .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            // Grade filter
            if (!empty($grade)) {
                $sql .= " AND s.grade_year = ?";
                $params[] = $grade;
            }

            // Status filter
            if (!empty($status)) {
                $sql .= " AND s.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY s.total_offenses DESC, s.last_name ASC";

            $students = fetchAll($sql, $params);

            // Format data for JavaScript
            $formattedStudents = array_map(function ($student) {
                return [
                    'id' => $student['student_id'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'studentId' => $student['student_id'],
                    'grade' => $student['grade_year'] ?? 'N/A',
                    'strand' => $student['track_course'] ?? 'N/A',
                    'incidents' => $student['total_offenses'] ?? 0,
                    'majorOffenses' => $student['major_offenses'] ?? 0,
                    'minorOffenses' => $student['minor_offenses'] ?? 0,
                    'lastIncident' => $student['last_incident_date'] ?? null,
                    'status' => $student['status'] ?? 'Good Standing'
                ];
            }, $students);

            echo json_encode(['success' => true, 'students' => $formattedStudents]);
            exit;
        }

        // Get student case history
        if ($_POST['action'] === 'getStudentHistory') {
            $studentId = $_POST['studentId'];
            
            $sql = "SELECT 
                        c.case_id,
                        c.case_type,
                        c.severity,
                        c.status,
                        c.date_reported,
                        c.description,
                        u.full_name as reported_by_name
                    FROM cases c
                    LEFT JOIN users u ON c.reported_by = u.user_id
                    WHERE c.student_id = ? AND c.is_archived = 0
                    ORDER BY c.date_reported DESC";
            
            $cases = fetchAll($sql, [$studentId]);
            
            echo json_encode(['success' => true, 'cases' => $cases]);
            exit;
        }

        // Add note to student
        if ($_POST['action'] === 'addNote') {
            $studentId = $_POST['studentId'];
            $note = $_POST['note'];
            $userId = $_SESSION['user_id'] ?? null;
            
            // Insert note as a case history entry
            $sql = "INSERT INTO case_history (case_id, changed_by, action, notes, timestamp)
                    SELECT TOP 1 case_id, ?, 'Note Added', ?, GETDATE()
                    FROM cases 
                    WHERE student_id = ?
                    ORDER BY date_reported DESC";
            
            executeQuery($sql, [$userId, $note, $studentId]);
            
            echo json_encode(['success' => true, 'message' => 'Note added successfully']);
            exit;
        }

    } catch (Exception $e) {
        error_log("Students AJAX Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$pageTitle = "Student List";
$adminName = $_SESSION['admin_name'] ?? 'Admin';
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

    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <?php include __DIR__ . '/../../includes/header.php'; ?>

            <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                <!-- Top Bar -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="relative flex-1 max-w-md">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="searchInput" placeholder="Search students by name or ID..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none"
                            oninput="filterStudents()">
                    </div>

                    <div class="ml-4 flex gap-3 items-center">
                        <!-- Filters Button -->
                        <button onclick="toggleFilters()"
                            class="px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filters
                        </button>

                        <!-- Grade Filter -->
                        <select id="gradeFilter" onchange="filterStudents()"
                            class="px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                            <option value="">All Levels</option>

                            <!-- Senior High -->
                            <option value="11">Grade 11</option>
                            <option value="12">Grade 12</option>

                            <!-- College -->
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>

                    </div>
                </div>

                <!-- Students Grid -->
                <div id="studentsGrid" class="space-y-4">
                    <!-- Populated by JavaScript -->
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex items-center justify-between">
                    <p id="paginationInfo" class="text-sm text-gray-600 dark:text-gray-400">Showing 1-6 of 248 students</p>
                    <div id="paginationButtons" class="flex gap-2">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Student History Modal -->
    <div id="historyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 p-6 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Student History</h3>
                <button onclick="closeHistoryModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="historyContent" class="p-6">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div id="noteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Add Note</h3>
                <button onclick="closeNoteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="addNoteForm">
                <textarea id="noteText" rows="4" placeholder="Enter note..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
                
                <div class="mt-4 flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        Save Note
                    </button>
                    <button type="button" onclick="closeNoteModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/students/data.js"></script>
    <script src="/PrototypeDO/assets/js/students/filters.js"></script>
    <script src="/PrototypeDO/assets/js/students/modals.js"></script>
    <script src="/PrototypeDO/assets/js/students/pagination.js"></script>
    <script src="/PrototypeDO/assets/js/students/main.js"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>

</html>