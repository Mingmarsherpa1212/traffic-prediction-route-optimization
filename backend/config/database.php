<?php
/**
 * Database Configuration & PDO Connection
 *
 * Traffic Level Prediction System
 * BCA 6th Semester Academic Project
 *
 * NOTE: '127.0.0.1' is used instead of 'localhost' because on this XAMPP
 * setup, MariaDB 10.4.32 uses socket auth for 'localhost', which causes
 * "Access denied". Using '127.0.0.1' forces a TCP/IP connection and works
 * correctly with an empty root password on port 3307.
 */

$host     = "127.0.0.1";
$port     = "3307";
$dbname   = "traffic_prediction";
$username = "root";
$password = "";

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Log the real error server-side only — never expose it to the client
    error_log("[TrafficPrediction] Database connection failed: " . $e->getMessage());
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]));
}
