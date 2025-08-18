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

    // MongoDB connection
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $collection = $client->task_manager->tasks;

    $taskId = $_GET['id'] ?? '';
    if (empty($taskId) || !preg_match('/^[0-9a-fA-F]{24}$/', $taskId)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or missing task ID.'
        ]);
        exit;
    }

    $task = $collection->findOne([
        '_id' => new MongoDB\BSON\ObjectId($taskId),
        'user_id' => $decoded->sub   // Ensure user can only see their own tasks
    ]);

    if ($task) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => (string) $task->_id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'user_id' => $task->user_id,
                'created_at' => $task->created_at->toDateTime()->format('Y-m-d H:i:s')
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Task not found.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or expired token.',
        'error' => $e->getMessage()
    ]);
}
