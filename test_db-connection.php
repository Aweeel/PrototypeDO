<?php
require_once __DIR__ . '/includes/config.php';

// Keep the diagnostic endpoint unavailable unless it is explicitly enabled.
$diagnosticEnabled = filter_var(
    getenv('DB_DIAGNOSTIC_ENABLED') ?: 'false',
    FILTER_VALIDATE_BOOLEAN
);

if (!$diagnosticEnabled) {
    http_response_code(404);
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/html; charset=utf-8');

try {
    getDBConnection();
    $userCount = fetchValue('SELECT COUNT(*) FROM users');
    $caseCount = fetchValue('SELECT COUNT(*) FROM cases');

    echo '<h1>Database connection test</h1>';
    echo '<p style="color: green;">Database connected successfully.</p>';
    echo '<p>Total users: <strong>' . (int) $userCount . '</strong></p>';
    echo '<p>Total cases: <strong>' . (int) $caseCount . '</strong></p>';
} catch (Throwable $exception) {
    error_log('Database diagnostic failed: ' . $exception->getMessage());
    http_response_code(503);
    echo '<h1>Database connection test</h1>';
    echo '<p style="color: red;">Database connection failed.</p>';
}