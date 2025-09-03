<?php
require '../vendor/autoload.php';
require '../config/db.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function getUserFromToken($pdo) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        die("❌ Authorization header missing");
    }

    $token = str_replace("Bearer ", "", $headers['Authorization']);
    $secret_key = "YOUR_SECRET_KEY_HERE";

    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
        $userId = $decoded->data->id;

        // Fetch user from DB
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            die("❌ Invalid token: user not found");
        }

        return $user;

    } catch (Exception $e) {
        http_response_code(401);
        die("❌ Invalid token: " . $e->getMessage());
    }
}

function checkRole($user, $allowedRoles, $pdo) {
    // $allowedRoles = array of role names, e.g., ['author']
    $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $stmt->execute([$user['role_id']]);
    $role = $stmt->fetchColumn();

    if (!in_array($role, $allowedRoles)) {
        http_response_code(403);
        die("❌ Access denied for role: $role");
    }
}
?>