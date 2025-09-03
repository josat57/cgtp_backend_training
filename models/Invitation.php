<?php
/**
 * Invitation model manages invitations for reviewers to review books.
 * Table expected: invitations(id, book_id, author_id, reviewer_email, status[pending,accepted], token, created_at)
 */
class Invitation {
	private $conn;
	public function __construct($conn) { $this->conn = $conn; }

	public function create($bookId, $authorId, $reviewerEmail, $token) {
		$status = 'pending';
		$stmt = $this->conn->prepare("INSERT INTO invitations (book_id, author_id, reviewer_email, status, token) VALUES (?, ?, ?, ?, ?)");
		$stmt->bind_param("iisss", $bookId, $authorId, $reviewerEmail, $status, $token);
		return $stmt->execute();
	}

	public function acceptByToken($token, $reviewerId) {
		// Mark invitation accepted and link to reviewer by email
		$stmt = $this->conn->prepare("UPDATE invitations SET status = 'accepted', reviewer_id = ? WHERE token = ? AND status = 'pending'");
		$stmt->bind_param("is", $reviewerId, $token);
		return $stmt->execute();
	}

	public function listByBook($bookId) {
		$stmt = $this->conn->prepare("SELECT id, reviewer_email, status, created_at FROM invitations WHERE book_id = ? ORDER BY created_at DESC");
		$stmt->bind_param("i", $bookId);
		$stmt->execute();
		$res = $stmt->get_result();
		$rows = [];
		while ($row = $res->fetch_assoc()) $rows[] = $row;
		return $rows;
	}
} 