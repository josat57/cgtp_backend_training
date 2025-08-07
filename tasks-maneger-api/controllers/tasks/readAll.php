<?php

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/task/findTasksByUser.php';

/**
 * Get all tasks for the authenticated user with pagination and filtering
 * 
 * @param array $data Request data including pagination and filter parameters
 * @return array Response with tasks and pagination info
 */
function getAllTasks($data) {
    // Authenticate the request
    $authResult = checkPermissions();
    
    if (!$authResult['success']) {
        return errorResponse($authResult['message'], 401);
    }
    
    // Get user ID from authenticated user
    $userId = $authResult['userId'];
    
    // Extract pagination parameters
    $page = isset($data['page']) ? (int)$data['page'] : 1;
    $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
    
    // Validate pagination parameters
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 10;
    
    // Extract filters
    $filters = [];
    
    // Status filter
    if (isset($data['status']) && in_array($data['status'], ['pending', 'completed'])) {
        $filters['status'] = $data['status'];
    }
    
    // Priority filter
    if (isset($data['priority']) && in_array($data['priority'], ['low', 'medium', 'high'])) {
        $filters['priority'] = $data['priority'];
    }
    
    // Expiry date filters
    if (isset($data['expiry_before']) && strtotime($data['expiry_before'])) {
        $filters['expiry_before'] = $data['expiry_before'];
    }
    
    if (isset($data['expiry_after']) && strtotime($data['expiry_after'])) {
        $filters['expiry_after'] = $data['expiry_after'];
    }
    
    // Call the model function to get tasks with pagination and filters
    $result = findTasksByUserModel($userId, $filters, $page, $limit);
    
    if (!$result['success']) {
        return errorResponse($result['message'], 500);
    }
    
    // Return success response with tasks and pagination info
    return successResponse([
        'tasks' => $result['tasks'],
        'pagination' => $result['pagination']
    ], 'Tasks retrieved successfully');
}