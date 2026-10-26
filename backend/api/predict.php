<?php
/**
 * Traffic Level Prediction API
 *
 * Traffic Level Prediction System - Phase 3
 * BCA 6th Semester Academic Project
 *
 * IMPORTANT: Predictions are based on SYNTHETIC/DEMO training data only.
 * This is NOT a real-time traffic system.
 *
 * Loads the pre-trained Decision Tree model and predicts traffic level
 * for a given location, day, time period, and weather.
 *
 * Method: GET
 * URL:    http://localhost/traffic_prediction/backend/api/predict.php
 *
 * Query parameters:
 *   location    - e.g. "Chabahil"
 *   day         - e.g. "Monday"
 *   time_period - e.g. "Morning"
 *   weather     - e.g. "Sunny"
 *
 * Example request:
 *   /predict.php?location=Chabahil&day=Monday&time_period=Morning&weather=Sunny
 *
 * Example response:
 *   {
 *     "location": "Chabahil",
 *     "day": "Monday",
 *     "time_period": "Morning",
 *     "weather": "Sunny",
 *     "predicted_level": "High",
 *     "data_note": "Prediction based on synthetic/demo data only."
 *   }
 *
 * IMPORTANT: Run train_model.php first to generate the model file.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Rubix\ML\PersistentModel;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Persisters\Filesystem;

// Set JSON header and allow React frontend to call this API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

// Handle preflight OPTIONS request from browsers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use GET.']);
    exit;
}

// ---------------------------------------------------------------------------
// Step 1: Validate input parameters
// ---------------------------------------------------------------------------
$allowedLocations   = ['Kumarigal', 'Chabahil', 'Boudha', 'Jorpati', 'Naya Basti', 'Kalanki'];
$allowedDays        = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$allowedTimePeriods = ['Morning', 'Afternoon', 'Evening', 'Night'];
$allowedWeathers    = ['Sunny', 'Cloudy', 'Rainy'];

$location    = trim($_GET['location']    ?? '');
$day         = trim($_GET['day']         ?? '');
$time_period = trim($_GET['time_period'] ?? '');
$weather     = trim($_GET['weather']     ?? '');

// Check that all parameters were provided
$errors = [];
if ($location === '')    $errors[] = 'Missing parameter: location';
if ($day === '')         $errors[] = 'Missing parameter: day';
if ($time_period === '') $errors[] = 'Missing parameter: time_period';
if ($weather === '')     $errors[] = 'Missing parameter: weather';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors),
        'allowed' => [
            'location'    => $allowedLocations,
            'day'         => $allowedDays,
            'time_period' => $allowedTimePeriods,
            'weather'     => $allowedWeathers,
        ],
    ]);
    exit;
}

// Check that all parameters are valid values (whitelist validation)
$validationErrors = [];
if (!in_array($location,    $allowedLocations,   true)) $validationErrors[] = "Invalid location: \"$location\"";
if (!in_array($day,         $allowedDays,         true)) $validationErrors[] = "Invalid day: \"$day\"";
if (!in_array($time_period, $allowedTimePeriods,  true)) $validationErrors[] = "Invalid time_period: \"$time_period\"";
if (!in_array($weather,     $allowedWeathers,     true)) $validationErrors[] = "Invalid weather: \"$weather\"";

if (!empty($validationErrors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $validationErrors),
        'allowed' => [
            'location'    => $allowedLocations,
            'day'         => $allowedDays,
            'time_period' => $allowedTimePeriods,
            'weather'     => $allowedWeathers,
        ],
    ]);
    exit;
}

// ---------------------------------------------------------------------------
// Step 2: Load the trained Decision Tree model from disk
// ---------------------------------------------------------------------------
$modelPath = __DIR__ . '/../../data/traffic_model.rbx';

if (!file_exists($modelPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Model file not found. Please run train_model.php first.',
        'train_url' => '/traffic_prediction/backend/ml/train_model.php',
    ]);
    exit;
}

try {
    $estimator = PersistentModel::load(new Filesystem($modelPath));

    // ---------------------------------------------------------------------------
    // Step 3: Run the prediction
    // ---------------------------------------------------------------------------
    // Build a single-row Unlabeled dataset with the 4 input features.
    // Feature order must match the order used during training:
    //   [location_name, day, time_period, weather]
    $dataset     = new Unlabeled([[$location, $day, $time_period, $weather]]);
    $predictions = $estimator->predict($dataset);

    $predictedLevel = $predictions[0]; // "Low" | "Medium" | "High"

    // ---------------------------------------------------------------------------
    // Step 4: Return the JSON response
    // ---------------------------------------------------------------------------
    echo json_encode([
        'location'        => $location,
        'day'             => $day,
        'time_period'     => $time_period,
        'weather'         => $weather,
        'predicted_level' => $predictedLevel,
        'data_note'       => 'Prediction based on synthetic/demo data only. Not real-time traffic.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[TrafficPrediction] predict.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Prediction failed. Please check the server error log.',
    ]);
}
