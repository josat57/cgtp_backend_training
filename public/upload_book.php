<?php
require '../config/db.php';      // PDO connection
require '../src/auth.php';       // JWT & role helpers

// Only Authors can upload books
$user = getUserFromToken($pdo);
checkRole($user, ['author'], $pdo);

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("❌ Method not allowed");
}

// Get POST data
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$file = $_FILES['file'] ?? null;

// Basic validation
if (empty($title) || !$file) {
    http_response_code(400);
    die("❌ Title and file are required");
}

// Handle file upload
$uploadDir = '../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = time() . '_' . basename($file['name']);
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    die("❌ Failed to upload file");
}

try {
    // Insert book record
    $stmt = $pdo->prepare("
        INSERT INTO books (author_id, title, description, file_path)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user['id'], $title, $description, $targetPath]);

    // Optional: log this action
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, entity_type, entity_id)
        VALUES (?, 'BOOK_UPLOAD', 'book', ?)
    ");
    $bookId = $pdo->lastInsertId();
    $stmt->execute([$user['id'], $bookId]);

    echo json_encode([
        'message' => '✅ Book uploaded successfully',
        'book_id' => $bookId
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    die("❌ Database error: " . $e->getMessage());
}
?>