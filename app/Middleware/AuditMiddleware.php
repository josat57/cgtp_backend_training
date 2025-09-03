<?php

namespace App\Middleware;

use App\Models\AuditLog;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuditMiddleware
{
    private $excludedPaths = [
        '/api/auth/login',
        '/api/auth/refresh',
        '/api/health'
    ];

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Skip middleware for OPTIONS requests (CORS preflight)
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();
        
        // Skip logging for excluded paths
        if (in_array($path, $this->excludedPaths)) {
            return $handler->handle($request);
        }

        // Get request data
        $requestData = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $request->getHeaders(),
            'body' => (string) $request->getBody(),
            'query_params' => $request->getQueryParams(),
            'ip' => $this->getClientIp($request)
        ];

        // Get user ID if authenticated
        $userId = null;
        $user = $request->getAttribute('user');
        if ($user && isset($user->id)) {
            $userId = $user->id;
        }

        // Handle the request and get the response
        $startTime = microtime(true);
        $response = $handler->handle($request);
        $endTime = microtime(true);

        // Get response data
        $responseBody = $response->getBody()->__toString();
        
        // Restore the response body stream for the actual response
        $response->getBody()->rewind();

        // Determine the action based on HTTP method and path
        $action = $this->determineAction($request->getMethod(), $path);
        $statusCode = $response->getStatusCode();

        // Log to database
        try {
            $auditLog = new AuditLog();
            $auditLog->create([
                'user_id' => $userId,
                'action' => $action,
                'model' => $this->getModelFromPath($path),
                'model_id' => $this->getModelIdFromPath($path, $request->getMethod()),
                'old_values' => $request->getMethod() === 'GET' ? null : $request->getParsedBody(),
                'new_values' => $request->getMethod() === 'GET' ? null : $responseBody,
                'url' => (string) $request->getUri(),
                'ip_address' => $this->getClientIp($request),
                'user_agent' => $request->getHeaderLine('User-Agent'),
                'status_code' => $statusCode,
                'execution_time' => round(($endTime - $startTime) * 1000, 2), // in milliseconds
                'created_at' => new \MongoDB\BSON\UTCDateTime()
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the application
            error_log('Audit log error: ' . $e->getMessage());
        }

        return $response;
    }

    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? 'unknown';
        
        // Check for forwarded IP in headers (if behind proxy)
        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
        if (!empty($forwardedFor)) {
            $ips = explode(',', $forwardedFor);
            $ip = trim($ips[0]);
        }
        
        return $ip;
    }

    private function determineAction(string $method, string $path): string
    {
        $action = strtolower($method);
        
        // Map HTTP methods to CRUD actions
        $actionMap = [
            'GET' => 'view',
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete'
        ];
        
        return $actionMap[$method] ?? $action;
    }
    
    private function getModelFromPath(string $path): ?string
    {
        $parts = explode('/', trim($path, '/'));
        
        // Skip 'api' and version if present
        $start = $parts[0] === 'api' ? 1 : 0;
        
        if (isset($parts[$start + 1])) {
            // Handle plural to singular (e.g., 'users' -> 'user')
            return rtrim($parts[$start + 1], 's');
        }
        
        return null;
    }
    
    private function getModelIdFromPath(string $path, string $method): ?string
    {
        $parts = explode('/', trim($path, '/'));
        
        // For GET /resource/{id} or PUT/DELETE /resource/{id}
        if (count($parts) >= 3 && is_numeric($parts[2])) {
            return $parts[2];
        }
        
        // For GET /resource/profile or similar
        if (count($parts) >= 3 && $method === 'GET') {
            return $parts[2];
        }
        
        return null;
    }
}
