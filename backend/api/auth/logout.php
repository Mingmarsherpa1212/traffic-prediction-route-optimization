<?php
/**
 * Logout API
 *
 * Traffic Level Prediction System - Phase 8
 * BCA 6th Semester Academic Project
 *
 * Method : POST
 * URL    : http://localhost/traffic_prediction/backend/api/auth/logout.php
 *
 * Destroys the PHP session and clears the session cookie.
 *
 * Success response (200):
 *   { "success": true, "message": "Logged out successfully." }
 */

require_once __DIR__ . '/../../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// Clear all session data
$_SESSION = [];

// Delete the session cookie from the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,         // set expiry in the past
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session on the server
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
