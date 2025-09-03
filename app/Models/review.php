<?php
class Review {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($bookId, $reviewerId, $comment) {
        $stmt = $this->pdo->prepare("INSERT INTO reviews (book_id, reviewer_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$bookId, $reviewerId, $comment]);
        return $this->pdo->lastInsertId();
    }

    public function findByBook($bookId) {
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.comment, r.created_at, u.name AS reviewer_name, u.email AS reviewer_email
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.id
            WHERE r.book_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByReviewer($reviewerId) {
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.comment, r.created_at, b.title AS book_title
            FROM reviews r
            JOIN books b ON r.book_id = b.id
            WHERE r.reviewer_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$reviewerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>