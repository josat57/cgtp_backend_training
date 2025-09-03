<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../config/config.php';

function generate_jwt($user_id, $role) {
    $cfg = require __DIR__ . '/../config/config.php';
    $now = time();
    $payload = [
        'iss' => $cfg['jwt_issuer'],
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + $cfg['jwt_exp'],
        'sub' => $user_id,
        'role' => $role
    ];
    return JWT::encode($payload, $cfg['jwt_secret'], 'HS256');
}

function get_bearer_token_from_header() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for some clients
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    if (!$headers) return null;
    if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }
    return null;
}

function decode_jwt_or_null($token) {
    try {
        $cfg = require __DIR__ . '/../config/config.php';
        $decoded = JWT::decode($token, new Key($cfg['jwt_secret'], 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        return null;
    }
}

// Checks whether token is blacklisted
function token_is_blacklisted($conn, $token) {
    if (!$token) return true;
    $token_hash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT id FROM token_blacklist WHERE token_hash = ? AND expires_at > NOW() LIMIT 1");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    return (bool)$row;
}
