<?php
/**
 * User Registration API
 *
 * Traffic Level Prediction System - Phase 8
 * BCA 6th Semester Academic Project
 *
 * Method : POST
 * URL    : http://localhost/traffic_prediction/backend/api/auth/register.php
 *
 * Request body (JSON):
 *   {
 *     "username": "john",
 *     "email"   : "john@example.com",
 *     "password": "secret123"
 *   }
 *
 * Success response (201):
 *   { "success": true, "message": "Registration successful.", "username": "john" }
 *
 * Error responses (400 / 409 / 500):
 *   { "success": false, "message": "..." }
 *
 * Security:
 *   - Password stored using password_hash() with PASSWORD_DEFAULT (bcrypt)
 *   - Plain-text password is NEVER stored or logged
 *   - Duplicate email is rejected with 409 Conflict
 *   - All inputs validated and sanitised
 *   - PDO prepared statements used throughout
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// ── Read and decode JSON body ─────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
    exit;
}

$username = trim($body['username'] ?? '');
$email    = trim($body['email']    ?? '');
$password =      $body['password'] ?? '';

// ── Validation ────────────────────────────────────────────────────────────────
$errors = [];

if ($username === '') {
    $errors[] = 'Username is required.';
} elseif (strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters.';
} elseif (strlen($username) > 50) {
    $errors[] = 'Username must not exceed 50 characters.';
}

if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
} elseif (strlen($email) > 100) {
    $errors[] = 'Email must not exceed 100 characters.';
}

if ($password === '') {
    $errors[] = 'Password is required.';
} elseif (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    // ── Check for duplicate email ─────────────────────────────────────────────
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->execute([$email]);

    if ($check->fetch()) {
        http_response_code(409); // 409 Conflict
        echo json_encode(['success' => false, 'message' => 'This email is already registered. Please log in.']);
        exit;
    }

    // ── Hash the password (bcrypt) ────────────────────────────────────────────
    // password_hash() uses PASSWORD_DEFAULT which is bcrypt.
    // Plain-text password is not stored anywhere.
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // ── Insert new user ───────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, role)
        VALUES (:username, :email, :password, 'user')
    ");
    $stmt->execute([
        ':username' => $username,
        ':email'    => $email,
        ':password' => $hashedPassword,
    ]);

    $newId = (int)$pdo->lastInsertId();

    // ── Start session for the newly registered user ───────────────────────────
    $_SESSION['user_id']  = $newId;
    $_SESSION['username'] = $username;
    $_SESSION['email']    = $email;
    $_SESSION['role']     = 'user';

    http_response_code(201);
    echo json_encode([
        'success'  => true,
        'message'  => 'Registration successful. You are now logged in.',
        'user' => [
            'id'       => $newId,
            'username' => $username,
            'email'    => $email,
            'role'     => 'user',
        ],
    ]);

} catch (Throwable $e) {
    error_log('[TrafficPrediction] register.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
