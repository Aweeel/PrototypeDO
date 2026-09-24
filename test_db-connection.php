<?php
require_once __DIR__ . '/includes/db_connect.php';

echo '<h1>MySQL Database Connection Test</h1>';

try {
    echo '<p>Available PDO drivers: ' . htmlspecialchars(implode(', ', PDO::getAvailableDrivers())) . '</p>';
    
    getDBConnection();
    echo '<p style="color: green;">Database connected successfully.</p>';

    $userCount = fetchValue('SELECT COUNT(*) FROM users');
    $caseCount = fetchValue('SELECT COUNT(*) FROM cases');
    echo '<p>Total users: <strong>' . (int)$userCount . '</strong></p>';
    echo '<p>Total cases: <strong>' . (int)$caseCount . '</strong></p>';

    $cases = fetchAll("SELECT c.case_id, c.severity, c.status,
                       CONCAT(s.first_name, ' ', s.last_name) AS student_name
                       FROM cases c
                       LEFT JOIN students s ON c.student_id = s.student_id
                       ORDER BY c.date_reported DESC
                       LIMIT 5");

    echo '<h3>Recent Cases</h3><ul>';
    foreach ($cases as $case) {
        echo '<li>' . htmlspecialchars($case['case_id'] . ' - ' . ($case['student_name'] ?? 'Unassigned')) . '</li>';
    }
    echo '</ul><p style="color: green;"><strong>MySQL database functions are working.</strong></p>';
} catch (Throwable $exception) {
    echo sprintf("<p style='color: red;'>Database error: %s</p>", htmlspecialchars($exception->getMessage()));
}