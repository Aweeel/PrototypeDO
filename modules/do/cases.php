<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cases Management - STI Discipline Office</title>
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
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto ml-64">
            <!-- Header -->
            <?php
            $pageTitle = "Cases";
            $adminName = $_SESSION['admin_name'] ?? 'Admin';
            include __DIR__ . '/../../includes/header.php';
            ?>

            <!-- Main Content Area -->
            <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                <!-- Page Header with New Case Button -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Cases Management</h1>
                    </div>
                    <button onclick="openAddCaseModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <span>+</span>
                        <span>New Case</span>
                    </button>
                </div>

                <!-- Filters and Search Section -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-6">
                    <!-- Search Bar -->
                    <div class="mb-4">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search cases..." 
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                oninput="filterCases()">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Filter Tabs and Dropdowns -->
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <!-- Status Tabs -->
                        <div class="flex space-x-2">
                            <button onclick="filterByStatus('current')" id="currentTab" class="px-4 py-2 rounded-lg font-medium transition-colors duration-200 bg-blue-600 text-white">
                                Current
                            </button>
                            <button onclick="filterByStatus('archived')" id="archivedTab" class="px-4 py-2 rounded-lg font-medium transition-colors duration-200 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300">
                                Archived
                            </button>
                        </div>

                        <!-- Filter Dropdowns -->
                        <div class="flex items-center space-x-3">
                            <!-- Filters Button -->
                            <button onclick="toggleFiltersDropdown()" id="filtersBtn" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                <span>Filters</span>
                            </button>

                            <!-- Type Filter -->
                            <select id="typeFilter" onchange="filterCases()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                                <option value="">All Types</option>
                                <option value="Tardiness">Tardiness</option>
                                <option value="Dress Code">Dress Code</option>
                                <option value="Classroom Disruption">Classroom Disruption</option>
                                <option value="Academic Dishonesty">Academic Dishonesty</option>
                                <option value="Attendance">Attendance</option>
                            </select>

                            <!-- Status Filter -->
                            <select id="statusFilter" onchange="filterCases()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Under Review">Under Review</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Escalated">Escalated</option>
                            </select>

                            <!-- Sort Filter -->
                            <select id="sortFilter" onchange="sortCases()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                                <option value="newest">Sort: Newest</option>
                                <option value="oldest">Sort: Oldest</option>
                                <option value="student">Sort: Student Name</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Cases Table -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Case ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date Reported</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assigned To</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="casesTableBody" class="divide-y divide-gray-200 dark:divide-slate-700">
                                <!-- Table rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white dark:bg-[#111827] px-6 py-4 border-t border-gray-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500 dark:text-gray-400" id="paginationInfo">
                                Showing 1-8 of 24 cases
                            </div>
                            <div class="flex space-x-2" id="paginationButtons">
                                <button class="px-3 py-1 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors duration-200">Previous</button>
                                <button class="px-3 py-1 bg-blue-600 text-white rounded">1</button>
                                <button class="px-3 py-1 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors duration-200">2</button>
                                <button class="px-3 py-1 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors duration-200">3</button>
                                <button class="px-3 py-1 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors duration-200">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Case Modal -->
    <div id="viewCaseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100" id="viewModalTitle">Case Details: C-1092</h2>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Student Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Student</label>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gray-300 dark:bg-slate-600 rounded-full"></div>
                                <span class="text-gray-800 dark:text-gray-100 font-medium" id="viewStudentName">Alex Johnson</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                            <span id="viewStatus" class="inline-block px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-[#713F12] dark:text-yellow-100">Pending</span>
                        </div>
                    </div>

                    <!-- Case Details -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Case Type</label>
                            <p class="text-gray-800 dark:text-gray-100 font-medium" id="viewCaseType">Tardiness</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Assigned To</label>
                            <p class="text-gray-800 dark:text-gray-100" id="viewAssignedTo">Ms. Parker</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Date Reported</label>
                        <p class="text-gray-800 dark:text-gray-100" id="viewDateReported">Oct 12, 2023</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Description</label>
                        <div class="bg-gray-50 dark:bg-slate-800 p-4 rounded-lg border border-gray-200 dark:border-slate-700">
                            <p class="text-gray-700 dark:text-gray-300" id="viewDescription">Student was late to class for the third time this month.</p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Notes</label>
                        <div class="bg-gray-50 dark:bg-slate-800 p-4 rounded-lg border border-gray-200 dark:border-slate-700">
                            <p class="text-gray-700 dark:text-gray-300" id="viewNotes">Parent has been contacted via email on Oct 11.</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex justify-end space-x-3 mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <button onclick="openEditModalFromView()" class="px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200">
                        Edit Case
                    </button>
                    <button onclick="closeViewModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Case Modal -->
    <div id="editCaseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100" id="editModalTitle">Edit Case: C-1092</h2>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="editCaseForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Student</label>
                            <input type="text" id="editStudent" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Case ID</label>
                            <input type="text" id="editCaseId" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-100">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Case Type</label>
                            <select id="editCaseType" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                                <option value="Tardiness">Tardiness</option>
                                <option value="Dress Code">Dress Code</option>
                                <option value="Classroom Disruption">Classroom Disruption</option>
                                <option value="Academic Dishonesty">Academic Dishonesty</option>
                                <option value="Attendance">Attendance</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Date Reported</label>
                            <input type="date" id="editDateReported" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Status</label>
                        <select id="editStatus" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                            <option value="Pending">Pending</option>
                            <option value="Under Review">Under Review</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Escalated">Escalated</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Description</label>
                        <textarea id="editDescription" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Notes</label>
                        <textarea id="editNotes" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </form>

                <!-- Modal Actions -->
                <div class="flex justify-end space-x-3 mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <button onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors duration-200">
                        Cancel
                    </button>
                    <button onclick="saveEditCase()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Case Modal -->
    <div id="addCaseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Add New Case</h2>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="addCaseForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Student Name <span class="text-red-500">*</span></label>
                            <input type="text" id="addStudent" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Student ID <span class="text-red-500">*</span></label>
                            <input type="text" id="addStudentId" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Case Type <span class="text-red-500">*</span></label>
                            <select id="addCaseType" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Type</option>
                                <option value="Tardiness">Tardiness</option>
                                <option value="Dress Code">Dress Code</option>
                                <option value="Classroom Disruption">Classroom Disruption</option>
                                <option value="Academic Dishonesty">Academic Dishonesty</option>
                                <option value="Attendance">Attendance</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Date Reported <span class="text-red-500">*</span></label>
                            <input type="date" id="addDateReported" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Status <span class="text-red-500">*</span></label>
                            <select id="addStatus" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                                <option value="Pending">Pending</option>
                                <option value="Under Review">Under Review</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Escalated">Escalated</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Assigned To <span class="text-red-500">*</span></label>
                            <select id="addAssignedTo" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Officer</option>
                                <option value="Ms. Parker">Ms. Parker</option>
                                <option value="Mr. Thompson">Mr. Thompson</option>
                                <option value="Principal Davis">Principal Davis</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Description <span class="text-red-500">*</span></label>
                        <textarea id="addDescription" rows="3" required class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Notes</label>
                        <textarea id="addNotes" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </form>

                <!-- Modal Actions -->
                <div class="flex justify-end space-x-3 mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <button onclick="closeAddModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors duration-200">
                        Cancel
                    </button>
                    <button onclick="saveAddCase()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        Add Case
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sample data - Replace with actual database calls
        const allCases = [
            { id: 'C-1092', student: 'Alex Johnson', type: 'Tardiness', date: 'Oct 12, 2023', status: 'Pending', assignedTo: 'Ms. Parker', statusColor: 'yellow', description: 'Student was late to class for the third time this month.', notes: 'Parent has been contacted via email on Oct 11.' },
            { id: 'C-1091', student: 'Maria Garcia', type: 'Dress Code', date: 'Oct 11, 2023', status: 'Resolved', assignedTo: 'Mr. Thompson', statusColor: 'green', description: 'Student not wearing proper uniform.', notes: 'Issue resolved after parent meeting.' },
            { id: 'C-1090', student: 'James Smith', type: 'Classroom Disruption', date: 'Oct 10, 2023', status: 'Under Review', assignedTo: 'Ms. Parker', statusColor: 'blue', description: 'Disruptive behavior during class hours.', notes: 'Second offense this semester.' },
            { id: 'C-1089', student: 'Emma Wilson', type: 'Academic Dishonesty', date: 'Oct 9, 2023', status: 'Escalated', assignedTo: 'Principal Davis', statusColor: 'red', description: 'Caught cheating during exam.', notes: 'Matter escalated to principal.' },
            { id: 'C-1088', student: 'Daniel Lee', type: 'Attendance', date: 'Oct 8, 2023', status: 'Resolved', assignedTo: 'Mr. Thompson', statusColor: 'green', description: 'Multiple unexcused absences.', notes: 'Medical certificate provided.' },
            { id: 'C-1087', student: 'Sophia Brown', type: 'Tardiness', date: 'Oct 7, 2023', status: 'Pending', assignedTo: 'Ms. Parker', statusColor: 'yellow', description: 'Late to first period class.', notes: 'First offense.' },
            { id: 'C-1086', student: 'Michael Wang', type: 'Dress Code', date: 'Oct 6, 2023', status: 'Resolved', assignedTo: 'Mr. Thompson', statusColor: 'green', description: 'Improper footwear.', notes: 'Corrected immediately.' },
            { id: 'C-1085', student: 'Olivia Martinez', type: 'Classroom Disruption', date: 'Oct 5, 2023', status: 'Under Review', assignedTo: 'Ms. Parker', statusColor: 'blue', description: 'Talking during lecture.', notes: 'Warning issued.' }
        ];

        let filteredCases = [...allCases];
        let currentStatus = 'current';
        let currentCaseId = null;

        const statusColors = {
            yellow: 'bg-yellow-100 text-yellow-800 dark:bg-[#713F12] dark:text-yellow-100',
            green: 'bg-green-100 text-green-800 dark:bg-[#14532D] dark:text-green-100',
            blue: 'bg-blue-100 text-blue-800 dark:bg-[#1E3A8A] dark:text-blue-100',
            red: 'bg-red-100 text-red-800 dark:bg-[#7F1D1D] dark:text-red-100'
        };

        // Render cases table
        function renderCases(){ 
            const tbody = document.getElementById('casesTableBody');
            
            if (filteredCases.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            No cases found matching your filters.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = filteredCases.map(case_ => `
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors duration-200">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">${case_.id}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-300 dark:bg-slate-600 rounded-full mr-3"></div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${case_.student}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${case_.type}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${case_.date}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 text-xs font-medium rounded-full ${statusColors[case_.statusColor]}">${case_.status}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${case_.assignedTo}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button onclick="viewCase('${case_.id}')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-3 font-medium">View</button>
                        <button onclick="editCase('${case_.id}')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">Edit</button>
                    </td>
                </tr>
            `).join('');
        }

        // Filter by status (Current/Archived)
        function filterByStatus(status) {
            currentStatus = status;
            const currentTab = document.getElementById('currentTab');
            const archivedTab = document.getElementById('archivedTab');

            if (status === 'current') {
                currentTab.className = 'px-4 py-2 rounded-lg font-medium transition-colors duration-200 bg-blue-600 text-white';
                archivedTab.className = 'px-4 py-2 rounded-lg font-medium transition-colors duration-200 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300';
            } else {
                archivedTab.className = 'px-4 py-2 rounded-lg font-medium transition-colors duration-200 bg-blue-600 text-white';
                currentTab.className = 'px-4 py-2 rounded-lg font-medium transition-colors duration-200 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300';
            }

            filterCases();
        }

        // Apply all filters
        function filterCases() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;

            filteredCases = allCases.filter(case_ => {
                const matchesSearch = case_.student.toLowerCase().includes(searchTerm) || 
                                     case_.id.toLowerCase().includes(searchTerm) ||
                                     case_.type.toLowerCase().includes(searchTerm);
                const matchesType = !typeFilter || case_.type === typeFilter;
                const matchesStatus = !statusFilter || case_.status === statusFilter;

                return matchesSearch && matchesType && matchesStatus;
            });

            renderCases();
            updatePaginationInfo();
        }

        // Sort cases
        function sortCases() {
            const sortValue = document.getElementById('sortFilter').value;

            if (sortValue === 'newest') {
                filteredCases.sort((a, b) => new Date(b.date) - new Date(a.date));
            } else if (sortValue === 'oldest') {
                filteredCases.sort((a, b) => new Date(a.date) - new Date(b.date));
            } else if (sortValue === 'student') {
                filteredCases.sort((a, b) => a.student.localeCompare(b.student));
            }

            renderCases();
        }

        // Update pagination info
        function updatePaginationInfo() {
            const info = document.getElementById('paginationInfo');
            info.textContent = `Showing 1-${Math.min(8, filteredCases.length)} of ${filteredCases.length} cases`;
        }

        // View Case Modal Functions
        function viewCase(id) {
            currentCaseId = id;
            const caseData = allCases.find(c => c.id === id);
            if (!caseData) return;

            document.getElementById('viewModalTitle').textContent = `Case Details: ${caseData.id}`;
            document.getElementById('viewStudentName').textContent = caseData.student;
            document.getElementById('viewStatus').textContent = caseData.status;
            document.getElementById('viewStatus').className = `inline-block px-3 py-1 text-xs font-medium rounded-full ${statusColors[caseData.statusColor]}`;
            document.getElementById('viewCaseType').textContent = caseData.type;
            document.getElementById('viewAssignedTo').textContent = caseData.assignedTo;
            document.getElementById('viewDateReported').textContent = caseData.date;
            document.getElementById('viewDescription').textContent = caseData.description;
            document.getElementById('viewNotes').textContent = caseData.notes;

            document.getElementById('viewCaseModal').classList.remove('hidden');
        }

        function closeViewModal() {
            document.getElementById('viewCaseModal').classList.add('hidden');
            currentCaseId = null;
        }

        function openEditModalFromView() {
            closeViewModal();
            editCase(currentCaseId);
        }

        // Edit Case Modal Functions
        function editCase(id) {
            currentCaseId = id;
            const caseData = allCases.find(c => c.id === id);
            if (!caseData) return;

            document.getElementById('editModalTitle').textContent = `Edit Case: ${caseData.id}`;
            document.getElementById('editStudent').value = caseData.student;
            document.getElementById('editCaseId').value = caseData.id;
            document.getElementById('editCaseType').value = caseData.type;
            
            // Convert date format for input field
            const dateParts = caseData.date.split(' ');
            const months = { 'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04', 'May': '05', 'Jun': '06', 
                           'Jul': '07', 'Aug': '08', 'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12' };
            const dateValue = `${dateParts[2]}-${months[dateParts[0]]}-${dateParts[1].replace(',', '').padStart(2, '0')}`;
            document.getElementById('editDateReported').value = dateValue;
            
            document.getElementById('editStatus').value = caseData.status;
            document.getElementById('editDescription').value = caseData.description;
            document.getElementById('editNotes').value = caseData.notes;

            document.getElementById('editCaseModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editCaseModal').classList.add('hidden');
            currentCaseId = null;
        }

        function saveEditCase() {
            // Get form values
            const caseType = document.getElementById('editCaseType').value;
            const dateReported = document.getElementById('editDateReported').value;
            const status = document.getElementById('editStatus').value;
            const description = document.getElementById('editDescription').value;
            const notes = document.getElementById('editNotes').value;

            // Find and update the case
            const caseIndex = allCases.findIndex(c => c.id === currentCaseId);
            if (caseIndex !== -1) {
                allCases[caseIndex].type = caseType;
                allCases[caseIndex].status = status;
                allCases[caseIndex].description = description;
                allCases[caseIndex].notes = notes;
                
                // Update status color
                const statusColorMap = {
                    'Pending': 'yellow',
                    'Under Review': 'blue',
                    'Resolved': 'green',
                    'Escalated': 'red'
                };
                allCases[caseIndex].statusColor = statusColorMap[status];
            }

            // In production, send data to backend here
            console.log('Saving case:', currentCaseId, { caseType, dateReported, status, description, notes });

            // Show success message (you can implement a toast notification)
            alert('Case updated successfully!');

            closeEditModal();
            filterCases(); // Refresh the table
        }

        // Add Case Modal Functions
        function openAddCaseModal() {
            // Reset form
            document.getElementById('addCaseForm').reset();
            
            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('addDateReported').value = today;
            
            document.getElementById('addCaseModal').classList.remove('hidden');
        }

        function closeAddModal() {
            document.getElementById('addCaseModal').classList.add('hidden');
        }

        function saveAddCase() {
            // Get form values
            const student = document.getElementById('addStudent').value;
            const studentId = document.getElementById('addStudentId').value;
            const caseType = document.getElementById('addCaseType').value;
            const dateReported = document.getElementById('addDateReported').value;
            const status = document.getElementById('addStatus').value;
            const assignedTo = document.getElementById('addAssignedTo').value;
            const description = document.getElementById('addDescription').value;
            const notes = document.getElementById('addNotes').value;

            // Validate required fields
            if (!student || !studentId || !caseType || !dateReported || !assignedTo || !description) {
                alert('Please fill in all required fields!');
                return;
            }

            // Generate new case ID
            const newCaseId = `C-${1093 + allCases.length}`;

            // Map status to color
            const statusColorMap = {
                'Pending': 'yellow',
                'Under Review': 'blue',
                'Resolved': 'green',
                'Escalated': 'red'
            };

            // Create new case object
            const newCase = {
                id: newCaseId,
                student: student,
                type: caseType,
                date: new Date(dateReported).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                status: status,
                assignedTo: assignedTo,
                statusColor: statusColorMap[status],
                description: description,
                notes: notes || 'No notes added.'
            };

            // Add to cases array
            allCases.unshift(newCase); // Add to beginning of array

            // In production, send data to backend here
            console.log('Adding new case:', newCase);

            // Show success message
            alert('Case added successfully!');

            closeAddModal();
            filterCases(); // Refresh the table
        }

        // Toggle filters dropdown (placeholder)
        function toggleFiltersDropdown() {
            alert('Advanced filters feature coming soon!');
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            renderCases();
            updatePaginationInfo();
            
            // Set today's date as max for date inputs
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('editDateReported').setAttribute('max', today);
            document.getElementById('addDateReported').setAttribute('max', today);
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewCaseModal');
            const editModal = document.getElementById('editCaseModal');
            const addModal = document.getElementById('addCaseModal');
            
            if (event.target === viewModal) {
                closeViewModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === addModal) {
                closeAddModal();
            }
        }
    </script>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
    <script src="/PrototypeDO/assets/js/exit_on_load.js"></script>
</body>

</html>