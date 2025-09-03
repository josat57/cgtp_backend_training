<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt.php';

// Controllers
require_once __DIR__ . '/../app/Controllers/BookController.php';
require_once __DIR__ . '/../app/Controllers/ReviewController.php';
require_once __DIR__ . '/../app/Controllers/ProfileController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

header('Content-Type: application/json');

$controllerType = $_GET['controller'] ?? '';
$action = $_GET['action'] ?? '';

switch ($controllerType) {

    // ================= AUTH =================
    case 'auth':
        $controller = new AuthController($pdo);
        switch ($action) {
            case 'register':
                $controller->register();
                break;
            case 'login':
                $controller->login();
                break;
            case 'verify':
                $controller->verifyEmail();
                break;
            default:
                echo json_encode(['error' => 'Invalid action for AuthController']);
        }
        break;

    // ================= PROFILE =================
    case 'profile':
        $controller = new ProfileController($pdo);
        switch ($action) {
            case 'get':
                $controller->getProfile();
                break;
            case 'update':
                $controller->updateProfile($_POST);
                break;
            case 'delete':
                $controller->deleteProfile();
                break;
            case 'list': // 🔑 Super Admin only
                $controller->listUsers();
                break;
            default:
                echo json_encode(['error' => 'Invalid action for ProfileController']);
        }
        break;

    // ================= BOOK =================
    case 'book':
        $controller = new BookController($pdo);
        switch ($action) {
            case 'upload':
                $controller->uploadBook($_POST);
                break;
            case 'invite':
                $controller->inviteReviewer($_POST);
                break;
            case 'list':
                if ($controller->isSuperAdmin()) {
                    $controller->listAllBooks(); // Super Admin: view all books
                } else {
                    $controller->listBooks();    // Regular: view own books
                }
                break;
            case 'reviews':
                $bookId = $_GET['book_id'] ?? null;
                if (!$bookId) die(json_encode(['error' => 'Book ID is required']));
                $controller->viewReviews($bookId);
                break;
            default:
                echo json_encode(['error' => 'Invalid action for BookController']);
        }
        break;

    // ================= REVIEW =================
    case 'review':
        $controller = new ReviewController($pdo);
        switch ($action) {
            case 'add':
                $controller->addReview($_POST);
                break;
            case 'assigned':
                $controller->listAssignedReviews();
                break;
            case 'all': // 🔑 Super Admin only
                $controller->listAllReviews();
                break;
            default:
                echo json_encode(['error' => 'Invalid action for ReviewController']);
        }
        break;

    // ================= DEFAULT =================
    default:
        echo json_encode(['error' => 'Invalid controller']);
}
