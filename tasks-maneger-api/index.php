<?php

// Load helper files
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/utils.php';

// Get request method and handle PUT/DELETE methods
$method = $_SERVER["REQUEST_METHOD"];
$queryString = $_SERVER["QUERY_STRING"] ?? '';

// Remove query string from URI if present
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Handle PUT, PATCH and DELETE requests that come as POST with _method parameter
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

$response = handleRequest($uri, $method);

// Set content type header
header('Content-Type: application/json');

// Output response
echo json_encode($response);

/**
 * Handle API requests by routing to appropriate controllers
 * 
 * @param string $uri Request URI
 * @param string $method HTTP method
 * @return array Response data
 */
function handleRequest($uri, $method) {
    // Clean up the URI
    $uri = trim($uri, '/');
    $parts = explode('/', $uri);
    
    // Get request data based on method
    $data = [];
    
    switch ($method) {
        case 'GET':
            $data = $_GET;
            break;
        case 'POST':
            // Try to get JSON data first, fall back to POST
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);
            
            // If JSON parsing failed, use POST data
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                $data = $_POST;
            }
            break;
        case 'PUT':
        case 'PATCH':
        case 'DELETE':
            // Parse JSON input for these methods
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true) ?: [];
            
            // Add any query parameters
            $data = array_merge($_GET, $data ?: []);
            break;
    }
    
    // Sanitize input data
    $data = sanitizeInput($data);
    
    // Handle different URI patterns
    if (count($parts) >= 1) {
        // Check if this is an API route
        if ($parts[0] === 'api') {
            // Extract route from URI
            $route = $parts[1] ?? '';
            
            // Extract ID from URL if present
            if (isset($parts[2])) {
                if (is_numeric($parts[2])) {
                    $data['id'] = (int)$parts[2];
                } elseif ($parts[2] === 'create') {
                    // Handle /api/task/create as POST /api/task
                    $route = $parts[1];
                    $method = 'POST';
                } elseif ($parts[2] === 'update' && isset($parts[3]) && is_numeric($parts[3])) {
                    // Handle /api/task/update/123 as PUT /api/task with id=123
                    $data['id'] = (int)$parts[3];
                    $route = $parts[1];
                    $method = 'PUT';
                } elseif ($parts[2] === 'delete' && isset($parts[3]) && is_numeric($parts[3])) {
                    // Handle /api/task/delete/123 as DELETE /api/task with id=123
                    $data['id'] = (int)$parts[3];
                    $route = $parts[1];
                    $method = 'DELETE';
                }
            }
            
            // Load the API routes file
            $apiRoutesFile = __DIR__ . '/routes/api.php';
            
            if (file_exists($apiRoutesFile)) {
                require_once $apiRoutesFile;
                
                if (function_exists('apiRoutes')) {
                    return apiRoutes($route, $data, $method);
                } else {
                    return errorResponse("API routes function not found", 500);
                }
            } else {
                return errorResponse("API routes file not found", 404);
            }
        } else {
            // Direct route file access
            $routeType = $parts[0]; // 'task' or 'user'
            $operation = $parts[1] ?? ''; // 'createTask', 'findByEmail', etc.
            
            // Handle special routes like 'register' and 'login'
            if ($routeType === 'register' && empty($operation)) {
                // For register, use the user/createUser route
                $routeFile = __DIR__ . "/routes/user/createUser.php";
                // Fix potential double slash issue
                $routeFile = str_replace('//', '/', $routeFile);
                if (file_exists($routeFile)) {
                    require_once $routeFile;
                    return createUserRoute($data);
                }
            } else if ($routeType === 'login' && empty($operation)) {
                // For login, use the user/findByEmail route
                $routeFile = __DIR__ . "/routes/user/findByEmail.php";
                // Fix potential double slash issue
                $routeFile = str_replace('//', '/', $routeFile);
                if (file_exists($routeFile)) {
                    // Ensure we have proper JSON data for login
                    if (empty($data) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
                        $jsonData = file_get_contents('php://input');
                        $data = json_decode($jsonData, true) ?: [];
                    }
                    require_once $routeFile;
                    return findByEmailRoute($data);
                }
            } else if ($routeType === 'task-manager-api' && $operation === 'register') {
                // Handle the specific error case in the URL
                $routeFile = __DIR__ . "/routes/user/createUser.php";
                // Fix potential double slash issue
                $routeFile = str_replace('//', '/', $routeFile);
                if (file_exists($routeFile)) {
                    require_once $routeFile;
                    return createUserRoute($data);
                }
            }
            
            // Extract ID from URL if present
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $data['id'] = (int)$parts[2];
            }
            
            // Check if operation is empty
            if (empty($operation)) {
                // For task or tasks route with no operation, default to findTasksByUser
                if ($routeType === 'task' || $routeType === 'tasks') {
                    $operation = 'findTasksByUser';
                    $routeType = 'task'; // Always use 'task' directory
                } else {
                    return errorResponse("Missing operation for route: {$routeType}", 400);
                }
            }
            
            // Build path to route file
            $routeFile = __DIR__ . "/routes/{$routeType}/{$operation}.php";
            
            // Fix potential double slash issue
            $routeFile = str_replace('//', '/', $routeFile);
            
            if (file_exists($routeFile)) {
                require_once $routeFile;
                
                // Construct function name
                $functionName = $operation . 'Route';
                
                if (function_exists($functionName)) {
                    return $functionName($data);
                } else {
                    return errorResponse("Route function not found: {$functionName}", 404);
                }
            } else {
                return errorResponse("Route file not found: {$routeFile}", 404);
            }
        }
    } else {
        return errorResponse("Invalid URI format", 400);
    }
}