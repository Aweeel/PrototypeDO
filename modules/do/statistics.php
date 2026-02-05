<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle AJAX requests for chart data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    try {
        // Get cases by type
        if ($_POST['action'] === 'getCasesByType') {
            $sql = "SELECT case_type, COUNT(*) as count
                    FROM cases
                    WHERE is_archived = 0
                    GROUP BY case_type
                    ORDER BY count DESC";
            
            $data = fetchAll($sql);
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        // Get cases by grade level
        if ($_POST['action'] === 'getCasesByGrade') {
            $sql = "SELECT s.grade_year, COUNT(c.case_id) as count
                    FROM cases c
                    JOIN students s ON c.student_id = s.student_id
                    WHERE c.is_archived = 0
                    GROUP BY s.grade_year
                    ORDER BY s.grade_year";
            
            $data = fetchAll($sql);
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        // Get monthly trends
        if ($_POST['action'] === 'getMonthlyTrends') {
            $year = $_POST['year'] ?? date('Y');
            
            $sql = "SELECT 
                        MONTH(date_reported) as month,
                        COUNT(*) as count
                    FROM cases
                    WHERE YEAR(date_reported) = ? AND is_archived = 0
                    GROUP BY MONTH(date_reported)
                    ORDER BY MONTH(date_reported)";
            
            $data = fetchAll($sql, [$year]);
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        // Get statistics overview
        if ($_POST['action'] === 'getStatistics') {
            $dateRange = $_POST['dateRange'] ?? 'all';
            
            // Build date filter
            $dateFilter = '';
            $params = [];
            
            switch ($dateRange) {
                case 'this_month':
                    $dateFilter = "AND MONTH(date_reported) = MONTH(GETDATE()) AND YEAR(date_reported) = YEAR(GETDATE())";
                    break;
                case 'last_month':
                    $dateFilter = "AND MONTH(date_reported) = MONTH(DATEADD(month, -1, GETDATE())) AND YEAR(date_reported) = YEAR(DATEADD(month, -1, GETDATE()))";
                    break;
                case 'this_year':
                    $dateFilter = "AND YEAR(date_reported) = YEAR(GETDATE())";
                    break;
            }
            
            $stats = [
                'totalCases' => fetchValue("SELECT COUNT(*) FROM cases WHERE is_archived = 0 $dateFilter", $params),
                'resolvedCases' => fetchValue("SELECT COUNT(*) FROM cases WHERE status = 'Resolved' AND is_archived = 0 $dateFilter", $params),
                'repeatOffenders' => fetchValue("SELECT COUNT(DISTINCT student_id) FROM cases WHERE student_id IN (SELECT student_id FROM cases WHERE is_archived = 0 GROUP BY student_id HAVING COUNT(*) > 1) $dateFilter", $params),
                'lostItemsClaimed' => fetchValue("SELECT CAST(COUNT(CASE WHEN status = 'Claimed' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) AS INT) FROM lost_found_items WHERE is_archived = 0", [])
            ];
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            exit;
        }

    } catch (Exception $e) {
        error_log("Statistics AJAX Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$pageTitle = "Statistics";
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office - <?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <?php include __DIR__ . '/../../includes/header.php'; ?>

            <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                <!-- Top Controls -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex gap-3">
                        <select id="dateRangeFilter" onchange="updateStatistics()" 
                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                            <option value="all">All Time</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_year" selected>This Year</option>
                        </select>

                        <select id="viewFilter" 
                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                            <option value="all">All Strands/Courses</option>
                        </select>
                    </div>

                    <button onclick="exportStatistics()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Cases -->
                    <div class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Cases</p>
                                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="totalCases">152</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <span class="text-red-600 dark:text-red-400">-17</span> This Month
                                </p>
                            </div>
                            <div class="bg-blue-100 dark:bg-[#1E3A8A] p-3 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Resolved Cases -->
                    <div class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Resolved Cases</p>
                                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="resolvedCases">74</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <span class="text-red-600 dark:text-red-400">-3</span> This Month
                                </p>
                            </div>
                            <div class="bg-green-100 dark:bg-[#14532D] p-3 rounded-lg">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Repeat Offenders -->
                    <div class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Repeat Offenders</p>
                                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="repeatOffenders">18</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <span class="text-green-600 dark:text-green-400">+3</span> This Month
                                </p>
                            </div>
                            <div class="bg-orange-100 dark:bg-[#7C2D12] p-3 rounded-lg">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Lost Items Claimed -->
                    <div class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Lost Items Claimed</p>
                                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="lostItemsClaimed">74<span class="text-lg">%</span></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <span class="text-green-600 dark:text-green-400">+8%</span> This Month
                                </p>
                            </div>
                            <div class="bg-purple-100 dark:bg-[#581C87] p-3 rounded-lg">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Cases by Type -->
                    <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Cases by Type</h3>
                            <select class="text-sm px-3 py-1.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                                <option>Last 30 Days</option>
                                <option>Last 3 Months</option>
                                <option>Last 6 Months</option>
                                <option>This Year</option>
                            </select>
                        </div>
                        <div class="h-80">
                            <canvas id="casesByTypeChart"></canvas>
                        </div>
                    </div>

                    <!-- Cases by Grade Level -->
                    <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Cases by Grade Level</h3>
                            <select class="text-sm px-3 py-1.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                                <option>All Grades</option>
                                <option>Strand/Course</option>
                            </select>
                        </div>
                        <div class="h-80">
                            <canvas id="casesByGradeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Monthly Trends</h3>
                        <select id="yearFilter" onchange="updateMonthlyTrends()" class="text-sm px-3 py-1.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                            <option value="2025">This Year</option>
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                        </select>
                    </div>
                    <div class="h-80">
                        <canvas id="monthlyTrendsChart"></canvas>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/statistics/main.js"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>