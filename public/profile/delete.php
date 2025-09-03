<?php
require '../../config/db.php';
require '../../vendor/autoload.php';
use Firebase\JWT\JWT;

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$authHeader) die("❌ No token provided.");

$token = str_replace('Bearer ', '', $authHeader);

try {
    $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    $userId = $decoded->id;

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    echo "✅ User account deleted successfully.";

} catch (Exception $e) {
    die("❌ Invalid token: " . $e->getMessage());
}
