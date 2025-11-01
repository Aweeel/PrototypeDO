<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    try {
        // Get audit logs with filters
        if ($_POST['action'] === 'getAuditLogs') {
            $filters = [
                'search' => $_POST['search'] ?? '',
                'action_type' => $_POST['actionType'] ?? '',
                'user' => $_POST['user'] ?? '',
                'date_from' => $_POST['dateFrom'] ?? '',
                'date_to' => $_POST['dateTo'] ?? '',
                'table_name' => $_POST['tableName'] ?? ''
            ];

            // Build SQL query
            $sql = "SELECT al.log_id, al.user_id, al.action, al.table_name, al.record_id, 
                           al.old_values, al.new_values, al.ip_address, al.user_agent, al.timestamp,
                           u.full_name as user_name 
                    FROM audit_log al 
                    LEFT JOIN users u ON al.user_id = u.user_id 
                    WHERE 1=1";
            
            $params = [];

            if (!empty($filters['search'])) {
                $sql .= " AND (u.full_name LIKE ? OR al.action LIKE ? OR al.table_name LIKE ? OR al.ip_address LIKE ?)";
                $searchParam = '%' . $filters['search'] . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            if (!empty($filters['action_type'])) {
                $sql .= " AND al.action = ?";
                $params[] = $filters['action_type'];
            }

            if (!empty($filters['user'])) {
                $sql .= " AND al.user_id = ?";
                $params[] = $filters['user'];
            }

            if (!empty($filters['table_name'])) {
                $sql .= " AND al.table_name = ?";
                $params[] = $filters['table_name'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND CAST(al.timestamp AS DATE) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND CAST(al.timestamp AS DATE) <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " ORDER BY al.log_id DESC";

            // Use executeQuery which returns results based on your implementation
            $logs = fetchAll($sql, $params);

            
            // Ensure $logs is an array
            if (!is_array($logs)) {
                $logs = [];
            }

            // Format data for JavaScript
            $formattedLogs = array_map(function ($log) {
                return [
                    'id' => $log['log_id'],
                    'user' => $log['user_name'] ?? 'System',
                    'userId' => $log['user_id'],
                    'action' => $log['action'],
                    'table' => $log['table_name'],
                    'recordId' => $log['record_id'],
                    'timestamp' => date('M d, Y h:i A', strtotime($log['timestamp'])),
                    'ipAddress' => $log['ip_address'] ?? 'N/A',
                    'userAgent' => $log['user_agent'] ?? 'N/A',
                    'oldValues' => $log['old_values'],
                    'newValues' => $log['new_values'],
                    'actionColor' => getActionColor($log['action'])
                ];
            }, $logs);

            echo json_encode(['success' => true, 'logs' => $formattedLogs]);
            exit;
        }

        // Get all users for filter dropdown
        if ($_POST['action'] === 'getUsers') {
            $sql = "SELECT user_id, full_name as name FROM users ORDER BY full_name";
            $users = executeQuery($sql, []);
            
            if (!is_array($users)) {
                $users = [];
            }
            
            echo json_encode(['success' => true, 'users' => $users]);
            exit;
        }

        // Get distinct action types
        if ($_POST['action'] === 'getActionTypes') {
            $sql = "SELECT DISTINCT action FROM audit_log WHERE action IS NOT NULL ORDER BY action";
            $actions = executeQuery($sql, []);
            
            if (!is_array($actions)) {
                $actions = [];
            }
            
            echo json_encode(['success' => true, 'actionTypes' => $actions]);
            exit;
        }

        // Get distinct table names
        if ($_POST['action'] === 'getTableNames') {
            $sql = "SELECT DISTINCT table_name FROM audit_log WHERE table_name IS NOT NULL ORDER BY table_name";
            $tables = executeQuery($sql, []);
            
            if (!is_array($tables)) {
                $tables = [];
            }
            
            echo json_encode(['success' => true, 'tableNames' => $tables]);
            exit;
        }

        // Export audit logs to CSV
        if ($_POST['action'] === 'exportLogs') {
            $filters = [
                'search' => $_POST['search'] ?? '',
                'action_type' => $_POST['actionType'] ?? '',
                'user' => $_POST['user'] ?? '',
                'date_from' => $_POST['dateFrom'] ?? '',
                'date_to' => $_POST['dateTo'] ?? '',
                'table_name' => $_POST['tableName'] ?? ''
            ];

            $sql = "SELECT al.log_id, al.user_id, al.action, al.table_name, al.record_id, 
                           al.timestamp, al.ip_address, u.full_name as user_name 
                    FROM audit_log al 
                    LEFT JOIN users u ON al.user_id = u.user_id 
                    WHERE 1=1";
            
            $params = [];

            if (!empty($filters['search'])) {
                $sql .= " AND (u.full_name LIKE ? OR al.action LIKE ? OR al.table_name LIKE ? OR al.ip_address LIKE ?)";
                $searchParam = '%' . $filters['search'] . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            if (!empty($filters['action_type'])) {
                $sql .= " AND al.action = ?";
                $params[] = $filters['action_type'];
            }

            if (!empty($filters['user'])) {
                $sql .= " AND al.user_id = ?";
                $params[] = $filters['user'];
            }

            if (!empty($filters['table_name'])) {
                $sql .= " AND al.table_name = ?";
                $params[] = $filters['table_name'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND CAST(al.timestamp AS DATE) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND CAST(al.timestamp AS DATE) <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " ORDER BY al.log_id DESC";

            $logs = executeQuery($sql, $params);
            
            if (!is_array($logs)) {
                $logs = [];
            }
            
            $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Log ID', 'User', 'Action', 'Table', 'Record ID', 'Timestamp', 'IP Address']);
            
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['log_id'],
                    $log['user_name'] ?? 'System',
                    $log['action'],
                    $log['table_name'],
                    $log['record_id'],
                    $log['timestamp'],
                    $log['ip_address'] ?? 'N/A'
                ]);
            }
            
            fclose($output);
            exit;
        }

    } catch (Exception $e) {
        error_log("Audit Log AJAX Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Helper function to get action color
function getActionColor($action) {
    $colors = [
        'Created' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Deleted' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Archived' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300',
        'Restored' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        'Unarchived' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        'Login' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300',
        'Logout' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
        'Failed Login' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Case Created' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Case Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Case Deleted' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Case Archived' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300'
    ];
    return $colors[$action] ?? 'bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office - Audit Logs</title>
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

<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <?php
            $pageTitle = "Audit Logs";
            $adminName = $_SESSION['admin_name'] ?? 'Admin';
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
                        <input type="text" id="searchInput" placeholder="Search logs..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none"
                            oninput="filterLogs()">
                    </div>

                    <button onclick="exportLogs()"
                        class="ml-4 px-4 py-2.5 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </button>
                </div>

                <!-- Filters -->
                <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
                    <div class="flex gap-3 items-center flex-wrap">
                        <!-- Action Type Filter -->
                        <select id="actionTypeFilter" onchange="filterLogs()"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                            <option value="">All Actions</option>
                            <!-- Populated by JS -->
                        </select>

                        <!-- User Filter -->
                        <select id="userFilter" onchange="filterLogs()"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                            <option value="">All Users</option>
                            <!-- Populated by JS -->
                        </select>

                        <!-- Table Filter -->
                        <select id="tableFilter" onchange="filterLogs()"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                            <option value="">All Tables</option>
                            <!-- Populated by JS -->
                        </select>

                        <!-- Advanced Filters Button -->
                        <button onclick="openAdvancedFilters()"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Date Range
                        </button>
                    </div>

                    <div class="flex gap-3 items-center">
                        <!-- Sort Dropdown -->
                        <select id="sortFilter" onchange="sortLogs()"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                            <option value="newest">Sort: Newest</option>
                            <option value="oldest">Sort: Oldest</option>
                            <option value="user">Sort: User</option>
                            <option value="action">Sort: Action</option>
                        </select>

                        <!-- Refresh Button -->
                        <button onclick="refreshLogs()"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-100 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Log ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Table</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Timestamp</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody" class="bg-white dark:bg-[#111827] divide-y divide-gray-200 dark:divide-slate-700">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex items-center justify-between">
                    <p id="paginationInfo" class="text-sm text-gray-600 dark:text-gray-400">Loading...</p>
                    <div id="paginationButtons" class="flex gap-2">
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Date Range Modal -->
    <div id="dateRangeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Filter by Date Range</h3>
                <button onclick="closeDateRangeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                    <input type="date" id="dateFrom" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                    <input type="date" id="dateTo" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button onclick="applyDateFilter()" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                    Apply Filter
                </button>
                <button onclick="clearDateFilter()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 p-6 z-10">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Audit Log Details</h3>
                    <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div id="detailsContent" class="p-6">
                <!-- Populated by JS -->
            </div>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/audit_log/main.js"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>

</html>