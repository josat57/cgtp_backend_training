<?php
require_once './controller/AuthController.php';
require_once './controller/TaskController.php';
// Connect to DB
require_once './config/database.php';


$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Handle multiple possible paths
if(($uri === '/register' || $uri === '/task_api/register' || $uri === '/task_api/index.php/register') && $method === 'POST') {
    AuthController::register($conn);
} elseif (($uri === '/login' || $uri === '/task_api/login' || $uri === '/task_api/index.php/login') && $method === 'POST') {
    AuthController::login($conn);
} 
// Task endpoints
elseif (($uri === '/tasks' || $uri === '/task_api/tasks' || $uri === '/task_api/index.php/tasks') && $method === 'POST') {
    TaskController::create($conn);
} elseif (($uri === '/tasks' || $uri === '/task_api/tasks' || $uri === '/task_api/index.php/tasks') && $method === 'GET') {
    TaskController::getAll($conn);
} elseif (($uri === '/tasks/overdue' || $uri === '/task_api/tasks/overdue' || $uri === '/task_api/index.php/tasks/overdue') && $method === 'GET') {
    TaskController::getOverdue($conn);
} elseif (($uri === '/tasks/due-today' || $uri === '/task_api/tasks/due-today' || $uri === '/task_api/index.php/tasks/due-today') && $method === 'GET') {
    TaskController::getDueToday($conn);
} elseif (preg_match('/^\/tasks\/(\d+)$/', $uri, $matches) || preg_match('/^\/task_api\/tasks\/(\d+)$/', $uri, $matches) || preg_match('/^\/task_api\/index\.php\/tasks\/(\d+)$/', $uri, $matches)) {
    $task_id = $matches[1];
    if ($method === 'GET') {
        TaskController::getById($conn, $task_id);
    } elseif ($method === 'PUT') {
        TaskController::update($conn, $task_id);
    } elseif ($method === 'DELETE') {
        TaskController::delete($conn, $task_id);
    } else {
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
    }
} else {
    http_response_code(404);
    echo json_encode(["message" => "Route not found", "uri" => $uri, "method" => $method]);
}
