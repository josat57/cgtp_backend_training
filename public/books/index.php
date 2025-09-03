<?php
require_once '../../controllers/BookController.php';

header('Content-Type: application/json');
$controller = new BookController($pdo);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $action = $_GET['action'] ?? '';

        if ($action === 'upload') {
            $controller->uploadBook($_POST);
        } elseif ($action === 'invite') {
            $controller->inviteReviewer($_POST);
        } elseif ($action === 'review') {
            $controller->addReview($_POST);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
        break;

    case 'GET':
        $bookId = $_GET['book_id'] ?? null;
        if (!$bookId) {
            http_response_code(400);
            echo json_encode(['error' => 'Book ID is required']);
            break;
        }
        $controller->getReviews($bookId);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
