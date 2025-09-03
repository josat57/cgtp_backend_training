<?php
// Expect $conn to be available (returned from config/db.php)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// normalize (if you run inside a folder, adjust or remove base path)
$script_name = dirname($_SERVER['SCRIPT_NAME']);
if ($script_name !== '/' && strpos($uri, $script_name) === 0) {
    $uri = substr($uri, strlen($script_name));
}

// Routing - minimal, explicit mapping
// Auth routes
if ($uri === '/api/auth/register' && $method === 'POST') {
    require_once __DIR__ . '/../controllers/AuthController.php';
    $ctrl = new AuthController($conn);
    $ctrl->register();
    exit;
}

if ($uri === '/api/auth/verify-otp' && $method === 'POST') {
    require_once __DIR__ . '/../controllers/AuthController.php';
    $ctrl = new AuthController($conn);
    $ctrl->verifyOtp();
    exit;
}

if ($uri === '/api/auth/login' && $method === 'POST') {
    require_once __DIR__ . '/../controllers/AuthController.php';
    $ctrl = new AuthController($conn);
    $ctrl->login();
    exit;
}

if ($uri === '/api/auth/logout' && $method === 'POST') {
    require_once __DIR__ . '/../controllers/AuthController.php';
    $ctrl = new AuthController($conn);
    $ctrl->logout();
    exit;
}

// user profile (authenticated)
if ($uri === '/api/users/me' && $method === 'GET') {
    require_once __DIR__ . '/../controllers/UserController.php';
    $ctrl = new UserController($conn);
    $ctrl->me();
    exit;
}

if ($uri === '/api/users/me' && $method === 'PUT') {
    require_once __DIR__ . '/../controllers/UserController.php';
    $ctrl = new UserController($conn);
    $ctrl->update();
    exit;
}

// file upload for avatar (multipart)
if ($uri === '/api/users/me/avatar' && $method === 'POST') {
    require_once __DIR__ . '/../controllers/UserController.php';
    $ctrl = new UserController($conn);
    $ctrl->uploadAvatar();
    exit;
}

// placeholder for books/reviews/invite routes — we'll implement next
// e.g. POST /api/books, GET /api/books, POST /api/books/{id}/invite etc.

// default: 404
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
exit;
