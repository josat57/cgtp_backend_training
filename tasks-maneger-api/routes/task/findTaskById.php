<?php

require_once __DIR__ . '/../../helpers/response.php';

/**
 * Handle get task by ID route
 * 
 * @param array $data Request data with task ID
 * @return array Response data
 */
function findTaskByIdRoute($data) {
    // Check if ID is provided
    if (!isset($data['id'])) {
        return errorResponse('Task ID is required', 400);
    }
    
    // Load the model file
    require_once __DIR__ . '/../../models/task/findTaskById.php';
    
    // Load the controller file
    require_once __DIR__ . '/../../controllers/tasks/readOne.php';
    
    // Call the controller function
    return getTaskById($data['id'], $data);
}