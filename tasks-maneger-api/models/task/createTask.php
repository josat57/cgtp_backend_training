<?php

require_once __DIR__ . '/../../config/db.php';

use MongoDB\BSON\UTCDateTime;

/**
 * Create a new task in the database
 * 
 * @param array $taskData Task data
 * @return array Result with success status and task data or error message
 */
if (!function_exists('createTaskModel')) {
function createTaskModel($taskData) {
    try {
        // Get tasks collection
        $collection = getCollection('tasks');
        
        if (!$collection) {
            return ['success' => false, 'message' => 'Failed to connect to database'];
        }
        
        // Insert task document
        $result = $collection->insertOne($taskData);
        
        if ($result->getInsertedCount() > 0) {
            // Get the inserted ID and convert it to string
            $id = (string) $result->getInsertedId();
            
            // Add _id to task data for response
            $taskData['_id'] = $id;
            
            // Convert MongoDB UTCDateTime objects to readable format for response
            if (isset($taskData['created_at']) && $taskData['created_at'] instanceof UTCDateTime) {
                $taskData['created_at'] = $taskData['created_at']->toDateTime()->format('Y-m-d H:i:s');
            }
            
            if (isset($taskData['updated_at']) && $taskData['updated_at'] instanceof UTCDateTime) {
                $taskData['updated_at'] = $taskData['updated_at']->toDateTime()->format('Y-m-d H:i:s');
            }
            
            return ['success' => true, 'task' => $taskData];
        } else {
            return ['success' => false, 'message' => 'Failed to insert task'];
        }
    } catch (Exception $e) {
        // Log error and return false
        error_log("Database Error in createTaskModel: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
}