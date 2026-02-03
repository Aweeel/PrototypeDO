<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    try {
        // Get events
        if ($_POST['action'] === 'getEvents') {
            $month = $_POST['month'] ?? date('n');
            $year = $_POST['year'] ?? date('Y');
            
            $sql = "SELECT event_id, event_name, event_date, event_time, category, description, location
                    FROM calendar_events 
                    WHERE MONTH(event_date) = ? AND YEAR(event_date) = ?
                    ORDER BY event_date, event_time";
            
            $events = fetchAll($sql, [$month, $year]);
            
            // Format events
            $formattedEvents = array_map(function($event) {
                return [
                    'id' => $event['event_id'],
                    'name' => $event['event_name'],
                    'date' => $event['event_date'],
                    'time' => $event['event_time'] ? date('g:i A', strtotime($event['event_time'])) : null,
                    'category' => $event['category'],
                    'description' => $event['description'],
                    'location' => $event['location'],
                    'color' => getCategoryColor($event['category'])
                ];
            }, $events);
            
            echo json_encode(['success' => true, 'events' => $formattedEvents]);
            exit;
        }

        // Create event
        if ($_POST['action'] === 'createEvent') {
            error_log("Creating event - Received data: " . print_r($_POST, true));
            
            $eventName = $_POST['eventName'] ?? '';
            $eventDate = $_POST['eventDate'] ?? '';
            $eventTime = isset($_POST['eventTime']) && !empty(trim($_POST['eventTime'])) ? trim($_POST['eventTime']) : null;
            $category = $_POST['category'] ?? '';
            $description = isset($_POST['description']) && !empty(trim($_POST['description'])) ? trim($_POST['description']) : null;
            $location = isset($_POST['location']) && !empty(trim($_POST['location'])) ? trim($_POST['location']) : null;
            $createdBy = $_SESSION['user_id'] ?? null;
            
            // Validate required fields
            if (empty($eventName) || empty($eventDate) || empty($category)) {
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                exit;
            }
            
            // Build SQL based on whether time is provided
            if ($eventTime !== null) {
                $sql = "INSERT INTO calendar_events (event_name, event_date, event_time, category, description, location, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE())";
                $params = [
                    $eventName,
                    $eventDate,
                    $eventTime,
                    $category,
                    $description,
                    $location,
                    $createdBy
                ];
            } else {
                $sql = "INSERT INTO calendar_events (event_name, event_date, category, description, location, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, GETDATE())";
                $params = [
                    $eventName,
                    $eventDate,
                    $category,
                    $description,
                    $location,
                    $createdBy
                ];
            }
            
            error_log("SQL: $sql");
            error_log("Params: " . print_r($params, true));
            
            executeQuery($sql, $params);
            
            // Log to audit
            auditCreate('calendar_events', $eventName, [
                'event_name' => $eventName,
                'event_date' => $eventDate,
                'category' => $category
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Event created successfully']);
            exit;
        }

        // Update event
        if ($_POST['action'] === 'updateEvent') {
            $eventId = $_POST['eventId'] ?? null;
            $eventName = $_POST['eventName'] ?? '';
            $eventDate = $_POST['eventDate'] ?? '';
            $eventTime = isset($_POST['eventTime']) && !empty(trim($_POST['eventTime'])) ? trim($_POST['eventTime']) : null;
            $category = $_POST['category'] ?? '';
            $description = isset($_POST['description']) && !empty(trim($_POST['description'])) ? trim($_POST['description']) : null;
            $location = isset($_POST['location']) && !empty(trim($_POST['location'])) ? trim($_POST['location']) : null;
            
            if (empty($eventId)) {
                echo json_encode(['success' => false, 'error' => 'Event ID required']);
                exit;
            }
            
            $sql = "UPDATE calendar_events 
                    SET event_name = ?, event_date = ?, event_time = ?, category = ?, description = ?, location = ?, updated_at = GETDATE()
                    WHERE event_id = ?";
            
            $params = [
                $eventName,
                $eventDate,
                $eventTime,
                $category,
                $description,
                $location,
                $eventId
            ];
            
            executeQuery($sql, $params);
            
            echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
            exit;
        }

        // Delete event
        if ($_POST['action'] === 'deleteEvent') {
            $eventId = $_POST['eventId'] ?? null;
            
            if (empty($eventId)) {
                echo json_encode(['success' => false, 'error' => 'Event ID required']);
                exit;
            }
            
            $sql = "DELETE FROM calendar_events WHERE event_id = ?";
            executeQuery($sql, [$eventId]);
            
            // Log to audit
            auditDelete('calendar_events', $eventId, ['event_id' => $eventId]);
            
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
            exit;
        }

        // Get event categories with counts
        if ($_POST['action'] === 'getCategories') {
            $categories = [
                ['name' => 'Meeting', 'count' => 0],
                ['name' => 'Conference', 'count' => 0],
                ['name' => 'Training', 'count' => 0],
                ['name' => 'Hearing', 'count' => 0],
                ['name' => 'Deadline', 'count' => 0],
                ['name' => 'Other', 'count' => 0]
            ];
            
            // Get counts
            $sql = "SELECT category, COUNT(*) as count FROM calendar_events GROUP BY category";
            $counts = fetchAll($sql) ?? [];
            
            foreach ($counts as $count) {
                foreach ($categories as &$cat) {
                    if ($cat['name'] === $count['category']) {
                        $cat['count'] = (int)$count['count'];
                    }
                }
            }
            
            echo json_encode(['success' => true, 'categories' => $categories]);
            exit;
        }

        // Unknown action
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    } catch (Exception $e) {
        error_log("Calendar AJAX Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

// Helper function
function getCategoryColor($category) {
    $colors = [
        'Meeting' => 'blue',
        'Conference' => 'green',
        'Training' => 'purple',
        'Hearing' => 'red',
        'Deadline' => 'yellow',
        'Other' => 'gray'
    ];
    return $colors[$category] ?? 'gray';
}

$pageTitle = "Calendar";
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office - Calendar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
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
        <div class="flex-1 overflow-y-auto ml-64">
            <?php include __DIR__ . '/../../includes/header.php'; ?>
            
            <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Calendar Section (3 columns) -->
                    <div class="lg:col-span-3">
                        <!-- Calendar Header -->
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-4">
                                    <button onclick="previousMonth()" class="p-2 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <h2 id="currentMonth" class="text-xl font-semibold text-gray-800 dark:text-gray-100"></h2>
                                    <button onclick="nextMonth()" class="p-2 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                    <button onclick="goToToday()" class="ml-2 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        Today
                                    </button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button id="weekBtn" onclick="switchView('week')" class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                                        Week
                                    </button>
                                    <button id="monthBtn" onclick="switchView('month')" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg">
                                        Month
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                            <!-- Calendar Days Header -->
                            <div class="grid grid-cols-7 bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Sun</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Mon</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Tue</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Wed</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Thu</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Fri</div>
                                <div class="p-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400">Sat</div>
                            </div>
                            
                            <!-- Calendar Days Grid -->
                            <div id="calendarGrid" class="grid grid-cols-7 divide-x divide-y divide-gray-200 dark:divide-slate-700">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar -->
                    <div class="space-y-6">
                        <!-- Quick Add Event -->
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Quick Add Event</h3>
                            <button onclick="openAddEventModal()" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Event
                            </button>
                        </div>

                        <!-- Event Categories -->
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Event Categories</h3>
                            <div id="categoriesList" class="space-y-3">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>

                        <!-- Upcoming Events -->
                        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Upcoming Events</h3>
                                <button onclick="loadEvents()" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                            </div>
                            <div id="upcomingEventsList" class="space-y-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Loading events...</p>
                            </div>
                            <button onclick="loadEvents()" class="w-full mt-4 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                View All
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/calendar/main.js"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>