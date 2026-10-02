<?php
// includes/db_connect.php

// Default to local XAMPP settings if environment variables are not set on Azure
define('DB_HOST', getenv('MYSQL_HOST') ?: getenv('MYSQLHOST') ?: '127.0.0.1');
define('DB_PORT', getenv('MYSQL_PORT') ?: getenv('MYSQLPORT') ?: '3306');
define('DB_USER', getenv('MYSQL_USER') ?: getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'prototypedo_db');

$configuredSslCa = getenv('MYSQL_SSL_CA') ?: '';
$systemSslCa = '/etc/ssl/certs/ca-certificates.crt';
define('DB_SSL_CA', $configuredSslCa !== '' ? $configuredSslCa : (is_file($systemSslCa) ? $systemSslCa : ''));

$conn = null;

function getDBConnection() {
    global $conn;

    if ($conn !== null) {
        return $conn;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];

        // SSL is only required for Azure MySQL, not local XAMPP
        if (DB_SSL_CA !== '') {
            $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            $options[PDO::MYSQL_ATTR_SSL_CIPHER] = 'DEFAULT';
        }

        $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $conn;

    } catch(PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        throw new RuntimeException('Database connection failed', 0, $e);
    }
}

// Close database connection
function closeDBConnection() {
    global $conn;
    $conn = null;
}

// Helper function to execute queries safely
function executeQuery($sql, $params = []): PDOStatement {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        throw $e;
    }
}

// Helper function to get single row
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

// Helper function to get all rows
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

// Helper function to get single value
function fetchValue($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    return $row ? $row[0] : null;
}

// Helper function for INSERT and get last inserted ID
function insertAndGetId($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $conn = getDBConnection();
    return $conn->lastInsertId();
}
?>
