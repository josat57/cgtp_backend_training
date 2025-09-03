<?php
require '../../config/db.php';
require '../../vendor/autoload.php';
require '../../config/jwt.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

// Get token from header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] 
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] 
           ?? '';
$authHeader = trim($authHeader);
$token = str_replace('Bearer ', '', $authHeader);
$token = trim($token);

if (!$token) die("❌ No token provided.");

try {
    $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    $userId = $decoded->data->id;

    $stmt = $pdo->prepare("SELECT id, name, email, phone, avatar, role_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) die("❌ User not found.");

    echo json_encode($user);

} catch (Exception $e) {
    die("❌ Invalid token: " . $e->getMessage());
}