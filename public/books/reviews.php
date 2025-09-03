<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Controllers\ReviewsController;

header('Content-Type: application/json');

// Authenticate user
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', trim($authHeader));
if (!$token) die(json_encode(['error' => '❌ No token provided']));

try {
    $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    $userId = $decoded->data->id;
    $roleId = $decoded->data->role_id;
} catch (Exception $e) {
    die(json_encode(['error' => '❌ Invalid token: ' . $e->getMessage()]));
}

$controller = new ReviewsController($pdo, $userId, $roleId);
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'submit':
        $controller->submitReview($_POST);
        break;

    case 'get':
        $controller->getReviews($_POST);
        break;

    default:
        echo json_encode(['error' => '❌ Invalid action']);
}
