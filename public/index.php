<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/BookController.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/controllers/ReviewController.php';

// Extract the path after /public for routing
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = parse_url($requestUri, PHP_URL_PATH);
if (strpos($path, $scriptName) === 0) {
    $path = substr($path, strlen($scriptName));
}
if ($path === false || $path === '') {
    $path = '/';
}

$bookIdPattern = '#^/books/([a-fA-F0-9]{24})$#';
$reviewIdPattern = '#^/reviews/([a-fA-F0-9]{24})$#';
$bookReviewsPattern = '#^/books/([a-fA-F0-9]{24})/reviews$#';

// Example: route to controllers (to be implemented)
// You will expand this switch as you add features
switch (true) {
    case $path === '/register' && $requestMethod === 'POST':
        AuthController::register();
        break;
    case $path === '/login' && $requestMethod === 'POST':
        AuthController::login();
        break;
    case $path === '/books' && $requestMethod === 'POST':
        $user = AuthMiddleware::authenticate();
        BookController::create($user);
        break;
    case $path === '/books' && $requestMethod === 'GET':
        BookController::getAll();
        break;
    case preg_match($bookIdPattern, $path, $matches) && $requestMethod === 'GET':
        BookController::getOne($matches[1]);
        break;
    case preg_match($bookIdPattern, $path, $matches) && $requestMethod === 'PUT':
        $user = AuthMiddleware::authenticate();
        BookController::update($matches[1], $user);
        break;
    case preg_match($bookIdPattern, $path, $matches) && $requestMethod === 'DELETE':
        $user = AuthMiddleware::authenticate();
        BookController::delete($matches[1], $user);
        break;
    case preg_match($bookReviewsPattern, $path, $matches) && $requestMethod === 'POST':
        $user = AuthMiddleware::authenticate();
        ReviewController::add($matches[1], $user);
        break;
    case preg_match($bookReviewsPattern, $path, $matches) && $requestMethod === 'GET':
        ReviewController::getAll($matches[1]);
        break;
    case preg_match($reviewIdPattern, $path, $matches) && $requestMethod === 'DELETE':
        $user = AuthMiddleware::authenticate();
        ReviewController::delete($matches[1], $user);
        break;
    // Add more routes here
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
} 