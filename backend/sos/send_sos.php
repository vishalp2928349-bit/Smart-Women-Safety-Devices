<?php
/**
 * Send SOS Alert
 * POST: /backend/sos/send_sos.php
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

$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed', 500);
}

// Get user's emergency contacts
$contactsStmt = $conn->prepare("SELECT name, phone FROM emergency_contacts WHERE user_id = ?");
$contactsStmt->bind_param("i", $userId);
$contactsStmt->execute();
$contactsResult = $contactsStmt->get_result();

$contacts = [];
while ($row = $contactsResult->fetch_assoc()) {
    $contacts[] = $row;
}
$contactsStmt->close();

// Get user info
$userStmt = $conn->prepare("SELECT name, phone, emergency_contact FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

// Prepare location data
$latitude = isset($input['latitude']) ? floatval($input['latitude']) : null;
$longitude = isset($input['longitude']) ? floatval($input['longitude']) : null;
$locationAddress = isset($input['location_address']) ? $input['location_address'] : null;

// Insert SOS alert
$stmt = $conn->prepare("INSERT INTO sos_alerts (user_id, latitude, longitude, location_address, status) VALUES (?, ?, ?, ?, 'active')");
$stmt->bind_param("idds", $userId, $latitude, $longitude, $locationAddress);

if ($stmt->execute()) {
    $alertId = $conn->insert_id;
    $stmt->close();
    
    // Integrate Twilio SMS
    require_once '../utils/twilio.php';
    
    $notifiedList = [];
    $sosMessage = "🚨 SOS ALERT from {$user['name']}! Location: " . 
                  ($latitude ? "https://www.google.com/maps?q={$latitude},{$longitude}" : "Unknown") . 
                  ". Please check the SafetyGuard app.";
                  
    foreach ($contacts as $contact) {
        $smsResult = sendTwilioSMS($contact['phone'], $sosMessage);
        $notifiedList[] = [
            'name' => $contact['name'],
            'phone' => $contact['phone'],
            'status' => $smsResult['success'] ? 'sent' : 'failed',
            'error' => $smsResult['success'] ? null : $smsResult['error']
        ];
    }
    
    // For now, we'll just return the alert data
    $alertData = [
        'alert_id' => $alertId,
        'user' => $user,
        'location' => [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $locationAddress
        ],
        'contacts_notified' => count($notifiedList),
        'notification_details' => $notifiedList,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $conn->close();
    
    sendSuccess('SOS alert sent successfully', $alertData);
} else {
    $error = $conn->error;
    $stmt->close();
    $conn->close();
    sendError('Failed to send SOS alert: ' . $error, 500);
}



