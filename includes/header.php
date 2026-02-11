<?php
require_once __DIR__ . '/functions.php';
$unreadNotifications = [];
if (isset($_SESSION['user_id'])) {
    $unreadNotifications = getUnreadNotifications($_SESSION['user_id']) ?? [];
}
$unreadCount = count($unreadNotifications);
?>

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
            <div class="relative">
                <button
                    onclick="toggleNotificationPanel()"
                    class="p-2 rounded-full transition-colors duration-300 hover:bg-gray-200 dark:hover:bg-slate-700 active:scale-95 relative">
                    <div class="relative w-6 h-6 flex items-center justify-center">
                        <img src="../../assets/images/icons/notification-dark-icon.png" alt="Notification icon"
                            class="absolute inset-0 w-6 h-6 block dark:hidden">
                        <img src="../../assets/images/icons/notification-light-icon.png"
                            alt="Notification icon dark"
                            class="absolute inset-0 w-6 h-6 hidden dark:inline">
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <span class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                            <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- Notification Panel -->
                <div id="notificationPanel" class="hidden absolute right-0 mt-2 w-96 bg-white dark:bg-[#111827] rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-96 overflow-y-auto">
                    <div class="sticky top-0 bg-white dark:bg-[#111827] border-b border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Notifications</h3>
                    </div>
                    
                    <?php if (empty($unreadNotifications)): ?>
                        <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                            No new notifications
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($unreadNotifications as $notification): ?>
                                <div class="p-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors" 
                                     onclick="markAsRead(<?php echo $notification['notification_id']; ?>, '<?php echo htmlspecialchars($notification['related_id'] ?? ''); ?>')">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </p>
                                            <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">
                                                <?php echo htmlspecialchars(substr($notification['message'], 0, 100)) . (strlen($notification['message']) > 100 ? '...' : ''); ?>
                                            </p>
                                            <p class="text-gray-500 dark:text-gray-500 text-xs mt-1">
                                                <?php echo date('M d, H:i', strtotime($notification['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-1 ml-2 flex-shrink-0"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

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

<script>
    function toggleNotificationPanel() {
        const panel = document.getElementById('notificationPanel');
        panel.classList.toggle('hidden');
    }

    // Close notification panel when clicking outside
    document.addEventListener('click', function(event) {
        const panel = document.getElementById('notificationPanel');
        const notificationArea = panel?.closest('div');
        if (!panel?.contains(event.target) && !event.target.closest('button')?.onclick?.toString().includes('toggleNotificationPanel')) {
            panel?.classList.add('hidden');
        }
    });

    async function markAsRead(notificationId, relatedId) {
        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'markNotificationAsRead');
            formData.append('notificationId', notificationId);

            const response = await fetch('/PrototypeDO/modules/do/doDashboard.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                // If there's a case ID, redirect to the case details
                if (relatedId) {
                    window.location.href = `/PrototypeDO/modules/do/cases.php?caseId=${encodeURIComponent(relatedId)}`;
                } else {
                    // Otherwise just reload the page
                    setTimeout(() => location.reload(), 300);
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
</script>
