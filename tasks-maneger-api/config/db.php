<?php

/**
 * MongoDB Database configuration
 */
if (!defined('MONGO_HOST')) define('MONGO_HOST', 'localhost');
if (!defined('MONGO_PORT')) define('MONGO_PORT', '27017');
if (!defined('MONGO_DB')) define('MONGO_DB', 'task_manager');
if (!defined('MONGO_USER')) define('MONGO_USER', '');
if (!defined('MONGO_PASS')) define('MONGO_PASS', '');

/**
 * Get MongoDB database connection
 * 
 * @return MongoDB\Database MongoDB database connection
 */
if (!function_exists('getConnection')) {
function getConnection() {
    try {
        // Require the MongoDB library
        require_once __DIR__ . '/../vendor/autoload.php';
        
        // Create connection string
        $connectionString = 'mongodb://';
        
        // Add authentication if credentials are provided
        if (MONGO_USER && MONGO_PASS) {
            $connectionString .= MONGO_USER . ':' . MONGO_PASS . '@';
        }
        
        $connectionString .= MONGO_HOST . ':' . MONGO_PORT;
        
        // Create client and select database
        $client = new MongoDB\Client($connectionString);
        $database = $client->selectDatabase(MONGO_DB);
        
        return $database;
    } catch(Exception $e) {
        // Log error and return null
        error_log("MongoDB Connection Error: " . $e->getMessage());
        return null;
    }
}
}

/**
 * Get a collection from the MongoDB database
 * 
 * @param string $collectionName Name of the collection
 * @return MongoDB\Collection Collection object
 */
if (!function_exists('getCollection')) {
function getCollection($collectionName) {
    $db = getConnection();
    if ($db) {
        return $db->selectCollection($collectionName);
    }
    return null;
}
}