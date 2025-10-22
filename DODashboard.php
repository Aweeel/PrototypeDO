<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .progress-bar {
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="flex h-screen">
        <aside class="w-64 bg-gray-900 text-white flex flex-col">
            <!-- Logo -->
            <div class="p-4 flex items-center space-x-3">
                <div class="bg-yellow-400 p-2 rounded">
                    <span class="text-blue-900 font-bold text-xl">STI</span>
                </div>
            </div>

            <!-- Department Badge -->
            <div class="px-4 py-2">
                <span class="bg-blue-600 text-white text-xs px-3 py-1 rounded">Discipline Office</span>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1">
                <a href="#" class="flex items-center px-3 py-2 bg-blue-600 rounded text-white">
                    <span class="mr-3">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 hover:bg-gray-800 rounded text-gray-300">
                    <span class="mr-3">📁</span>
                    <span>Cases</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 hover:bg-gray-800 rounded text-gray-300">
                    <span class="mr-3">📈</span>
                    <span>Statistics</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 hover:bg-gray-800 rounded text-gray-300">
                    <span class="mr-3">🔍</span>
                    <span>Lost & Found</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 hover:bg-gray-800 rounded text-gray-300">
                    <span class="mr-3">👥</span>
                    <span>Student History</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 hover:bg-gray-800 rounded text-gray-300">
                    <span class="mr-3">📋</span>
                    <span>Reports</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 hover:bg-gray-800 rounded text-gray-300">
                    <span class="mr-3">📅</span>
                    <span>Calendar</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 hover:bg-gray-800 rounded text-gray-300">
                    <span class="mr-3">📚</span>
                    <span>Student Handbook</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 hover:bg-gray-800 rounded text-gray-300">
                    <span class="mr-3">📝</span>
                    <span>Audit Log</span>
                </a>
            </nav>

            <!-- User Profile -->
            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-600 rounded-full"></div>
                    <div>
                        <p class="text-sm font-medium">Admin User</p>
                        <p class="text-xs text-gray-400">Discipline Officer</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 px-8 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <button class="p-2 hover:bg-gray-100 rounded">🌙</button>
                        <button class="p-2 hover:bg-gray-100 rounded relative">
                            🔔
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">John Doe</span>
                            <div class="w-8 h-8 bg-gray-300 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="p-8">
                <!-- Metrics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Active Cases -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Active Cases</p>
                                <p class="text-3xl font-bold text-gray-800" id="activeCases">24</p>
                                <p class="text-xs text-gray-500 mt-1">+2 from last week</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded">
                                <span class="text-2xl">📄</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Review -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Pending Review</p>
                                <p class="text-3xl font-bold text-gray-800" id="pendingReview">8</p>
                                <p class="text-xs text-gray-500 mt-1">-1 from last week</p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded">
                                <span class="text-2xl">⏱️</span>
                            </div>
                        </div>
                    </div>

                    <!-- Urgent Cases -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Urgent Cases</p>
                                <p class="text-3xl font-bold text-gray-800" id="urgentCases">3</p>
                                <p class="text-xs text-gray-500 mt-1">+1 from last week</p>
                            </div>
                            <div class="bg-red-100 p-3 rounded">
                                <span class="text-2xl">⚠️</span>
                            </div>
                        </div>
                    </div>

                    <!-- Unresolved Items -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Unresolved Items</p>
                                <p class="text-3xl font-bold text-gray-800" id="unresolvedItems">128</p>
                                <p class="text-xs text-gray-500 mt-1">+5 from last week</p>
                            </div>
                            <div class="bg-gray-100 p-3 rounded">
                                <span class="text-2xl">🔒</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                    <!-- Recent Cases -->
                    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-800">Recent Cases</h2>
                            <a href="#" class="text-sm text-blue-600 hover:underline">View All</a>
                        </div>
                        <div class="space-y-4" id="recentCasesList">
                            <!-- Cases will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Case Types -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-800">Case Types</h2>
                            <a href="#" class="text-sm text-blue-600 hover:underline">Details</a>
                        </div>
                        <div class="space-y-4" id="caseTypesList">
                            <!-- Case types will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout - Lost & Found and Pending Cases -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Lost & Found Items -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-800">Lost & Found Items</h2>
                            <a href="#" class="text-sm text-blue-600 hover:underline">View All</a>
                        </div>
                        <div class="space-y-4" id="lostFoundList">
                            <!-- Items will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Pending Cases -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-800">Pending Cases</h2>
                            <a href="#" class="text-sm text-blue-600 hover:underline">View All</a>
                        </div>
                        <div class="space-y-4" id="pendingCasesList">
                            <!-- Cases will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Sample data - In production, this would come from your MySQL database via API
        const recentCases = [
            { name: 'Mae Johnson', violation: 'Tardiness', code: 'C-1001', status: 'Pending', date: 'Oct 15, 2023', statusColor: 'yellow' },
            { name: 'Maria Garcia', violation: 'Dress Code', code: 'C-1001', status: 'Resolved', date: 'Oct 11, 2023', statusColor: 'green' },
            { name: 'Joshua Smith', violation: 'Classroom Disruption', code: 'C-1090', status: 'Under Review', date: 'Oct 10, 2023', statusColor: 'blue' },
            { name: 'Emma Wilson', violation: 'Academic Dishonesty', code: 'C-0483', status: 'Escalated', date: 'Oct 9, 2023', statusColor: 'red' },
            { name: 'Daniel Lee', violation: 'Attendance', code: 'C-0483', status: 'Resubmit', date: 'Oct 8, 2023', statusColor: 'green' }
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
            { item: 'Calculator', location: 'Math Class', date: 'Oct 8, 2023', status: 'Claimed', statusColor: 'green' }
        ];

        const pendingCases = [
            { name: 'Alex Johnson', violation: 'Tardiness', code: 'C-1062', date: 'Oct 12, 2023' },
            { name: 'Maria Garcia', violation: 'Dress Code', code: 'C-0101', date: 'Oct 12, 2023' },
            { name: 'James Smith', violation: 'Classroom Disruption', code: 'C-1090', date: 'Oct 12, 2023' },
            { name: 'Emma Wilson', violation: 'Academic Dishonesty', code: 'C-0488', date: 'Oct 12, 2023' }
        ];

        // Status color mapping
        const statusColors = {
            yellow: 'bg-yellow-100 text-yellow-800',
            green: 'bg-green-100 text-green-800',
            blue: 'bg-blue-100 text-blue-800',
            red: 'bg-red-100 text-red-800'
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
                <div class="flex items-center justify-between p-4 hover:bg-gray-50 rounded-lg transition border border-gray-100">
                    <div class="flex items-center space-x-3 flex-1">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800">${case_.name}</p>
                            <p class="text-sm text-gray-500">${case_.violation} • ${case_.code}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 text-xs font-medium rounded-full ${statusColors[case_.statusColor]}">${case_.status}</span>
                        <span class="text-sm text-gray-500">${case_.date}</span>
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
                        <span class="text-sm font-medium text-gray-700">${type.name}</span>
                        <span class="text-sm font-semibold text-gray-800">${type.percentage}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="progress-bar ${progressColors[type.color]} h-2 rounded-full" style="width: ${type.percentage}%"></div>
                    </div>
                </div>
            `).join('');
        }

        // Populate Lost & Found
        function populateLostFound() {
            const container = document.getElementById('lostFoundList');
            container.innerHTML = lostFoundItems.map(item => `
                <div class="flex items-center justify-between p-4 hover:bg-gray-50 rounded-lg transition border border-gray-100">
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">${item.item}</p>
                        <p class="text-sm text-gray-500">Found at: ${item.location} • ${item.date}</p>
                    </div>
                    <span class="px-3 py-1 text-xs font-medium rounded-full ${statusColors[item.statusColor]}">${item.status}</span>
                </div>
            `).join('');
        }

        // Populate Pending Cases
        function populatePendingCases() {
            const container = document.getElementById('pendingCasesList');
            container.innerHTML = pendingCases.map(case_ => `
                <div class="flex items-center justify-between p-4 hover:bg-gray-50 rounded-lg transition border border-gray-100">
                    <div class="flex items-center space-x-3 flex-1">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800">${case_.name}</p>
                            <p class="text-sm text-gray-500">${case_.violation} • ${case_.code}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                        <span class="text-sm text-gray-500">${case_.date}</span>
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
</body>
</html>