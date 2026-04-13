<?php
/**
 * Add Incident Report
 * POST: /backend/reports/add_report.php
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
validateRequired(['incident_type', 'date', 'time', 'location_address', 'description'], $input);

$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed', 500);
}

// Prepare data
$incidentType = $input['incident_type'];
$date = $input['date'];
$time = $input['time'];
$locationAddress = $input['location_address'];
$locationCity = isset($input['location_city']) ? $input['location_city'] : '';
$locationState = isset($input['location_state']) ? $input['location_state'] : '';
$description = $input['description'];
$perpetratorInfo = isset($input['perpetrator_info']) ? json_encode($input['perpetrator_info']) : null;
$witnesses = isset($input['witnesses']) ? $input['witnesses'] : '';
$emergencyLevel = isset($input['emergency_level']) ? $input['emergency_level'] : 'medium';

// Insert report
$stmt = $conn->prepare("INSERT INTO reports (user_id, incident_type, date, time, location_address, location_city, location_state, description, perpetrator_info, witnesses, emergency_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssssssss", $userId, $incidentType, $date, $time, $locationAddress, $locationCity, $locationState, $description, $perpetratorInfo, $witnesses, $emergencyLevel);

if ($stmt->execute()) {
    $reportId = $conn->insert_id;
    $stmt->close();
    $conn->close();
    
    sendSuccess('Report submitted successfully', [
        'report_id' => $reportId,
        'status' => 'pending'
    ]);
} else {
    $error = $conn->error;
    $stmt->close();
    $conn->close();
    sendError('Failed to submit report: ' . $error, 500);
}



