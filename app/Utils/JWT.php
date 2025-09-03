<?php

namespace App\Utils;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Exception;

class JWT
{
    private static $key;
    private static $algorithm = 'HS256';

    public static function init($key = null)
    {
        self::$key = $key ?: getenv('JWT_SECRET');
        if (!self::$key) {
            throw new Exception('JWT_SECRET is not set in environment variables');
        }
    }

    public static function encode(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + (getenv('JWT_EXPIRES_IN') ?: 86400);

        $token = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'nbf' => $issuedAt - 1,
            'data' => $payload
        ];

        return FirebaseJWT::encode($token, self::$key, self::$algorithm);
    }

    public static function decode(string $token): ?array
    {
        try {
            $decoded = FirebaseJWT::decode($token, new Key(self::$key, self::$algorithm));
            return (array) $decoded->data;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function validate(string $token): bool
    {
        try {
            FirebaseJWT::decode($token, new Key(self::$key, self::$algorithm));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getTokenFromHeaders(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}

// Initialize the JWT class with the secret key
JWT::init();
