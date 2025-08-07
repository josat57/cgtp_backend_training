<?php

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/task/findTaskById.php';

/**
 * Get a single task by ID for the authenticated user
 * 
 * @param array $data Request data with task ID
 * @return array Response with task
 */
function getTaskById($data) {
    // Authenticate the request
    $authResult = checkPermissions();
    
    if (!$authResult['success']) {
        return errorResponse($authResult['message'], 401);
    }
    
    // Check if task ID is provided
    if (!isset($data['id']) || empty($data['id'])) {
        return errorResponse('Task ID is required', 400);
    }
    
    $taskId = $data['id'];
    $userId = $authResult['userId'];
    
    // Call the model function to get the task
    $result = findTaskByIdModel($taskId, $userId);
    
    if (!$result['success']) {
        return errorResponse($result['message'], 404);
    }
    
    // Return success response with task
    return successResponse($result['task'], 'Task retrieved successfully');
}