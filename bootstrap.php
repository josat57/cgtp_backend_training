<?php

use Dotenv\Dotenv;
use MongoDB\Client as MongoClient;

// ------------------------------
// Suppress deprecation notices
// ------------------------------
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// ------------------------------
// Load Composer's autoloader
// ------------------------------
require __DIR__ . '/vendor/autoload.php';

// ------------------------------
// Load environment variables
// ------------------------------
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); // safeLoad avoids fatal error if .env missing

// ------------------------------
// Helper env() function
// ------------------------------
function env(string $key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null) return $default;

    switch (strtolower($value)) {
        case 'true':  return true;
        case 'false': return false;
        case 'null':  return null;
    }
    return $value;
}

// ------------------------------
// Validate required environment variables
// ------------------------------
$requiredEnvVars = ['DB_DSN', 'DB_NAME', 'JWT_SECRET'];
$missingVars = array_filter($requiredEnvVars, fn($var) => env($var) === null);

if (!empty($missingVars)) {
    die("❌ Missing required environment variables: " . implode(', ', $missingVars));
}

// ------------------------------
// Set error reporting based on environment
// ------------------------------
if (env('APP_ENV') === 'development' || env('APP_DEBUG') === true) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ------------------------------
// Set timezone
// ------------------------------
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

// ------------------------------
// Initialize MongoDB
// ------------------------------
try {
    $mongoClient = new MongoClient(env('DB_DSN'), [
        'connectTimeoutMS' => 5000,
        'socketTimeoutMS' => 5000,
        'serverSelectionTimeoutMS' => 5000,
    ]);
    
    // Force connection to check if server is available
    $mongoClient->listDatabases();
    
    $db = $mongoClient->selectDatabase(env('DB_NAME'));
    
    // Test the connection
    $db->command(['ping' => 1]);
    
} catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
    die(json_encode([
        'error' => 'MongoDB Connection Failed',
        'message' => 'Could not connect to MongoDB server. Is it running?',
        'details' => $e->getMessage(),
        'dsn' => env('DB_DSN')
    ]));
} catch (Exception $e) {
    die(json_encode([
        'error' => 'MongoDB Error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]));
}

// ------------------------------
// Default JWT Config
// ------------------------------
if (!env('JWT_ALGORITHM')) {
    putenv('JWT_ALGORITHM=HS256');
}

if (!env('JWT_EXPIRATION')) {
    putenv('JWT_EXPIRATION=86400'); // 24h
}

// ------------------------------
// Debug: Check loaded variables
// ------------------------------
if (env('APP_ENV') === 'development') {
    // Optional: Print JWT_SECRET for testing (remove in production)
    // echo "JWT_SECRET: " . env('JWT_SECRET') . PHP_EOL;
    // echo "MongoDB DSN: " . env('DB_DSN') . PHP_EOL;
}
