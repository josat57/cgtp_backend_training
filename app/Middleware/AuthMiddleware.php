<?php

namespace App\Middleware;

use App\Utils\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Skip middleware for OPTIONS requests (CORS preflight)
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }

        // Get token from Authorization header
        $token = JWT::getTokenFromHeaders();
        
        if (!$token) {
            return $this->json(['error' => 'No token provided'], 401);
        }

        // Validate token
        if (!JWT::validate($token)) {
            return $this->json(['error' => 'Invalid or expired token'], 401);
        }

        // Decode token and add user data to request
        $decoded = JWT::decode($token);
        if (!$decoded) {
            return $this->json(['error' => 'Invalid token'], 401);
        }

        // Add user data to request attributes
        $request = $request->withAttribute('user', $decoded);

        // Continue to the next middleware/controller
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
