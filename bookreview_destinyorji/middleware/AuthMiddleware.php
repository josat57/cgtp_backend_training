<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Authenticate and return user from JWT
 *
 * @return array|null
 */
function getAuthenticatedUser()
{
    $headers = getallheaders();

    // Normalize header keys (case-insensitive)
    $authorizationHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

    if (!$authorizationHeader || !preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
        return null;
    }

    $token = $matches[1];

    try {
        $jwtConfig = require __DIR__ . '/../config/jwt.php';
        $decoded = JWT::decode($token, new Key($jwtConfig['secret'], 'HS256'));

        return [
            'id'    => (string) $decoded->id,
            'email' => $decoded->email,
            'role'  => $decoded->role ?? 'user'
        ];
    } catch (Exception $e) {
        // You can optionally log $e->getMessage() for debugging
        return null;
    }
}
