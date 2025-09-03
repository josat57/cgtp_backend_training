<?php

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\RoleController;
use App\Controllers\BookController;
use App\Controllers\ReviewController;
use App\Controllers\AuditLogController;
use App\Controllers\AuthorController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\Routing\RouteCollectorProxy;

// ------------------------
// Auth Routes
// ------------------------
$app->group('/api/auth', function (RouteCollectorProxy $group) {
    $authController = new AuthController();

    $group->post('/register', [$authController, 'register']);
    $group->post('/login', [$authController, 'login']);
    
    // Protected routes
    $group->group('', function (RouteCollectorProxy $group) use ($authController) {
        $group->get('/me', [$authController, 'me']);
        $group->post('/logout', [$authController, 'logout']);
        $group->post('/refresh', [$authController, 'refresh']);
    })->add(new AuthMiddleware());
});

// ------------------------
// User Routes (Protected)
// ------------------------
$app->group('/api/users', function (RouteCollectorProxy $group) {
    $userController = new UserController();

    $group->get('', [$userController, 'index']);
    $group->post('', [$userController, 'store']);
    $group->get('/{id}', [$userController, 'show']);
    $group->put('/{id}', [$userController, 'update']);
    $group->delete('/{id}', [$userController, 'delete']);
    $group->put('/profile/update', [$userController, 'updateProfile']);
})->add(new AuthMiddleware());

// ------------------------
// Book Routes
// ------------------------
$app->group('/api/books', function (RouteCollectorProxy $group) {
    $bookController = new BookController();

    // Public
    $group->get('', [$bookController, 'index']);
    $group->get('/search', [$bookController, 'search']);
    $group->get('/{id:[0-9a-fA-F]{24}}', [$bookController, 'show']);

    // Protected
    $group->group('', function (RouteCollectorProxy $group) use ($bookController) {
        $group->post('', [$bookController, 'store']);
        $group->put('/{id:[0-9a-fA-F]{24}}', [$bookController, 'update']);
        $group->delete('/{id:[0-9a-fA-F]{24}}', [$bookController, 'delete']);
    })->add(new AuthMiddleware());
});

// ------------------------
// Review Routes
// ------------------------
$app->group('/api/reviews', function (RouteCollectorProxy $group) {
    $reviewController = new ReviewController();

    // Public
    $group->get('', [$reviewController, 'index']);
    $group->get('/book/{bookId}', [$reviewController, 'getBookReviews']);
    $group->get('/{id}', [$reviewController, 'show']);

    // Protected
    $group->group('', function (RouteCollectorProxy $group) use ($reviewController) {
        $group->post('', [$reviewController, 'store']);
        $group->put('/{id}', [$reviewController, 'update']);
        $group->delete('/{id}', [$reviewController, 'delete']);
    })->add(new AuthMiddleware());
});

// ------------------------
// Audit Logs (Admin Only)
// ------------------------
$app->group('/api/audit-logs', function (RouteCollectorProxy $group) {
    $auditLogController = new AuditLogController();

    $group->get('', [$auditLogController, 'index']);
    $group->get('/actions', [$auditLogController, 'getActions']);
    $group->get('/model-types', [$auditLogController, 'getModelTypes']);
    $group->get('/user/{userId}', [$auditLogController, 'getUserLogs']);
    $group->get('/model/{modelType}[/{modelId}]', [$auditLogController, 'getModelLogs']);
    $group->get('/{id}', [$auditLogController, 'show']);
})->add(new AuthMiddleware())
  ->add(new RoleMiddleware(['admin']));

// ------------------------
// Role Management (Admin Only)
// ------------------------
$app->group('/api', function (RouteCollectorProxy $group) {
    $roleController = new RoleController();

    $group->get('/roles', [$roleController, 'index']);
    $group->post('/roles', [$roleController, 'store']);
    $group->get('/roles/permissions', [$roleController, 'permissions']);
    $group->get('/roles/{id}', [$roleController, 'show']);
    $group->put('/roles/{id}', [$roleController, 'update']);
    $group->delete('/roles/{id}', [$roleController, 'delete']);

    $group->post('/users/{userId}/assign-role', [$roleController, 'assignRole']);
    $group->post('/users/{userId}/revoke-role', [$roleController, 'revokeRole']);
})->add(new AuthMiddleware())
  ->add(new RoleMiddleware(['admin']));

// ------------------------
// Author Routes
// ------------------------
$app->group('/api/authors', function (RouteCollectorProxy $group) {
    $authorController = new AuthorController();

    $group->get('', [$authorController, 'getProfile']);
    $group->get('/pending-invitations', [$authorController, 'getPendingInvitations']);
    $group->get('/{id:[0-9a-fA-F]{24}}', [$authorController, 'getPublicProfile']);
    $group->get('/{id:[0-9a-fA-F]{24}}/books', [$authorController, 'getBooks']);
    $group->get('/books/{book_id:[0-9a-fA-F]{24}}/reviews', [$authorController, 'getBookReviews']);
    $group->put('/profile/update', [$authorController, 'updateProfile']);
})->add(new AuthMiddleware())
  ->add(new RoleMiddleware(['author', 'admin']));
