<?php
/**
 * Session & CORS Helper
 *
 * Traffic Level Prediction System - Phase 8
 * BCA 6th Semester Academic Project
 *
 * Include this file at the top of every auth endpoint.
 * It starts the PHP session and sets the CORS headers needed for
 * the React dev server (localhost:5173) to send cookies.
 *
 * Why specific origin instead of '*'?
 * Browsers block credentials (cookies) with Access-Control-Allow-Origin: *
 * We must specify the exact origin to allow session cookies.
 */

// Allow session cookies across ports on localhost (Vite dev server)
session_set_cookie_params([
    'lifetime' => 0,          // cookie lives until browser closes
    'path'     => '/',
    'domain'   => 'localhost',
    'secure'   => false,      // localhost does not use HTTPS
    'httponly' => true,        // JS cannot read the cookie (security)
    'samesite' => 'Lax',      // allows cross-port on same hostname
]);

session_start();

// ── CORS headers ──────────────────────────────────────────────────────────────
// Allow requests from the React Vite dev server
$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:5174',
    'http://localhost',       // production build served from XAMPP
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Default to Vite dev port when no origin header (e.g. direct browser access)
    header('Access-Control-Allow-Origin: http://localhost:5173');
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
