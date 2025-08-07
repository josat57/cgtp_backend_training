<?php

require_once __DIR__ . '/../../helpers/response.php';

/**
 * Handle user login route
 * 
 * @param array $data Request data
 * @return array Response data
 */
function findByEmailRoute($data) {
    // Debug: Log the received data
    error_log('Login data received: ' . print_r($data, true));
    
    // Load the model file
    require_once __DIR__ . '/../../models/user/findByEmail.php';
    
    // Load the controller file
    require_once __DIR__ . '/../../controllers/auth/login.php';
    
    // Call the controller function
    return login($data);
}