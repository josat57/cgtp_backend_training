<?php

require_once __DIR__ . '/../../helpers/response.php';

/**
 * Handle task update route
 * 
 * @param array $data Request data with task ID
 * @return array Response data
 */
function updateTaskRoute($data) {
    // Check if ID is provided
    if (!isset($data['id'])) {
        return errorResponse('Task ID is required', 400);
    }
    
    // Load the model file
    require_once __DIR__ . '/../../models/task/updateTask.php';
    
    // Load the controller file
    require_once __DIR__ . '/../../controllers/tasks/update.php';
    
    // Call the controller function
    return updateTask($data['id'], $data);
}