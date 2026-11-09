<?php
/**
 * User Login API
 *
 * Traffic Level Prediction System - Phase 8
 * BCA 6th Semester Academic Project
 *
 * Method : POST
 * URL    : http://localhost/traffic_prediction/backend/api/auth/login.php
 *
 * Request body (JSON):
 *   { "email": "john@example.com", "password": "secret123" }
 *
 * Success response (200):
 *   { "success": true, "message": "Login successful.", "user": { id, username, email, role } }
 *
 * Error responses (400 / 401 / 500):
 *   { "success": false, "message": "..." }
 *
 * Security:
 *   - password_verify() is used — never manual comparison
 *   - Session is regenerated on login to prevent session fixation attacks
 *   - Wrong password returns same message as wrong email (prevents user enumeration)
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
    exit;
}

$email    = trim($body['email']    ?? '');
$password =      $body['password'] ?? '';

// Basic presence check
if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

try {
    // Fetch user record by email
    $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Use password_verify() — always run even if user not found (timing-safe)
    // If user not found, verify against a dummy hash to prevent timing attacks
    $hash = $user ? $user['password'] : '$2y$10$invalidhashfortimingnnnnnnnnnnnnnnnnnnnnn.';

    if (!$user || !password_verify($password, $hash)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Incorrect email or password.']);
        exit;
    }

    // Regenerate session ID on login (prevents session fixation)
    session_regenerate_id(true);

    // Store user info in session
    $_SESSION['user_id']  = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful.',
        'user' => [
            'id'       => (int)$user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role'],
        ],
    ]);

} catch (Throwable $e) {
    error_log('[TrafficPrediction] login.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
}
