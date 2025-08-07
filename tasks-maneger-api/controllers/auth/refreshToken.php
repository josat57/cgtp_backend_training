<?php

require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/utils.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

// JWT configuration
if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', 'your_jwt_secret_key'); // Change this to a secure random key in production
}
if (!defined('JWT_ALGORITHM')) {
    define('JWT_ALGORITHM', 'HS256');
}
if (!defined('JWT_EXPIRY')) {
    define('JWT_EXPIRY', 3600); // 1 hour in seconds
}

/**
 * Refresh an expired JWT token
 * 
 * @param array $data Request data containing the expired token
 * @return array Response data with new token
 */
if (!function_exists('refreshToken')) {
function refreshToken($data) {
    // Validate required fields
    if (!isset($data['token']) || empty($data['token'])) {
        return errorResponse('Token is required', 400);
    }
    
    $token = $data['token'];
    
    try {
        // Try to decode the token without verification to extract the user ID
        // This is safe because we're only using it to generate a new token if the user exists
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
        
        // Get user ID from token
        $userId = $decoded->data->id;
        
        // Verify user exists in database
        $collection = getCollection('users');
        $user = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
        
        if (!$user) {
            return errorResponse('User not found', 404);
        }
        
        // Generate a new token
        $newToken = generateRefreshedJWT([
            '_id' => (string) $user->_id,
            'email' => $user->email,
            'name' => $user->name
        ]);
        
        // Return success with new token
        return successResponse([
            'message' => 'Token refreshed successfully',
            'token' => $newToken,
            'user' => [
                '_id' => (string) $user->_id,
                'email' => $user->email,
                'name' => $user->name
            ]
        ]);
        
    } catch (ExpiredException $e) {
        // This is expected - we're refreshing an expired token
        // Continue with the token refresh process
        try {
            // Extract the payload without verification
            $tks = explode('.', $token);
            if (count($tks) != 3) {
                return errorResponse('Invalid token format', 400);
            }
            
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($tks[1]));
            $userId = $payload->data->id;
            
            // Verify user exists in database
            $collection = getCollection('users');
            $user = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
            
            if (!$user) {
                return errorResponse('User not found', 404);
            }
            
            // Generate a new token
            $newToken = generateRefreshedJWT([
                '_id' => (string) $user->_id,
                'email' => $user->email,
                'name' => $user->name
            ]);
            
            // Return success with new token
            return successResponse([
                'message' => 'Token refreshed successfully',
                'token' => $newToken,
                'user' => [
                    '_id' => (string) $user->_id,
                    'email' => $user->email,
                    'name' => $user->name
                ]
            ]);
            
        } catch (Exception $e) {
            return errorResponse('Invalid token: ' . $e->getMessage(), 400);
        }
    } catch (SignatureInvalidException $e) {
        return errorResponse('Invalid token signature', 400);
    } catch (Exception $e) {
        return errorResponse('Invalid token: ' . $e->getMessage(), 400);
    }
}
}

/**
 * Generate a new JWT token for a user
 * 
 * @param array $user User data
 * @return string JWT token
 */
if (!function_exists('generateRefreshedJWT')) {
function generateRefreshedJWT($user) {
    $issuedAt = time();
    $expiresAt = $issuedAt + JWT_EXPIRY;
    
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expiresAt,
        'data' => [
            'id' => $user['_id'],
            'email' => $user['email']
        ]
    ];
    
    return JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);
}
}