<?php
/**
 * Add Emergency Contact
 * POST: /backend/contacts/add_contact.php
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

// Validate required fields
validateRequired(['name', 'phone'], $input);

// Validate phone
if (!validatePhone($input['phone'])) {
    sendError('Invalid phone number', 400);
}

$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed', 500);
}

// Insert new contact
$relationship = isset($input['relationship']) ? $input['relationship'] : 'Other';
$stmt = $conn->prepare("INSERT INTO emergency_contacts (user_id, name, phone, relationship) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $userId, $input['name'], $input['phone'], $relationship);

if ($stmt->execute()) {
    $contactId = $conn->insert_id;
    $stmt->close();
    $conn->close();
    
    sendSuccess('Contact added successfully', [
        'contact_id' => $contactId,
        'name' => $input['name'],
        'phone' => $input['phone'],
        'relationship' => $relationship
    ]);
} else {
    $error = $conn->error;
    $stmt->close();
    $conn->close();
    sendError('Failed to add contact: ' . $error, 500);
}



