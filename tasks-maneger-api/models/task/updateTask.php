<?php

require_once __DIR__ . '/../../config/db.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

/**
 * Update a task in the database
 * 
 * @param array $taskData The task data to update
 * @return array Result with success status and message
 */
if (!function_exists('updateTaskModel')) {
function updateTaskModel($taskData) {
    try {
        // Get tasks collection
        $collection = getCollection('tasks');
        
        if (!$collection) {
            return ['success' => false, 'message' => 'Failed to connect to database'];
        }
        
        // Convert string ID to MongoDB ObjectId
        try {
            $objectId = new ObjectId($taskData['id']);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Invalid task ID format'];
        }
        
        // Prepare update data
        $updateData = [
            '$set' => [
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'status' => $taskData['status'],
                'priority' => $taskData['priority'],
                'updated_at' => $taskData['updated_at']
            ]
        ];
        
        // Add expiry date if it exists in the data
        if (isset($taskData['expiry_date'])) {
            $updateData['$set']['expiry_date'] = $taskData['expiry_date'];
        }
        
        // Update task by ID and user ID (for security)
        $result = $collection->updateOne(
            ['_id' => $objectId, 'user_id' => $taskData['user_id']],
            $updateData
        );
        
        if ($result->getModifiedCount() > 0) {
            return ['success' => true, 'message' => 'Task updated successfully'];
        } else if ($result->getMatchedCount() > 0) {
            return ['success' => true, 'message' => 'No changes made to task'];
        } else {
            return ['success' => false, 'message' => 'Task not found or access denied'];
        }
    } catch (Exception $e) {
        // Log error and return error message
        error_log("Database Error in updateTaskModel: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
}