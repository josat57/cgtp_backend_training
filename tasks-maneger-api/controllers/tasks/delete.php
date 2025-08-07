<?php

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/task/deleteTask.php';

/**
 * Delete a task by ID for the authenticated user
 * 
 * @param string $taskId The ID of the task to delete
 * @param array $data Additional request data
 * @return array Response data
 */
function deleteTask($taskId, $data) {
    // Authenticate the request
    $authResult = checkPermissions();
    
    if (!$authResult['success']) {
        return errorResponse($authResult['message'], 401);
    }
    
    // Get user ID from authenticated user
    $userId = $authResult['userId'];
    
    // Call the model function to delete the task
    $result = deleteTaskModel($taskId, $userId);
    
    if (!$result['success']) {
        return errorResponse($result['message'], 404);
    }
    
    // Return success response
    return successResponse(['id' => $taskId], 'Task deleted successfully');
}