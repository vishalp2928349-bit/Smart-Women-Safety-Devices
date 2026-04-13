<?php
/**
 * Response Utility Functions
 * Standardized API responses
 */

header('Content-Type: application/json');

function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

function sendSuccess($message, $data = null) {
    sendResponse(true, $message, $data, 200);
}

function sendError($message, $statusCode = 400) {
    sendResponse(false, $message, null, $statusCode);
}

function validateRequired($fields, $data) {
    $missing = [];
    
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        sendError('Missing required fields: ' . implode(', ', $missing), 400);
    }
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    // Remove non-numeric characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($phone) >= 10;
}



