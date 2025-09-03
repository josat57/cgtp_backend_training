<?php
require_once '../../controllers/ProfileController.php';

header('Content-Type: application/json');
$controller = new ProfileController($pdo);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $controller->getProfile();
        break;

    case 'POST': // for updates
        $controller->updateProfile($_POST);
        break;

    case 'DELETE':
        $controller->deleteProfile();
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}