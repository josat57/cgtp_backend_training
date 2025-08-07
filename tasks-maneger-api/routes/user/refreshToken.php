<?php

require_once __DIR__ . '/../../controllers/auth/refreshToken.php';

/**
 * Route handler for refreshing JWT token
 * 
 * @param array $data Request data
 * @return array Response data
 */
function refreshTokenRoute($data) {
    return refreshToken($data);
}