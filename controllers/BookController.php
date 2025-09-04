<?php
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../helpers/utility.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Invitation.php';

/**
 * BookController
 * - Authors: upload books, invite reviewers, view reviewers and reviews
 * - Reviewers: read/list books, add comments, see assignments and reviewed books
 */
class BookController {
	private $conn;
	private $bookModel;
	private $reviewModel;
	private $invitationModel;
	private $cfg;

	public function __construct($conn) {
		$this->conn = $conn;
		$this->bookModel = new Book($conn);
		$this->reviewModel = new Review($conn);
		$this->invitationModel = new Invitation($conn);
		$this->cfg = require __DIR__ . '/../config/config.php';
	}

	private function requireAuth() {
		$token = get_bearer_token_from_header();
		if (!$token) send_json(['success' => false, 'error' => 'Missing token'], 401);
		if (token_is_blacklisted($this->conn, $token)) send_json(['success' => false, 'error' => 'Token revoked'], 401);
		$decoded = decode_jwt_or_null($token);
		if (!$decoded) send_json(['success' => false, 'error' => 'Invalid token'], 401);
		return $decoded; // contains sub (user_id) and role
	}

	// POST /index.php/books (author only) - multipart upload with fields: title, description, file
	public function uploadBook() {
		$decoded = $this->requireAuth();
		if (($decoded->role ?? '') !== 'author') send_json(['success' => false, 'error' => 'Only authors can upload books'], 403);

		if (!isset($_POST['title']) || !isset($_FILES['file'])) {
			send_json(['success' => false, 'error' => 'title and file are required'], 400);
		}
		$title = trim($_POST['title']);
		$description = trim($_POST['description'] ?? '');

		$upload_dir = $this->cfg['upload_dir'];
		if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

		$file = $_FILES['file'];
		if ($file['error'] !== UPLOAD_ERR_OK) send_json(['success' => false, 'error' => 'Upload error'], 400);
		$allowed = ['application/pdf'];
		if (!in_array($file['type'], $allowed)) send_json(['success' => false, 'error' => 'Only PDF files allowed'], 400);
		if ($file['size'] > 20 * 1024 * 1024) send_json(['success' => false, 'error' => 'Max size 20MB'], 400);

		$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
		$targetFilename = 'book_' . $decoded->sub . '_' . time() . '.' . $ext;
		$targetPath = rtrim($upload_dir, '/') . '/' . $targetFilename;
		if (!move_uploaded_file($file['tmp_name'], $targetPath)) send_json(['success' => false, 'error' => 'Failed to store file'], 500);

		$fileUrl = $this->cfg['upload_url'] . '/' . $targetFilename;
		$bookId = $this->bookModel->create((int)$decoded->sub, $title, $description, $fileUrl);
		if (!$bookId) send_json(['success' => false, 'error' => 'DB error creating book'], 500);

		record_activity($this->conn, (int)$decoded->sub, 'book.uploaded', 'book', (int)$bookId, ['title' => $title]);
		send_json(['success' => true, 'book_id' => $bookId, 'file_path' => $fileUrl], 201);
	}

	// GET /index.php/books - list all books (for browsing/reading)
	public function listBooks() {
		$this->requireAuth();
		$rows = $this->bookModel->listAll();
		send_json(['success' => true, 'books' => $rows]);
	}

	// GET /index.php/books/mine (author) - list authored books
	public function listMyBooks() {
		$decoded = $this->requireAuth();
		if (($decoded->role ?? '') !== 'author') send_json(['success' => false, 'error' => 'Only authors can list their books'], 403);
		$rows = $this->bookModel->listByAuthor((int)$decoded->sub);
		send_json(['success' => true, 'books' => $rows]);
	}

	// POST /index.php/books/{id}/reviews (reviewer) - add comment
	public function addReview($bookId) {
		$decoded = $this->requireAuth();
		if (($decoded->role ?? '') !== 'reviewer') send_json(['success' => false, 'error' => 'Only reviewers can comment'], 403);
		$body = json_decode(file_get_contents('php://input'), true);
		$comment = trim($body['comment'] ?? '');
		if (!$comment) send_json(['success' => false, 'error' => 'comment is required'], 400);
		$ok = $this->reviewModel->add((int)$bookId, (int)$decoded->sub, $comment);
		if (!$ok) send_json(['success' => false, 'error' => 'DB error adding review'], 500);
		record_activity($this->conn, (int)$decoded->sub, 'review.added', 'book', (int)$bookId);
		send_json(['success' => true, 'message' => 'Review added']);
	}

	// GET /index.php/books/{id}/reviews - list reviews on a book
	public function listBookReviews($bookId) {
		$this->requireAuth();
		$rows = $this->reviewModel->listByBook((int)$bookId);
		send_json(['success' => true, 'reviews' => $rows]);
	}

	// GET /index.php/reviews/mine (reviewer) - reviews created by me
	public function listMyReviews() {
		$decoded = $this->requireAuth();
		if (($decoded->role ?? '') !== 'reviewer') send_json(['success' => false, 'error' => 'Only reviewers can view this'], 403);
		$rows = $this->reviewModel->listByReviewer((int)$decoded->sub);
		send_json(['success' => true, 'reviews' => $rows]);
	}

	// POST /index.php/books/{id}/invite (author) - invite reviewer by email
	public function inviteReviewer($bookId) {
		$decoded = $this->requireAuth();
		if (($decoded->role ?? '') !== 'author') send_json(['success' => false, 'error' => 'Only authors can invite reviewers'], 403);
		$body = json_decode(file_get_contents('php://input'), true);
		$email = strtolower(trim($body['email'] ?? ''));
		if (!$email) send_json(['success' => false, 'error' => 'email is required'], 400);
		$token = bin2hex(random_bytes(16));
		$ok = $this->invitationModel->create((int)$bookId, (int)$decoded->sub, $email, $token);
		if (!$ok) send_json(['success' => false, 'error' => 'DB error creating invitation'], 500);
		// In a full app, send email with token link. For now return token.
		record_activity($this->conn, (int)$decoded->sub, 'invite.created', 'book', (int)$bookId, ['email' => $email]);
		send_json(['success' => true, 'message' => 'Invitation created', 'token' => $token]);
	}

	// GET /index.php/books/{id}/invitations (author) - list invitations
	public function listInvitations($bookId) {
		$decoded = $this->requireAuth();
		if (($decoded->role ?? '') !== 'author') send_json(['success' => false, 'error' => 'Only authors can view invitations'], 403);
		$rows = $this->invitationModel->listByBook((int)$bookId);
		send_json(['success' => true, 'invitations' => $rows]);
	}
} 