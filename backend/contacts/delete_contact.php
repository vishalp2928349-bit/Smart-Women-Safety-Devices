<?php
/**
 * Delete Emergency Contact
 * DELETE: /backend/contacts/delete_contact.php
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

// Get contact ID
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_GET;
}

if (!isset($input['contact_id'])) {
    sendError('Contact ID is required', 400);
}

$contactId = intval($input['contact_id']);

$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed', 500);
}

// Verify contact belongs to user and delete
$stmt = $conn->prepare("DELETE FROM emergency_contacts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $contactId, $userId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $conn->close();
        sendSuccess('Contact deleted successfully');
    } else {
        $stmt->close();
        $conn->close();
        sendError('Contact not found or unauthorized', 404);
    }
} else {
    $error = $conn->error;
    $stmt->close();
    $conn->close();
    sendError('Failed to delete contact: ' . $error, 500);
}



