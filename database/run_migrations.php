<?php
/**
 * Database Migration Runner
 * 
 * Creates the users table if it does not already exist.
 * Safe to run multiple times (uses CREATE TABLE IF NOT EXISTS).
 * 
 * Access via: http://localhost/traffic_prediction/database/run_migrations.php
 * 
 * NOTE: Remove this file after running, or protect it in production.
 */

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/config/helpers.php';

setApiHeaders();

try {
    $pdo = getDBConnection();

    $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id         INT(11)      NOT NULL AUTO_INCREMENT,
            username   VARCHAR(50)  NOT NULL,
            email      VARCHAR(100) NOT NULL,
            password   VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed password',
            role       ENUM('user', 'admin') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_username (username),
            UNIQUE KEY uq_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";

    $pdo->exec($sql);

    // Verify all expected tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    sendSuccess([
        'message' => 'Migration complete. users table created (or already exists).',
        'tables'  => $tables,
    ]);

} catch (PDOException $e) {
    sendError('Migration failed: ' . $e->getMessage(), 500);
}
