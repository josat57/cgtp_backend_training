<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/BookController.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/controllers/ReviewController.php';

// Basic routing skeleton
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

$bookIdPattern = '#^/books/([a-fA-F0-9]{24})$#';
$reviewIdPattern = '#^/reviews/([a-fA-F0-9]{24})$#';
$bookReviewsPattern = '#^/books/([a-fA-F0-9]{24})/reviews$#';

// Example: route to controllers (to be implemented)
// You will expand this switch as you add features
switch (true) {
    case $requestUri === '/register' && $requestMethod === 'POST':
        AuthController::register();
        break;
    case $requestUri === '/login' && $requestMethod === 'POST':
        AuthController::login();
        break;
    case $requestUri === '/books' && $requestMethod === 'POST':
        $user = AuthMiddleware::authenticate();
        BookController::create($user);
        break;
    case $requestUri === '/books' && $requestMethod === 'GET':
        BookController::getAll();
        break;
    case preg_match($bookIdPattern, $requestUri, $matches) && $requestMethod === 'GET':
        BookController::getOne($matches[1]);
        break;
    case preg_match($bookIdPattern, $requestUri, $matches) && $requestMethod === 'PUT':
        $user = AuthMiddleware::authenticate();
        BookController::update($matches[1], $user);
        break;
    case preg_match($bookIdPattern, $requestUri, $matches) && $requestMethod === 'DELETE':
        $user = AuthMiddleware::authenticate();
        BookController::delete($matches[1], $user);
        break;
    case preg_match($bookReviewsPattern, $requestUri, $matches) && $requestMethod === 'POST':
        $user = AuthMiddleware::authenticate();
        ReviewController::add($matches[1], $user);
        break;
    case preg_match($bookReviewsPattern, $requestUri, $matches) && $requestMethod === 'GET':
        ReviewController::getAll($matches[1]);
        break;
    case preg_match($reviewIdPattern, $requestUri, $matches) && $requestMethod === 'DELETE':
        $user = AuthMiddleware::authenticate();
        ReviewController::delete($matches[1], $user);
        break;
    // Add more routes here
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
} 