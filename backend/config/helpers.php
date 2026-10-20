<?php
/**
 * Common API helpers
 * 
 * Used by all backend API endpoints to send consistent JSON responses
 * and set appropriate headers.
 */

/**
 * Send a JSON success response and exit.
 *
 * @param mixed $data
 * @param int   $statusCode HTTP status code
 */
function sendSuccess(mixed $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send a JSON error response and exit.
 *
 * @param string $message
 * @param int    $statusCode HTTP status code
 */
function sendError(string $message, int $statusCode = 400): void {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Set CORS and JSON content-type headers.
 * Called at the top of every API endpoint.
 */
function setApiHeaders(): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    // Handle OPTIONS preflight request (from React's fetch)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
