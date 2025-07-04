<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

switch (true) {
    // =====================
    // AUTH ROUTES
    // =====================
    case $uri === '/register' && $method === 'POST':
        require_once __DIR__ . '/../controllers/AuthController.php';
        $rawInput = file_get_contents("php://input");
        error_log("Raw input: " . $rawInput);
        $input = json_decode($rawInput, true);
        error_log("Decoded input: " . print_r($input, true));
        $auth = new AuthController($input);
        $response = $auth->register();
        error_log("Response: " . json_encode($response));
        echo json_encode($response);
        break;

    case $uri === '/login' && $method === 'POST':
        require_once __DIR__ . '/../controllers/AuthController.php';
        $input = json_decode(file_get_contents("php://input"), true);
        $auth = new AuthController($input);
        $response = $auth->login();
        echo json_encode($response);
        break;

    // =====================
    // BOOK ROUTES
    // =====================
    case $uri === '/books' && $method === 'GET':
        require_once __DIR__ . '/../controllers/BookController.php';
        getAllBooks();
        break;

    case preg_match('#^/books/([a-zA-Z0-9]+)$#', $uri, $matches) && $method === 'GET':
        require_once __DIR__ . '/../controllers/BookController.php';
        getBookById($matches[1]);
        break;

    case $uri === '/books' && $method === 'POST':
        require_once __DIR__ . '/../controllers/BookController.php';
        $user = getAuthenticatedUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            break;
        }
        createBook($user);
        break;

    case preg_match('#^/books/([a-zA-Z0-9]+)$#', $uri, $matches) && $method === 'PUT':
        require_once __DIR__ . '/../controllers/BookController.php';
        $user = getAuthenticatedUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            break;
        }
        updateBook($matches[1], $user);
        break;

    case preg_match('#^/books/([a-zA-Z0-9]+)$#', $uri, $matches) && $method === 'DELETE':
        require_once __DIR__ . '/../controllers/BookController.php';
        $user = getAuthenticatedUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            break;
        }
        deleteBook($matches[1], $user);
        break;

    // =====================
    // REVIEW ROUTES
    // =====================
    case preg_match('#^/books/([a-zA-Z0-9]+)/reviews$#', $uri, $matches) && $method === 'POST':
        require_once __DIR__ . '/../controllers/ReviewController.php';
        $user = getAuthenticatedUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            break;
        }
        addReview($matches[1], $user);
        break;

    case preg_match('#^/books/([a-zA-Z0-9]+)/reviews$#', $uri, $matches) && $method === 'GET':
        require_once __DIR__ . '/../controllers/ReviewController.php';
        getReviews($matches[1]);
        break;

    case preg_match('#^/reviews/([a-zA-Z0-9]+)$#', $uri, $matches) && $method === 'DELETE':
        require_once __DIR__ . '/../controllers/ReviewController.php';
        $user = getAuthenticatedUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            break;
        }
        deleteReview($matches[1], $user);
        break;

    // =====================
    // ADMIN ROUTES
    // =====================
    case $uri === '/admin/users' && $method === 'GET':
        require_once __DIR__ . '/../controllers/AdminController.php';
        $user = getAuthenticatedUser();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden']);
            break;
        }
        getUsers();
        break;

    case preg_match('#^/admin/users/([a-zA-Z0-9]+)/role$#', $uri, $matches) && $method === 'PUT':
        require_once __DIR__ . '/../controllers/AdminController.php';
        $user = getAuthenticatedUser();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden']);
            break;
        }
        updateUserRole($matches[1]);
        break;

    case preg_match('#^/admin/users/([a-zA-Z0-9]+)$#', $uri, $matches) && $method === 'DELETE':
        require_once __DIR__ . '/../controllers/AdminController.php';
        $user = getAuthenticatedUser();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden']);
            break;
        }
        deleteUser($matches[1]);
        break;

    // =====================
    // DEFAULT 404
    // =====================
    default:
        http_response_code(404);
        echo json_encode(['message' => 'Route not found']);
        break;
}
