<?php
/**
 * Review model for book comments.
 * Table expected: reviews(id, book_id, reviewer_id, comment, created_at)
 */
class Review {
	private $conn;
	public function __construct($conn) { $this->conn = $conn; }

	public function add($bookId, $reviewerId, $comment) {
		$stmt = $this->conn->prepare("INSERT INTO reviews (book_id, reviewer_id, comment) VALUES (?, ?, ?)");
		$stmt->bind_param("iis", $bookId, $reviewerId, $comment);
		return $stmt->execute();
	}

	public function listByBook($bookId) {
		$stmt = $this->conn->prepare("SELECT r.id, r.comment, r.created_at, u.fullname AS reviewer_name FROM reviews r JOIN users u ON u.id = r.reviewer_id WHERE r.book_id = ? ORDER BY r.created_at DESC");
		$stmt->bind_param("i", $bookId);
		$stmt->execute();
		$res = $stmt->get_result();
		$rows = [];
		while ($row = $res->fetch_assoc()) $rows[] = $row;
		return $rows;
	}

	public function listByReviewer($reviewerId) {
		$stmt = $this->conn->prepare("SELECT r.id, r.comment, r.created_at, b.title FROM reviews r JOIN books b ON b.id = r.book_id WHERE r.reviewer_id = ? ORDER BY r.created_at DESC");
		$stmt->bind_param("i", $reviewerId);
		$stmt->execute();
		$res = $stmt->get_result();
		$rows = [];
		while ($row = $res->fetch_assoc()) $rows[] = $row;
		return $rows;
	}
} 