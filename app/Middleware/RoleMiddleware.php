<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class RoleMiddleware
{
    private $requiredRoles;

    public function __construct(array $requiredRoles = [])
    {
        $this->requiredRoles = $requiredRoles;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Skip middleware for OPTIONS requests (CORS preflight)
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }

        // Get user from request
        $user = $request->getAttribute('user');
        
        if (!$user) {
            return $this->json(['error' => 'Unauthorized - No user data found'], 401);
        }

        // If no roles are required, allow access
        if (empty($this->requiredRoles)) {
            return $handler->handle($request);
        }

        // Check if user has any of the required roles
        $userRole = is_array($user) ? ($user['role'] ?? 'user') : ($user->role ?? 'user');
        
        if (!in_array($userRole, $this->requiredRoles) && $userRole !== 'admin') {
            return $this->json(['error' => 'Forbidden - Insufficient permissions'], 403);
        }

        // User has required role, continue to the next middleware/controller
        return $handler->handle($request);
    }

    private function json($data, int $status = 200): Response
    {
        $response = new \Slim\Psr7\Response($status);
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
