<?php

require_once __DIR__ . '/../../config/db.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

/**
 * Find a task by ID and verify it belongs to the specified user
 * 
 * @param string $taskId Task ID
 * @param string $userId User ID
 * @return array Result with success status and task or error message
 */
if (!function_exists('findTaskByIdModel')) {
function findTaskByIdModel($taskId, $userId) {
    try {
        // Get tasks collection
        $collection = getCollection('tasks');
        
        if (!$collection) {
            return ['success' => false, 'message' => 'Failed to connect to database'];
        }
        
        // Convert string ID to MongoDB ObjectId
        try {
            $objectId = new ObjectId($taskId);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Invalid task ID format'];
        }
        
        // Find task by ID and user ID (for security)
        $task = $collection->findOne([
            '_id' => $objectId,
            'user_id' => $userId
        ]);
        
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found or access denied'];
        }
        
        // Convert MongoDB ObjectId to string
        $task['_id'] = (string) $task['_id'];
        
        // Convert MongoDB UTCDateTime objects to readable format
        if (isset($task['created_at']) && $task['created_at'] instanceof UTCDateTime) {
            $task['created_at'] = $task['created_at']->toDateTime()->format('Y-m-d H:i:s');
        }
        
        if (isset($task['updated_at']) && $task['updated_at'] instanceof UTCDateTime) {
            $task['updated_at'] = $task['updated_at']->toDateTime()->format('Y-m-d H:i:s');
        }
        
        return ['success' => true, 'task' => $task];
    } catch (Exception $e) {
        // Log error and return error message
        error_log("Database Error in findTaskByIdModel: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
}