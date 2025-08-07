<?php

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/utils.php';
require_once __DIR__ . '/../../models/task/updateTask.php';

/**
 * Update a task by ID
 * 
 * @param string $taskId The ID of the task to update
 * @param array $data The task data to update
 * @return array Response data
 */
function updateTask($taskId, $data) {
    // Authenticate the request
    $auth = checkPermissions();
    if (!$auth['success']) {
        return errorResponse($auth['message'], 401);
    }
    
    // Get user ID from authentication
    $userId = $auth['userId'];
    
    // Validate required fields
    $requiredFields = ['title', 'description'];
    $validation = validateRequiredFields($data, $requiredFields);
    
    if (!$validation['isValid']) {
        return errorResponse('Missing required fields: ' . implode(', ', $validation['missingFields']), 400);
    }
    
    // Prepare task data
    $taskData = [
        'id' => $taskId,
        'user_id' => $userId,
        'title' => $data['title'],
        'description' => $data['description'],
        'status' => $data['status'] ?? 'pending',
        'priority' => $data['priority'] ?? 'medium',
        
        // Handle expiry date if provided
        'expiry_date' => isset($data['expiry_date']) && !empty($data['expiry_date']) && strtotime($data['expiry_date']) 
            ? new MongoDB\BSON\UTCDateTime(strtotime($data['expiry_date']) * 1000) 
            : (isset($data['expiry_date']) && empty($data['expiry_date']) ? null : null),
        'updated_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
    ];
    
    // Update the task using the model function
    $result = updateTaskModel($taskData);
    
    if ($result['success']) {
        // Get the updated task to return with properly formatted dates
        require_once __DIR__ . '/../../models/task/findTaskById.php';
        $taskResult = findTaskByIdModel($taskId, $userId);
        
        if ($taskResult['success']) {
            return successResponse($taskResult['task'], 'Task updated successfully');
        } else {
            // Still return success even if we can't fetch the updated task
            return successResponse(['message' => 'Task updated successfully', 'id' => $taskId]);
        }
    } else {
        return errorResponse($result['message'] ?? 'Failed to update task', 500);
    }
}