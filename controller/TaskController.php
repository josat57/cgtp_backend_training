<?php
require_once './models/task.php';
require_once './middleware/AuthMiddleware.php';

class TaskController {
    
    // Create a new task
    public static function create($conn) {
        // Authenticate user
        if (!AuthMiddleware::authenticate($conn)) {
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['title']) || empty($data['description'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Title and description are required']);
            return;
        }
        
        $title = $data['title'];
        $description = $data['description'];
        $status = isset($data['status']) ? $data['status'] : 'pending';
        $due_date = isset($data['due_date']) ? $data['due_date'] : null;
        $priority = isset($data['priority']) ? $data['priority'] : 'medium';
        $user = AuthMiddleware::getCurrentUser();
        
        $taskModel = new Task($conn);
        
        if ($taskModel->create($title, $description, $status, $user['id'], $due_date, $priority)) {
            http_response_code(201);
            echo json_encode(['message' => 'Task created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to create task']);
        }
    }
    
    // Get all tasks for the authenticated user with pagination
    public static function getAll($conn) {
        // Authenticate user
        if (!AuthMiddleware::authenticate($conn)) {
            return;
        }
        
        $user = AuthMiddleware::getCurrentUser();
        $taskModel = new Task($conn);
        
        // Get query parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $priority = isset($_GET['priority']) ? $_GET['priority'] : null;
        
        // Validate pagination parameters
        if ($page < 1) $page = 1;
        if ($limit < 1 || $limit > 100) $limit = 10;
        
        $tasks = $taskModel->getAllByUserId($user['id'], $page, $limit, $status, $priority);
        $total = $taskModel->getTotalCountByUserId($user['id'], $status, $priority);
        $totalPages = ceil($total / $limit);
        
        http_response_code(200);
        echo json_encode([
            'message' => 'Tasks retrieved successfully',
            'tasks' => $tasks,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_tasks' => $total,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_prev_page' => $page > 1
            ]
        ]);
    }
    
    // Get a specific task
    public static function getById($conn, $task_id) {
        // Authenticate user
        if (!AuthMiddleware::authenticate($conn)) {
            return;
        }
        
        $user = AuthMiddleware::getCurrentUser();
        $taskModel = new Task($conn);
        $task = $taskModel->getByIdAndUserId($task_id, $user['id']);
        
        if (!$task) {
            http_response_code(404);
            echo json_encode(['message' => 'Task not found']);
            return;
        }
        
        http_response_code(200);
        echo json_encode([
            'message' => 'Task retrieved successfully',
            'task' => $task
        ]);
    }
    
    // Update a task
    public static function update($conn, $task_id) {
        // Authenticate user
        if (!AuthMiddleware::authenticate($conn)) {
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $user = AuthMiddleware::getCurrentUser();
        $taskModel = new Task($conn);
        
        // Check if task exists and belongs to user
        if (!$taskModel->taskExists($task_id, $user['id'])) {
            http_response_code(404);
            echo json_encode(['message' => 'Task not found']);
            return;
        }
        
        $title = isset($data['title']) ? $data['title'] : '';
        $description = isset($data['description']) ? $data['description'] : '';
        $status = isset($data['status']) ? $data['status'] : '';
        $due_date = isset($data['due_date']) ? $data['due_date'] : null;
        $priority = isset($data['priority']) ? $data['priority'] : null;
        
        if ($taskModel->update($task_id, $user['id'], $title, $description, $status, $due_date, $priority)) {
            http_response_code(200);
            echo json_encode(['message' => 'Task updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update task']);
        }
    }
    
    // Delete a task
    public static function delete($conn, $task_id) {
        // Authenticate user
        if (!AuthMiddleware::authenticate($conn)) {
            return;
        }
        
        $user = AuthMiddleware::getCurrentUser();
        $taskModel = new Task($conn);
        
        // Check if task exists and belongs to user
        if (!$taskModel->taskExists($task_id, $user['id'])) {
            http_response_code(404);
            echo json_encode(['message' => 'Task not found']);
            return;
        }
        
        if ($taskModel->delete($task_id, $user['id'])) {
            http_response_code(200);
            echo json_encode(['message' => 'Task deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to delete task']);
        }
    }
    
    // Get overdue tasks
    public static function getOverdue($conn) {
        if (!AuthMiddleware::authenticate($conn)) {
            return;
        }
        
        $user = AuthMiddleware::getCurrentUser();
        $taskModel = new Task($conn);
        $tasks = $taskModel->getOverdueTasks($user['id']);
        
        http_response_code(200);
        echo json_encode([
            'message' => 'Overdue tasks retrieved successfully',
            'tasks' => $tasks
        ]);
    }
    
    // Get tasks due today
    public static function getDueToday($conn) {
        if (!AuthMiddleware::authenticate($conn)) {
            return;
        }
        
        $user = AuthMiddleware::getCurrentUser();
        $taskModel = new Task($conn);
        $tasks = $taskModel->getTasksDueToday($user['id']);
        
        http_response_code(200);
        echo json_encode([
            'message' => 'Tasks due today retrieved successfully',
            'tasks' => $tasks
        ]);
    }
}
