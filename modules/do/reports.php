<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = "Reports";

// Get the current user's name - multi-layered approach
$adminName = 'User'; // Default fallback

// Layer 1: Try session admin_name first
if (!empty($_SESSION['admin_name'])) {
    $adminName = $_SESSION['admin_name'];
}
// Layer 2: Try to get directly from database
else if (!empty($_SESSION['user_id'])) {
    try {
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'] ?? null;
        
        // Query directly from database
        if ($userRole === 'student') {
            $student = fetchOne("SELECT first_name, last_name FROM students WHERE user_id = ? LIMIT 1", [$userId]);
            if ($student && !empty($student['first_name'])) {
                $adminName = $student['first_name'] . ' ' . $student['last_name'];
            }
        } else {
            $user = fetchOne("SELECT full_name FROM users WHERE user_id = ? LIMIT 1", [$userId]);
            if ($user && !empty($user['full_name'])) {
                $nameStr = trim($user['full_name']);
                $parts = explode(' ', $nameStr);
                // Take first and last name only
                $adminName = count($parts) >= 2 ? $parts[0] . ' ' . end($parts) : ($parts[0] ?? 'User');
            }
        }
    } catch (Exception $e) {
        error_log("Exception getting admin name: " . $e->getMessage());
    }
}

// Ensure it's not empty
if (empty($adminName) || is_null($adminName)) {
    $adminName = 'User';
}


// ============================================================
//  CSV DOWNLOAD — pure PHP, no library needed
// ============================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $type   = $_GET['type']   ?? 'incident';
    $params = $_GET;

    // 🧾 Audit Log - Log the export
    auditReportGenerated(ucfirst($type) . ' Report (CSV Export)', [
        'export_type' => 'CSV',
        'report_type' => $type
    ]);

    $csvData = buildCSVData($type, $params);

    $filename = 'STI_' . ucfirst($type) . '_Report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache');

    $out = fopen('php://output', 'w');
    // BOM for Excel UTF-8 compatibility
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    foreach ($csvData as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// ============================================================
//  DATA FUNCTIONS  (shared by AJAX + CSV export)
// ============================================================
function fetchIncidentData($p) {
    $where = "WHERE c.is_archived = 0"; $params = [];
    if (!empty($p['caseId']))   { $where .= " AND c.case_id = ?";        $params[] = $p['caseId']; }
    if (!empty($p['dateFrom'])) { $where .= " AND c.date_reported >= ?"; $params[] = $p['dateFrom']; }
    if (!empty($p['dateTo']))   { $where .= " AND c.date_reported <= ?"; $params[] = $p['dateTo']; }
    if (!empty($p['severity'])) { $where .= " AND c.severity = ?";       $params[] = $p['severity']; }
    if (!empty($p['status']))   { $where .= " AND c.status = ?";         $params[] = $p['status']; }

    $cases = fetchAll("SELECT c.*,
        CONCAT(s.first_name,' ',s.last_name) AS student_name,
        s.student_id AS student_number, s.grade_year, s.track_course,
        ub.full_name AS reported_by_name, ua.full_name AS assigned_to_name
        FROM cases c
        LEFT JOIN students s  ON c.student_id  = s.student_id
        LEFT JOIN users   ub  ON c.reported_by  = ub.user_id
        LEFT JOIN users   ua  ON c.assigned_to  = ua.user_id
        $where ORDER BY c.date_reported DESC", $params);

    if (($p['reportType'] ?? '') === 'detailed') {
        foreach ($cases as &$case) {
            $case['sanctions'] = fetchAll(
                "SELECT cs.*, s.sanction_name, s.severity_level
                 FROM case_sanctions cs JOIN sanctions s ON cs.sanction_id = s.sanction_id
                 WHERE cs.case_id = ? ORDER BY cs.applied_date DESC",
                [$case['case_id']]
            );
        }
        unset($case);
    }

    $total    = count($cases);
    $pending  = count(array_filter($cases, fn($c) => $c['status'] === 'Pending'));
    $ongoing  = count(array_filter($cases, fn($c) => $c['status'] === 'On Going'));
    $resolved = count(array_filter($cases, fn($c) => $c['status'] === 'Resolved'));
    $major    = count(array_filter($cases, fn($c) => $c['severity'] === 'Major'));
    $minor    = count(array_filter($cases, fn($c) => $c['severity'] === 'Minor'));

    return ['success' => true, 'cases' => $cases,
        'stats'   => compact('total','pending','ongoing','resolved','major','minor'),
        'filters' => $p];
}

function fetchStatisticsData($p) {
    $year  = !empty($p['year']) ? (int)$p['year'] : null;
    $month = !empty($p['month']) ? (int)$p['month'] : null;
    $where = "WHERE c.is_archived = 0";
    $params = [];
    
    if ($year) {
        $where .= " AND YEAR(c.date_reported) = ?";
        $params[] = $year;
    }
    
    if ($month) {
        $where .= " AND MONTH(c.date_reported) = ?";
        $params[] = $month;
    }
    
    if (!empty($p['severity']))   { $where .= " AND c.severity = ?";   $params[] = $p['severity']; }
    if (!empty($p['gradeLevel'])) { $where .= " AND s.grade_year = ?"; $params[] = $p['gradeLevel']; }
    if (!empty($p['course']))     { $where .= " AND s.track_course = ?"; $params[] = $p['course']; }
    $joins = "FROM cases c LEFT JOIN students s ON c.student_id = s.student_id";

    // Build keyed monthly array so JS lookup is O(1) and correct
    $monthlyRaw = fetchAll("SELECT MONTH(c.date_reported) AS month, COUNT(*) AS count
        $joins $where GROUP BY MONTH(c.date_reported) ORDER BY MONTH(c.date_reported)", $params);
    $monthly = [];
    foreach ($monthlyRaw as $r) { $monthly[(int)$r['month']] = (int)$r['count']; }

    return ['success' => true,
        'monthly'         => $monthly,
        'byType'          => fetchAll("SELECT c.case_type, COUNT(*) AS count, SUM(CASE WHEN c.severity='Major' THEN 1 ELSE 0 END) AS major_count $joins $where GROUP BY c.case_type ORDER BY count DESC", $params),
        'byGrade'         => fetchAll("SELECT s.grade_year, COUNT(*) AS count $joins $where AND s.grade_year IS NOT NULL GROUP BY s.grade_year ORDER BY s.grade_year", $params),
        'byStatus'        => fetchAll("SELECT c.status, COUNT(*) AS count $joins $where GROUP BY c.status", $params),
        'totals'          => fetchOne("SELECT COUNT(*) AS total, SUM(CASE WHEN c.severity='Major' THEN 1 ELSE 0 END) AS major, SUM(CASE WHEN c.severity='Minor' THEN 1 ELSE 0 END) AS minor, SUM(CASE WHEN c.status='Resolved' THEN 1 ELSE 0 END) AS resolved $joins $where", $params),
        'repeatOffenders' => fetchAll("SELECT s.student_id, CONCAT(s.first_name,' ',s.last_name) AS name, s.grade_year, s.track_course, COUNT(c.case_id) AS offense_count $joins $where GROUP BY s.student_id, s.first_name, s.last_name, s.grade_year, s.track_course HAVING COUNT(c.case_id) > 1 ORDER BY offense_count DESC", $params),
        'filters'         => $p];
}

function fetchLostFoundData($p) {
    $where = "WHERE is_archived = 0"; $params = [];
    if (!empty($p['dateFrom'])) { $where .= " AND date_found >= ?"; $params[] = $p['dateFrom']; }
    if (!empty($p['dateTo']))   { $where .= " AND date_found <= ?"; $params[] = $p['dateTo']; }
    if (!empty($p['status']))   { $where .= " AND status = ?";      $params[] = $p['status']; }
    if (!empty($p['category'])) { $where .= " AND category = ?";    $params[] = $p['category']; }

    return ['success' => true,
        'items'      => fetchAll("SELECT * FROM lost_found_items $where ORDER BY date_found DESC", $params),
        'byCategory' => fetchAll("SELECT category, COUNT(*) AS total, SUM(CASE WHEN status='Claimed' THEN 1 ELSE 0 END) AS claimed, SUM(CASE WHEN status='Unclaimed' THEN 1 ELSE 0 END) AS unclaimed FROM lost_found_items $where GROUP BY category ORDER BY total DESC", $params),
        'totals'     => fetchOne("SELECT COUNT(*) AS total, SUM(CASE WHEN status='Claimed' THEN 1 ELSE 0 END) AS claimed, SUM(CASE WHEN status='Unclaimed' THEN 1 ELSE 0 END) AS unclaimed FROM lost_found_items $where", $params),
        'filters'    => $p];
}

function fetchStudentData($p) {
    $where = "WHERE 1=1"; $params = [];
    if (!empty($p['studentId']))  { $where .= " AND s.student_id = ?"; $params[] = $p['studentId']; }
    if (!empty($p['gradeLevel'])) { $where .= " AND s.grade_year = ?"; $params[] = $p['gradeLevel']; }
    if (!empty($p['status']))     { $where .= " AND s.status = ?";     $params[] = $p['status']; }

    return ['success' => true,
        'students'   => fetchAll("SELECT s.*,
            (SELECT COUNT(*) FROM cases c WHERE c.student_id=s.student_id AND c.is_archived=0) AS active_cases,
            (SELECT COUNT(*) FROM cases c WHERE c.student_id=s.student_id AND c.severity='Major' AND c.is_archived=0) AS major_count,
            (SELECT COUNT(*) FROM cases c WHERE c.student_id=s.student_id AND c.severity='Minor' AND c.is_archived=0) AS minor_count
            FROM students s $where ORDER BY s.total_offenses DESC, s.last_name", $params),
        'statusDist' => fetchAll("SELECT status, COUNT(*) AS count FROM students GROUP BY status"),
        'filters'    => $p];
}

// ============================================================
//  CSV BUILDER
// ============================================================
function buildCSVData($type, $params) {
    // Use the admin name from session, which was already computed at login
    $exportedBy = $_SESSION['admin_name'] ?? 'User';
    
    $rows  = [];
    $today = date('F d, Y H:i');

    switch ($type) {
        case 'incident':
            $data   = fetchIncidentData($params);
            $rows[] = ['STI Discipline Office – Incident Report'];
            $rows[] = ['Generated:', $today];
            $rows[] = ['Exported By:', $exportedBy];
            $rows[] = [];
            $rows[] = ['Case ID','Student','Student ID','Grade/Year','Track','Case Type','Severity','Status','Date Reported','Reported By','Assigned To','Description'];
            foreach ($data['cases'] as $c) {
                $rows[] = [
                    $c['case_id'], $c['student_name']??'', $c['student_number']??'',
                    $c['grade_year']??'', $c['track_course']??'', $c['case_type'],
                    $c['severity'], $c['status'],
                    $c['date_reported'] ? date('Y-m-d', strtotime($c['date_reported'])) : '',
                    $c['reported_by_name']??'', $c['assigned_to_name']??'', $c['description']??'',
                ];
            }
            break;

        case 'statistics':
            $data   = fetchStatisticsData($params);
            $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            $rows[] = ['STI Discipline Office – Case Statistics ' . ($params['year'] ?? date('Y'))];
            $rows[] = ['Generated:', $today];
            $rows[] = ['Exported By:', $exportedBy];
            $rows[] = [];
            $rows[] = ['TOTALS'];
            $rows[] = ['Total','Major','Minor','Resolved'];
            $t = $data['totals'];
            $rows[] = [$t['total']??0, $t['major']??0, $t['minor']??0, $t['resolved']??0];
            $rows[] = [];
            $rows[] = ['MONTHLY BREAKDOWN'];
            $rows[] = ['Month','Total Cases'];
            foreach ($months as $i => $m) { $rows[] = [$m, $data['monthly'][$i+1] ?? 0]; }
            $rows[] = [];
            $rows[] = ['BY CASE TYPE'];
            $rows[] = ['Case Type','Total','Major'];
            foreach ($data['byType'] as $r) { $rows[] = [$r['case_type'], $r['count'], $r['major_count']]; }
            $rows[] = [];
            $rows[] = ['REPEAT OFFENDERS'];
            $rows[] = ['Student ID','Name','Grade/Year','Track','Offense Count'];
            foreach ($data['repeatOffenders'] as $r) { $rows[] = [$r['student_id'],$r['name'],$r['grade_year'],$r['track_course'],$r['offense_count']]; }
            break;

        case 'lostfound':
            $data   = fetchLostFoundData($params);
            $rows[] = ['STI Discipline Office – Lost & Found Report'];
            $rows[] = ['Generated:', $today];
            $rows[] = ['Exported By:', $exportedBy];
            $rows[] = [];
            $rows[] = ['Item ID','Name','Category','Location Found','Date Found','Status','Finder','Claimer','Date Claimed','Description'];
            foreach ($data['items'] as $i) {
                $rows[] = [
                    $i['item_id'], $i['item_name'], $i['category'], $i['found_location'],
                    $i['date_found'] ? date('Y-m-d', strtotime($i['date_found'])) : '',
                    $i['status'], $i['finder_name']??'', $i['claimer_name']??'',
                    $i['date_claimed'] ? date('Y-m-d', strtotime($i['date_claimed'])) : '',
                    $i['description']??'',
                ];
            }
            break;

        case 'student':
            $data   = fetchStudentData($params);
            $rows[] = ['STI Discipline Office – Student Behavior Report'];
            $rows[] = ['Generated:', $today];
            $rows[] = ['Exported By:', $exportedBy];
            $rows[] = [];
            $rows[] = ['Student ID','First Name','Last Name','Grade/Year','Track/Course','Status','Total Cases','Major','Minor','Last Incident'];
            foreach ($data['students'] as $s) {
                $rows[] = [
                    $s['student_id'], $s['first_name'], $s['last_name'],
                    $s['grade_year']??'', $s['track_course']??'', $s['status']??'Good Standing',
                    $s['active_cases']??0, $s['major_count']??0, $s['minor_count']??0,
                    $s['last_incident_date'] ? date('Y-m-d', strtotime($s['last_incident_date'])) : '',
                ];
            }
            break;


    }
    return $rows;
}

// ============================================================
//  AJAX HANDLERS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    // Mark password warning as shown in this login session
    if ($action === 'markPasswordWarningShown') {
        $_SESSION['password_warning_modal_shown'] = true;
        echo json_encode(['success' => true, 'message' => 'Password warning marked as shown']);
        exit;
    }
    
    try {
        if ($action === 'generateIncidentReport') {
            $data = fetchIncidentData($_POST);
            // 🧾 Audit Log
            auditReportGenerated('Incident Report', [
                'date_from' => $_POST['dateFrom'] ?? '',
                'date_to' => $_POST['dateTo'] ?? '',
                'severity' => $_POST['severity'] ?? ''
            ]);
            echo json_encode($data);
            exit;
        }
        if ($action === 'generateStatisticsReport') {
            $data = fetchStatisticsData($_POST);
            // 🧾 Audit Log
            auditReportGenerated('Statistics Report', [
                'groupBy' => $_POST['groupBy'] ?? '',
                'filters' => 'Applied'
            ]);
            echo json_encode($data);
            exit;
        }
        if ($action === 'generateLostFoundReport') {
            $data = fetchLostFoundData($_POST);
            // 🧾 Audit Log
            auditReportGenerated('Lost & Found Report', [
                'status' => $_POST['status'] ?? 'All',
                'category' => $_POST['category'] ?? 'All'
            ]);
            echo json_encode($data);
            exit;
        }
        if ($action === 'generateStudentReport') {
            $data = fetchStudentData($_POST);
            // 🧾 Audit Log
            auditReportGenerated('Student Report', [
                'gradeLevel' => $_POST['gradeLevel'] ?? '',
                'course' => $_POST['course'] ?? ''
            ]);
            echo json_encode($data);
            exit;
        }

        if ($action === 'getGradeLevels') {
            echo json_encode(['success'=>true,'data'=>fetchAll("SELECT DISTINCT grade_year FROM students WHERE grade_year IS NOT NULL ORDER BY grade_year")]);
            exit;
        }
        if ($action === 'getAvailableCourses') {
            echo json_encode(['success'=>true,'data'=>fetchAll("SELECT DISTINCT track_course FROM students WHERE track_course IS NOT NULL ORDER BY track_course")]);
            exit;
        }
        if ($action === 'getCoursesByGradeLevel') {
            $gradeLevel = $_POST['gradeLevel'] ?? '';
            if (empty($gradeLevel)) {
                echo json_encode(['success'=>true,'data'=>fetchAll("SELECT DISTINCT track_course FROM students WHERE track_course IS NOT NULL ORDER BY track_course")]);
            } else {
                echo json_encode(['success'=>true,'data'=>fetchAll("SELECT DISTINCT track_course FROM students WHERE track_course IS NOT NULL AND grade_year = ? ORDER BY track_course", [$gradeLevel])]);
            }
            exit;
        }
        if ($action === 'getAvailableYears') {
            echo json_encode(['success'=>true,'data'=>fetchAll("SELECT DISTINCT YEAR(date_reported) AS year FROM cases WHERE is_archived=0 ORDER BY year DESC")]);
            exit;
        }
        if ($action === 'getCategories') {
            echo json_encode(['success'=>true,'data'=>fetchAll("SELECT DISTINCT category FROM lost_found_items WHERE category IS NOT NULL ORDER BY category")]);
            exit;
        }
        if ($action === 'getCaseList') {
            echo json_encode(['success'=>true,'data'=>fetchAll("SELECT case_id FROM cases WHERE is_archived=0 ORDER BY case_id DESC")]);
            exit;
        }
        if ($action === 'getStudentList') {
            echo json_encode(['success'=>true,'data'=>fetchAll("SELECT student_id, CONCAT(first_name,' ',last_name) AS name FROM students ORDER BY last_name")]);
            exit;
        }
        echo json_encode(['success'=>false,'error'=>'Unknown action']);
        exit;
    } catch (Exception $e) {
        error_log("Reports AJAX Error: " . $e->getMessage());
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office – Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
    </script>
    <meta name="data-admin-name" content="<?= htmlspecialchars($adminName) ?>">
    <script>
        // Set ADMIN_NAME directly from PHP to avoid DOM timing issues
        window.ADMIN_NAME = '<?= htmlspecialchars(addslashes($adminName)) ?>';
    </script>
    <style>
        /* Essential styles for reports - tab active state and print */
        .tab-active { background: #2563eb !important; color: white !important; }
        .input-field {
            padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
            background: white; color: #111827; font-size: 0.875rem; outline: none; width: 100%;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .input-field:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
        .dark .input-field { background: #1e293b; border-color: #475569; color: #f1f5f9; }
        #print-root { display: none; }
        .preview-wrap { font-family: Arial, sans-serif; color: #111827; }
        .dark .preview-wrap { color: #f1f5f9; }
        .tab-panel > div > div:last-child { height: calc(100vh - 280px); overflow-y: auto; overflow-x: hidden; }

        /* Print styles */
        @media print {
            body > * { display: none !important; }
            #print-root { display: block !important; font-family: Arial, sans-serif; font-size: 9pt; color: #111827; }
            .overflow-x-auto { overflow: visible !important; }
            table { page-break-inside: auto; width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb; margin-bottom: 0.5rem; font-size: 8pt; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            th { background: #1e3a8a !important; color: white !important; padding: 4px 6px; font-size: 8pt; font-weight: 600; text-align: left; white-space: normal; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            td { color: #111827 !important; padding: 4px 6px; font-size: 8pt; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
            tr:nth-child(even) td { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page-break-inside { page-break-inside: avoid; }
            h1, h2, h3 { page-break-after: avoid; margin: 0.25rem 0; }
            @page { margin: 15mm 10mm; size: A4; }
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">

    <!-- Hidden print root — only shown at @media print -->
    <div id="print-root" aria-hidden="true"></div>

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <?php include __DIR__ . '/../../includes/header.php'; ?>

            <main class="p-8 pt-28 min-h-screen">

                <!-- Tabs -->
                <div class="flex flex-wrap gap-2 mb-8">
                    <?php
                    $tabs = [
                        ['id'=>'incident',   'label'=>'Incident Reports'],
                        ['id'=>'statistics', 'label'=>'Case Statistics'],
                        ['id'=>'lostfound',  'label'=>'Lost & Found'],
                        ['id'=>'student',    'label'=>'Student Behavior'],
                    ];
                    foreach ($tabs as $i => $tab): ?>
                        <button id="tab-<?= $tab['id'] ?>"
                            onclick="switchTab('<?= $tab['id'] ?>')"
                            class="px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors <?= $i===0?'tab-active':'' ?>">
                            <?= $tab['label'] ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Tab panels -->
                <?php
                $panels = [
                    'incident' => [
                        'type'   => 'incident',
                        'fields' => [
                            ['id'=>'inc-reportType','label'=>'Report Type','type'=>'select',
                             'opts'=>['summary'=>'Summary','detailed'=>'Detailed (with Sanctions)']],
                            ['id'=>'inc-caseId',   'label'=>'Case ID (Optional)', 'type'=>'search','placeholder'=>'Search by Case ID (C-2026000)'],
                            ['id'=>'inc-dateFrom', 'label'=>'Date From', 'type'=>'date'],
                            ['id'=>'inc-dateTo',   'label'=>'Date To',   'type'=>'date'],
                            ['id'=>'inc-severity', 'label'=>'Severity',  'type'=>'select','opts'=>[''=>'All','Major'=>'Major','Minor'=>'Minor']],
                            ['id'=>'inc-status',   'label'=>'Status',    'type'=>'select','opts'=>[''=>'All','Pending'=>'Pending','On Going'=>'On Going','Resolved'=>'Resolved']],
                        ],
                    ],
                    'statistics' => [
                        'type'   => 'statistics',
                        'fields' => [
                            ['id'=>'stat-year',       'label'=>'Year',        'type'=>'ajax','action'=>'getAvailableYears','vk'=>'year'],
                            ['id'=>'stat-view',       'label'=>'View',        'type'=>'select','opts'=>[''=>'Yearly Overview','monthly'=>'Monthly Breakdown']],
                            ['id'=>'stat-month',      'label'=>'Month (Optional)','type'=>'select','opts'=>
                                [''=>'All Months','1'=>'January','2'=>'February','3'=>'March','4'=>'April','5'=>'May','6'=>'June',
                                '7'=>'July','8'=>'August','9'=>'September','10'=>'October','11'=>'November','12'=>'December']],
                            ['id'=>'stat-severity',   'label'=>'Severity',    'type'=>'select','opts'=>[''=>'All','Major'=>'Major','Minor'=>'Minor']],
                            ['id'=>'stat-gradeLevel', 'label'=>'Grade Level', 'type'=>'ajax','action'=>'getGradeLevels','vk'=>'grade_year','all'=>'All Levels'],
                            ['id'=>'stat-course',     'label'=>'Course',      'type'=>'ajax','action'=>'getAvailableCourses','vk'=>'track_course','all'=>'All Courses'],
                        ],
                    ],
                    'lostfound' => [
                        'type'   => 'lostfound',
                        'fields' => [
                            ['id'=>'lf-dateFrom','label'=>'Date From','type'=>'date'],
                            ['id'=>'lf-dateTo',  'label'=>'Date To',  'type'=>'date'],
                            ['id'=>'lf-status',  'label'=>'Status',   'type'=>'select','opts'=>[''=>'All','Unclaimed'=>'Unclaimed','Claimed'=>'Claimed']],
                            ['id'=>'lf-category','label'=>'Category', 'type'=>'ajax','action'=>'getCategories','vk'=>'category','all'=>'All Categories'],
                        ],
                    ],
                    'student' => [
                        'type'   => 'student',
                        'fields' => [
                            ['id'=>'stu-studentId', 'label'=>'Student Name/ID (Optional)', 'type'=>'search','placeholder'=>'Search by Student ID or Name'],
                            ['id'=>'stu-gradeLevel','label'=>'Grade / Year', 'type'=>'ajax','action'=>'getGradeLevels','vk'=>'grade_year','all'=>'All Levels'],
                            ['id'=>'stu-status',    'label'=>'Standing',     'type'=>'select','opts'=>[''=>'All','Good Standing'=>'Good Standing','On Watch'=>'On Watch','On Probation'=>'On Probation']],
                        ],
                    ],
                ];
                foreach ($panels as $panelId => $cfg):
                    $isFirst = $panelId === 'incident';
                ?>
                <div id="tab-panel-<?= $panelId ?>" class="tab-panel <?= $isFirst?'':'hidden' ?>">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        <!-- Filters -->
                        <div class="bg-white dark:bg-[#111827] rounded-xl border border-gray-200 dark:border-slate-700 p-6 h-fit sticky top-28">
                            <h3 class="text-sm font-semibold mb-5 flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Filters
                            </h3>
                            <div class="space-y-4">
                                <?php foreach ($cfg['fields'] as $f): ?>
                                    <div>
                                        <label for="<?= $f['id'] ?>" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                                            <?= $f['label'] ?>
                                        </label>
                                        <?php if ($f['type'] === 'date'): ?>
                                            <input type="date" id="<?= $f['id'] ?>" class="input-field">
                                        <?php elseif ($f['type'] === 'search'): ?>
                                            <input type="text" id="<?= $f['id'] ?>" class="input-field" placeholder="<?= $f['placeholder'] ?? '' ?>">
                                        <?php elseif ($f['type'] === 'select'): ?>
                                            <select id="<?= $f['id'] ?>" class="input-field">
                                                <?php foreach ($f['opts'] as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
                                            </select>
                                        <?php elseif ($f['type'] === 'year'): ?>
                                            <select id="<?= $f['id'] ?>" class="input-field">
                                                <?php for ($y=date('Y');$y>=2020;$y--): ?>
                                                    <option value="<?= $y ?>" <?= $y==date('Y')?'selected':'' ?>><?= $y ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        <?php else: ?>
                                            <select id="<?= $f['id'] ?>" class="input-field"
                                                data-ajax="<?= $f['action'] ?>"
                                                data-vk="<?= $f['vk'] ?>"
                                                data-lk="<?= $f['lk'] ?? $f['vk'] ?>"
                                                data-all="<?= $f['all'] ?? 'All' ?>">
                                                <option value=""><?= $f['all'] ?? 'All' ?></option>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <div class="pt-2 space-y-2">
                                    <button onclick="generateReport('<?= $cfg['type'] ?>')"
                                        class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95
                                               text-white rounded-lg font-medium transition-all
                                               flex items-center justify-center gap-2 text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Generate
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="lg:col-span-2">
                            <div id="<?= $panelId ?>-preview"
                                 class="bg-white dark:bg-[#111827] rounded-xl border border-gray-200 dark:border-slate-700 h-[900px] overflow-y-auto flex flex-col">

                                <!-- empty state -->
                                <div id="<?= $panelId ?>-empty" class="flex items-center justify-center min-h-[420px]">
                                    <div class="text-center text-gray-400 dark:text-gray-600 p-12">
                                        <svg class="w-14 h-14 mx-auto mb-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="font-medium">Set filters &amp; click Generate</p>
                                        <p class="text-sm mt-1 opacity-70">Report preview appears here</p>
                                    </div>
                                </div>

                                <!-- action bar -->
                                <div id="<?= $panelId ?>-actions"
                                     class="hidden items-center justify-between px-5 py-3
                                            border-b border-gray-200 dark:border-slate-700
                                            bg-gray-50 dark:bg-slate-800/50 rounded-t-xl
                                            sticky top-0 z-10">
                                    <span id="<?= $panelId ?>-count" class="text-sm text-gray-500 dark:text-gray-400"></span>
                                    <div class="flex gap-2">
                                        <button onclick="exportCSV('<?= $cfg['type'] ?>')"
                                            class="flex items-center gap-1.5 px-3 py-1.5 text-sm border
                                                   border-gray-300 dark:border-slate-600 rounded-lg
                                                   hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors font-medium">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Export CSV
                                        </button>
                                        <button onclick="printReport('<?= $cfg['type'] ?>')"
                                            class="flex items-center gap-1.5 px-3 py-1.5 text-sm
                                                   bg-blue-600 hover:bg-blue-700 text-white rounded-lg
                                                   transition-colors font-medium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                            Print / Save PDF
                                        </button>
                                    </div>
                                </div>

                                <!-- report content -->
                                <div id="<?= $panelId ?>-content" class="p-5 overflow-x-auto"></div>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>

            </main>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/reports/main.js"></script>
    <script src="/PrototypeDO/assets/js/reports/filters.js"></script>
    <script src="/PrototypeDO/assets/js/reports/modals.js"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>