<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load bootstrap file
require __DIR__ . '/../bootstrap.php';

// Create Container using PHP-DI
$container = new Container();

// Set container to create App with on AppFactory
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add Error Middleware with more detailed error reporting
$errorMiddleware = $app->addErrorMiddleware(
    true, // Display error details
    true, // Log errors
    true  // Log error details
);

// Error handler
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');

// Add CORS Middleware
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', getenv('CORS_ALLOWED_ORIGINS') ?: '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Add JSON Body Parser Middleware
$app->addBodyParsingMiddleware();

// Add Routing Middleware
$app->addRoutingMiddleware();

// Define routes
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'message' => 'Welcome to User Management API',
        'version' => '1.0.0',
        'status' => 'running',
        'timestamp' => date('c')
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Include API routes
try {
    require __DIR__ . '/../routes/api.php';
} catch (\Exception $e) {
    die(json_encode([
        'error' => 'Failed to load routes',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]));
}

// Catch-all route for 404
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'error' => 'Not Found',
        'path' => $request->getUri()->getPath(),
        'method' => $request->getMethod()
    ]));
    return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
});

// Run the app
try {
    $app->run();
} catch (\Exception $e) {
    die(json_encode([
        'error' => 'Application Error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]));
}
