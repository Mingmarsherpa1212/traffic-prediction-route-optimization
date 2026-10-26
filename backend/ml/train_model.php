<?php
/**
 * Decision Tree Model Trainer
 *
 * Traffic Level Prediction System - Phase 3
 * BCA 6th Semester Academic Project
 *
 * IMPORTANT: The traffic_data table contains SYNTHETIC/DEMO data only.
 * This is NOT real-time or live traffic information.
 *
 * What this script does:
 *   1. Reads all 504 rows from traffic_data (joined with locations).
 *   2. Builds feature samples and labels.
 *   3. Trains a ClassificationTree using Rubix ML 2.6.
 *   4. Saves the model to: data/traffic_model.rbx
 *
 * Features (all categorical strings):
 *   - location_name  e.g. "Chabahil"
 *   - day            e.g. "Monday"
 *   - time_period    e.g. "Morning"
 *   - weather        e.g. "Sunny"
 *
 * Target: traffic_level -> "Low" | "Medium" | "High"
 *
 * URL: http://localhost/traffic_prediction/backend/ml/train_model.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Rubix\ML\PersistentModel;
use Rubix\ML\Classifiers\ClassificationTree;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Persisters\Filesystem;

header('Content-Type: application/json; charset=utf-8');

// Load $pdo from database config
require_once __DIR__ . '/../config/database.php';

try {

    // Step 1: Read training data from MySQL
    // JOIN traffic_data with locations to get the location name string
    $stmt = $pdo->prepare("
        SELECT l.location_name,
               td.day,
               td.time_period,
               td.weather,
               td.traffic_level
        FROM   traffic_data td
        JOIN   locations    l ON td.location_id = l.id
        ORDER  BY td.id ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No training data found in traffic_data table.']);
        exit;
    }

    // Step 2: Build samples (features) and labels (targets)
    // Rubix ML ClassificationTree handles string values as categorical features
    $samples = [];
    $labels  = [];

    foreach ($rows as $row) {
        $samples[] = [
            $row['location_name'],  // Feature 1: location
            $row['day'],            // Feature 2: day of week
            $row['time_period'],    // Feature 3: time period
            $row['weather'],        // Feature 4: weather
        ];
        $labels[] = $row['traffic_level']; // Label: Low / Medium / High
    }

    // Step 3: Create Rubix ML Labeled dataset
    $dataset = new Labeled($samples, $labels);

    // Step 4: Create the Decision Tree classifier
    // Rubix ML 2.6 ClassificationTree constructor:
    //   maxHeight         - max depth of tree
    //   maxLeafSize       - max samples at a leaf node
    //   minPurityIncrease - min information gain to make a split
    $tree = new ClassificationTree(
        maxHeight: 10,
        maxLeafSize: 3,
        minPurityIncrease: 1e-7
    );

    // Step 5: Define model save path
    $modelPath = __DIR__ . '/../../data/traffic_model.rbx';

    if (!is_dir(dirname($modelPath))) {
        mkdir(dirname($modelPath), 0755, true);
    }

    // Step 6: Wrap in PersistentModel for save/load support
    $estimator = new PersistentModel($tree, new Filesystem($modelPath));

    // Step 7: Train the model on all 504 rows
    $estimator->train($dataset);

    // Step 8: Save the trained model to disk
    $estimator->save();

    // Step 9: Quick verification - predict one sample
    $testDataset = new Unlabeled([['Chabahil', 'Monday', 'Morning', 'Sunny']]);
    $testPred    = $estimator->predict($testDataset);

    echo json_encode([
        'success'         => true,
        'message'         => 'Decision Tree trained and saved successfully.',
        'model_path'      => $modelPath,
        'training_rows'   => count($rows),
        'features'        => ['location_name', 'day', 'time_period', 'weather'],
        'classes'         => ['Low', 'Medium', 'High'],
        'data_note'       => 'Training data is SYNTHETIC/DEMO only - not real-time traffic.',
        'test_prediction' => [
            'input' => [
                'location'    => 'Chabahil',
                'day'         => 'Monday',
                'time_period' => 'Morning',
                'weather'     => 'Sunny',
            ],
            'predicted_level' => $testPred[0],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[TrafficPrediction] train_model.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Training failed: ' . $e->getMessage(),
    ]);
}