<?php
require_once __DIR__ . '/includes/db_connect.php';

echo "<h1>Testing SQL Server Connection</h1>";

try {
    // Test 1: Check if PDO extension exists
    if (extension_loaded('pdo')) {
        echo "<p style='color: green;'>✅ PDO extension is loaded</p>";
    } else {
        echo "<p style='color: red;'>❌ PDO extension is NOT loaded</p>";
    }
    
    // Test 2: Check available PDO drivers
    $drivers = PDO::getAvailableDrivers();
    echo "<p><strong>Available PDO Drivers:</strong> " . implode(', ', $drivers) . "</p>";
    
    if (in_array('odbc', $drivers)) {
        echo "<p style='color: green;'>✅ ODBC driver is available</p>";
    } else {
        echo "<p style='color: red;'>❌ ODBC driver is NOT available</p>";
    }
    
    // Test 3: Try connection
    echo "<hr><h2>Attempting Connection...</h2>";
    
    $server = "(localdb)\\MSSQLLocalDB";
    $database = "PrototypeDO_DB";
    
    echo "<p>Server: $server</p>";
    echo "<p>Database: $database</p>";
    
    // Try ODBC Driver 17
    try {
        $connectionString = "odbc:Driver={ODBC Driver 17 for SQL Server};Server=$server;Database=$database;Trusted_Connection=yes;";
        echo "<p>Connection String: $connectionString</p>";
        
        $conn = new PDO($connectionString);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p style='color: green; font-size: 20px;'>✅ CONNECTION SUCCESSFUL with ODBC Driver 17!</p>";
        
        // Test query
        $stmt = $conn->query("SELECT @@VERSION as version");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>SQL Server Version:</strong> " . $row['version'] . "</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ ODBC Driver 17 failed: " . $e->getMessage() . "</p>";
        
        // Try ODBC Driver 18
        try {
            $connectionString = "odbc:Driver={ODBC Driver 18 for SQL Server};Server=$server;Database=$database;Trusted_Connection=yes;TrustServerCertificate=yes;";
            echo "<p>Trying ODBC Driver 18...</p>";
            
            $conn = new PDO($connectionString);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<p style='color: green; font-size: 20px;'>✅ CONNECTION SUCCESSFUL with ODBC Driver 18!</p>";
            
        } catch (PDOException $e2) {
            echo "<p style='color: red;'>❌ ODBC Driver 18 also failed: " . $e2->getMessage() . "</p>";
            
            // Try without specifying database
            try {
                $connectionString = "odbc:Driver={ODBC Driver 17 for SQL Server};Server=$server;Trusted_Connection=yes;";
                echo "<p>Trying connection to master database...</p>";
                
                $conn = new PDO($connectionString);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                echo "<p style='color: orange;'>⚠️ Connected to SQL Server, but PrototypeDO_DB database might not exist!</p>";
                
                // Check if database exists
                $stmt = $conn->query("SELECT name FROM sys.databases WHERE name = 'PrototypeDO_DB'");
                $result = $stmt->fetch();
                
                if ($result) {
                    echo "<p style='color: green;'>✅ PrototypeDO_DB database EXISTS</p>";
                } else {
                    echo "<p style='color: red;'>❌ PrototypeDO_DB database DOES NOT EXIST - You need to run the schema.sql script!</p>";
                }
                
            } catch (PDOException $e3) {
                echo "<p style='color: red;'>❌ All connection attempts failed!</p>";
                echo "<p><strong>Last Error:</strong> " . $e3->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Fatal Error: " . $e->getMessage() . "</p>";
}

echo "<h1>Database Connection Test</h1>";

try {
    $conn = getDBConnection();
    echo "<p style='color: green;'>✅ Database connected successfully!</p>";
    
    // Test query - count users
    $userCount = fetchValue("SELECT COUNT(*) FROM users");
    echo "<p>📊 Total users in database: <strong>$userCount</strong></p>";
    
    // Test query - get all users
    $users = fetchAll("SELECT user_id, username, full_name, role FROM users");
    echo "<h3>Users List:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test query - count cases
    $caseCount = fetchValue("SELECT COUNT(*) FROM cases");
    echo "<p>📋 Total cases in database: <strong>$caseCount</strong></p>";
    
    // Test query - get recent cases
    $cases = fetchAll("SELECT TOP 5 c.case_id, c.case_type, c.status, 
                       CONCAT(s.first_name, ' ', s.last_name) as student_name
                       FROM cases c
                       LEFT JOIN students s ON c.student_id = s.student_id
                       ORDER BY c.date_reported DESC");
    
    echo "<h3>Recent Cases:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Case ID</th><th>Student</th><th>Type</th><th>Status</th></tr>";
    foreach ($cases as $case) {
        echo "<tr>";
        echo "<td>{$case['case_id']}</td>";
        echo "<td>{$case['student_name']}</td>";
        echo "<td>{$case['case_type']}</td>";
        echo "<td>{$case['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p style='color: green;'><strong>✅ All database functions working correctly!</strong></p>";
    echo "<p><a href='/PrototypeDO/modules/login/login.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>