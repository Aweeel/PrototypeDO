<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Page metadata
$pageTitle = "Dashboard"; // 👈 Replace dynamically (e.g., “Cases”)
$adminName = $_SESSION['admin_name'] ?? 'student'; 
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
                <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-slate-700 rounded-lg shadow-sm p-8">
                    <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-100">
                        <?php echo htmlspecialchars($pageTitle); ?>
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        This is a placeholder for the <strong><?php echo htmlspecialchars($pageTitle); ?></strong> module.
                    </p>

                    <div class="mt-6 flex items-center justify-center">
                        <button
                            class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 dark:hover:bg-blue-500 transition transform hover:-translate-y-0.5 shadow-md hover:shadow-lg">
                            Coming Soon
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>
