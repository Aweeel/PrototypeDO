<?php
require_once __DIR__ . '/functions.php';
$unreadNotifications = [];
if (isset($_SESSION['user_id'])) {
    $unreadNotifications = getUnreadNotifications($_SESSION['user_id']) ?? [];
}
$unreadCount = count($unreadNotifications);

// Get consistent user name for header - use database value to ensure consistency
if (!isset($adminName) || empty($adminName)) {
    $adminName = getFormattedUserName();
}

// Check for default password warning
// Don't show on user profile page or if already shown in this login session
$currentPage = basename($_SERVER['PHP_SELF']);
$showPasswordWarning = isset($_SESSION['has_default_password']) && $_SESSION['has_default_password'] === true && $currentPage !== 'userProfile.php' && !isset($_SESSION['password_warning_modal_shown']);
?>

<header
    class="fixed top-0 left-64 right-0 z-40
           bg-white dark:bg-[#111827]
           border-b border-gray-200 dark:border-gray-700
           px-8 py-4
           transition-all duration-300">
    
    <!-- Global Notifications System -->
    <script src="/PrototypeDO/assets/js/notifications.js"></script>
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

            <!-- Admin Info - Profile Link -->
            <a href="/PrototypeDO/modules/shared/userProfile.php" 
               class="flex items-center space-x-2 hover:opacity-80 transition-opacity active:scale-95 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                <span class="text-m text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                    <?php echo isset($adminName) ? htmlspecialchars($adminName) : 'User'; ?>
                </span>
                <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center cursor-pointer">
                    <span class="text-xs font-bold text-white">
                        <?php 
                        $adminName = isset($adminName) ? htmlspecialchars($adminName) : 'U';
                        $names = explode(' ', $adminName);
                        $initials = strtoupper(substr($names[0], 0, 1));
                        if (isset($names[1])) {
                            $initials .= strtoupper(substr($names[1], 0, 1));
                        }
                        echo $initials;
                        ?>
                    </span>
                </div>
            </a>
        </div>
    </div>
</header>

<!-- Default Password Warning Modal -->
<?php if ($showPasswordWarning): ?>
<div id="passwordWarningModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-slate-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Security Alert</h2>
            </div>
            <button onclick="closePasswordWarningModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-gray-700 dark:text-gray-300">
                You are currently using the default password. <br>
                Please change your password immediately.
            </p>
        </div>
        <div class="flex gap-3 p-6 border-t border-gray-200 dark:border-slate-700">
            <button onclick="closePasswordWarningModal()" 
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                Dismiss
            </button>
            <a href="/PrototypeDO/modules/shared/userProfile.php#change-password" 
                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium text-center">
                Change Password
            </a>
        </div>
    </div>
</div>

<script>
    // Mark the password warning modal as shown in this login session
    async function markPasswordWarningAsShown() {
        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'markPasswordWarningShown');

            await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
        } catch (error) {
            console.error('Error marking password warning as shown:', error);
        }
    }

    // Mark as shown when modal first appears
    window.addEventListener('DOMContentLoaded', function() {
        markPasswordWarningAsShown();
    });

    function closePasswordWarningModal() {
        const modal = document.getElementById('passwordWarningModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('passwordWarningModal');
        if (modal && event.target === modal) {
            closePasswordWarningModal();
        }
    });

    // Close modal with ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closePasswordWarningModal();
        }
    });
</script>
<?php endif; ?>

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
                // If there's a case ID, redirect to the case details based on user role
                if (relatedId) {
                    // Get the user role from the HTML data attribute or session
                    const userRole = document.documentElement.getAttribute('data-user-role') || '<?php echo $_SESSION['user_role'] ?? 'discipline_office'; ?>';
                    
                    // Small delay to allow notification to be stored
                    setTimeout(() => {
                        // Check if it's a password reset notification
                        if (relatedId.startsWith('password_reset:')) {
                            // Navigate to admin users page for password reset requests
                            window.location.href = `/PrototypeDO/modules/super-admin/adminUsers.php`;
                        } else if (userRole === 'student') {
                            window.location.href = `/PrototypeDO/modules/student/studentCases.php?case_id=${encodeURIComponent(relatedId)}`;
                        } else {
                            window.location.href = `/PrototypeDO/modules/do/cases.php?caseId=${encodeURIComponent(relatedId)}`;
                        }
                    }, 100);
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
