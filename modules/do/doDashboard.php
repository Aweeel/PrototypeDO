<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Get statistics from database
$stats = getCaseStatistics();
$lostFoundStats = getLostFoundStatistics();
$recentCases = getRecentCases(5);
$caseTypes = getCaseTypeDistribution();
$recentLostFound = getRecentLostFoundItems(4);
$pendingCases = fetchAll("SELECT TOP 4 c.*, CONCAT(s.first_name, ' ', s.last_name) as student_name, s.student_id as student_number
                          FROM cases c
                          LEFT JOIN students s ON c.student_id = s.student_id
                          WHERE c.status = 'Pending' AND c.is_archived = 0
                          ORDER BY c.date_reported DESC");

// Dynamic color generator for case types using Tailwind classes
function generateCaseTypeColors($caseTypes) {
    $colors = [
        'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 
        'bg-purple-500', 'bg-indigo-500', 'bg-pink-500', 'bg-orange-500',
        'bg-teal-500', 'bg-cyan-500', 'bg-lime-500', 'bg-amber-500',
        'bg-emerald-500', 'bg-violet-500', 'bg-fuchsia-500', 'bg-rose-500'
    ];
    
    $colorMap = [];
    $index = 0;
    
    foreach ($caseTypes as $type) {
        $caseTypeName = $type['case_type'];
        if (!isset($colorMap[$caseTypeName])) {
            $colorMap[$caseTypeName] = $colors[$index % count($colors)];
            $index++;
        }
    }
    
    return $colorMap;
}

$progressColors = generateCaseTypeColors($caseTypes);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        // Ensure tailwind uses class-based dark mode
        tailwind.config = {
            darkMode: 'class'
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

<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="flex h-screen">
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto overflow-x-hidden scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-transparent hover:scrollbar-thumb-blue-400">
            <header class="bg-white dark:bg-slate-800">
                <!-- Main Content -->
                <div class="flex-1 overflow-y-auto ml-64 overflow-x-hidden scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-transparent hover:scrollbar-thumb-blue-400">
                    <!-- Header -->
                    <?php
                    $pageTitle = "Dashboard";
                    $adminName = $_SESSION['admin_name'] ?? 'Admin';
                    include __DIR__ . '/../../includes/header.php';
                    ?>

                    <!-- Dashboard Content -->
                    <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                        <!-- Metrics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <!-- Active Cases -->
                            <div class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 
                                transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Active Cases</p>
                                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="activeCases">
                                            <?php echo $stats['total_active']; ?>
                                        </p>
                                    </div>
                                    <div class="bg-blue-100 dark:bg-[#1E3A8A] p-5 rounded-full transition-colors duration-300">
                                        <img src="../../assets/images/icons/active-icon.png" alt="Active icon" />
                                    </div>
                                </div>
                            </div>

                            <!-- Pending Review -->
                            <div class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700
                                transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Pending Review</p>
                                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-100"
                                            id="pendingReview"><?php echo $stats['pending_review']; ?></p>
                                    </div>
                                    <div class="bg-yellow-100 dark:bg-[#713F12] p-5 rounded-full transition-colors duration-300">
                                        <img src="../../assets/images/icons/pending-icon.png" alt="Pending icon" />
                                    </div>
                                </div>
                            </div>

                            <!-- Urgent Cases -->
                            <div class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700
                                transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Urgent Cases</p>
                                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="urgentCases">
                                            <?php echo $stats['urgent_cases']; ?>
                                        </p>
                                    </div>
                                    <div class="bg-red-100 dark:bg-[#7F1D1D] p-5 rounded-full transition-colors duration-300">
                                        <img src="../../assets/images/icons/urgent-icon.png" alt="Urgent icon" />
                                    </div>
                                </div>
                            </div>

                            <!-- Unclaimed Items -->
                            <div class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700
                                transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Unclaimed Items</p>
                                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-100"
                                            id="unresolvedItems"><?php echo $lostFoundStats['total_unclaimed']; ?>
                                        </p>
                                    </div>
                                    <div class="bg-gray-300 dark:bg-[#6B7280] p-5 rounded-full transition-colors duration-300">
                                        <img src="../../assets/images/icons/unclaimed-icon.png" alt="Unclaimed icon" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Two Column Layout -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                            <!-- Recent Cases -->
                            <div class="lg:col-span-2 bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-[#E5E7EB] dark:border-slate-700 p-6 transition-colors duration-300
                                transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Recent Cases
                                    </h2>
                                    <a href="../do/cases.php" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 
          font-medium transition-all duration-200 active:scale-95">
                                        View All
                                    </a>
                                </div>

                                <div class="divide-y divide-gray-200 dark:divide-slate-700">
                                    <?php foreach ($recentCases as $case): 
                                        $statusColor = getStatusColor($case['status']);
                                        $statusColors = [
                                            'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-[#713F12] dark:text-yellow-100',
                                            'green' => 'bg-green-100 text-green-800 dark:bg-[#14532D] dark:text-green-100',
                                            'blue' => 'bg-blue-100 text-blue-800 dark:bg-[#1E3A8A] dark:text-blue-100',
                                            'red' => 'bg-red-100 text-red-800 dark:bg-[#7F1D1D] dark:text-red-100'
                                        ];
                                    ?>
                                    <div class="flex items-center justify-between p-4 hover:bg-[#E0F2FE] dark:hover:bg-slate-700 transition-all duration-200 first:rounded-t-lg last:rounded-b-lg">
                                        <div class="flex items-center space-x-3 flex-1">
                                            <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0"></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-800 dark:text-gray-100"><?php echo htmlspecialchars($case['student_name']); ?></p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($case['case_type']); ?> • <?php echo htmlspecialchars($case['student_id']); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $statusColors[$statusColor]; ?>"><?php echo htmlspecialchars($case['status']); ?></span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo formatDate($case['date_reported']); ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Case Types - Dynamic Colors Only with Tailwind -->
                            <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6 transition-colors duration-300
                                transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Case Types
                                    </h2>
                                </div>
                                <div class="space-y-4">
                                    <?php foreach ($caseTypes as $type): 
                                        $color = $progressColors[$type['case_type']] ?? 'bg-gray-500';
                                    ?>
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($type['case_type']); ?></span>
                                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100"><?php echo number_format($type['percentage'], 0); ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                                            <div class="<?php echo $color; ?> h-2 rounded-full transition-all duration-300" style="width: <?php echo $type['percentage']; ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Two Column Layout - Lost & Found and Pending Cases -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <!-- Lost & Found Items -->
                            <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6 transition-colors duration-300
                                transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Lost & Found
                                        Items
                                    </h2>
                                    <a href="../do/lostAndFound.php" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 
          font-medium transition-all duration-200 active:scale-95">
                                        View All
                                    </a>
                                </div>
                                <div class="divide-y divide-gray-200 dark:divide-slate-700">
                                    <?php foreach ($recentLostFound as $item): 
                                        $itemStatusColors = [
                                            'Unclaimed' => 'bg-yellow-100 text-yellow-800 dark:bg-[#713F12] dark:text-yellow-100',
                                            'Claimed' => 'bg-green-100 text-green-800 dark:bg-[#14532D] dark:text-green-100'
                                        ];
                                    ?>
                                    <div class="flex items-center justify-between p-4 hover:bg-[#E0F2FE] dark:hover:bg-slate-700 transition-all duration-200 first:rounded-t-lg last:rounded-b-lg">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-800 dark:text-gray-100"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Found at: <?php echo htmlspecialchars($item['found_location']); ?> • <?php echo formatDate($item['date_found']); ?></p>
                                        </div>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $itemStatusColors[$item['status']]; ?>"><?php echo htmlspecialchars($item['status']); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Pending Cases -->
                            <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6 transition-colors duration-300
                                transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Pending
                                        Cases</h2>
                                    <a href="../do/cases.php" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 
          font-medium transition-all duration-200 active:scale-95">
                                        View All
                                    </a>
                                </div>
                                <div class="divide-y divide-gray-200 dark:divide-slate-700">
                                    <?php foreach ($pendingCases as $case): ?>
                                    <div class="flex items-center justify-between p-4 hover:bg-[#E0F2FE] dark:hover:bg-slate-700 transition-all duration-200 first:rounded-t-lg last:rounded-b-lg">
                                        <div class="flex items-center space-x-3 flex-1">
                                            <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0"></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-800 dark:text-gray-100"><?php echo htmlspecialchars($case['student_name']); ?></p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($case['case_type']); ?> • <?php echo htmlspecialchars($case['student_number']); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-[#713F12] dark:text-yellow-100">Pending</span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo formatDate($case['date_reported']); ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </header>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>

</html>