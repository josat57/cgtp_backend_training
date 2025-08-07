<?php

require_once __DIR__ . '/../../config/db.php';

use MongoDB\BSON\UTCDateTime;

/**
 * Find a user by email
 * 
 * @param string $email User email
 * @return array|null User data or null if not found
 */
if (!function_exists('findByEmail')) {
function findByEmail($email) {
    try {
        // Get users collection
        $collection = getCollection('users');
        
        // Find user by email
        $user = $collection->findOne(['email' => $email]);
        
        if ($user) {
            // Convert MongoDB document to array and convert _id to string
            $userData = (array) $user;
            $userData['_id'] = (string) $user->_id;
            
            // Convert MongoDB UTCDateTime objects to readable strings
            if (isset($userData['created_at']) && $userData['created_at'] instanceof UTCDateTime) {
                $userData['created_at'] = $userData['created_at']->toDateTime()->format('Y-m-d H:i:s');
            }
            if (isset($userData['updated_at']) && $userData['updated_at'] instanceof UTCDateTime) {
                $userData['updated_at'] = $userData['updated_at']->toDateTime()->format('Y-m-d H:i:s');
            }
            
            return $userData;
        }
        
        return null;
    } catch (Exception $e) {
        // Log error
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}
}

/**
 * Verify user password
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password from database
 * @return bool True if password matches, false otherwise
 */
if (!function_exists('verifyPassword')) {
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
}