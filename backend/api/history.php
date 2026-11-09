<?php
/**
 * Prediction History API
 *
 * Traffic Level Prediction System - Phase 7 & 8
 * BCA 6th Semester Academic Project
 *
 * GET  /history.php          - Returns recent predictions (newest first) — public
 * POST /history.php          - Saves a new prediction record — requires login
 *
 * POST body (JSON):
 *   {
 *     "location_id"     : 2,
 *     "day"             : "Monday",
 *     "time_period"     : "Morning",
 *     "weather"         : "Sunny",
 *     "predicted_level" : "High"
 *   }
 *
 * GET response example:
 *   [
 *     {
 *       "id": 1,
 *       "location_name": "Chabahil",
 *       "day": "Monday",
 *       "time_period": "Morning",
 *       "weather": "Sunny",
 *       "predicted_level": "High",
 *       "created_at": "2024-01-01 10:00:00"
 *     },
 *     ...
 *   ]
 */

// Use session-aware CORS (required since Phase 8 for credential-based auth)
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// ─────────────────────────────────────────────────────────────────────────────
// GET — Fetch recent predictions
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $limit = min((int)($_GET['limit'] ?? 20), 100); // max 100

        $stmt = $pdo->prepare("
            SELECT  p.id,
                    l.location_name,
                    p.day,
                    p.time_period,
                    p.weather,
                    p.predicted_level,
                    p.created_at
            FROM    predictions p
            JOIN    locations   l ON p.location_id = l.id
            ORDER   BY p.created_at DESC, p.id DESC
            LIMIT   :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        error_log('[TrafficPrediction] history.php GET error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load history.']);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// POST — Save a new prediction (requires login)
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Auth check — user must be logged in to save predictions
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'You must be logged in to save predictions.']);
        exit;
    }
    try {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
            exit;
        }

        $location_id     = (int)($body['location_id']     ?? 0);
        $day             = trim($body['day']              ?? '');
        $time_period     = trim($body['time_period']      ?? '');
        $weather         = trim($body['weather']          ?? '');
        $predicted_level = trim($body['predicted_level']  ?? '');

        // Validate required fields
        $errors = [];
        if ($location_id <= 0)     $errors[] = 'Invalid location_id';
        if ($day === '')           $errors[] = 'Missing day';
        if ($time_period === '')   $errors[] = 'Missing time_period';
        if ($weather === '')       $errors[] = 'Missing weather';
        if (!in_array($predicted_level, ['Low', 'Medium', 'High'], true))
            $errors[] = 'Invalid predicted_level (must be Low, Medium, or High)';

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            exit;
        }

        // Verify location_id exists in locations table
        $check = $pdo->prepare("SELECT id FROM locations WHERE id = ?");
        $check->execute([$location_id]);
        if (!$check->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "location_id $location_id not found."]);
            exit;
        }

        // Insert the prediction record
        $stmt = $pdo->prepare("
            INSERT INTO predictions (location_id, day, time_period, weather, predicted_level)
            VALUES (:location_id, :day, :time_period, :weather, :predicted_level)
        ");
        $stmt->execute([
            ':location_id'     => $location_id,
            ':day'             => $day,
            ':time_period'     => $time_period,
            ':weather'         => $weather,
            ':predicted_level' => $predicted_level,
        ]);

        $newId = (int)$pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Prediction saved.',
            'id'      => $newId,
        ]);

    } catch (Throwable $e) {
        error_log('[TrafficPrediction] history.php POST error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save prediction.']);
    }
    exit;
}

// Any other method
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed. Use GET or POST.']);
