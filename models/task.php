<?php
// interacts with the tasks table in the database

class Task {
    private $conn;

    //constructor to pass databse connection

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new task
    public function create($title, $description, $status, $user_id, $due_date = null, $priority = 'medium') {
        $sql = "INSERT INTO tasks (title, description, status, user_id, due_date, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssiss", $title, $description, $status, $user_id, $due_date, $priority);
        return $stmt->execute();
    }

    // Get all tasks for a specific user with pagination
    public function getAllByUserId($user_id, $page = 1, $limit = 10, $status = null, $priority = null) {
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause
        $whereConditions = ["user_id = ?"];
        $params = [$user_id];
        $types = "i";
        
        if ($status) {
            $whereConditions[] = "status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        if ($priority) {
            $whereConditions[] = "priority = ?";
            $params[] = $priority;
            $types .= "s";
        }
        
        $whereClause = implode(" AND ", $whereConditions);
        
        $sql = "SELECT * FROM tasks WHERE {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get total count of tasks for pagination
    public function getTotalCountByUserId($user_id, $status = null, $priority = null) {
        $whereConditions = ["user_id = ?"];
        $params = [$user_id];
        $types = "i";
        
        if ($status) {
            $whereConditions[] = "status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        if ($priority) {
            $whereConditions[] = "priority = ?";
            $params[] = $priority;
            $types .= "s";
        }
        
        $whereClause = implode(" AND ", $whereConditions);
        
        $sql = "SELECT COUNT(*) as total FROM tasks WHERE {$whereClause}";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }

    // Get a specific task by ID and user_id
    public function getByIdAndUserId($task_id, $user_id) {
        $sql = "SELECT * FROM tasks WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Update a task
    public function update($task_id, $user_id, $title, $description, $status, $due_date = null, $priority = null) {
        $sql = "UPDATE tasks SET title = ?, description = ?, status = ?, due_date = ?, priority = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssii", $title, $description, $status, $due_date, $priority, $task_id, $user_id);
        return $stmt->execute();
    }

    // Delete a task
    public function delete($task_id, $user_id) {
        $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $task_id, $user_id);
        return $stmt->execute();
    }

    // Check if task exists and belongs to user
    public function taskExists($task_id, $user_id) {
        $sql = "SELECT id FROM tasks WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    // Get overdue tasks
    public function getOverdueTasks($user_id) {
        $sql = "SELECT * FROM tasks WHERE user_id = ? AND due_date < CURDATE() AND status != 'completed' ORDER BY due_date ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get tasks due today
    public function getTasksDueToday($user_id) {
        $sql = "SELECT * FROM tasks WHERE user_id = ? AND due_date = CURDATE() ORDER BY priority DESC, created_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
