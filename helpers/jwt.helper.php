<?php
// /helpers/jwt.helper.php

require_once __DIR__ . '/../config/config.php';

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    $padding = 4 - (strlen($data) % 4);
    if ($padding < 4) {
        $data .= str_repeat('=', $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function generateJwt($userId) {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $issuedAt = time();
    $expiration = $issuedAt + JWT_EXPIRATION_SECONDS;

    $payload = [
        'sub' => $userId,
        'iat' => $issuedAt,
        'exp' => $expiration
    ];

    $base64Header = base64url_encode(json_encode($header));
    $base64Payload = base64url_encode(json_encode($payload));

    $signature = hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET_KEY, true);
    $base64Signature = base64url_encode($signature);

    return "$base64Header.$base64Payload.$base64Signature";
}

function validateJwt($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return false;
    }

    list($base64Header, $base64Payload, $base64Signature) = $parts;

    $header = json_decode(base64url_decode($base64Header), true);
    $payload = json_decode(base64url_decode($base64Payload), true);
    $signature = base64url_decode($base64Signature);

    if (!$header || !$payload || !$signature) {
        return false;
    }

    // Validate algorithm
    if ($header['alg'] !== 'HS256') {
        return false;
    }

    // Recompute signature
    $expectedSignature = hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET_KEY, true);
    if (!hash_equals($expectedSignature, $signature)) {
        return false;
    }

    // Check expiration
    if (isset($payload['exp']) && time() >= $payload['exp']) {
        return false;
    }

    return $payload;
}
