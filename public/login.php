<?php
require '../config/db.php';
require '../vendor/autoload.php';
require '../config/jwt.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    die("❌ Email and password required.");
}

// Fetch user
$stmt = $pdo->prepare("SELECT id, name, email, password, role_id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    die("❌ Invalid credentials.");
}

// JWT payload
$payload = [
    'iat' => time(),
    'exp' => time() + 3600,  // 1 hour expiration
    'data' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'role_id' => $user['role_id']
    ]
];

// Encode token
$token = JWT::encode($payload, JWT_SECRET, 'HS256');

echo json_encode([
    'message' => '✅ Login successful',
    'token' => $token
]);