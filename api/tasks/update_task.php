<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

    $client = new MongoDB\Client("mongodb://localhost:27017");
    $collection = $client->task_manager->tasks;

    // Parse input body
    $data = json_decode(file_get_contents("php://input"), true);

    $taskId = $data['id'] ?? '';
    if (empty($taskId) || !preg_match('/^[0-9a-fA-F]{24}$/', $taskId)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or missing task ID.'
        ]);
        exit;
    }

    // Build update fields dynamically
    $updateFields = [];
    if (isset($data['title'])) $updateFields['title'] = $data['title'];
    if (isset($data['description'])) $updateFields['description'] = $data['description'];
    if (isset($data['status'])) $updateFields['status'] = $data['status'];

    // Handle expiry_date if provided
    if (isset($data['expiry_date'])) {
        try {
            $expiryDate = new MongoDB\BSON\UTCDateTime(strtotime($data['expiry_date']) * 1000);
            $updateFields['expiry_date'] = $expiryDate;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid expiry_date format. Use YYYY-MM-DD.'
            ]);
            exit;
        }
    }

    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'No fields provided to update.'
        ]);
        exit;
    }

    $result = $collection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($taskId), 'user_id' => $decoded->sub],
        ['$set' => $updateFields]
    );

    if ($result->getModifiedCount() > 0) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Task updated successfully.'
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Task not found or no changes made.'
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