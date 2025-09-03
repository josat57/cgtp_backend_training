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

    if ($roleId != 3) { // 3 = Reviewer
        die("❌ Access denied: only Reviewers can add comments.");
    }

    // Get POST data
    $bookId = $_POST['book_id'] ?? '';
    $comment = $_POST['comment'] ?? '';

    if (!$bookId || !$comment) die("❌ Book ID and comment are required.");

    // Insert review
    $stmt = $pdo->prepare("INSERT INTO reviews (book_id, reviewer_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$bookId, $userId, $comment]);

    // Log action
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
    $stmtLog->execute([$userId, "Added review for book ID $bookId"]);

    echo json_encode([
        'success' => true,
        'message' => "✅ Review added successfully."
    ]);

} catch (Exception $e) {
    die("❌ Invalid token: " . $e->getMessage());
}