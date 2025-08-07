<?php

require_once __DIR__ . '/../../helpers/response.php';

/**
 * Handle task creation route
 * 
 * @param array $data Request data
 * @return array Response data
 */
function createTaskRoute($data) {
    // Load the model file
    require_once __DIR__ . '/../../models/task/createTask.php';
    
    // Load the controller file
    require_once __DIR__ . '/../../controllers/tasks/create.php';
    
    // Call the controller function
    return createTask($data);
}