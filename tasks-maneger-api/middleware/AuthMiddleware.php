<?php

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

// JWT configuration (should match the one in login.php)
if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', 'your_jwt_secret_key'); // Change this to a secure random key in production
}
if (!defined('JWT_ALGORITHM')) {
    define('JWT_ALGORITHM', 'HS256');
}

/**
 * Middleware to verify JWT token for protected routes
 * 
 * @return array Response with success status and user ID if authenticated
 */
if (!function_exists('authenticateRequest')) {
function authenticateRequest() {
    // Get headers
    $headers = getallheaders();
    
    // Check if Authorization header exists
    if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
        return ['success' => false, 'message' => 'Authorization header missing'];
    }
    
    // Get the token from the Authorization header
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
    $token = str_replace('Bearer ', '', $authHeader);
    
    if (empty($token)) {
        return ['success' => false, 'message' => 'Token missing'];
    }
    
    try {
        // Decode the token
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
        
        // Get user ID from token
        $userId = $decoded->data->id;
        
        // Verify user exists in database
        $collection = getCollection('users');
        $user = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Return success with user ID
        return [
            'success' => true, 
            'userId' => $userId,
            'user' => [
                '_id' => (string) $user->_id,
                'email' => $user->email,
                'name' => $user->name
            ]
        ];
        
    } catch (ExpiredException $e) {
        return ['success' => false, 'message' => 'Token expired'];
    } catch (SignatureInvalidException $e) {
        return ['success' => false, 'message' => 'Invalid token signature'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Invalid token: ' . $e->getMessage()];
    }
}
}

/**
 * Check if user is authenticated and has required permissions
 * 
 * @param array $requiredPermissions List of required permissions
 * @return array Response with success status and user data if authorized
 */
if (!function_exists('checkPermissions')) {
function checkPermissions($requiredPermissions = []) {
    $authResult = authenticateRequest();
    
    if (!$authResult['success']) {
        setResponseCode(401);
        return $authResult; // Return the error from authenticateRequest
    }
    
    // If no specific permissions required, just being authenticated is enough
    if (empty($requiredPermissions)) {
        return $authResult;
    }
    
    // For future implementation: Check if user has all required permissions
    // This would involve checking the user's roles/permissions in the MongoDB collection
    
    return $authResult;
}
}