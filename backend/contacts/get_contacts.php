<?php
/**
 * Get Emergency Contacts
 * GET: /backend/contacts/get_contacts.php
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

// Get all contacts for user
$stmt = $conn->prepare("SELECT id, name, phone, relationship, created_at FROM emergency_contacts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$contacts = [];
while ($row = $result->fetch_assoc()) {
    $contacts[] = $row;
}

$stmt->close();
$conn->close();

sendSuccess('Contacts retrieved successfully', $contacts);



