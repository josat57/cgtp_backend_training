<?php
require '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'] ?? '';
    $book_id = $data['book_id'] ?? '';
    $rating = $data['rating'] ?? '';
    $comment = $data['comment'] ?? '';

    if (!$user_id || !$book_id || !$rating) {
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO reviews (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $book_id, $rating, $comment);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Review added']);
    } else {
        echo json_encode(['error' => 'Failed to add review']);
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $book_id = $_GET['book_id'] ?? '';
    if (!$book_id) {
        echo json_encode(['error' => 'Missing book_id']);
        exit;
    }
    $stmt = $conn->prepare("SELECT r.id, r.rating, r.comment, r.created_at, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    echo json_encode($reviews);
    $stmt->close();
}
?>
