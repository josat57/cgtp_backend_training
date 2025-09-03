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

    if ($roleId != 2) { // Author
        die("❌ Access denied: only Authors can view book reviews.");
    }

    // Book ID from GET
    $bookId = $_GET['book_id'] ?? null;
    if (!$bookId) die("❌ Book ID is required.");

    // Ensure this Author owns the book
    $stmtCheck = $pdo->prepare("SELECT id FROM books WHERE id = ? AND author_id = ?");
    $stmtCheck->execute([$bookId, $userId]);
    if (!$stmtCheck->fetch()) die("❌ Access denied: this book does not belong to you.");

    // Fetch reviews
    $stmt = $pdo->prepare("
        SELECT r.id, r.comment, r.created_at, u.name AS reviewer_name, u.email AS reviewer_email
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.book_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$bookId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log action
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
    $stmtLog->execute([$userId, "Viewed reviews for book ID $bookId"]);

    echo json_encode([
        'success' => true,
        'book_id' => $bookId,
        'reviews' => $reviews
    ]);

} catch (Exception $e) {
    die("❌ Invalid token: " . $e->getMessage());
}
