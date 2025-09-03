<?php
/**
 * Book model encapsulates DB interactions for books.
 * Table expected: books(id, author_id, title, description, file_path, created_at)
 */
class Book {
	private $conn;
	public function __construct($conn) { $this->conn = $conn; }

	public function create($authorId, $title, $description, $filePath) {
		$stmt = $this->conn->prepare("INSERT INTO books (author_id, title, description, file_path) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("isss", $authorId, $title, $description, $filePath);
		if (!$stmt->execute()) return false;
		return $this->conn->insert_id;
	}

	public function findById($id) {
		$stmt = $this->conn->prepare("SELECT id, author_id, title, description, file_path, created_at FROM books WHERE id = ? LIMIT 1");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$res = $stmt->get_result();
		return $res->fetch_assoc() ?: null;
	}

	public function listAll() {
		$sql = "SELECT b.id, b.title, b.description, b.file_path, b.created_at, u.fullname AS author_name FROM books b JOIN users u ON u.id = b.author_id ORDER BY b.created_at DESC";
		$res = $this->conn->query($sql);
		$rows = [];
		while ($row = $res->fetch_assoc()) $rows[] = $row;
		return $rows;
	}

	public function listByAuthor($authorId) {
		$stmt = $this->conn->prepare("SELECT id, title, description, file_path, created_at FROM books WHERE author_id = ? ORDER BY created_at DESC");
		$stmt->bind_param("i", $authorId);
		$stmt->execute();
		$res = $stmt->get_result();
		$rows = [];
		while ($row = $res->fetch_assoc()) $rows[] = $row;
		return $rows;
	}
} 