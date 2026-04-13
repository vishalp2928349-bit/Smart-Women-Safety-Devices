<?php
/**
 * Get Incident Reports
 * GET: /backend/reports/get_reports.php
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

// Get all reports for user
$stmt = $conn->prepare("SELECT id, incident_type, date, time, location_address, location_city, location_state, description, perpetrator_info, witnesses, emergency_level, status, created_at FROM reports WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$reports = [];
while ($row = $result->fetch_assoc()) {
    // Decode JSON fields
    if ($row['perpetrator_info']) {
        $row['perpetrator_info'] = json_decode($row['perpetrator_info'], true);
    }
    $reports[] = $row;
}

$stmt->close();
$conn->close();

sendSuccess('Reports retrieved successfully', $reports);



