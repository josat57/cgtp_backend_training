<?php
// /index.php

require_once __DIR__ . '/controller/auth.controller.php';
require_once __DIR__ . '/controller/tasks.controller.php';

// Set content type for API responses
header('Content-Type: application/json');

// Parse the request
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Extract the route - handle both /benrich_task/register and /benrich_task/index.php/register
$route = '/';

// First, remove the project path if it exists
if (strpos($uri, '/benrich_task') !== false) {
    $parts = explode('/benrich_task', $uri);
    if (isset($parts[1])) {
        $uri = $parts[1];
    }
}

// Then, remove index.php if it exists in the path
if (strpos($uri, '/index.php') !== false) {
    $parts = explode('/index.php', $uri);
    if (isset($parts[1])) {
        $route = $parts[1];
    }
} else {
    // If no index.php, use the remaining path
    $route = $uri;
}

// Clean up the route
$route = rtrim($route, '/');
if (empty($route)) {
    $route = '/';
}

// ROUTING LOGIC
switch (true) {
    // Auth Routes
    case $route === '/register' && $method === 'POST':
        registerUser();
        break;

    case $route === '/login' && $method === 'POST':
        loginUser();
        break;

    // Task Routes
    case $route === '/tasks' && $method === 'GET':
        getTasks();
        break;

    case $route === '/tasks' && $method === 'POST':
        createTask();
        break;

    case preg_match('#^/tasks/(\d+)$#', $route, $matches) && $method === 'GET':
        getTaskById((int)$matches[1]);
        break;

    case preg_match('#^/tasks/(\d+)$#', $route, $matches) && $method === 'PUT':
        updateTask((int)$matches[1]);
        break;

    case preg_match('#^/tasks/(\d+)$#', $route, $matches) && $method === 'DELETE':
        deleteTask((int)$matches[1]);
        break;

    // 404 fallback
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found', 'route' => $route, 'method' => $method]);
        break;
}
