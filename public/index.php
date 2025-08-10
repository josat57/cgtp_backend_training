<?php
// router to handle different request paths (URLs).
$request = $_SERVER['REQUEST_URI'];
switch (true) {
    case preg_match('/^\/books/', $request):
        require '../routes/books.php';
        break;
     case preg_match('/^\/reviews/', $request):
        require '../routes/reviews.php';
        break;
    case preg_match('/^\/users/', $request):
        require '../routes/users.php';
        break; 

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not Found']);





    }
        
    
?>