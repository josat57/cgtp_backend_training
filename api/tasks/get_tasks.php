<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Check for token in Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization']) || !preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authorization token not found or invalid format.'
    ]);
    exit;
}

$jwt = $matches[1];

try {
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
    $userId = $decoded->sub; // from token

    // MongoDB connection
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $collection = $client->task_manager->tasks;

    // --- Filtering ---
    $filter = ['user_id' => $userId];
    if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'completed'])) {
        $filter['status'] = $_GET['status'];
    }

    // --- Pagination ---
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 10;
    $skip = ($page - 1) * $limit;

    // Fetch tasks with filter + pagination
    $cursor = $collection->find($filter, [
        'skip' => $skip,
        'limit' => $limit,
        'sort' => ['created_at' => -1] // newest first
    ]);

    $tasks = [];
    foreach ($cursor as $task) {
        $taskData = [
            'id' => (string) $task->_id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'user_id' => $task->user_id,
            'created_at' => $task->created_at->toDateTime()->format('Y-m-d H:i:s')
        ];
        if (isset($task->expiry_date)) {
            $taskData['expiry_date'] = $task->expiry_date->toDateTime()->format('Y-m-d');
        }
        $tasks[] = $taskData;
    }

    // Count total tasks for pagination info
    $totalTasks = $collection->countDocuments($filter);
    $totalPages = ceil($totalTasks / $limit);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $totalPages,
        'total_tasks' => $totalTasks,
        'data' => $tasks
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or expired token.',
        'error' => $e->getMessage()
    ]);
}
