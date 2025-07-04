<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {
    public static function authenticate() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authorization header missing']);
            exit;
        }
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        $config = require __DIR__ . '/../../config/jwt.php';
        try {
            $decoded = JWT::decode($jwt, new Key($config['secret'], 'HS256'));
            return (array)$decoded;
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            exit;
        }
    }
} 