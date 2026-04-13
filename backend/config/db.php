<?php
/**
 * Database Configuration
 * Smart Woman Safety Device - Database Connection
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'safetyguard_db');

// Create database connection
function getDBConnection($includeDb = true) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, $includeDb ? DB_NAME : "");
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Initialize database tables (run once)
function initializeDatabase() {
    $conn = getDBConnection(false);
    if (!$conn) return false;
    
    // Create database if not exists
    $dbName = DB_NAME;
    if (!$conn->query("CREATE DATABASE IF NOT EXISTS $dbName")) {
        error_log("Error creating database: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $conn->select_db($dbName);
    
    // Users table
    $usersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        emergency_contact VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    // Emergency contacts table
    $contactsTable = "CREATE TABLE IF NOT EXISTS emergency_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        relationship VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    // Reports table
    $reportsTable = "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        incident_type VARCHAR(50) NOT NULL,
        date DATE NOT NULL,
        time TIME NOT NULL,
        location_address TEXT,
        location_city VARCHAR(100),
        location_state VARCHAR(100),
        description TEXT NOT NULL,
        perpetrator_info TEXT,
        witnesses TEXT,
        emergency_level VARCHAR(20) DEFAULT 'medium',
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    // SOS alerts table
    $sosTable = "CREATE TABLE IF NOT EXISTS sos_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        location_address TEXT,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $tables = [$usersTable, $contactsTable, $reportsTable, $sosTable];
    
    foreach ($tables as $table) {
        if (!$conn->query($table)) {
            error_log("Error creating table: " . $conn->error);
            return false;
        }
    }
    
    $conn->close();
    return true;
}



