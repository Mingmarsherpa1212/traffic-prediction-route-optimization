<?php
/**
 * Route Optimization API
 *
 * Traffic Level Prediction System - Phase 4
 * BCA 6th Semester Academic Project
 *
 * Combines the Decision Tree prediction (Phase 3) with Dijkstra's algorithm
 * (Phase 4) to recommend the lowest-cost route between two locations.
 *
 * IMPORTANT: All traffic predictions are based on SYNTHETIC/DEMO data.
 * This is NOT a real-time navigation system.
 *
 * Method : GET
 * URL    : http://localhost/traffic_prediction/backend/api/route.php
 *
 * Query parameters:
 *   start       - e.g. "Kumarigal"
 *   destination - e.g. "Jorpati"
 *   day         - e.g. "Monday"
 *   time_period - e.g. "Morning"
 *   weather     - e.g. "Sunny"
 *
 * Traffic-to-cost weights (academic prototype):
 *   Low    = 1
 *   Medium = 3
 *   High   = 6
 *
 * Edge cost formula:
 *   edge_cost = distance_km x traffic_weight_of_destination_node
 *
 * Example response:
 * {
 *   "start": "Kumarigal",
 *   "destination": "Jorpati",
 *   "route": ["Kumarigal", "Chabahil", "Boudha", "Jorpati"],
 *   "total_distance_km": 3.30,
 *   "total_cost": ...,
 *   "traffic_levels": { "Kumarigal": "Low", "Chabahil": "High", ... },
 *   ...
 * }
 */

// ── Headers ───────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use GET.']);
    exit;
}

// ── Load dependencies ─────────────────────────────────────────────────────────
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';       // gives us $pdo
require_once __DIR__ . '/../algorithm/dijkstra.php';    // gives us dijkstra()

use Rubix\ML\PersistentModel;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Persisters\Filesystem;

// ── Traffic cost weights (academic prototype) ─────────────────────────────────
const TRAFFIC_WEIGHTS = [
    'Low'    => 1,
    'Medium' => 3,
    'High'   => 6,
];

// ── Allowed input values ──────────────────────────────────────────────────────
$allowedLocations   = ['Kumarigal', 'Chabahil', 'Boudha', 'Jorpati', 'Naya Basti', 'Kalanki'];
$allowedDays        = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$allowedTimePeriods = ['Morning', 'Afternoon', 'Evening', 'Night'];
$allowedWeathers    = ['Sunny', 'Cloudy', 'Rainy'];

// ── Step 1: Read and validate input parameters ────────────────────────────────
$start       = trim($_GET['start']       ?? '');
$destination = trim($_GET['destination'] ?? '');
$day         = trim($_GET['day']         ?? '');
$time_period = trim($_GET['time_period'] ?? '');
$weather     = trim($_GET['weather']     ?? '');

$errors = [];
if ($start === '')       $errors[] = 'Missing parameter: start';
if ($destination === '') $errors[] = 'Missing parameter: destination';
if ($day === '')         $errors[] = 'Missing parameter: day';
if ($time_period === '') $errors[] = 'Missing parameter: time_period';
if ($weather === '')     $errors[] = 'Missing parameter: weather';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Whitelist validation
$validationErrors = [];
if (!in_array($start,       $allowedLocations,   true)) $validationErrors[] = "Invalid start: \"$start\"";
if (!in_array($destination, $allowedLocations,   true)) $validationErrors[] = "Invalid destination: \"$destination\"";
if (!in_array($day,         $allowedDays,         true)) $validationErrors[] = "Invalid day: \"$day\"";
if (!in_array($time_period, $allowedTimePeriods,  true)) $validationErrors[] = "Invalid time_period: \"$time_period\"";
if (!in_array($weather,     $allowedWeathers,     true)) $validationErrors[] = "Invalid weather: \"$weather\"";

if (!empty($validationErrors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $validationErrors)]);
    exit;
}

if ($start === $destination) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Start and destination cannot be the same location.']);
    exit;
}

// ── Step 2: Load the trained Decision Tree model ──────────────────────────────
$modelPath = __DIR__ . '/../../data/traffic_model.rbx';

if (!file_exists($modelPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'ML model not found. Run train_model.php first.',
        'train_url' => '/traffic_prediction/backend/ml/train_model.php',
    ]);
    exit;
}

try {
    $mlModel = PersistentModel::load(new Filesystem($modelPath));
} catch (Throwable $e) {
    error_log('[TrafficPrediction] route.php - model load error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load ML model.']);
    exit;
}

// ── Step 3: Load all locations from the database ──────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT id, location_name, latitude, longitude FROM locations ORDER BY id ASC");
    $stmt->execute();
    $locationsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[TrafficPrediction] route.php - locations query error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load locations from database.']);
    exit;
}

// Build lookup maps
// $locById[id]   = ['id', 'location_name', 'latitude', 'longitude']
// $locByName[name] = id
$locById   = [];
$locByName = [];

foreach ($locationsRaw as $loc) {
    $locById[(int)$loc['id']]    = $loc;
    $locByName[$loc['location_name']] = (int)$loc['id'];
}

// Get IDs for start and destination
$startId = $locByName[$start]       ?? null;
$endId   = $locByName[$destination] ?? null;

if ($startId === null || $endId === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Location IDs not found in database.']);
    exit;
}

// ── Step 4: Predict traffic level for EVERY location ─────────────────────────
//
// We predict traffic for all 6 locations using the same day/time/weather.
// These predictions are used to:
//   (a) Colour the map markers (Phase 6)
//   (b) Calculate traffic-adjusted edge costs for Dijkstra
//
$trafficLevels  = []; // location_name => "Low" | "Medium" | "High"
$trafficWeights = []; // location_id   => 1 | 3 | 6

$predSamples = [];
$predLocIds  = [];

foreach ($locationsRaw as $loc) {
    $predSamples[] = [$loc['location_name'], $day, $time_period, $weather];
    $predLocIds[]  = (int)$loc['id'];
}

try {
    $predictions = $mlModel->predict(new Unlabeled($predSamples));

    foreach ($predictions as $i => $level) {
        $locId   = $predLocIds[$i];
        $locName = $locById[$locId]['location_name'];

        $trafficLevels[$locName]  = $level;
        $trafficWeights[$locId]   = TRAFFIC_WEIGHTS[$level] ?? 1;
    }
} catch (Throwable $e) {
    error_log('[TrafficPrediction] route.php - prediction error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Traffic prediction failed.']);
    exit;
}

// ── Step 5: Load road segments and build the graph ────────────────────────────
//
// Road segments are treated as BIDIRECTIONAL (both directions are added).
// Edge cost = distance_km × traffic_weight_of_the_destination_node
//
// Rationale: if a destination location has High traffic, it costs more to
// travel there. This is a simplified academic prototype model.
//
try {
    $stmt = $pdo->prepare("
        SELECT start_location_id, end_location_id, distance_km
        FROM   road_segments
        ORDER  BY id ASC
    ");
    $stmt->execute();
    $segments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[TrafficPrediction] route.php - segments query error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load road segments from database.']);
    exit;
}

// Build adjacency list graph
// $graph[fromId][toId] = traffic-adjusted edge cost
$graph         = [];
$segmentDistances = []; // key: "fromId-toId" => distance_km (for total distance calc)

// Initialise every location as a node (even if it has no edges yet)
foreach ($locById as $id => $_) {
    $graph[$id] = [];
}

foreach ($segments as $seg) {
    $fromId  = (int)$seg['start_location_id'];
    $toId    = (int)$seg['end_location_id'];
    $distKm  = (float)$seg['distance_km'];

    // Forward direction: cost = distance × traffic_weight_at_destination (toId)
    $forwardCost  = $distKm * ($trafficWeights[$toId]   ?? 1);
    // Backward direction: cost = distance × traffic_weight_at_destination (fromId)
    $backwardCost = $distKm * ($trafficWeights[$fromId] ?? 1);

    $graph[$fromId][$toId] = $forwardCost;
    $graph[$toId][$fromId] = $backwardCost;

    // Store raw distances for both directions (for reporting)
    $segmentDistances["$fromId-$toId"] = $distKm;
    $segmentDistances["$toId-$fromId"] = $distKm;
}

// ── Step 6: Run Dijkstra ──────────────────────────────────────────────────────
$result = dijkstra($graph, $startId, $endId);

if (!$result['found']) {
    echo json_encode([
        'success'    => false,
        'message'    => "No route found from \"$start\" to \"$destination\".",
        'start'      => $start,
        'destination'=> $destination,
    ]);
    exit;
}

// ── Step 7: Build the detailed route response ─────────────────────────────────
$routeIds   = $result['path'];  // e.g. [1, 2, 3, 4]
$totalCost  = $result['cost'];  // Dijkstra total cost

// Convert node ID path to location name path
$routeNames = array_map(fn($id) => $locById[$id]['location_name'], $routeIds);

// Calculate total raw distance (sum of km along the chosen path)
$totalDistanceKm = 0.0;

// Build step-by-step breakdown
$steps = [];
for ($i = 0; $i < count($routeIds) - 1; $i++) {
    $fromId      = $routeIds[$i];
    $toId        = $routeIds[$i + 1];
    $distKm      = $segmentDistances["$fromId-$toId"] ?? 0.0;
    $toName      = $locById[$toId]['location_name'];
    $trafficLevel = $trafficLevels[$toName] ?? 'Unknown';
    $weight      = TRAFFIC_WEIGHTS[$trafficLevel] ?? 1;
    $edgeCost    = round($distKm * $weight, 4);

    $totalDistanceKm += $distKm;

    $steps[] = [
        'from'          => $locById[$fromId]['location_name'],
        'to'            => $toName,
        'distance_km'   => $distKm,
        'traffic_level' => $trafficLevel,
        'traffic_weight'=> $weight,
        'edge_cost'     => $edgeCost,
    ];
}

// Build full traffic_levels map for the map display (all 6 locations)
$trafficMap = [];
foreach ($locationsRaw as $loc) {
    $name = $loc['location_name'];
    $trafficMap[$name] = [
        'traffic_level'  => $trafficLevels[$name]  ?? 'Unknown',
        'traffic_weight' => $trafficWeights[$locByName[$name]] ?? 1,
        'latitude'       => (float)$loc['latitude'],
        'longitude'      => (float)$loc['longitude'],
    ];
}

// ── Step 8: Return the JSON response ─────────────────────────────────────────
echo json_encode([
    'success'          => true,
    'start'            => $start,
    'destination'      => $destination,
    'day'              => $day,
    'time_period'      => $time_period,
    'weather'          => $weather,
    'route'            => $routeNames,
    'total_distance_km'=> round($totalDistanceKm, 2),
    'total_cost'       => $totalCost,
    'steps'            => $steps,
    'all_traffic_levels' => $trafficMap,
    'data_note'        => 'Traffic predictions are based on SYNTHETIC/DEMO data only. Not real-time.',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
