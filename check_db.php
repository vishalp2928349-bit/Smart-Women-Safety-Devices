<?php
require_once 'backend/config/db.php';

header('Content-Type: text/html');
echo "<h1>Database Connection Diagnostics</h1>";

// Check basic connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ ERROR: Could not connect to MySQL server. <br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "User: " . DB_USER . "<br>";
    echo "Error: " . $conn->connect_error . "</p>";
    echo "<p>Please check your XAMPP Control Panel and ensure MySQL is running.</p>";
    exit;
}
echo "<p style='color: green;'>✅ MySQL Server connection successful!</p>";

// Check database existence
$dbName = DB_NAME;
$result = $conn->query("SHOW DATABASES LIKE '$dbName'");
if ($result->num_rows == 0) {
    echo "<p style='color: orange;'>⚠️ WARNING: Database '$dbName' does not exist.</p>";
    echo "<p>Please run <a href='init_db.php'>init_db.php</a> to create the database and tables.</p>";
} else {
    echo "<p style='color: green;'>✅ Database '$dbName' exists!</p>";
    
    $conn->select_db($dbName);
    
    // Check tables
    $tables = ['users', 'emergency_contacts', 'reports', 'sos_alerts'];
    echo "<h3>Table Check:</h3><ul>";
    foreach ($tables as $table) {
        $res = $conn->query("SHOW TABLES LIKE '$table'");
        if ($res->num_rows > 0) {
            echo "<li><span style='color: green;'>✅ Table '$table' exists.</span></li>";
        } else {
            echo "<li><span style='color: red;'>❌ Table '$table' is MISSING.</span></li>";
        }
    }
    echo "</ul>";
}

$conn->close();
?>
