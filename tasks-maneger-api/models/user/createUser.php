<?php

require_once __DIR__ . '/../../config/db.php';

use MongoDB\BSON\UTCDateTime;

/**
 * Create a new user in the database
 * 
 * @param array $userData User data (email, password, name)
 * @return array|bool User data with ID on success, false on failure
 */
if (!function_exists('createUser')) {
function createUser($userData) {
    try {
        // Get users collection
        $collection = getCollection('users');
        
        // Check if email already exists
        $existingUser = $collection->findOne(['email' => $userData['email']]);
        if ($existingUser) {
            return false; // Email already exists
        }
        
        // Hash the password
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Add timestamps
        $userData['created_at'] = new UTCDateTime(time() * 1000);
        $userData['updated_at'] = new UTCDateTime(time() * 1000);
        
        // Insert the user
        $result = $collection->insertOne($userData);
        
        if ($result->getInsertedCount() > 0) {
            // Get the inserted ID and convert it to string
            $userData['_id'] = (string) $result->getInsertedId();
            
            // Convert MongoDB UTCDateTime objects to readable strings
            if (isset($userData['created_at']) && $userData['created_at'] instanceof UTCDateTime) {
                $userData['created_at'] = $userData['created_at']->toDateTime()->format('Y-m-d H:i:s');
            }
            if (isset($userData['updated_at']) && $userData['updated_at'] instanceof UTCDateTime) {
                $userData['updated_at'] = $userData['updated_at']->toDateTime()->format('Y-m-d H:i:s');
            }
            
            unset($userData['password']); // Don't return the password
            return $userData;
        }
        
        return false;
    } catch (Exception $e) {
        // Log error
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}
}