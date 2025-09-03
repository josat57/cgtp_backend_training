<?php
require '../config/db.php'; 
require '../vendor/autoload.php'; 

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Get POST input
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    die("Please provide email and password.");
}

try {
    // 1️⃣ Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        die("❌ Invalid email or password");
    }

    if ($user['email_verified'] == 0) {
        die("❌ Email not verified");
    }

    // 2️⃣ Generate JWT (simplified)
    $secret_key = "YOUR_SECRET_KEY_HERE"; 
    $issuedAt = time();
    $expire = $issuedAt + (60 * 60); // 1 hour

    $payload = [
        "iat" => $issuedAt,
        "exp" => $expire,
        "data" => [
            "id" => $user['id'],
            "name" => $user['name'],
            "email" => $user['email'],
            "role_id" => $user['role_id']
        ]
    ];

    $jwt = JWT::encode($payload, $secret_key, 'HS256');

    echo json_encode([
        "message" => "✅ Login successful",
        "token" => $jwt
    ]);

} catch (PDOException $e) {
    die("❌ Database error: " . $e->getMessage());
}
?>
