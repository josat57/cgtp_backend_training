<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->task_manager->users;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    


    // Check if all fields are filled
    if ($firstName && $lastName && $email && $phone && $password) {
        
        $errors = [];
        $existingUser = $collection->findOne(['email' => $email]);
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email format.'
            ]);
            exit;
        }
        // Check if the email already exists
        if ($existingUser) {
            http_response_code(409); // Conflict
            echo json_encode([
                'status' => 'error',
                'message' => 'Email already exists.'
            ]);
            exit;
        }

        // Validate and Hash the password before storing
        if (strlen($password) < 8) {
            http_response_code(400); // Bad Request
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/', $password)) {
            // Password must contain at least one letter, one number, and one special character
            http_response_code(400); // Bad Request
            $errors[] = 'Password not strong enough. must contain at least one letter, one number, and one special character (e.g. @, $, !, %).';
            //exit;
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Validate phone number format (simple check)
        if (!preg_match('/^0[789][01]\d{8}+$/', $phone)){
            http_response_code(400); // Bad Request
            $errors[] = 'Invalid phone number format.';
            // exit;
        }
        // Validate first name format (simple check)
        if (!preg_match('/^[a-zA-Z]+$/', $firstName)) {
            http_response_code(400); // Bad Request
            $errors[] = 'Invalid first name format.';
            // exit;
        }
        // Validate last name format (simple check)
        if (!preg_match('/^[a-zA-Z]+$/', $lastName)) {
            http_response_code(400); // Bad Request
            $errors[] = 'Invalid last name format.';
            // exit;
        }

        if (!empty($errors)) {
            echo json_encode([
                'status' => 'error',
                'message' => implode(', ', $errors)
            ]);
            exit;
        }
        http_response_code(201); // Created
        // Insert the new user into the database
        $insertResult = $collection->insertOne([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'password' => $hashedPassword,
        ]);

        $userId = (string) $insertResult->getInsertedId();

        // Create JWT Payload
        $payload = [
            'iss' => JWT_ISSUER,    //Issuer
            'sub' => $userId,        //Subject (User ID)
            'email' => $email,      //User Email
            'iat' => time(),         //Issued At
            'exp' => time() + (60 * 60 * 24) // Token valid for 1 day
        ];

        // Generate JWT Token
        $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');

        http_response_code(201); // Created
        // Return the JWT token and user ID
        echo json_encode([
            'status' => 'success',
            'message' => 'User registered successfully.',
            'user_id' => $userId,
            'token' => $jwt
        ]);
        if ($insertResult->getInsertedCount() > 0) {
            header("Location: login.html");
            exit;
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please fill in all required fields.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
}

