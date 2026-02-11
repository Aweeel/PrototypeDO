<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle CSV Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
    header('Content-Type: application/json');

    try {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error occurred']);
            exit;
        }

        $file = $_FILES['csv_file'];
        $fileName = $file['tmp_name'];

        // Validate file type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($fileExtension !== 'csv') {
            echo json_encode(['success' => false, 'error' => 'Only CSV files are allowed']);
            exit;
        }

        // Open and parse CSV
        $handle = fopen($fileName, 'r');
        if (!$handle) {
            echo json_encode(['success' => false, 'error' => 'Could not open the CSV file']);
            exit;
        }

        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            echo json_encode(['success' => false, 'error' => 'CSV file is empty']);
            exit;
        }

        // Expected columns: student_id, first_name, last_name, middle_name, grade_year, track_course, section, student_type, guardian_name, guardian_contact
        $imported = 0;
        $errors = [];
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map CSV columns to array
            $data = array_combine($header, $row);

            // Validate required fields
            if (empty($data['student_id']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['grade_year'])) {
                $errors[] = "Row skipped: Missing required fields (student_id, first_name, last_name, or grade_year)";
                $skipped++;
                continue;
            }

            try {
                // Check if student already exists
                $checkSql = "SELECT student_id FROM students WHERE student_id = ?";
                $existing = fetchOne($checkSql, [$data['student_id']]);

                if ($existing) {
                    // Update existing student
                    $updateSql = "UPDATE students SET 
                                    first_name = ?,
                                    last_name = ?,
                                    middle_name = ?,
                                    grade_year = ?,
                                    track_course = ?,
                                    section = ?,
                                    student_type = ?,
                                    guardian_name = ?,
                                    guardian_contact = ?,
                                    updated_at = GETDATE()
                                  WHERE student_id = ?";
                    
                    executeQuery($updateSql, [
                        $data['first_name'],
                        $data['last_name'],
                        $data['middle_name'] ?? null,
                        $data['grade_year'],
                        $data['track_course'] ?? null,
                        $data['section'] ?? null,
                        $data['student_type'] ?? null,
                        $data['guardian_name'] ?? null,
                        $data['guardian_contact'] ?? null,
                        $data['student_id']
                    ]);
                } else {
                    // Insert new student
                    $insertSql = "INSERT INTO students (student_id, first_name, last_name, middle_name, grade_year, track_course, section, student_type, guardian_name, guardian_contact)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    executeQuery($insertSql, [
                        $data['student_id'],
                        $data['first_name'],
                        $data['last_name'],
                        $data['middle_name'] ?? null,
                        $data['grade_year'],
                        $data['track_course'] ?? null,
                        $data['section'] ?? null,
                        $data['student_type'] ?? null,
                        $data['guardian_name'] ?? null,
                        $data['guardian_contact'] ?? null
                    ]);
                }

                $imported++;
            } catch (Exception $e) {
                $errors[] = "Error importing student " . $data['student_id'] . ": " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ]);
        exit;

    } catch (Exception $e) {
        error_log("CSV Import Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

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
                        <!-- Import CSV Button -->
                        <button onclick="openImportModal()"
                            class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Import CSV
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

    <!-- Import CSV Modal -->
    <div id="importModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Import Students from CSV</h3>
                <button onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <h4 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">CSV Format Requirements:</h4>
                <p class="text-sm text-blue-800 dark:text-blue-400 mb-2">The CSV file must have the following columns:</p>
                <code class="text-xs bg-white dark:bg-slate-900 px-2 py-1 rounded block overflow-x-auto">
                    student_id, first_name, last_name, middle_name, grade_year, track_course, section, student_type, guardian_name, guardian_contact
                </code>
                <p class="text-xs text-blue-700 dark:text-blue-400 mt-2">* Required fields: student_id, first_name, last_name, grade_year</p>
            </div>

            <form id="importCsvForm" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select CSV File
                    </label>
                    <input type="file" id="csvFile" name="csv_file" accept=".csv" required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/20 dark:file:text-blue-400">
                </div>

                <div id="importProgress" class="hidden mb-4">
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Importing students...</span>
                    </div>
                </div>

                <div id="importResult" class="hidden mb-4"></div>

                <div class="flex gap-3">
                    <button type="submit" id="importBtn" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        Upload and Import
                    </button>
                    <button type="button" onclick="closeImportModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/students/filters.js?v=<?php echo time(); ?>"></script>
    <script src="/PrototypeDO/assets/js/students/modals.js?v=<?php echo time(); ?>"></script>
    <script src="/PrototypeDO/assets/js/students/pagination.js?v=<?php echo time(); ?>"></script>
    <script src="/PrototypeDO/assets/js/students/main.js?v=<?php echo time(); ?>"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>

</html>