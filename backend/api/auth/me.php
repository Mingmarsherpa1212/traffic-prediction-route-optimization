<?php
/**
 * Current User / Session Check API
 *
 * Traffic Level Prediction System - Phase 8
 * BCA 6th Semester Academic Project
 *
 * Method : GET
 * URL    : http://localhost/traffic_prediction/backend/api/auth/me.php
 *
 * Called by React on app load to check if the user is already logged in.
 *
 * Response when logged in (200):
 *   { "logged_in": true, "user": { id, username, email, role } }
 *
 * Response when not logged in (200):
 *   { "logged_in": false }
 */

require_once __DIR__ . '/../../config/session.php';

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email'    => $_SESSION['email'],
            'role'     => $_SESSION['role'],
        ],
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
