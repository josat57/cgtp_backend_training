<?php

require_once __DIR__ . '/../helpers/response.php';

/**
 * Handle API routes
 * 
 * @param string $route The specific route being requested
 * @param array $data Request data (GET or POST)
 * @param string $method HTTP method
 * @return array Response data
 */
function apiRoutes($route, $data, $method) {
    // Map HTTP methods to route file operations
    $methodMap = [        'GET' => [
            'task' => 'findTasksByUser', // GET /api/task - Get all tasks
            'tasks' => 'findTasksByUser', // GET /api/tasks - Get all tasks (alias)
            'user' => 'findByEmail'
        ],
        'POST' => [
            'task' => 'createTask', // POST /api/task - Create a new task
            'tasks' => 'createTask', // POST /api/tasks - Create a new task (alias)
            'user' => 'createUser',
            'login' => 'user/findByEmail',
            'register' => 'user/createUser'
        ],
        'PUT' => [
            'task' => 'updateTask', // PUT /api/task - Update a task (requires id in data)
            'tasks' => 'updateTask'  // PUT /api/tasks - Update a task (alias)
        ],
        'DELETE' => [
            'task' => 'deleteTask', // DELETE /api/task - Delete a task (requires id in data)
            'tasks' => 'deleteTask'  // DELETE /api/tasks - Delete a task (alias)
        ]
    ];
    
    // Handle legacy routes for login and register
    if ($route === 'login' && $method === 'POST') {
        $routeFile = __DIR__ . '/user/findByEmail.php';
        if (file_exists($routeFile)) {
            require_once $routeFile;
            return findByEmailRoute($data);
        }
    }
    
    if ($route === 'register' && $method === 'POST') {
        $routeFile = __DIR__ . '/user/createUser.php';
        if (file_exists($routeFile)) {
            require_once $routeFile;
            return createUserRoute($data);
        }
    }
    
    // Check if route is empty
    if (empty($route)) {
        // Default to 'task' for all methods
        if ($method === 'GET' || $method === 'POST') {
            $route = 'task';
        } else {
            return errorResponse('Missing route parameter', 400);
        }
    }
    
    // Handle special case for GET with ID - use findTaskById instead of findTasksByUser
    if (($route === 'task' || $route === 'tasks') && $method === 'GET' && isset($data['id'])) {
        $methodMap[$method][$route] = 'findTaskById';
    }
    
    // Check if we have a mapping for this route and method
    if (isset($methodMap[$method]) && isset($methodMap[$method][$route])) {
        $operation = $methodMap[$method][$route];
        
        // If operation contains a slash, it's a direct path
        if (strpos($operation, '/') !== false) {
            $routeFile = __DIR__ . '/' . $operation . '.php';
            // Fix potential double slash issue
            $routeFile = str_replace('//', '/', $routeFile);
        } else {
            // Otherwise, construct path based on route and operation
            $routeFile = __DIR__ . '/' . $route . '/' . $operation . '.php';
            // Fix potential double slash issue
            $routeFile = str_replace('//', '/', $routeFile);
        }
        
        // Check if route file exists
        if (file_exists($routeFile)) {
            require_once $routeFile;
            
            // Construct function name based on operation
            $functionName = basename($operation) . 'Route';
            
            if (function_exists($functionName)) {
                return $functionName($data);
            } else {
                return errorResponse('Route function not found: ' . $functionName, 500);
            }
        } else {
            return errorResponse('Route file not found: ' . $routeFile, 404);
        }
    }
    
    return errorResponse('Route not found for ' . $method . ' ' . $route, 404);
}