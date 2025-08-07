<?php

require_once __DIR__ . '/../../helpers/response.php';

/**
 * Handle user creation route
 * 
 * @param array $data Request data
 * @return array Response data
 */
function createUserRoute($data) {
    // Load the model file
    require_once __DIR__ . '/../../models/user/createUser.php';
    
    // Load the controller file
    require_once __DIR__ . '/../../controllers/auth/register.php';
    
    // Call the controller function
    return register($data);
}