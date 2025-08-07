<?php

require_once __DIR__ . '/../../helpers/response.php';

/**
 * Handle get all tasks for a user route
 * 
 * @param array $data Request data
 * @return array Response data
 */
function findTasksByUserRoute($data) {
    // Load the model file
    require_once __DIR__ . '/../../models/task/findTasksByUser.php';
    
    // Load the controller file
    require_once __DIR__ . '/../../controllers/tasks/readAll.php';
    
    // Call the controller function
    return getAllTasks($data);
}