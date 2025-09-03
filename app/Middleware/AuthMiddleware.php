<?php
namespace App\Middleware;

require_once __DIR__ . '/../../config/jwt.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {

    /**
     * Check if the JWT token is valid and if the user has the required role.
     * @param string $requiredRole e.g., 'super_admin', 'author', 'reviewer'
     * @return object $userData - decoded user data
     */
    public static function checkRole($requiredRole) {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization header missing']);
            exit;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);

        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            $userRole = $decoded->data->role ?? null;

            if ($userRole !== $requiredRole) {
                http_response_code(403);
                echo json_encode(['message' => 'Forbidden: insufficient permissions']);
                exit;
            }

            // Return user data for endpoint use
            return $decoded->data;

        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid or expired token']);
            exit;
        }
    }
}
