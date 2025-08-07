<?php

require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/utils.php';
require_once __DIR__ . '/../../models/user/createUser.php';

/**
 * Register a new user
 * 
 * @param array $data Request data
 * @return array Response data
 */
if (!function_exists('register')) {
function register($data) {
    // Validate required fields
    $requiredFields = ['email', 'password', 'name'];
    $validation = validateRequiredFields($data, $requiredFields);
    
    if (!$validation['isValid']) {
        return errorResponse('Missing required fields: ' . implode(', ', $validation['missingFields']), 400);
    }
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return errorResponse('Invalid email format', 400);
    }
    
    // Validate password length
    if (strlen($data['password']) < 6) {
        return errorResponse('Password must be at least 6 characters', 400);
    }
    
    // Prepare user data
    $userData = [
        'email' => $data['email'],
        'password' => $data['password'],
        'name' => $data['name']
    ];
    
    // Create user
    $result = createUser($userData);
    
    if ($result) {
        return successResponse([
            'message' => 'User registered successfully',
            'user' => $result
        ]);
    } else {
        return errorResponse('Email already exists or registration failed', 400);
    }
}
}