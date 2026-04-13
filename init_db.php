<?php
require_once 'backend/config/db.php';

echo "<h1>Database Initialization</h1>";

if (initializeDatabase()) {
    echo "<p style='color: green;'>✅ Database and tables initialized successfully!</p>";
    echo "<p>You can now use the application.</p>";
    echo "<a href='html/index.html'>Go to Home Page</a>";
} else {
    echo "<p style='color: red;'>❌ Database initialization failed. Check your MySQL connection settings in <code>backend/config/db.php</code>.</p>";
}
?>
