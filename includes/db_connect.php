<?php
// includes/db_connect.php
// SQL Server Connection using PDO

// Database configuration
define('DB_SERVER', '(localdb)\\MSSQLLocalDB');
define('DB_NAME', 'PrototypeDO_DB');
define('DB_DRIVER', '{ODBC Driver 17 for SQL Server}');

// Global connection variable
$conn = null;

function getDBConnection() {
    global $conn;
    
    // Return existing connection if already established
    if ($conn !== null) {
        return $conn;
    }
    
    try {
        // Connection string for Windows Authentication with LocalDB
        $connectionString = "odbc:Driver=" . DB_DRIVER . ";Server=" . DB_SERVER . ";Database=" . DB_NAME . ";Trusted_Connection=yes;";
        
        // Create PDO connection
        $conn = new PDO($connectionString);
        
        // Set error mode to exceptions
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Set default fetch mode to associative array
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $conn;
        
    } catch(PDOException $e) {
        // Log error (in production, log to file instead of displaying)
        error_log("Database Connection Error: " . $e->getMessage());
        die("Database connection failed. Please contact system administrator.");
    }
}

// Close database connection
function closeDBConnection() {
    global $conn;
    $conn = null;
}

// Helper function to execute queries safely
function executeQuery($sql, $params = []) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare($sql);
        
        // For SQL Server ODBC, let PDO handle parameter binding automatically
        // This avoids "Invalid character value for cast specification" errors
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