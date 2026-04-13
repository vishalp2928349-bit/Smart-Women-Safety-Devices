<?php
/**
 * User Login
 * POST: /backend/auth/login.php
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
validateRequired(['email', 'password'], $input);

$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed', 500);
}

// Get user by email
$stmt = $conn->prepare("SELECT id, name, email, password, phone, emergency_contact as contact, address FROM users WHERE email = ?");
$stmt->bind_param("s", $input['email']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    sendError('Invalid email or password', 401);
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($input['password'], $user['password'])) {
    $stmt->close();
    $conn->close();
    sendError('Invalid email or password', 401);
}

// Start session
session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['name'];

// Remove password from response
unset($user['password']);

$stmt->close();
$conn->close();

sendSuccess('Login successful', [
    'user' => $user,
    'session_id' => session_id()
]);



?>