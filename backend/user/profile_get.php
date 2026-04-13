<?php
/**
 * Get User Profile
 * GET: /backend/user/profile_get.php
 */

require_once '../config/db.php';
require_once '../utils/response.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendError('Unauthorized', 401);
}

$userId = $_SESSION['user_id'];

$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed', 500);
}

// Get user profile
$stmt = $conn->prepare("SELECT id, name, email, phone, emergency_contact, address, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    sendError('User not found', 404);
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

sendSuccess('Profile retrieved successfully', $user);



