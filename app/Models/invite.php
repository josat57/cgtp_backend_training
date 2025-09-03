<?php
class Invite {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($bookId, $reviewerEmail, $token) {
        $stmt = $this->pdo->prepare("INSERT INTO invitations (book_id, reviewer_email, token, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$bookId, $reviewerEmail, $token]);
        return $this->pdo->lastInsertId();
    }

    public function findByToken($token) {
        $stmt = $this->pdo->prepare("SELECT * FROM invitations WHERE token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markAsUsed($token) {
        $stmt = $this->pdo->prepare("UPDATE invitations SET status = 'accepted' WHERE token = ?");
        return $stmt->execute([$token]);
    }

    public function findByBook($bookId) {
        $stmt = $this->pdo->prepare("SELECT * FROM invitations WHERE book_id = ?");
        $stmt->execute([$bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
