<?php
require '../../config/db.php';
require '../../vendor/autoload.php';
require '../../config/jwt.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

// JWT
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', trim($authHeader));
if (!$token) die("❌ No token provided.");

try {
    $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    $userId = $decoded->data->id;
    $roleId = $decoded->data->role_id;

    if ($roleId != 3) { // Reviewer
        die("❌ Access denied: only Reviewers can view assigned books.");
    }

    // Fetch books assigned via invitations
    $stmt = $pdo->prepare("
        SELECT b.id AS book_id, b.title, b.description, b.author_id, b.created_at
        FROM books b
        JOIN invitations i ON i.book_id = b.id
        WHERE i.reviewer_email = (
            SELECT email FROM users WHERE id = ?
        ) AND i.status = 'accepted'
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$userId]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log action
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
    $stmtLog->execute([$userId, "Viewed list of assigned books"]);

    echo json_encode([
        'success' => true,
        'assigned_books' => $books
    ]);

} catch (Exception $e) {
    die("❌ Invalid token: " . $e->getMessage());
}
