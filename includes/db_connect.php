<?php
// includes/db_connect.php
// MySQL connection using PDO with Railway & local env support.

// Railway provides environment variables with or without underscores (e.g., MYSQLHOST or MYSQL_HOST)
define('DB_HOST', getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: '127.0.0.1'));
define('DB_PORT', getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: '3306'));
define('DB_USER', getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: 'root'));
define('DB_PASS', getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: ''));
define('DB_NAME', getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: 'sti_handbook_db'));

// Global connection variable
$conn = null;

function getDBConnection() {
    global $conn;
    
    // Return existing connection if already established
    if ($conn !== null) {
        return $conn;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $conn = new PDO($dsn, DB_USER, DB_PASS);
        
        // Set error mode to exceptions
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Set default fetch mode to associative array
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $conn;
        
    } catch(PDOException $e) {
        // Log error
        error_log("Database Connection Error: " . $e->getMessage());
        die("Database connection failed. Details: " . $e->getMessage());
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
