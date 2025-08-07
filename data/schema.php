<?php
// /data/schema.php

require_once __DIR__ . '/../config/config.php';

/**
 * Initialize database schema
 */
function initializeDatabase() {
    $pdo = getDbConnection();
    
    // Create users table
    $usersTable = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    // Create tasks table
    $tasksTable = "
        CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
            expiry_date DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    
    try {
        $pdo->exec($usersTable);
        $pdo->exec($tasksTable);
        return true;
    } catch (PDOException $e) {
        error_log("Database schema creation failed: " . $e->getMessage());
        return false;
    }
}

// Auto-initialize if this file is run directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    if (initializeDatabase()) {
        echo "Database schema initialized successfully!\n";
    } else {
        echo "Failed to initialize database schema!\n";
    }
}
