<?php
require_once __DIR__ . '/../../includes/auth_check.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office - [Module Name]</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        };
    </script>

    <!-- Theme Handling -->
    <script>
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

    <!-- Sidebar -->
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- Main Layout -->
    <div class="flex h-screen">
        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto ml-64">
            
            <!-- Header -->
            <header class="bg-white dark:bg-[#111827] border-b border-gray-200 dark:border-gray-700 px-8 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                        [Module Name]
                    </h1>

                    <div class="flex items-center space-x-3">
                        <!-- Theme Toggle -->
                        <button onclick="toggleDarkMode()" id="theme-toggle"
                            class="p-1 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-full transition transform hover:scale-105">
                            <div class="w-7 h-7 relative flex items-center justify-center">
                                <img id="theme-toggle-light-icon" src="../../assets/images/icons/dark-mode-icon.png"
                                    alt="Light Mode Icon" class="absolute inset-0 w-7 h-7 dark:hidden" />
                                <img id="theme-toggle-dark-icon" src="../../assets/images/icons/light-mode-icon.png"
                                    alt="Dark Mode Icon" class="absolute inset-0 w-7 h-7 hidden dark:inline" />
                            </div>
                        </button>

                        <!-- Notifications -->
                        <button class="p-2 rounded-full transition-colors duration-300 hover:bg-gray-200 dark:hover:bg-slate-700 active:scale-95">
                            <div class="relative w-6 h-6 flex items-center justify-center">
                                <img src="../../assets/images/icons/notification-dark-icon.png" alt="Notification icon"
                                    class="absolute inset-0 w-6 h-6 block dark:hidden">
                                <img src="../../assets/images/icons/notification-light-icon.png"
                                    alt="Notification icon dark"
                                    class="absolute inset-0 w-6 h-6 hidden dark:inline">
                            </div>
                        </button>

                        <!-- Admin Info -->
                        <div class="flex items-center space-x-2">
                            <span class="text-m text-gray-600 dark:text-gray-400">Admin Name</span>
                            <div class="w-8 h-8 bg-gray-300 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- MODULE CONTENT -->
            <main class="p-8 min-h-screen transition-colors duration-300">
                <!-- Replace this section per module -->
                
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-8">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">[Section Title]</h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        This is a placeholder area. Replace with the main layout or content of this module.
                    </p>

                    <!-- Example content block -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-gray-100 dark:bg-slate-800 p-6 rounded-lg text-center">
                            <p class="text-gray-700 dark:text-gray-300">Card 1 Placeholder</p>
                        </div>
                        <div class="bg-gray-100 dark:bg-slate-800 p-6 rounded-lg text-center">
                            <p class="text-gray-700 dark:text-gray-300">Card 2 Placeholder</p>
                        </div>
                        <div class="bg-gray-100 dark:bg-slate-800 p-6 rounded-lg text-center">
                            <p class="text-gray-700 dark:text-gray-300">Card 3 Placeholder</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>
