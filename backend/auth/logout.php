<?php
/**
 * User Logout
 * POST: /backend/auth/logout.php
 */

require_once '../utils/response.php';

// Start session
session_start();

// Destroy session
session_unset();
session_destroy();

sendSuccess('Logout successful');



