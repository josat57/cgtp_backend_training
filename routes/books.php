<?php
//for adding and listing books 
require '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $title = $data['title'] ?? '';
    $author = $data['author'] ?? '';
    $description = $data['description'] ?? '';

    if (!$title || !$author) {
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO books (title, author, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $author, $description);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Book added']);
    } else {
        echo json_encode(['error' => 'Failed to add book']);
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM books");
    $books = [];
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    echo json_encode($books);
}
?>