<?php
/**
 * Share Location
 * POST: /backend/sos/share_location.php
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
    sendError('Unauthorized access', 401);
}

$userId = $_SESSION['user_id'];

// Get and sanitize input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$input = sanitizeInput($input);

// Validate required fields
validateRequired(['lat', 'lng'], $input);

$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed', 500);
}

// Log location to sos_alerts table as a 'share' event
$stmt = $conn->prepare("INSERT INTO sos_alerts (user_id, latitude, longitude, status) VALUES (?, ?, ?, 'shared')");
$stmt->bind_param("idd", $userId, $input['lat'], $input['lng']);

if ($stmt->execute()) {
    $alertId = $conn->insert_id;
    $stmt->close();
    $conn->close();
    
    sendSuccess('Location shared successfully', [
        'alert_id' => $alertId,
        'lat' => $input['lat'],
        'lng' => $input['lng']
    ]);
} else {
    $error = $conn->error;
    $stmt->close();
    $conn->close();
    sendError('Failed to share location: ' . $error, 500);
}
?>
