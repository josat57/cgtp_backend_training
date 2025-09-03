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

    if ($roleId != 2) { // 2 = Author
        die("❌ Access denied: only Authors can send invites.");
    }

    // Get POST data
    $bookId = $_POST['book_id'] ?? null;
    $reviewerEmail = $_POST['reviewer_email'] ?? null;

    if (!$bookId || !$reviewerEmail) {
        die("❌ Book ID and reviewer email are required.");
    }

    // Ensure this Author owns the book
    $stmtCheck = $pdo->prepare("SELECT id FROM books WHERE id = ? AND author_id = ?");
    $stmtCheck->execute([$bookId, $userId]);
    if (!$stmtCheck->fetch()) die("❌ Access denied: this book does not belong to you.");

    // Generate a unique token for the invitation
    $inviteToken = bin2hex(random_bytes(16));

    // Insert invitation
    $stmt = $pdo->prepare("
        INSERT INTO invitations (book_id, reviewer_email, token, status)
        VALUES (?, ?, ?, 'pending')
    ");
    $stmt->execute([$bookId, $reviewerEmail, $inviteToken]);

    // Log action
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
    $stmtLog->execute([$userId, "Sent invitation to $reviewerEmail for book ID $bookId"]);

    echo json_encode([
        'success' => true,
        'message' => "✅ Invitation sent successfully.",
        'invite_token' => $inviteToken
    ]);

} catch (Exception $e) {
    die("❌ Invalid token: " . $e->getMessage());
}