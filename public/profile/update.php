<?php
require '../../config/db.php';
require '../../vendor/autoload.php';
require '../../config/jwt.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

// Get token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$authHeader = trim($authHeader);
$token = str_replace('Bearer ', '', $authHeader);
$token = trim($token);

if (!$token) die("❌ No token provided.");

try {
    $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    $userId = $decoded->data->id;
    $roleId = $decoded->data->role_id;

    // Optional: allow Super Admin to update any user via query parameter
    $targetId = $_POST['target_id'] ?? $userId;
    if ($roleId != 1 && $targetId != $userId) { // 1 = Super Admin
        die("❌ Access denied: you cannot update other users.");
    }

    // Only update fields that are provided
    $fields = [];
    $values = [];

    if (!empty($_POST['name'])) {
        $fields[] = "name = ?";
        $values[] = $_POST['name'];
    }
    if (!empty($_POST['phone'])) {
        $fields[] = "phone = ?";
        $values[] = $_POST['phone'];
    }
    if (!empty($_POST['avatar'])) {
        $fields[] = "avatar = ?";
        $values[] = $_POST['avatar'];
    }

    if (empty($fields)) {
        die("❌ No fields provided to update.");
    }

    $values[] = $targetId; // for WHERE clause

    $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    // Log the update
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
    $stmtLog->execute([$userId, "Updated profile details for user ID $targetId"]);

    echo "✅ Profile updated successfully!";

} catch (Exception $e) {
    die("❌ Invalid token: " . $e->getMessage());
}
