<?php
namespace App\Controllers;

use App\Models\Review;
use App\Models\Book;
use App\Models\Invitation;
use App\Models\Log;
use PDO;

class ReviewsController {
    private $pdo;
    private $userId;
    private $roleId;

    public function __construct(PDO $pdo, $userId, $roleId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->roleId = $roleId;
    }

    /**
     * Submit a review (Reviewer only)
     */
    public function submitReview(array $data) {
        if ($this->roleId != 3) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied: only Reviewers can submit reviews']);
            return;
        }

        $bookId = $data['book_id'] ?? null;
        $reviewText = $data['review'] ?? null;

        if (!$bookId || !$reviewText) {
            http_response_code(400);
            echo json_encode(['error' => 'Book ID and review are required']);
            return;
        }

        // Check invitation status
        $stmt = $this->pdo->prepare("
            SELECT * FROM invitations 
            WHERE book_id = ? 
              AND reviewer_email = (SELECT email FROM users WHERE id = ?) 
              AND status = 'pending'
        ");
        $stmt->execute([$bookId, $this->userId]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invite) {
            http_response_code(403);
            echo json_encode(['error' => 'You are not invited to review this book or you already submitted a review']);
            return;
        }

        // Insert review
        $stmt = $this->pdo->prepare("INSERT INTO reviews (book_id, reviewer_id, review_text) VALUES (?, ?, ?)");
        $stmt->execute([$bookId, $this->userId, $reviewText]);

        // Update invitation status
        $stmt = $this->pdo->prepare("UPDATE invitations SET status = 'completed' WHERE id = ?");
        $stmt->execute([$invite['id']]);

        // Log action
        $stmtLog = $this->pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$this->userId, "Submitted review for book ID $bookId"]);

        echo json_encode([
            'success' => true,
            'message' => '✅ Review submitted successfully'
        ]);
    }

    /**
     * Get all reviews for a book (Author only)
     */
    public function getReviews(array $data) {
        if ($this->roleId != 2) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied: only Authors can view reviews']);
            return;
        }

        $bookId = $data['book_id'] ?? null;
        if (!$bookId) {
            http_response_code(400);
            echo json_encode(['error' => 'Book ID is required']);
            return;
        }

        // Check if book belongs to author
        $stmt = $this->pdo->prepare("SELECT title FROM books WHERE id = ? AND author_id = ?");
        $stmt->execute([$bookId, $this->userId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            http_response_code(404);
            echo json_encode(['error' => 'Book not found or you do not own it']);
            return;
        }

        // Fetch reviews
        $stmt = $this->pdo->prepare("
            SELECT r.id, u.name AS reviewer_name, r.review_text, r.created_at
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.id
            WHERE r.book_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$bookId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'book' => $book['title'],
            'reviews' => $reviews
        ]);
    }
}
