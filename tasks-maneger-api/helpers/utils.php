<?php

/**
 * Validate if all required fields are present in the data
 * 
 * @param array $data The data to validate
 * @param array $requiredFields List of required field names
 * @return array [isValid, missingFields]
 */
if (!function_exists('validateRequiredFields')) {
function validateRequiredFields($data, $requiredFields) {
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    return [
        'isValid' => empty($missingFields),
        'missingFields' => $missingFields
    ];
}
}

/**
 * Sanitize input data
 * 
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
if (!function_exists('sanitizeInput')) {
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
        return $data;
    }
    
    // Sanitize string data
    if (is_string($data)) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}
}

/**
 * Generate a random token
 * 
 * @param int $length Token length
 * @return string Random token
 */
if (!function_exists('generateToken')) {
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}
}

/**
 * Get the current timestamp
 * 
 * @return string Current timestamp in Y-m-d H:i:s format
 */
if (!function_exists('getCurrentTimestamp')) {
function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}
}