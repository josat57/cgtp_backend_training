<?php

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../helpers/utils.php';
require_once __DIR__ . '/../../models/task/createTask.php';

/**
 * Create a new task for the authenticated user
 * 
 * @param array $data Task data
 * @return array Response with created task
 */
function createTask($data) {
    // Authenticate the request
    $authResult = checkPermissions();
    
    if (!$authResult['success']) {
        return errorResponse($authResult['message'], 401);
    }
    
    // Validate required fields
    $requiredFields = ['title', 'description'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return errorResponse("Missing required field: {$field}", 400);
        }
    }
    
    // Prepare task data
    $taskData = [
        'user_id' => $authResult['userId'],
        'title' => $data['title'],
        'description' => $data['description'],
        'status' => $data['status'] ?? 'pending',
        'priority' => $data['priority'] ?? 'medium',
        'due_date' => $data['due_date'] ?? null,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ];
    
    // Add expiry date if provided
    if (isset($data['expiry_date']) && !empty($data['expiry_date'])) {
        // Validate date format
        $expiryTimestamp = strtotime($data['expiry_date']);
        if ($expiryTimestamp) {
            $taskData['expiry_date'] = new MongoDB\BSON\UTCDateTime($expiryTimestamp * 1000);
        }
    }
    
    // Call model function to create task
    $result = createTaskModel($taskData);
    
    if (!$result['success']) {
        return errorResponse($result['message'], 500);
    }
    
    // Return success response with task data including ID
    return successResponse($result['task'], 'Task created successfully');
}