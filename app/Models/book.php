<?php
class Book {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($title, $description, $authorId) {
        $stmt = $this->pdo->prepare("INSERT INTO books (title, description, author_id) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $authorId]);
        return $this->pdo->lastInsertId();
    }

    public function findByAuthor($authorId) {
        $stmt = $this->pdo->prepare("SELECT id, title, description, created_at FROM books WHERE author_id = ? ORDER BY created_at DESC");
        $stmt->execute([$authorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll() {
        $stmt = $this->pdo->prepare("
            SELECT b.id, b.title, b.description, b.author_id, u.name AS author_name
            FROM books b
            JOIN users u ON b.author_id = u.id
            ORDER BY b.id ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function belongsToAuthor($bookId, $authorId) {
        $stmt = $this->pdo->prepare("SELECT id, title FROM books WHERE id = ? AND author_id = ?");
        $stmt->execute([$bookId, $authorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
