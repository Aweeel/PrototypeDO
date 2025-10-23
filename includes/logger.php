<?php
function write_log($message, $type = 'system') {
    $logDir = __DIR__ . '/../logs/';
    
    // Create log directory if it doesn’t exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $file = $logDir . $type . '.log';
    $date = date('Y-m-d H:i:s');
    $entry = "[$date] $message" . PHP_EOL;

    file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
}
?>
