<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    // Mark password warning as shown in this login session
    if (isset($_POST['action']) && $_POST['action'] === 'markPasswordWarningShown') {
        $_SESSION['password_warning_modal_shown'] = true;
        echo json_encode(['success' => true, 'message' => 'Password warning marked as shown']);
        exit;
    }

    try {
        // Get audit logs with filters
        if ($_POST['action'] === 'getAuditLogs') {
            $filters = [
                'search' => $_POST['search'] ?? '',
                'action_type' => $_POST['actionType'] ?? '',
                'user' => $_POST['user'] ?? '',
                'date_from' => $_POST['dateFrom'] ?? '',
                'date_to' => $_POST['dateTo'] ?? '',
            ];

            $sql = "SELECT al.log_id, al.user_id, al.action, al.table_name, al.record_id, 
                           al.old_values, al.new_values, al.ip_address, al.user_agent, al.timestamp,
                           u.full_name as user_name, u.role as user_role
                    FROM audit_log al 
                    LEFT JOIN users u ON al.user_id = u.user_id 
                    WHERE 1=1";

            $params = [];

            // DO/Discipline Office users cannot see super_admin actions, or teacher/security actions
            // EXCEPT for reporting actions from those roles
            if (in_array($_SESSION['user_role'], ['do', 'discipline_office'])) {
                $sql .= " AND (u.user_id IS NOT NULL AND u.role != 'super_admin')";
                $sql .= " AND (u.role NOT IN ('teacher', 'security') OR al.action LIKE '%Report%')";
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (u.full_name LIKE ? OR al.action LIKE ? OR al.table_name LIKE ? OR al.ip_address LIKE ?)";
                $searchParam = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            }

            if (!empty($filters['action_type'])) {
                $sql .= " AND al.action = ?";
                $params[] = $filters['action_type'];
            }

            if (!empty($filters['user'])) {
                $sql .= " AND u.role = ?";
                $params[] = $filters['user'];
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
            
            try {
                $logs = fetchAll($sql, $params) ?? [];
            } catch (Exception $dbError) {
                error_log("Audit Log Query Error: " . $dbError->getMessage());
                echo json_encode(['success' => false, 'error' => 'Database query failed: ' . $dbError->getMessage()]);
                exit;
            }

            $formattedLogs = array_map(function ($log) {
                return [
                    'id' => $log['log_id'],
                    'user' => $log['user_name'] ?? 'System',
                    'userId' => $log['user_id'],
                    'role' => $log['user_role'] ?? 'N/A',
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

        // Get distinct user roles
        if ($_POST['action'] === 'getUsers') {
            $sql = "SELECT DISTINCT role FROM users WHERE role IS NOT NULL";
            
            // DO/Discipline Office users cannot see super_admin, teacher, or security roles in filter options
            if (in_array($_SESSION['user_role'], ['do', 'discipline_office'])) {
                $sql .= " AND role NOT IN ('super_admin', 'teacher', 'security')";
            }
            
            $sql .= " ORDER BY role";
            $roles = fetchAll($sql, []) ?? [];
            
            // Format roles for display
            $formattedRoles = array_map(function($row) {
                $role = $row['role'];
                $display = match($role) {
                    'super_admin' => 'Super Admin',
                    'do' => 'Discipline Office',
                    'discipline_office' => 'Discipline Office',
                    'teacher' => 'Teacher',
                    'student' => 'Student',
                    default => ucwords(str_replace('_', ' ', $role))
                };
                return ['role' => $role, 'display' => $display];
            }, $roles);
            echo json_encode(['success' => true, 'users' => $formattedRoles]);
            exit;
        }

        // Get distinct action types
        if ($_POST['action'] === 'getActionTypes') {
            $sql = "SELECT DISTINCT al.action FROM audit_log al 
                    LEFT JOIN users u ON al.user_id = u.user_id 
                    WHERE al.action IS NOT NULL";
            
            // DO/Discipline Office users cannot see actions from super_admin, teacher, or security
            // EXCEPT for report-related actions
            if (in_array($_SESSION['user_role'], ['do', 'discipline_office'])) {
                $sql .= " AND ((u.role IS NOT NULL AND u.role NOT IN ('super_admin', 'teacher', 'security')) OR al.action LIKE '%Report%')";
            }
            
            $sql .= " ORDER BY al.action";
            $actions = fetchAll($sql, []) ?? [];
            echo json_encode(['success' => true, 'actionTypes' => $actions]);
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

            // DO/Discipline Office users cannot see super_admin actions, or teacher/security actions
            // EXCEPT for reporting actions from those roles
            if (in_array($_SESSION['user_role'], ['do', 'discipline_office'])) {
                $sql .= " AND (u.user_id IS NOT NULL AND u.role != 'super_admin')";
                $sql .= " AND (u.role NOT IN ('teacher', 'security') OR al.action LIKE '%Report%')";
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (u.full_name LIKE ? OR al.action LIKE ? OR al.table_name LIKE ? OR al.ip_address LIKE ?)";
                $searchParam = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            }

            if (!empty($filters['action_type'])) {
                $sql .= " AND al.action = ?";
                $params[] = $filters['action_type'];
            }

            if (!empty($filters['user'])) {
                $sql .= " AND u.role = ?";
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
            $logs = executeQuery($sql, $params) ?? [];

            $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add metadata rows
            fputcsv($output, ['STI Discipline Office – Audit Log Export']);
            fputcsv($output, ['Exported by:', $adminName]);
            fputcsv($output, ['Date & Time:', date('F d, Y h:i A')]);
            fputcsv($output, []);
            
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

// Helper function
function getActionColor($action) {
    $colors = [
        // Core Operations
        'Created' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Deleted' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Archived' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        'Restored' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
        'Unarchived' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
        
        // Authentication
        'Login' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300',
        'Logout' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
        'Failed Login' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        
        // User Management
        'User Created' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'User Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'User Deleted' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Password Reset' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
        'User Activated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'User Deactivated' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
        'User Activated (Bulk)' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'User Deactivated (Bulk)' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
        'Student Imported' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Bulk Import' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        
        // Students
        'Student Created' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Student Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Student Deleted' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Student Archived' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        'Student Restored' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
        
        // Case Management
        'Case Created' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Case Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Case Deleted' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Case Archived' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        'Case Restored' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
        'Case Resolved' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Report Submitted' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Student Case Viewed' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        
        // Sanctions
        'Sanction Created' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
        'Sanction Applied' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
        'Sanction Updated' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
        'Sanction Removed' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Sanction Deadline Extended' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
        'Sanction Duration Increased' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
        
        // Check-In/Check-Out
        'Check-In Recorded' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300',
        'Check-Out Recorded' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300',
        'Time Corrected (check_in)' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Time Corrected (check_out)' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Time Record Reverted (check_in)' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Time Record Reverted (check_out)' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        
        // Lost & Found
        'Lost Item Added' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Lost Item Created' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Lost Item Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Lost Item Deleted' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Lost Item Archived' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        'Lost Item Restored' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
        'Lost Item Claimed' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Lost Item Unclaimed' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
        
        // Portfolio & Submissions
        'Portfolio Submitted' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Portfolio Viewed' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        
        // Calendar
        'Calendar Event Created' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Calendar Event Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Calendar Event Deleted' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        
        // Notifications
        'Notification Created' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Notification Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Notification Deleted' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        'Notification Archived' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        'Notification Restored' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
        'Notification Read' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        
        // Reports
        'Report Generated' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300',
        
        // Handbook
        'Student Handbook PDF Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Student Handbook Content Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        
        // Terms and Conditions
        'Terms Accepted' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'Terms and Conditions Updated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'Terms Viewed' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
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
    <meta name="data-admin-name" content="<?= htmlspecialchars($adminName) ?>">
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
<style>
    /* Print styles */
    #print-root { display: none; }
    .preview-wrap { font-family: Arial, sans-serif; color: #111827; }
    .dark .preview-wrap { color: #f1f5f9; }
    
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
    
    @media print {
    span[class*="rounded-full"] {
        background: none !important;
        color: #111827 !important;
        padding: 0 !important;
        font-weight: 600;
    }
}
</style>
</head>

<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">
    <!-- Hidden print root — only shown at @media print -->
    <div id="print-root" aria-hidden="true"></div>
    
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <?php
            $pageTitle = "Audit Logs";
            $adminName = getFormattedUserName();
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
                        class="ml-4 px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export
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

                        <!-- Role Filter -->
                        <select id="userFilter" onchange="filterLogs()"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                            <option value="">All Roles</option>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Log ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">IP Address</th>
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

    <!-- Export Preview Modal -->
    <div id="exportModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Export Audit Logs</h3>
                <button onclick="closeExportModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Filters Summary -->
            <div class="px-6 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-slate-700">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-semibold">Applied Filters:</span>
                    <span id="filtersSummary" class="text-gray-600 dark:text-gray-400"></span>
                </p>
            </div>

            <!-- Preview Content -->
            <div id="exportPreviewContent" class="flex-1 overflow-y-auto p-6">
                <div class="animate-pulse text-center text-gray-500">
                    <p>Generating preview...</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50">
                <span id="exportCount" class="text-sm text-gray-500 dark:text-gray-400"></span>
                <div class="flex gap-2">
                    <button onclick="exportAuditLogsCSV()"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-sm border
                               border-gray-300 dark:border-slate-600 rounded-lg
                               hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors font-medium">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export CSV
                    </button>
                    <button onclick="printAuditReport()"
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

    <!-- Audit Log Detail Modal -->
    <div id="logDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Audit Log Detail - <span id="detailLogId"></span></h3>
                <button onclick="closeLogDetailModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">User</h4>
                        <p class="text-gray-900 dark:text-gray-100" id="detailUser"></p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Role</h4>
                        <p class="text-gray-900 dark:text-gray-100" id="detailRole"></p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Action</h4>
                        <div id="detailAction"></div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Timestamp</h4>
                        <p class="text-gray-900 dark:text-gray-100" id="detailTimestamp"></p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Table</h4>
                        <p class="text-gray-900 dark:text-gray-100" id="detailTable"></p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Record ID</h4>
                        <p class="text-gray-900 dark:text-gray-100" id="detailRecordId"></p>
                    </div>
                </div>

                <!-- Network Information -->
                <div class="border-t border-gray-200 dark:border-slate-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Network Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">IP Address</h4>
                            <p class="text-gray-900 dark:text-gray-100 font-mono text-sm" id="detailIpAddress"></p>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">User Agent</h4>
                            <p class="text-gray-900 dark:text-gray-100 text-xs break-all" id="detailUserAgent"></p>
                        </div>
                    </div>
                </div>

                <!-- Old Values -->
                <div class="border-t border-gray-200 dark:border-slate-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Previous Values</h3>
                    <div id="oldValuesSection">
                        <p class="text-gray-500 dark:text-gray-400 italic">Loading...</p>
                    </div>
                </div>

                <!-- New Values -->
                <div class="border-t border-gray-200 dark:border-slate-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">New Values</h3>
                    <div id="newValuesSection">
                        <p class="text-gray-500 dark:text-gray-400 italic">Loading...</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 flex justify-between gap-3">
                <div class="flex gap-3">
                    <button onclick="exportDetailAsCSV()" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export CSV
                    </button>
                    <button onclick="printDetailAsPDF()" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print / Save PDF
                    </button>
                </div>
                <button onclick="closeLogDetailModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/audit_log/main.js"></script>
    <script src="/PrototypeDO/assets/js/audit_log/filters.js"></script>
    <script src="/PrototypeDO/assets/js/audit_log/modals.js"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>
