<?php
require_once __DIR__ . '/functions.php';

if (!function_exists('initializeGlobalTheme')) {
    function initializeGlobalTheme() {
        echo '<script>
            (function () {
                document.documentElement.classList.toggle(
                    "dark",
                    localStorage.getItem("theme") === "dark"
                );
                window.toggleDarkMode = window.toggleDarkMode || function () {
                    const isDark = !document.documentElement.classList.contains("dark");
                    document.documentElement.classList.toggle("dark", isDark);
                    localStorage.setItem("theme", isDark ? "dark" : "light");
                    document.dispatchEvent(new CustomEvent("themechange", { detail: { isDark } }));
                };
            })();
        </script>';
    }
}

// Include Terms of Service (shows once per session)
require_once __DIR__ . '/terms.php';

// Include Password Warning Modal
require_once __DIR__ . '/password_warning.php';

if (isset($_SESSION['user_id']) && (($_SESSION['user_role'] ?? '') === 'student')) {
    $headerStudentRecord = getStudentRecordForUser($_SESSION['user_id']);
    if (!empty($headerStudentRecord['student_id'])) {
        $headerStudentId = (string) $headerStudentRecord['student_id'];
        $headerSyncSessionKey = 'student_community_service_overdue_sync_date';
        $headerToday = date('Y-m-d');

        if (!isset($_SESSION[$headerSyncSessionKey]) || !is_array($_SESSION[$headerSyncSessionKey])) {
            $_SESSION[$headerSyncSessionKey] = [];
        }

        if (($_SESSION[$headerSyncSessionKey][$headerStudentId] ?? null) !== $headerToday) {
            // Mark before syncing so repeated page loads in the same session/day
            // do not repeatedly trigger potentially expensive DB work.
            $_SESSION[$headerSyncSessionKey][$headerStudentId] = $headerToday;
            syncStudentCommunityServiceOverdueNotifications($headerStudentId);
        }
    }
}

$headerRole = $_SESSION['user_role'] ?? ($_SESSION['user']['role'] ?? '');
$headerTeacherSubrole = $_SESSION['teacher_subrole'] ?? ($_SESSION['user']['teacher_subrole'] ?? null);
$showNotifications = $headerRole !== 'security'
    && ($headerRole !== 'teacher' || $headerTeacherSubrole === 'department_head');

$unreadNotifications = [];
if ($showNotifications && isset($_SESSION['user_id'])) {
    try {
        $unreadNotifications = getUnreadNotifications($_SESSION['user_id']) ?? [];
    } catch (Throwable $e) {
        error_log('Notification lookup failed while rendering header: ' . $e->getMessage());
    }
}
$unreadCount = count($unreadNotifications);
$globalBannerEnabled = strtolower(trim((string)getSystemSetting('global_banner_enabled', 'disabled'))) === 'enabled';
$globalBannerText = getSystemSetting('global_banner_text', '');

// Get consistent user name for header - use database value to ensure consistency
if (!isset($adminName) || empty($adminName)) {
    $adminName = getFormattedUserName();
}
?>

<?php initializeGlobalTheme(); ?>
<script>
    window.APP_BASE_URL = <?= json_encode(BASE_URL) ?>;
    window.ASSETS_URL = <?= json_encode(ASSETS_URL) ?>;
    window.appUrl = function (path) {
        return window.APP_BASE_URL + path;
    };
    window.REALTIME_CONFIG = {
        notificationsUrl: <?= json_encode(BASE_URL . '/modules/shared/realtime.php') ?>,
        initialNotificationIds: <?= json_encode(array_map(static function ($notification) {
            return (int)($notification['notification_id'] ?? 0);
        }, $unreadNotifications)) ?>
    };
</script>

<header
    class="fixed top-0 left-0 md:left-64 right-0 z-30
           bg-white dark:bg-[#111827]
           border-b border-gray-200 dark:border-gray-700
           px-4 sm:px-6 md:px-8 py-3 md:py-4
           transition-all duration-300">
    
    <!-- Global Notifications System -->
    <script src="<?= htmlspecialchars(ASSETS_URL) ?>/js/notifications.js"></script>
    <script src="<?= htmlspecialchars(ASSETS_URL) ?>/js/confirmation.js"></script>
    <script src="<?= htmlspecialchars(ASSETS_URL) ?>/js/realtime.js?v=20261020-2"></script>
    <script src="<?= htmlspecialchars(ASSETS_URL) ?>/js/preventDoubleTap.js"></script>
    <script src="<?= htmlspecialchars(ASSETS_URL) ?>/js/validation.js?v=<?= filemtime(__DIR__ . '/../assets/js/validation.js') ?>"></script>
    <div class="flex items-center justify-between">
        
        <!-- Left Side: Mobile Hamburger & Page Title -->
        <div class="flex items-center space-x-3">
            <!-- Mobile Hamburger Menu Button -->
            <button type="button" 
                    onclick="toggleSidebar()" 
                    class="md:hidden p-1.5 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg focus:outline-none transition-colors"
                    aria-label="Toggle Sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Dynamic Page Title -->
            <h1 class="text-lg sm:text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100 truncate max-w-[180px] sm:max-w-xs md:max-w-none">
                <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?>
            </h1>
        </div>

        <!-- Right Controls -->
        <div class="flex items-center space-x-2 sm:space-x-3">
            <!-- Theme Toggle -->
            <button onclick="toggleDarkMode()" id="theme-toggle"
                class="w-9 h-9 flex-shrink-0 p-1 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-full transition transform hover:scale-105">
                <div class="w-7 h-7 relative flex items-center justify-center">
                    <img id="theme-toggle-light-icon" src="../../assets/images/icons/dark-mode-icon.png"
                        alt="Light Mode Icon" class="absolute inset-0 w-7 h-7 dark:hidden" />
                    <img id="theme-toggle-dark-icon" src="../../assets/images/icons/light-mode-icon.png"
                        alt="Dark Mode Icon" class="absolute inset-0 w-7 h-7 hidden dark:inline" />
                </div>
            </button>

            <div class="w-10 h-10 flex-shrink-0 flex items-center justify-center relative">
            <?php if ($showNotifications): ?>
            <!-- Notifications -->
            <div class="relative">
                <button
                    onclick="toggleNotificationPanel()"
                    class="w-10 h-10 p-2 rounded-full transition-colors duration-300 hover:bg-gray-200 dark:hover:bg-slate-700 active:scale-95 relative">
                    <div class="relative w-6 h-6 flex items-center justify-center">
                        <img src="../../assets/images/icons/notification-dark-icon.png" alt="Notification icon"
                            class="absolute inset-0 w-6 h-6 block dark:hidden">
                        <img src="../../assets/images/icons/notification-light-icon.png"
                            alt="Notification icon dark"
                            class="absolute inset-0 w-6 h-6 hidden dark:inline">
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <span id="notificationBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                            <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                        </span>
                    <?php else: ?>
                        <span id="notificationBadge" class="hidden absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 items-center justify-center">0</span>
                    <?php endif; ?>
                </button>

                <!-- Notification Panel -->
                <div id="notificationPanel" class="hidden absolute right-0 mt-2 w-80 sm:w-96 max-w-[calc(100vw-1rem)] bg-white dark:bg-[#111827] rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-96 overflow-y-auto">
                    <div class="sticky top-0 bg-white dark:bg-[#111827] border-b border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Notifications</h3>
                    </div>
                    
                    <div id="notificationList">
                    <?php if (empty($unreadNotifications)): ?>
                        <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                            No new notifications
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($unreadNotifications as $notification): ?>
                                  <?php $isPasswordResetNotification = ($notification['type'] ?? '') === 'password_reset_request'; ?>
                                  <div class="notification-item p-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors"
                                       data-notification-id="<?php echo (int)$notification['notification_id']; ?>"
                                       data-related-id="<?php echo htmlspecialchars((string)($notification['related_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       role="button" tabindex="0">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium text-sm <?php echo $isPasswordResetNotification ? 'text-red-700 dark:text-red-300' : 'text-gray-900 dark:text-gray-100'; ?>">
                                                <?php if ($isPasswordResetNotification): ?>
                                                    <span class="mr-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white" aria-label="Urgent">!</span>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </p>
                                            <p class="<?php echo $isPasswordResetNotification ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400'; ?> text-xs mt-1">
                                                <?php echo htmlspecialchars(substr($notification['message'], 0, 100)) . (strlen($notification['message']) > 100 ? '...' : ''); ?>
                                            </p>
                                            <p class="text-gray-500 dark:text-gray-500 text-xs mt-1">
                                                <?php echo htmlspecialchars(formatDatabaseDateTime($notification['created_at'], 'M d, g:i A')); ?>
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
            </div>
            <?php endif; ?>
            </div>

            <!-- Admin Info - Profile Link -->
            <a href="<?= htmlspecialchars(BASE_URL) ?>/modules/shared/userProfile.php" 
               class="w-10 sm:w-auto flex-shrink-0 flex items-center justify-end space-x-2 hover:opacity-80 transition-opacity active:scale-95 px-2 sm:px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                <span class="hidden sm:inline whitespace-nowrap text-m text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                    <?php echo isset($adminName) ? htmlspecialchars($adminName) : 'User'; ?>
                </span>
                <div class="w-8 h-8 min-w-8 flex-shrink-0 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center cursor-pointer">
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
<style>
    .app-content-scroll {
        box-sizing: border-box;
        height: calc(var(--app-viewport-height, 100vh) - var(--app-header-height));
        margin-top: var(--app-header-height);
        min-height: 0;
        overflow-y: auto;
    }

    .app-content-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .app-content-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(156, 163, 175, 0.5);
        border-radius: 4px;
    }

    .app-content-scroll::-webkit-scrollbar-thumb:hover {
        background-color: rgba(96, 165, 250, 0.8);
    }

    .app-content-scroll > main {
        min-height: 100%;
        padding-top: 1rem;
    }

    @media (min-width: 768px) {
        .app-content-scroll > main {
            padding-top: 2rem;
        }
    }

    @media (max-width: 639px) {
        #notificationPanel {
            position: fixed;
            top: 4.5rem;
            left: 0.5rem;
            right: 0.5rem;
            width: auto;
            max-width: none;
            margin-top: 0;
        }
    }

    @media (max-width: 767px) {
        .app-content-scroll > main.p-8 {
            padding-top: 1rem !important;
        }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fixedHeaders = document.querySelectorAll('header.fixed');
        const header = fixedHeaders[fixedHeaders.length - 1];
        if (!header) return;

        let scrollContainer = header.parentElement;
        while (scrollContainer && scrollContainer !== document.body) {
            const overflowY = window.getComputedStyle(scrollContainer).overflowY;
            if (overflowY === 'auto' || overflowY === 'scroll') break;
            scrollContainer = scrollContainer.parentElement;
        }
        if (!scrollContainer || scrollContainer === document.body) {
            scrollContainer = document.querySelector('main.overflow-y-auto');
        }
        if (!scrollContainer) return;

        const headerHeight = header.offsetHeight;
        document.documentElement.style.setProperty('--app-header-height', headerHeight + 'px');
        const handbookScroll = document.querySelector('.handbook-scroll');
        if (handbookScroll) {
            return;
        }
        scrollContainer.classList.add('app-content-scroll');
    });
</script>

<div id="globalAnnouncementBanner"
     data-banner-text="<?= htmlspecialchars($globalBannerText, ENT_QUOTES, 'UTF-8') ?>"
     data-banner-enabled="<?= $globalBannerEnabled && trim($globalBannerText) !== '' ? '1' : '0' ?>"
     class="fixed top-[4.5rem] left-0 lg:left-64 right-0 z-20 flex items-center gap-3 border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
     <?= $globalBannerEnabled && trim($globalBannerText) !== '' ? '' : 'hidden' ?>>
    <span class="min-w-0 flex-1 text-center break-words"><?= htmlspecialchars($globalBannerText) ?></span>
    <button id="minimizeGlobalAnnouncement"
            type="button"
            class="shrink-0 rounded p-1 text-xl leading-none text-amber-700 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:text-amber-200 dark:hover:bg-amber-900"
            aria-label="Minimize announcement"
            title="Minimize announcement">&minus;</button>
</div>
<button id="restoreGlobalAnnouncement"
        type="button"
        class="fixed top-[5.25rem] right-4 md:right-8 z-20 rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-900 shadow-sm hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100 dark:hover:bg-amber-900 sm:px-3"
        aria-label="Show announcement"
        title="Show announcement"
        hidden><svg class="h-4 w-4 sm:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 11 18-5v12L3 13v-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg><span class="hidden sm:inline">Show announcement</span></button>

<script src="<?= htmlspecialchars(ASSETS_URL) ?>/js/globalBanner.js?v=20261105-7"></script>
<script>
    function toggleNotificationPanel() {
        const panel = document.getElementById('notificationPanel');
        panel.classList.toggle('hidden');
    }

    // Close notification panel when clicking outside
    document.addEventListener('click', function(event) {
        const panel = document.getElementById('notificationPanel');
        if (panel && !panel.classList.contains('hidden')) {
            const isClickInside = panel.contains(event.target) || event.target.closest('button')?.getAttribute('onclick')?.includes('toggleNotificationPanel');
            if (!isClickInside) {
                panel.classList.add('hidden');
            }
        }
    });

    function removeNotificationFromPanel(notificationId) {
        const item = document.querySelector(`[data-notification-id="${Number(notificationId)}"]`);
        if (item) {
            item.remove();
        }

        const list = document.getElementById('notificationList');
        const remainingItems = list ? list.querySelectorAll('[data-notification-id]').length : 0;
        if (list && remainingItems === 0) {
            list.innerHTML = '<div class="p-6 text-center text-gray-500 dark:text-gray-400">No new notifications</div>';
        }

        const badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.textContent = remainingItems > 9 ? '9+' : String(remainingItems);
            badge.classList.toggle('hidden', remainingItems === 0);
        }
    }

    document.addEventListener('click', (event) => {
        const target = event.target;
        const item = target instanceof Element
            ? target.closest('#notificationList [data-notification-id]')
            : null;
        if (item) {
            event.preventDefault();
            window.markAsRead(item.dataset.notificationId, item.dataset.relatedId || '');
        }
    }, true);

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        const target = event.target;
        const item = target instanceof Element
            ? target.closest('#notificationList [data-notification-id]')
            : null;
        if (item) {
            event.preventDefault();
            window.markAsRead(item.dataset.notificationId, item.dataset.relatedId || '');
        }
    });

    window.markAsRead = async function markAsRead(notificationId, relatedId) {
        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'markNotificationAsRead');
            formData.append('notificationId', notificationId);

            const response = await fetch('<?= BASE_URL ?>/modules/shared/realtime.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || `Notification request failed (${response.status})`);
            }

            removeNotificationFromPanel(notificationId);
            
            if (relatedId) {
                    const userRole = document.documentElement.getAttribute('data-user-role') || '<?php echo $_SESSION['user_role'] ?? 'discipline_office'; ?>';
                    const isPortfolioNotification = relatedId.startsWith('community_service_submission:');
                    const isCommunityServiceEvent = relatedId.startsWith('community_service_deadline_extended:') || 
                                                   relatedId.startsWith('community_service_hours_added:') ||
                                                   relatedId.startsWith('community_service_checkin:') ||
                                                   relatedId.startsWith('community_service_checkout:') ||
                                                   relatedId.startsWith('community_service_overdue:');
                    let relatedCaseId = relatedId;
                    let relatedSanctionType = 'corrective';
                    if (isCommunityServiceEvent) {
                        const relatedParts = relatedId.split(':');
                        if (relatedParts.length >= 4) {
                            relatedCaseId = relatedParts[1];
                        } else if (relatedParts.length >= 3) {
                            relatedCaseId = relatedParts[1];
                        }
                    } else if (isPortfolioNotification) {
                        const relatedParts = relatedId.split(':');
                        if (relatedParts.length >= 3) {
                            relatedSanctionType = (relatedParts[1] === 'suspension') ? 'suspension' : 'corrective';
                            relatedCaseId = relatedParts.slice(2).join(':');
                        } else {
                            relatedCaseId = relatedParts.slice(1).join(':');
                        }
                    }
                    
                    setTimeout(() => {
                        if (relatedId.startsWith('password_reset:')) {
                            const userIdParts = relatedId.split(':');
                            const userId = userIdParts.length > 1 ? userIdParts[1] : null;
                            const url = userId ? `${window.APP_BASE_URL}/modules/super-admin/adminUsers.php?resetUser=${encodeURIComponent(userId)}` : `${window.APP_BASE_URL}/modules/super-admin/adminUsers.php`;
                            window.location.href = url;
                        } else if (relatedId.startsWith('hearing:')) {
                            const hearingParts = relatedId.split(':');
                            const hearingCaseId = hearingParts.length > 1 ? hearingParts[1] : null;
                            const hearingEventId = hearingParts.length > 2 ? hearingParts[2] : null;
                            const hearingDate = hearingParts.length > 3 ? hearingParts[3] : null;
                            const url = new URL(`${window.APP_BASE_URL}/modules/do/calendar.php`, window.location.origin);
                            url.searchParams.set('calendar_view', 'personal');
                            if (hearingEventId && hearingEventId !== 'new') {
                                url.searchParams.set('event_id', hearingEventId);
                            }
                            if (hearingDate) {
                                url.searchParams.set('event_date', hearingDate);
                            }
                            if (hearingCaseId) {
                                url.searchParams.set('case_id', hearingCaseId);
                            }
                            window.location.href = url.toString();
                        } else if (isPortfolioNotification && userRole === 'student') {
                            window.location.href = `${window.APP_BASE_URL}/modules/student/studentCases.php?case_id=${encodeURIComponent(relatedCaseId)}&highlightCase=1`;
                        } else if (isPortfolioNotification) {
                            window.location.href = `${window.APP_BASE_URL}/modules/do/cases.php?caseId=${encodeURIComponent(relatedCaseId)}&highlightCaseId=${encodeURIComponent(relatedCaseId)}&highlightCase=1&openCheckIn=1&sanctionType=${encodeURIComponent(relatedSanctionType)}`;
                        } else if (userRole === 'student') {
                            window.location.href = `${window.APP_BASE_URL}/modules/student/studentCases.php?case_id=${encodeURIComponent(relatedCaseId)}&highlightCase=1`;
                        } else {
                            window.location.href = `${window.APP_BASE_URL}/modules/do/cases.php?caseId=${encodeURIComponent(relatedCaseId)}&highlightCaseId=${encodeURIComponent(relatedCaseId)}&highlightCase=1&viewCase=1`;
                        }
                    }, 100);
            } else {
                document.getElementById('notificationPanel')?.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
            if (relatedId && relatedId.startsWith('hearing:')) {
                const hearingParts = relatedId.split(':');
                const fallbackUrl = new URL(`${window.APP_BASE_URL}/modules/do/calendar.php`, window.location.origin);
                fallbackUrl.searchParams.set('calendar_view', 'personal');
                if (hearingParts[2] && hearingParts[2] !== 'new') {
                    fallbackUrl.searchParams.set('event_id', hearingParts[2]);
                }
                if (hearingParts[3]) {
                    fallbackUrl.searchParams.set('event_date', hearingParts[3]);
                }
                if (hearingParts[1]) {
                    fallbackUrl.searchParams.set('case_id', hearingParts[1]);
                }
                window.location.href = fallbackUrl.toString();
            }
        }
    };
</script>