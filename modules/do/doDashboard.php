<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office Dashboard</title>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
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

    <style>
        .progress-bar {
            transition: width 0.3s ease;
        }
    </style>
</head>

<body
    class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="flex h-screen">
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <header class="bg-white dark:bg-slate-800 ...">

                <!-- Main Content -->
                <div class="flex-1 overflow-y-auto ml-64">
                    <!-- Header -->
                    <?php
                    $pageTitle = "Dashboard"; // dynamic title
                    $adminName = $_SESSION['admin_name'] ?? 'Admin'; // you can fetch from session later
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
                                            24</p>
                                    </div>
                                    <div
                                        class="bg-blue-100 dark:bg-[#1E3A8A] p-5 rounded-full transition-colors duration-300">
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
                                            id="pendingReview">8</p>
                                    </div>
                                    <div
                                        class="bg-yellow-100 dark:bg-[#713F12] p-5 rounded-full transition-colors duration-300">
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
                                            3</p>
                                    </div>
                                    <div
                                        class="bg-red-100 dark:bg-[#7F1D1D] p-5 rounded-full transition-colors duration-300">
                                        <img src="../../assets/images/icons/urgent-icon.png" alt="Urgent icon" />
                                    </div>
                                </div>
                            </div>

                            <!-- Unresolved Items -->
                            <div class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700
                                transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Unclaimed Items</p>
                                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-100"
                                            id="unresolvedItems">128
                                        </p>
                                    </div>
                                    <div
                                        class="bg-gray-300 dark:bg-[#6B7280] p-5 rounded-full transition-colors duration-300">
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

                                <!-- Inner list with dividers and no top line -->
                                <div class="divide-y divide-gray-200 dark:divide-slate-700 [&>*:first-child]:border-t-0"
                                    id="recentCasesList">
                                    <!-- JS populates cases here -->
                                </div>
                            </div>

                            <!-- Case Types -->
                            <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6 transition-colors duration-300
                                transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Case Types
                                    </h2>
                                </div>
                                <div class="space-y-4" id="caseTypesList">
                                    <!-- Case types will be populated by JavaScript -->
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
                                <div class="divide-y divide-gray-200 dark:divide-slate-700 [&>*:first-child]:border-t-0"
                                    id="lostFoundList">
                                    <!-- Items will be populated by JavaScript -->
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
                                <div class="divide-y divide-gray-200 dark:divide-slate-700 [&>*:first-child]:border-t-0"
                                    id="pendingCasesList">
                                    <!-- Cases will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                </div>
        </div>
        </main>
    </div>
    </div>

    <script>
        // Sample data - In production, this would come from your MySQL database via API
        const recentCases = [
            { name: 'Mae Johnson', violation: 'Tardiness', code: '02000372341', status: 'Pending', date: 'Oct 15, 2025', statusColor: 'yellow' },
            { name: 'Maria Garcia', violation: 'Dress Code', code: '02000372341', status: 'Resolved', date: 'Oct 11, 2025', statusColor: 'green' },
            { name: 'Joshua Smith', violation: 'Classroom Disruption', code: '02000372341', status: 'Under Review', date: 'Oct 10, 2025', statusColor: 'blue' },
            { name: 'Emma Wilson', violation: 'Academic Dishonesty', code: '02000372341', status: 'Escalated', date: 'Oct 9, 2025', statusColor: 'red' },
            { name: 'Daniel Lee', violation: 'Attendance', code: '02000372341', status: 'Resolved', date: 'Oct 8, 2025', statusColor: 'green' }
        ];

        const caseTypes = [
            { name: 'Tardiness', percentage: 42, color: 'blue' },
            { name: 'Dress Code', percentage: 28, color: 'green' },
            { name: 'Classroom Disruption', percentage: 18, color: 'yellow' },
            { name: 'Academic Dishonesty', percentage: 12, color: 'red' },
            { name: 'Other', percentage: 10, color: 'purple' }
        ];

        const lostFoundItems = [
            { item: 'Blue Backpack', location: 'Cafeteria', date: 'Oct 14, 2023', status: 'Unclaimed', statusColor: 'yellow' },
            { item: 'Water Bottle', location: 'Gym', date: 'Oct 13, 2023', status: 'Unclaimed', statusColor: 'yellow' },
            { item: 'Textbook', location: 'Library', date: 'Oct 12, 2023', status: 'Claimed', statusColor: 'green' },
            { item: 'Calculator', location: 'C401', date: 'Oct 8, 2023', status: 'Claimed', statusColor: 'green' }
        ];

        const pendingCases = [
            { name: 'Alex Johnson', violation: 'Tardiness', code: '02000372341', date: 'Oct 12, 2023' },
            { name: 'Maria Garcia', violation: 'Dress Code', code: '02000372341', date: 'Oct 12, 2023' },
            { name: 'James Smith', violation: 'Classroom Disruption', code: '02000372341', date: 'Oct 12, 2023' },
            { name: 'Emma Wilson', violation: 'Academic Dishonesty', code: '02000372341', date: 'Oct 12, 2023' }
        ];

        // Status color mapping
        const statusColors = {
            yellow: 'bg-yellow-100 text-yellow-800 dark:bg-[#713F12] dark:text-yellow-100', // Pending / Unclaimed
            green: 'bg-green-100 text-green-800 dark:bg-[#14532D] dark:text-green-100',   // Resolved / Claimed
            blue: 'bg-blue-100 text-blue-800 dark:bg-[#1E3A8A] dark:text-blue-100',       // Under Review
            red: 'bg-red-100 text-red-800 dark:bg-[#7F1D1D] dark:text-red-100'            // Escalated
        };

        // Progress bar colors
        const progressColors = {
            blue: 'bg-blue-500',
            green: 'bg-green-500',
            yellow: 'bg-yellow-500',
            red: 'bg-red-500',
            purple: 'bg-purple-500'
        };

        // Populate Recent Cases
        function populateRecentCases() {
            const container = document.getElementById('recentCasesList');
            container.innerHTML = recentCases.map(case_ => `
                <div class="flex items-center justify-between p-4 hover:bg-[#E0F2FE] dark:hover:bg-slate-700 transition-all duration-200 rounded-none first:rounded-t-lg last:rounded-b-lg">
                    <div class="flex items-center space-x-3 flex-1">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 dark:text-gray-100">${case_.name}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">${case_.violation} • ${case_.code}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 text-xs font-medium rounded-full ${statusColors[case_.statusColor]}">${case_.status}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">${case_.date}</span>
                    </div>
                </div>
            `).join('');
        }

        // Populate Case Types
        function populateCaseTypes() {
            const container = document.getElementById('caseTypesList');
            container.innerHTML = caseTypes.map(type => `
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">${type.name}</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">${type.percentage}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                        <div class="progress-bar ${progressColors[type.color]} h-2 rounded-full" style="width: ${type.percentage}%"></div>
                    </div>
                </div>
            `).join('');
        }

        // Populate Lost & Found
        function populateLostFound() {
            const container = document.getElementById('lostFoundList');
            container.innerHTML = lostFoundItems.map(item => `
                <div class="flex items-center justify-between p-4 hover:bg-[#E0F2FE] dark:hover:bg-slate-700 transition-all duration-200 rounded-none first:rounded-t-lg last:rounded-b-lg">
                    <div class="flex-1">
                        <p class="font-medium text-gray-800 dark:text-gray-100">${item.item}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Found at: ${item.location} • ${item.date}</p>
                    </div>
                    <span class="px-3 py-1 text-xs font-medium rounded-full ${statusColors[item.statusColor]}">${item.status}</span>
                </div>
            `).join('');
        }

        // Populate Pending Cases
        function populatePendingCases() {
            const container = document.getElementById('pendingCasesList');
            container.innerHTML = pendingCases.map(case_ => `
                <div class="flex items-center justify-between p-4 hover:bg-[#E0F2FE] dark:hover:bg-slate-700 transition-all duration-200 rounded-none first:rounded-t-lg last:rounded-b-lg">
                    <div class="flex items-center space-x-3 flex-1">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 dark:text-gray-100">${case_.name}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">${case_.violation} • ${case_.code}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-[#713F12] dark:text-yellow-100">Pending</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">${case_.date}</span>
                    </div>
                </div>
            `).join('');
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', () => {
            populateRecentCases();
            populateCaseTypes();
            populateLostFound();
            populatePendingCases();
        });
    </script>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
    <script src="/PrototypeDO/assets/js/assets/js/exit_on_load.js"></script>
</body>

</html>