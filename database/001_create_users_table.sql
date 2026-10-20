-- ============================================================
-- Traffic Level Prediction System
-- BCA 6th Semester Academic Project
-- 
-- Migration: Add missing users table
-- 
-- IMPORTANT:
--   - This script ONLY creates the users table.
--   - It does NOT touch or modify existing tables.
--   - Run this once if the users table does not exist.
--   - USE IF NOT EXISTS ensures it is safe to re-run.
-- ============================================================

USE traffic_prediction;

CREATE TABLE IF NOT EXISTS users (
    id         INT(11)      NOT NULL AUTO_INCREMENT,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed password',
    role       ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verify
SELECT 'users table created (or already exists).' AS status;
