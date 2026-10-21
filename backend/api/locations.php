<?php
// ── Headers ──────────────────────────────────────────────────────────────────
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");        // Allow React frontend (localhost:5173)
header("Access-Control-Allow-Methods: GET");

// ── Only allow GET requests ───────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed. Use GET."]);
    exit;
}

// ── Load database connection ($pdo) ──────────────────────────────────────────
require_once __DIR__ . "/../config/database.php";

// ── Query the locations table ─────────────────────────────────────────────────
try {
    // Prepared statement — no user input here, but using prepare() is good practice
    $stmt = $pdo->prepare(
        "SELECT id, location_name, latitude, longitude
         FROM   locations
         ORDER  BY id ASC"
    );
    $stmt->execute();

    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast latitude and longitude from string (DECIMAL) to float
    // so JSON output is: 27.7167 instead of "27.7167000"
    foreach ($locations as &$loc) {
        $loc["id"]        = (int)   $loc["id"];
        $loc["latitude"]  = (float) $loc["latitude"];
        $loc["longitude"] = (float) $loc["longitude"];
    }
    unset($loc); // break reference

    // Return the array directly (matching the spec format)
    echo json_encode($locations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Log real error server-side only
    error_log("[TrafficPrediction] locations.php error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to fetch locations."]);
}








































/**
 * Locations API
 *
 *
 * Returns all locations stored in the `locations` table.
 * Reads data from MySQL using PDO prepared statements.
 * Does NOT hardcode any location data.
 *
 * Method : GET
 * URL    : http://localhost/traffic_prediction/backend/api/locations.php
 *
 * Response example:
 * [
 *   { "id": 1, "location_name": "Kumarigal", "latitude": 27.7167, "longitude": 85.3436 },
 *   ...
 * ]
 */