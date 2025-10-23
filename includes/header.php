<header
    class="fixed top-0 left-64 right-0 z-40
           bg-white dark:bg-[#111827]
           border-b border-gray-200 dark:border-gray-700
           px-8 py-4
           transition-all duration-300">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">
            <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?>
        </h1>

        <!-- Right Controls -->
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
            <button
                class="p-2 rounded-full transition-colors duration-300 hover:bg-gray-200 dark:hover:bg-slate-700 active:scale-95">
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
                <span class="text-m text-gray-600 dark:text-gray-400">
                    <?php echo isset($adminName) ? htmlspecialchars($adminName) : 'Admin Name'; ?>
                </span>
                <div class="w-8 h-8 bg-gray-300 rounded-full"></div>
            </div>
        </div>
    </div>
</header>
