<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = "Student Report";
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

<body
    class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">
    
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <?php
            include __DIR__ . '/../../includes/header.php';
            ?>

            <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                <!-- Page Title -->
                <h1 class="text-2xl font-semibold mb-6 text-gray-800 dark:text-gray-100">
                    <?php echo htmlspecialchars($pageTitle); ?>
                </h1>

                <!-- Student Report Form -->
                <form action="save_case.php" method="POST" enctype="multipart/form-data"
                    class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Student Information -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4 border-b border-gray-300 dark:border-slate-600 pb-2">
                                Student Information
                            </h2>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm mb-1">Student ID</label>
                                    <input type="text" name="student_number" placeholder="e.g., 02000372341" required
                                        class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm mb-1">Student Name</label>
                                    <input type="text" name="student_name" placeholder="Enter student name..." required
                                        class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm mb-1">Location</label>
                                    <input type="text" name="location" placeholder="Where did it happen?"
                                        class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm mb-1">Witnesses (if any)</label>
                                    <input type="text" name="witnesses" placeholder="Enter witness names..."
                                        class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm mb-1">Offense Type</label>
                                    <select name="severity" required
                                        class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                                        <option value="Minor">Minor</option>
                                        <option value="Major" selected>Major</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Incident Details -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4 border-b border-gray-300 dark:border-slate-600 pb-2">
                                Incident Details
                            </h2>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm mb-1">Incident Type</label>
                                    <select name="incident_type" required
                                        class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                                        <option value="">Select Incident Type</option>
                                        <option value="Uniform Violation">Uniform Violation</option>
                                        <option value="Disrespect">Disrespect</option>
                                        <option value="Late/Absent">Late / Absent</option>
                                        <option value="Vandalism">Vandalism</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm mb-1">Date</label>
                                        <input type="date" name="incident_date"
                                            class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm mb-1">Time</label>
                                        <input type="time" name="incident_time"
                                            class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm mb-1">Description of Incident</label>
                                    <textarea name="description" rows="3" placeholder="Describe what happened..."
                                        class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm mb-1">Action Taken (if any)</label>
                                    <textarea name="teacher_action" rows="2" placeholder="Describe actions taken..."
                                        class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="mt-8">
                        <label class="block text-sm mb-1">Attachments (optional)</label>
                        <div class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-lg h-40 flex flex-col justify-center items-center text-gray-400 hover:border-blue-500 transition">
                            <input type="file" name="attachment" accept=".png,.jpg,.jpeg,.pdf" class="hidden" id="fileInput">
                            <label for="fileInput" class="cursor-pointer hover:text-blue-400 transition text-center">
                                Upload a file or drag and drop<br>
                                <span class="text-sm text-gray-500">(PNG, JPG, PDF up to 10MB)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Hidden Status -->
                    <input type="hidden" name="status" value="Pending">

                    <!-- Buttons -->
                    <div class="flex justify-end gap-3 pt-8">
                        <a href="cases.php"
                            class="px-4 py-2 bg-gray-600 text-gray-200 rounded-lg hover:bg-gray-500 transition">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition">
                            Submit Report
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>
