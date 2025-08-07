<?php
require_once './models/user.php';

class AuthMiddleware {
    
    public static function authenticate($conn) {
        // Get the Authorization header
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        // Check if Authorization header exists and has Bearer token
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization token required']);
            return false;
        }
        
        $token = $matches[1];
        
        // For now, we'll use a simple token validation
        $userModel = new User($conn);
        $user = $userModel->findByToken($token);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid or expired token']);
            return false;
        }
        
        // Store user data in a global variable or session for use in controllers
        $GLOBALS['current_user'] = $user;
        return true;
    }
    
    public static function getCurrentUser() {
        return isset($GLOBALS['current_user']) ? $GLOBALS['current_user'] : null;
    }
} 