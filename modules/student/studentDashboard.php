<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Page metadata
$pageTitle = "Dashboard";
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$studentId = $_SESSION['student_id'] ?? null;


// Fetch student dashboard data
$totalCases = 0;
$activeCases = 0;
$nextHearing = null;
$recentCases = [];

if ($studentId) {
    // Get total and active cases count
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('pending', 'under_review') THEN 1 ELSE 0 END) as active
        FROM cases 
        WHERE student_id = ?
    ");
    $stmt->execute([$studentId]);
    $caseStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalCases = $caseStats['total'] ?? 0;
    $activeCases = $caseStats['active'] ?? 0;

    // Get next hearing date
    $stmt = $pdo->prepare("
        SELECT MIN(hearing_date) as next_hearing
        FROM cases 
        WHERE student_id = ? 
        AND status IN ('pending', 'under_review')
        AND hearing_date > NOW()
    ");
    $stmt->execute([$studentId]);
    $hearingData = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextHearing = $hearingData['next_hearing'] ?? null;

    // Get recent cases
    $stmt = $pdo->prepare("
        SELECT 
            case_id,
            case_type,
            created_at,
            status
        FROM cases 
        WHERE student_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$studentId]);
    $recentCases = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Format next hearing date
$nextHearingFormatted = $nextHearing ? date('M d', strtotime($nextHearing)) : 'Not Scheduled';

// Status badge colors
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-orange-500/10 text-orange-500 border border-orange-500/20';
        case 'under_review':
            return 'bg-blue-500/10 text-blue-500 border border-blue-500/20';
        case 'resolved':
            return 'bg-green-500/10 text-green-500 border border-green-500/20';
        default:
            return 'bg-gray-500/10 text-gray-500 border border-gray-500/20';
    }
}

// Status display text
function getStatusText($status) {
    switch ($status) {
        case 'pending':
            return 'Pending';
        case 'under_review':
            return 'Under Review';
        case 'resolved':
            return 'Resolved';
        default:
            return ucfirst($status);
    }
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
                <!-- Dashboard Title -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Student Dashboard</h1>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Total Cases -->
                    <div class="bg-white dark:bg-[#111827] rounded-lg p-6 shadow-sm border border-gray-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Cases</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?php echo $totalCases; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-500/10 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Active Cases -->
                    <div class="bg-white dark:bg-[#111827] rounded-lg p-6 shadow-sm border border-gray-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Active Cases</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?php echo $activeCases; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-500/10 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Next Hearing -->
                    <div class="bg-white dark:bg-[#111827] rounded-lg p-6 shadow-sm border border-gray-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Next Hearing</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($nextHearingFormatted); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-500/10 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Cases -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
                    <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Recent Cases</h2>
                        <a href="/cases" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View all cases</a>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-slate-700">
                        <?php if (empty($recentCases)): ?>
                            <div class="p-8 text-center">
                                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">No cases found</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Your case history will appear here</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentCases as $case): ?>
                                <div class="p-6 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                                                <?php echo htmlspecialchars(str_replace('_', ' ', ucwords($case['case_type'], '_'))); ?>
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo date('M d, Y', strtotime($case['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo getStatusBadgeClass($case['status']); ?>">
                                                <?php echo getStatusText($case['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>