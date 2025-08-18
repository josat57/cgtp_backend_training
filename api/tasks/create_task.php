<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->task_manager->tasks;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401); // Unauthorized
        echo json_encode([
            'status' => 'error',
            'message' => 'Authorization token not provided or invalid'
        ]);
        exit;
    }

    try {
        $jwt = $matches[1];
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
        $userId = $decoded->sub; // user ID from token
    } catch (Exception $e) {
        http_response_code(401); // Unauthorized
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or expired token'
        ]);
        exit;
    }

    // Collect input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'pending'); // default to pending
    $expiryDate = $_POST['expiry_date'] ?? null;

    // Validate input
    if (!$title || !$description || !in_array($status, ['pending', 'completed'])) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid input. Title, description, and valid status are required.'
        ]);
        exit;
    }

    // Prepare task data
    $taskData = [
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'user_id' => $userId,
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];

    // Add expiry_date if provided
    if ($expiryDate) {
        try {
            $taskData['expiry_date'] = new MongoDB\BSON\UTCDateTime(strtotime($expiryDate) * 1000);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid expiry date format. Use YYYY-MM-DD.'
            ]);
            exit;
        }
    }

    // Insert task into DB
    $insertResult = $collection->insertOne($taskData);

    echo json_encode([
        'status' => 'success',
        'message' => 'Task created successfully',
        'task_id' => (string) $insertResult->getInsertedId()
    ]);
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
}
