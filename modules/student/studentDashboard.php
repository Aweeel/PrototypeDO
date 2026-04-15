<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    // Mark password warning as shown in this login session
    if (isset($_POST['action']) && $_POST['action'] === 'markPasswordWarningShown') {
        $_SESSION['password_warning_modal_shown'] = true;
        echo json_encode(['success' => true, 'message' => 'Password warning marked as shown']);
        exit;
    }
}

// Page metadata
$pageTitle = "Dashboard";
$adminName = getFormattedUserName();
$userId = $_SESSION['user_id'] ?? null;
$studentId = null;

// Get database connection
$pdo = getDBConnection();

// Get student_id from user_id
if ($userId && $pdo) {
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->execute([$userId]);
    $studentRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    $studentId = $studentRecord['student_id'] ?? null;
}

// Fetch student dashboard data
$totalCases = 0;
$activeCases = 0;
$nextHearing = null;
$recentCases = [];

if ($studentId) {
    // Get total and active cases count
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('Pending', 'On Going') THEN 1 ELSE 0 END) as active
        FROM cases 
        WHERE student_id = ?
    ");
    $stmt->execute([$studentId]);
    $caseStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalCases = $caseStats['total'] ?? 0;
    $activeCases = $caseStats['active'] ?? 0;

    // Get next scheduled hearing from calendar_events
    // Find hearings that mention any of this student's cases
    $stmt = $pdo->prepare("
        SELECT TOP 1 
            ce.event_date,
            ce.event_time,
            ce.event_end_time,
            ce.event_name,
            ce.location
        FROM calendar_events ce
        WHERE ce.category = 'Hearing'
        AND ce.event_date >= CAST(GETDATE() AS DATE)
        AND EXISTS (
            SELECT 1 FROM cases c 
            WHERE c.student_id = ?
            AND ce.event_name LIKE '%Case ' + CAST(c.case_id AS VARCHAR) + ')%'
        )
        ORDER BY ce.event_date ASC, ce.event_time ASC
    ");
    $stmt->execute([$studentId]);
    $hearingData = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextHearing = $hearingData;

    // Get recent cases
    $stmt = $pdo->prepare("
        SELECT TOP 5
            case_id,
            case_type,
            created_at,
            status,
            is_archived
        FROM cases 
        WHERE student_id = ?
        ORDER BY is_archived ASC, created_at DESC
    ");
    $stmt->execute([$studentId]);
    $recentCases = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch notifications for the student
$notifications = [];
$unreadNotificationCount = 0;
if ($userId) {
    $notifications = getUnreadNotifications($userId);
    $unreadNotificationCount = count($notifications);
}

// Handle AJAX requests for notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'markNotificationAsRead') {
        $notificationId = $_POST['notificationId'] ?? null;
        
        if ($notificationId) {
            try {
                markNotificationAsRead($notificationId);
                echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Notification ID required']);
        }
        exit;
    }
}

// Format next hearing date and time
if ($nextHearing && isset($nextHearing['event_date'])) {
    $hearingDate = date('M d, Y', strtotime($nextHearing['event_date']));
    $hearingTime = '';
    if ($nextHearing['event_time']) {
        $startTime = date('g:i A', strtotime($nextHearing['event_time']));
        if ($nextHearing['event_end_time']) {
            $endTime = date('g:i A', strtotime($nextHearing['event_end_time']));
            $hearingTime = $startTime . ' - ' . $endTime;
        } else {
            $hearingTime = $startTime;
        }
    }
    $nextHearingFormatted = $hearingDate;
    $nextHearingTimeFormatted = $hearingTime;
    $nextHearingLocation = $nextHearing['location'] ?? 'TBA';
} else {
    $nextHearingFormatted = 'Not Scheduled';
    $nextHearingTimeFormatted = '';
    $nextHearingLocation = '';
}

// Status badge colors
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pending':
            return 'bg-orange-500/10 text-orange-500 border border-orange-500/20';
        case 'On Going':
            return 'bg-blue-500/10 text-blue-500 border border-blue-500/20';
        case 'Resolved':
            return 'bg-green-500/10 text-green-500 border border-green-500/20';
        case 'Dismissed':
            return 'bg-gray-500/10 text-gray-500 border border-gray-500/20';
        default:
            return 'bg-gray-500/10 text-gray-500 border border-gray-500/20';
    }
}

// Status display text
function getStatusText($status) {
    switch ($status) {
        case 'Pending':
            return 'Pending';
        case 'On Going':
            return 'On Going';
        case 'Resolved':
            return 'Resolved';
        case 'Dismissed':
            return 'Dismissed';
        default:
            return ucfirst($status);
    }
}
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
                <!-- Dashboard Title -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Student Dashboard</h1>
                </div>

                <!-- Notifications Section -->
                <?php if (!empty($notifications)): ?>
                    <div class="mb-8 space-y-3">
                        <?php foreach ($notifications as $notification): ?>
                            <a href="/PrototypeDO/modules/student/studentCases.php?case_id=<?php echo htmlspecialchars($notification['related_id']); ?>" class="block">
                                <div class="bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 rounded-lg p-4 flex items-start justify-between notification-item hover:bg-blue-100 dark:hover:bg-blue-500/20 transition-colors cursor-pointer" data-notification-id="<?php echo $notification['notification_id']; ?>">
                                    <div class="flex items-start gap-4 flex-1">
                                        <div class="w-10 h-10 flex-shrink-0 bg-blue-100 dark:bg-blue-500/20 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                <?php echo date('M d, Y \a\t h:i A', strtotime($notification['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <button 
                                        class="ml-4 flex-shrink-0 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400 transition-colors mark-read-btn" 
                                        onclick="event.preventDefault(); event.stopPropagation(); markNotificationAsRead(<?php echo $notification['notification_id']; ?>)"
                                        title="Mark as read"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Active Cases -->
                    <div class="bg-white dark:bg-[#111827] rounded-lg p-6 shadow-sm border border-gray-200 dark:border-slate-700 min-h-[140px] flex items-center">
                        <div class="flex items-center justify-between w-full">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Active Cases</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?php echo $activeCases; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-500/10 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Scheduled Hearing -->
                    <div class="bg-white dark:bg-[#111827] rounded-lg p-6 shadow-sm border border-gray-200 dark:border-slate-700 min-h-[140px]">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Scheduled Hearing</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($nextHearingFormatted); ?></p>
                                <?php if ($nextHearingTimeFormatted): ?>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <?php echo htmlspecialchars($nextHearingTimeFormatted); ?>
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <?php echo htmlspecialchars($nextHearingLocation); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-500/10 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Cases -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
                    <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Recent Cases</h2>
                        <a href="/PrototypeDO/modules/student/studentCases.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View all cases</a>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-slate-700">
                        <?php if (empty($recentCases)): ?>
                            <div class="p-8 text-center">
                                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">No cases found</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Your case history will appear here</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentCases as $case): ?>
                                <?php $isArchived = isset($case['is_archived']) && $case['is_archived'] == 1; ?>
                                <a href="/PrototypeDO/modules/student/studentCases.php?case_id=<?php echo $case['case_id']; ?>" class="block p-6 hover:bg-blue-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer border-l-4 border-transparent hover:border-blue-600 dark:hover:border-blue-400 <?php echo $isArchived ? 'opacity-70' : ''; ?>">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-2 hover:text-blue-600 dark:hover:text-blue-400">
                                                Case #<?php echo htmlspecialchars($case['case_id']); ?> - <?php echo htmlspecialchars(str_replace('_', ' ', ucwords($case['case_type'], '_'))); ?>
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo date('M d, Y', strtotime($case['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo getStatusBadgeClass($case['status']); ?>">
                                                <?php echo getStatusText($case['status']); ?>
                                            </span>
                                            <?php if ($isArchived): ?>
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-700 dark:text-gray-400 border border-gray-300 dark:border-gray-600">
                                                    Archived
                                                </span>
                                            <?php endif; ?>
                                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
    <script>
        function markNotificationAsRead(notificationId) {
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=markNotificationAsRead&notificationId=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the notification with fade animation
                    if (notificationElement) {
                        notificationElement.style.transition = 'opacity 0.3s ease-out';
                        notificationElement.style.opacity = '0';
                        setTimeout(() => {
                            notificationElement.remove();
                            
                            // If no more notifications, optionally show a message
                            const allNotifications = document.querySelectorAll('.notification-item');
                            if (allNotifications.length === 0) {
                                const container = document.querySelector('main > div');
                                if (container && container.classList.contains('mb-8')) {
                                    container.parentElement.style.display = 'none';
                                }
                            }
                        }, 300);
                    }
                } else {
                    console.error('Failed to mark notification as read:', data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>