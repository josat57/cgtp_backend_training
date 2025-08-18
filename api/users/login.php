<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->task_manager->users;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Check if all fields are filled
    if (!$email || !$password) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Email and password are required.'
        ]);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email format.'
        ]);
        exit;
    }

    // Check if the user exists
    $existingUser = $collection->findOne(['email' => $email]);
    if (!$existingUser) {
      http_response_code(404); // Not Found
      echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email or password.'
      ]);
      exit;
    }

    //Verify password
    if (!password_verify($password, $existingUser['password'])) {
      http_response_code(401);
      echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email or password.'
      ]);
      exit;
    }

    // Create JWT Payload
    $userId = (string) $existingUser['_id'];
    $payload = [
      'iss' => JWT_ISSUER,    //Issuer
      'sub' => $userId,        //Subject (User ID)
      'email' => $email,      //User Email
      'iat' => time(),         //Issued At
      'exp' => time() + (60 * 60 * 24) // Token valid for 1 day
    ];

    // Generate JWT Token
    $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');

    http_response_code(200); // OK
    
    // Return the JWT token and user ID
    echo json_encode([
      'status' => 'success',
      'message' => 'User logged in successfully.',
      'user_id' => $userId,
      'token' => $jwt
    ]);
    
} else {
  http_response_code(405); // Method Not Allowed
  echo json_encode([
    'status' => 'error',
    'message' => 'Invalid request method.'
  ]);
}