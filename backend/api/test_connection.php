<?php
/**
 * Database Connection Test
 
 * Tests whether PHP can connect to the traffic_prediction database.
 * Loads $pdo directly from database.php via require_once.
 *
 * URL: http://localhost/traffic_prediction/backend/api/test_connection.php
 */

header("Content-Type: application/json; charset=utf-8");

// Load $pdo — if connection fails, database.php calls die() with a JSON error
require_once __DIR__ . "/../config/database.php";

try {
    // Confirm the connection is alive with a minimal query
    $pdo->query("SELECT 1");

    echo json_encode([
        "success" => true,
        "message" => "Database connection successful"
    ]);

} catch (PDOException $e) {
    error_log("[TrafficPrediction] Query failed: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
}
