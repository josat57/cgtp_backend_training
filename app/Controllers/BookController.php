<?php
require '../../config/db.php';
require '../../vendor/autoload.php';
require '../../config/jwt.php';

require_once __DIR__ . '/../Models/book.php';
require_once __DIR__ . '/../Models/review.php';
require_once __DIR__ . '/../Models/invite.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

class BookController {
    private $pdo;
    private $userId;
    private $roleId;
    private $bookModel;
    private $reviewModel;
    private $inviteModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->bookModel = new Book($pdo);
        $this->reviewModel = new Review($pdo);
        $this->inviteModel = new Invite($pdo);

        // JWT authentication
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', trim($authHeader));
        if (!$token) die("❌ No token provided.");

        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            $this->userId = $decoded->data->id;
            $this->roleId = $decoded->data->role_id;
        } catch (Exception $e) {
            die("❌ Invalid token: " . $e->getMessage());
        }
    }

    // --------------------
    // Upload book
    // --------------------
    public function uploadBook($data) {
        if ($this->roleId != 2) die("❌ Access denied: only Authors can upload books.");

        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';

        if (!$title) die("❌ Book title is required.");

        $bookId = $this->bookModel->create($title, $description, $this->userId);

        // Log action
        $stmtLog = $this->pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$this->userId, "Uploaded book ID $bookId: $title"]);

        echo json_encode([
            'success' => true,
            'message' => "✅ Book uploaded successfully.",
            'book_id' => $bookId
        ]);
    }

    // --------------------
    // Invite reviewer
    // --------------------
    public function inviteReviewer($data) {
        if ($this->roleId != 2) die("❌ Access denied: only Authors can invite reviewers.");

        $bookId = $data['book_id'] ?? '';
        $reviewerEmail = $data['reviewer_email'] ?? '';
        if (!$bookId || !$reviewerEmail) die("❌ Book ID and reviewer email are required.");

        // Ensure book belongs to author
        $book = $this->bookModel->belongsToAuthor($bookId, $this->userId);
        if (!$book) die("❌ Book not found or you don't own it.");

        // Generate invitation token
        $inviteToken = bin2hex(random_bytes(16));

        $this->inviteModel->create($bookId, $reviewerEmail, $inviteToken);

        // Send email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = '127.0.0.1';
            $mail->Port = 1025;
            $mail->SMTPAuth = false;

            $mail->setFrom('no-reply@cinforex.local', 'Cinforex User Management');
            $mail->addAddress($reviewerEmail);
            $mail->isHTML(true);

            $mail->Subject = "Invitation to review '{$book['title']}'";
            $mail->Body = "Hi,<br><br>You have been invited to review the book <b>{$book['title']}</b>.<br>
                           Use this token to submit your review: <b>$inviteToken</b><br><br>Thanks.";

            $mail->send();
        } catch (Exception $e) {
            die("❌ Could not send email: {$mail->ErrorInfo}");
        }

        // Log action
        $stmtLog = $this->pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$this->userId, "Sent invitation to $reviewerEmail for book ID $bookId"]);

        echo json_encode([
            'success' => true,
            'message' => "✅ Reviewer invited successfully.",
            'invite_token' => $inviteToken
        ]);
    }

    // --------------------
    // List all books for author
    // --------------------
    public function listBooks() {
        if ($this->roleId != 2) die("❌ Access denied: only Authors can list books.");

        $books = $this->bookModel->findByAuthor($this->userId);

        echo json_encode([
            'success' => true,
            'books' => $books
        ]);
    }

    // --------------------
    // List all books (Super Admin only)
    // --------------------
    public function listAllBooks() {
        if ($this->roleId != 1) { // Super Admin
            http_response_code(403);
            echo json_encode(['error' => 'Access denied: only Super Admin can view all books']);
            return;
        }

        $books = $this->bookModel->findAll();

        // Log action
        $stmtLog = $this->pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$this->userId, "Viewed all books"]);

        echo json_encode([
            'success' => true,
            'books' => $books
        ]);
    }

    // --------------------
    // View reviews for a specific book
    // --------------------
    public function viewReviews($bookId) {
        if ($this->roleId != 2) die("❌ Access denied: only Authors can view reviews.");

        // Ensure book belongs to author
        $book = $this->bookModel->belongsToAuthor($bookId, $this->userId);
        if (!$book) die("❌ Book not found or you don't own it.");

        $reviews = $this->reviewModel->findByBook($bookId);

        // Log action
        $stmtLog = $this->pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$this->userId, "Viewed reviews for book ID $bookId"]);

        echo json_encode([
            'success' => true,
            'book_id' => $bookId,
            'reviews' => $reviews
        ]);
    }
}

// --------------------
// Controller execution
// --------------------
$controller = new BookController($pdo);
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        $controller->uploadBook($_POST);
        break;
    case 'invite':
        $controller->inviteReviewer($_POST);
        break;
    case 'list':
        $controller->listBooks();
        break;
    case 'reviews':
        $bookId = $_GET['book_id'] ?? null;
        if (!$bookId) die("❌ Book ID is required.");
        $controller->viewReviews($bookId);
        break;
    case 'all':
        $controller->listAllBooks();
        break;
    case 'listAll':
        $controller->listAllBooks();
        break;
    default:
        echo json_encode(['error' => '❌ Invalid action']);
}
