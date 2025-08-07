<?php

require_once __DIR__ . '/../../config/db.php';

/**
 * Delete a task from the database by ID
 * 
 * @param string $taskId Task ID
 * @param string $userId User ID (for security verification)
 * @return array Result with success status and message
 */
if (!function_exists('deleteTaskModel')) {
function deleteTaskModel($taskId, $userId) {
    try {
        // Get tasks collection
        $collection = getCollection('tasks');
        
        if (!$collection) {
            return ['success' => false, 'message' => 'Failed to connect to database'];
        }
        
        // Convert string ID to MongoDB ObjectId
        try {
            $objectId = new MongoDB\BSON\ObjectId($taskId);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Invalid task ID format'];
        }
        
        // Delete task by ID and user ID (for security)
        $result = $collection->deleteOne([
            '_id' => $objectId,
            'user_id' => $userId
        ]);
        
        if ($result->getDeletedCount() > 0) {
            return ['success' => true, 'message' => 'Task deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Task not found or access denied'];
        }
    } catch (Exception $e) {
        // Log error and return error message
        error_log("Database Error in deleteTaskModel: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
}