<?php
/**
 * Twilio SMS Utility
 */

require_once __DIR__ . '/../config/twilio_config.php';

/**
 * Sends an SMS using the Twilio API
 * @param string $to The recipient phone number
 * @param string $message The message body
 * @return array Result with success and message/error
 */
function sendTwilioSMS($to, $message) {
    if (TWILIO_ACCOUNT_SID === 'YOUR_ACCOUNT_SID_HERE') {
        return [
            'success' => false,
            'error' => 'Twilio credentials not configured'
        ];
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . "/Messages.json";
    $from = TWILIO_PHONE_NUMBER;
    
    $data = [
        'From' => $from,
        'To' => $to,
        'Body' => $message
    ];
    
    $postParams = http_build_query($data);
    $auth = TWILIO_ACCOUNT_SID . ":" . TWILIO_AUTH_TOKEN;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $auth);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postParams);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing only
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'CURL Error: ' . $error
        ];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'sid' => $result['sid']
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Twilio Error (' . $httpCode . '): ' . ($result['message'] ?? 'Unknown error')
        ];
    }
}
?>
