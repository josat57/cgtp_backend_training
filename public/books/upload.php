<?php
require '../../config/db.php';
require '../../vendor/autoload.php';
require '../../config/jwt.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

// Get JWT from headers
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$authHeader = trim($authHeader);
$token = str_replace('Bearer ', '', $authHeader);
$token = trim($token);

if (!$token) die("❌ No token provided.");

try {
    $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    $userId = $decoded->data->id;
    $roleId = $decoded->data->role_id;

    if ($roleId != 2) { // 2 = Author
        die("❌ Access denied: only Authors can upload books.");
    }

    // Get POST data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';

    if (!$title) die("❌ Book title is required.");

    // Insert into books
    $stmt = $pdo->prepare("INSERT INTO books (title, description, author_id) VALUES (?, ?, ?)");
    $stmt->execute([$title, $description, $userId]);

    $bookId = $pdo->lastInsertId();

    // Log action
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
    $stmtLog->execute([$userId, "Uploaded book ID $bookId: $title"]);

    echo json_encode([
        'success' => true,
        'message' => "✅ Book uploaded successfully.",
        'book_id' => $bookId
    ]);

} catch (Exception $e) {
    die("❌ Invalid token: " . $e->getMessage());
}
