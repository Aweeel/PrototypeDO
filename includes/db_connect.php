<?php
// includes/db_connect.php

// SmarterASP Database Credentials
$host     = getenv('DB_HOST')     ?: 'mssqlXXXX.smarterasp.net'; // Replace with your SmarterASP SQL Host
$dbname   = getenv('DB_NAME')     ?: 'db_acea8f_doms';           // Your SmarterASP Database Name
$username = getenv('DB_USER')     ?: 'db_acea8f_doms_admin';     // Your SmarterASP DB User
$password = getenv('DB_PASS')     ?: 'YourDatabasePassword';    // Your SmarterASP DB Password

try {
    // PDO Connection string for Microsoft SQL Server
    $pdo = new PDO("sqlsrv:Server=$host;Database=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Alias for legacy scripts that might use $conn
    $conn = $pdo;
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

/**
 * Helper function: Fetch all records from a query
 */
if (!function_exists('fetchAll')) {
    function fetchAll($sql, $params = []) {
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

/**
 * Helper function: Fetch a single setting
 */
if (!function_exists('getSystemSetting')) {
    function getSystemSetting($key, $default = null) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    }
}

/**
 * Helper function: Set or update a setting
 */
if (!function_exists('setSystemSetting')) {
    function setSystemSetting($key, $value) {
        global $pdo;
        $stmt = $pdo->prepare("
            IF EXISTS (SELECT 1 FROM system_settings WHERE setting_key = ?)
                UPDATE system_settings SET setting_value = ?, updated_at = GETDATE() WHERE setting_key = ?
            ELSE
                INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
        ");
        $stmt->execute([$key, $value, $key, $key, $value]);
    }
}
?>