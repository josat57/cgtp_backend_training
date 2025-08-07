<?php
// /config/config.php

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'benrich_task');
define('DB_USER', 'root');
define('DB_PASS', '');

// JWT Configuration
define('JWT_SECRET_KEY', 'your-secret-key-change-this-in-production');
define('JWT_EXPIRATION_SECONDS', 3600); // 1 hour

// Application Configuration
define('APP_NAME', 'BenRich Task API');
define('APP_VERSION', '1.0.0');

/**
 * Get database connection
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed']);
            exit;
        }
    }
    
    return $pdo;
}
