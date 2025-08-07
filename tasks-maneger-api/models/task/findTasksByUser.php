<?php

require_once __DIR__ . '/../../config/db.php';

use MongoDB\BSON\UTCDateTime;

/**
 * Find all tasks for a specific user with pagination and filtering
 * 
 * @param string $userId User ID
 * @param array $filters Optional filters (status, priority, etc)
 * @param int $page Page number for pagination (default: 1)
 * @param int $limit Items per page (default: 10)
 * @return array Result with success status, tasks, pagination info or error message
 */
function findTasksByUserModel($userId, $filters = [], $page = 1, $limit = 10) {
    try {
        // Get tasks collection
        $collection = getCollection('tasks');
        
        if (!$collection) {
            return ['success' => false, 'message' => 'Failed to connect to database'];
        }
        
        // Base query filter
        $filter = ['user_id' => $userId];
        
        // Add filters if provided
        if (isset($filters['status'])) {
            $filter['status'] = $filters['status'];
        }
        
        if (isset($filters['priority'])) {
            $filter['priority'] = $filters['priority'];
        }
        
        // Filter by expiry date if provided
        if (isset($filters['expiry_before'])) {
            $expiryDate = new UTCDateTime(strtotime($filters['expiry_before']) * 1000);
            $filter['expiry_date'] = ['$lte' => $expiryDate];
        }
        
        if (isset($filters['expiry_after'])) {
            $expiryDate = new UTCDateTime(strtotime($filters['expiry_after']) * 1000);
            $filter['expiry_date'] = ['$gte' => $expiryDate];
        }
        
        // Calculate pagination values
        $skip = ($page - 1) * $limit;
        
        // Get total count for pagination
        $totalCount = $collection->countDocuments($filter);
        $totalPages = ceil($totalCount / $limit);
        
        // Find tasks with filter, sort, and pagination
        $cursor = $collection->find($filter, [
            'sort' => ['created_at' => -1],
            'limit' => $limit,
            'skip' => $skip
        ]);
        
        // Convert cursor to array and format dates
        $tasks = [];
        foreach ($cursor as $task) {
            // Convert MongoDB ObjectId to string
            $task['_id'] = (string) $task['_id'];
            
            // Convert MongoDB UTCDateTime objects to readable format
            if (isset($task['created_at']) && $task['created_at'] instanceof UTCDateTime) {
                $task['created_at'] = $task['created_at']->toDateTime()->format('Y-m-d H:i:s');
            }
            
            if (isset($task['updated_at']) && $task['updated_at'] instanceof UTCDateTime) {
                $task['updated_at'] = $task['updated_at']->toDateTime()->format('Y-m-d H:i:s');
            }
            
            $tasks[] = $task;
        }
        
        // Return tasks with pagination information
        return [
            'success' => true, 
            'tasks' => $tasks,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => ($page < $totalPages)
            ]
        ];
    } catch (Exception $e) {
        // Log error and return error message
        error_log("Database Error in findTasksByUserModel: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}