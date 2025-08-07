<?php
// /helpers/utility.helper.php

require_once __DIR__ . '/../helpers/jwt.helper.php';

/**
 * Fallback for getallheaders() for environments where it's not available
 */
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = str_replace('_', '-', strtolower(substr($name, 5)));
                $headers[ucwords($key, '-')] = $value;
            }
        }
        return $headers;
    }
}

/**
 * Sends a JSON response with optional HTTP status code
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Auth middleware: Validates JWT from Authorization header
 * Returns user ID if token is valid, else exits with 401
 */
function authenticate() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        sendJsonResponse(['error' => 'Missing Authorization header'], 401);
        return null;
    }

    $authHeader = $headers['Authorization'];
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        sendJsonResponse(['error' => 'Invalid Authorization format'], 401);
        return null;
    }

    $token = $matches[1];
    $decoded = validateJwt($token);
    if (!$decoded || !isset($decoded['sub'])) {
        sendJsonResponse(['error' => 'Invalid or expired token'], 401);
        return null;
    }

    return $decoded['sub']; // user ID
}
