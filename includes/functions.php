<?php
function get_sidebar_items($role = 'guest') {
    $items = [
        'do' => [
            ['label' => 'Dashboard', 'icon' => 'Dashboard-icon.png', 'path' => 'doDashboard.php'],
            ['label' => 'Cases', 'icon' => 'Cases-icon.png', 'path' => 'cases.php'],
            ['label' => 'Statistics', 'icon' => 'Statistics-icon.png', 'path' => 'statistics.php'],
            ['label' => 'Lost & Found', 'icon' => 'Lost-and-Found-icon.png', 'path' => 'lostAndFound.php'],
            ['label' => 'Student History', 'icon' => 'Student-history-icon.png', 'path' => 'studentHistory.php'],
            ['label' => 'Reports', 'icon' => 'Reports-icon.png', 'path' => 'reports.php'],
            ['label' => 'Calendar', 'icon' => 'Calendar-icon.png', 'path' => 'calendar.php'],
            ['label' => 'Student Handbook', 'icon' => 'Student-handbook-icon.png', 'path' => 'studentHandbook.php'],
            ['label' => 'Audit Log', 'icon' => 'Audit-log-icon.png', 'path' => 'auditLog.php']
        ],
        'superAdmin' => [
            ['label' => 'Dashboard', 'icon' => 'Dashboard-icon.png', 'path' => 'adminDashboard.php'],
            ['label' => 'Manage Users', 'icon' => 'User-icon.png', 'path' => '#'],
            ['label' => 'Reports', 'icon' => 'Reports-icon.png', 'path' => '#']
        ],
        'student' => [
            ['label' => 'Dashboard', 'icon' => 'Dashboard-icon.png', 'path' => '#']
        ]
    ];

    return $items[$role] ?? [];
}
?>
