<?php
// includes/db_connect.php
// SQL Server Connection using PDO (Compatible with Railway & LocalDB)

// Pull credentials from Railway environment variables (or default to local fallback)
define('DB_HOST', getenv('MSSQL_HOST') ?: '(localdb)\\MSSQLLocalDB');
define('DB_PORT', getenv('MSSQL_PORT') ?: '1433');
define('DB_USER', getenv('MSSQL_USER') ?: 'sa');
define('DB_PASS', getenv('MSSQL_PASSWORD') ?: '');
define('DB_NAME', getenv('MSSQL_DATABASE') ?: 'PrototypeDO_DB');

// Global connection variable
$conn = null;

function getDBConnection() {
    global $conn;
    
    // Return existing connection if already established
    if ($conn !== null) {
        return $conn;
    }
    
    try {
        // Detect environment: Use sqlsrv DSN on Railway / Linux servers, or fall back to ODBC for LocalDB
        if (getenv('MSSQL_HOST')) {
            // Railway / Production Server (pdo_sqlsrv driver)
            $dsn = "sqlsrv:Server=" . DB_HOST . "," . DB_PORT . ";Database=" . DB_NAME . ";TrustServerCertificate=true";
            $conn = new PDO($dsn, DB_USER, DB_PASS);
        } else {
            // Local Development fallback (Windows LocalDB via ODBC)
            $connectionString = "odbc:Driver={ODBC Driver 17 for SQL Server};Server=" . DB_HOST . ";Database=" . DB_NAME . ";Trusted_Connection=yes;";
            $conn = new PDO($connectionString);
        }
        
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
function executeQuery($sql, $params = []) {
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
