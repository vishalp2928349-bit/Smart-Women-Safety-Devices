<?php
/**
 * Update User Profile
 * POST: /backend/user/profile_update.php
 */

require_once '../config/db.php';
require_once '../utils/response.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendError('Unauthorized', 401);
}

$userId = $_SESSION['user_id'];

// Get and sanitize input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$input = sanitizeInput($input);

// Validate email if provided
if (isset($input['email']) && !validateEmail($input['email'])) {
    sendError('Invalid email address', 400);
}

// Validate phone if provided
if (isset($input['phone']) && !validatePhone($input['phone'])) {
    sendError('Invalid phone number', 400);
}

$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed', 500);
}

// Build update query dynamically
$updateFields = [];
$params = [];
$types = '';

if (isset($input['name'])) {
    $updateFields[] = "name = ?";
    $params[] = $input['name'];
    $types .= 's';
}

if (isset($input['email'])) {
    // Check if email is already taken by another user
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->bind_param("si", $input['email'], $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        sendError('Email already taken', 409);
    }
    $checkStmt->close();
    
    $updateFields[] = "email = ?";
    $params[] = $input['email'];
    $types .= 's';
}

if (isset($input['phone'])) {
    $updateFields[] = "phone = ?";
    $params[] = $input['phone'];
    $types .= 's';
}

if (isset($input['emergency_contact'])) {
    $updateFields[] = "emergency_contact = ?";
    $params[] = $input['emergency_contact'];
    $types .= 's';
}

if (isset($input['address'])) {
    $updateFields[] = "address = ?";
    $params[] = $input['address'];
    $types .= 's';
}

if (empty($updateFields)) {
    $conn->close();
    sendError('No fields to update', 400);
}

// Add user_id to params
$params[] = $userId;
$types .= 'i';

// Build and execute update query
$query = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    sendSuccess('Profile updated successfully');
} else {
    $error = $conn->error;
    $stmt->close();
    $conn->close();
    sendError('Update failed: ' . $error, 500);
}



