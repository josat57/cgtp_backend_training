<?php

/**
 * Generate a success response
 * 
 * @param mixed $data The data to return
 * @param string $message Success message
 * @return array Formatted success response
 */
if (!function_exists('successResponse')) {
function successResponse($data, $message = 'Operation successful') {
    return [
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ];
}
}

/**
 * Generate an error response
 * 
 * @param string $message Error message
 * @param int $code Error code (default: 400)
 * @return array Formatted error response
 */
if (!function_exists('errorResponse')) {
function errorResponse($message, $code = 400) {
    return [
        'status' => 'error',
        'message' => $message,
        'code' => $code
    ];
}
}

/**
 * Set appropriate HTTP response code
 * 
 * @param int $code HTTP status code
 * @return void
 */
if (!function_exists('setResponseCode')) {
function setResponseCode($code) {
    http_response_code($code);
}
}