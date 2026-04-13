<?php
/**
 * User Registration
 * POST: /backend/auth/register.php
 */

require_once '../config/db.php';
require_once '../utils/response.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Get and sanitize input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$input = sanitizeInput($input);

// Validate required fields
validateRequired(['name', 'email', 'password', 'contact'], $input);

// Validate email
if (!validateEmail($input['email'])) {
    sendError('Invalid email address', 400);
}

// Validate phone
if (!validatePhone($input['contact'])) {
    sendError('Invalid phone number', 400);
}

// Validate password strength
if (strlen($input['password']) < 8) {
    sendError('Password must be at least 8 characters long', 400);
}

$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed', 500);
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $input['email']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    $conn->close();
    sendError('Email already registered', 409);
}

// Hash password
$hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (name, email, password, emergency_contact) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $input['name'], $input['email'], $hashedPassword, $input['contact']);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    $stmt->close();
    
    // Fetch the newly created user to return a consistent object
    $stmt = $conn->prepare("SELECT id, name, email, emergency_contact as contact FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    // Start session
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    
    sendSuccess('Registration successful', [
        'user' => $user,
        'session_id' => session_id()
    ]);
} else {
    $error = $conn->error;
    $stmt->close();
    $conn->close();
    sendError('Registration failed: ' . $error, 500);
}



