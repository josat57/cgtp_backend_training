<?php

require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/utils.php';
require_once __DIR__ . '/../../models/user/findByEmail.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;

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
 * Login a user
 * 
 * @param array $data Request data
 * @return array Response data
 */
if (!function_exists('login')) {
function login($data) {
    // Validate required fields
    $requiredFields = ['email', 'password'];
    $validation = validateRequiredFields($data, $requiredFields);
    
    if (!$validation['isValid']) {
        return errorResponse('Missing required fields: ' . implode(', ', $validation['missingFields']), 400);
    }
    
    // Find user by email
    $user = findByEmail($data['email']);
    
    if (!$user) {
        return errorResponse('Invalid email or password', 401);
    }
    
    // Verify password
    if (!verifyPassword($data['password'], $user['password'])) {
        return errorResponse('Invalid email or password', 401);
    }
    
    // Generate JWT token
    $token = generateJWT($user);
    
    // Return success with token
    return successResponse([
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            '_id' => $user['_id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'created_at' => $user['created_at'] ?? null,
            'updated_at' => $user['updated_at'] ?? null
        ]
    ]);
}
}

/**
 * Generate a JWT token for a user
 * 
 * @param array $user User data
 * @return string JWT token
 */
if (!function_exists('generateJWT')) {
function generateJWT($user) {
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