<?php
// for the user logic 
require_once './models/user.php';

class AuthController{
    private $conn;
    private $userModel;

     public function __construct($db) {
        $this->conn = $db;
        $this->userModel = new User($db);
    }



//for user registration(post)
   public static function register($conn) {

    //to get raw json body input
    $data = json_decode(file_get_contents('php://input'), true);

    //to check if name, email and password are provided
    if(empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode(['message'=> 'name, email, and password are required']);
        return;
    }
        $name = $data['name'];
        $email = $data['email'];
        $password = $data['password'];

        // Create user model instance
        $userModel = new User($conn);

        // Check if user already exists
        if ($userModel->findByEmail($email)) {
            http_response_code(409); // Conflict
            echo json_encode(['message' => 'Email already exists.']);
            return;
        }

        // Hash the password before storing it
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Create user
        if ($userModel->create($name, $email, $hashedPassword)) {
            http_response_code(201); // Created
            echo json_encode(['message' => 'User registered successfully.']);
        } else {
            http_response_code(500); // Server error
            echo json_encode(['message' => 'Failed to register user.']);
        }



   }

   //for login (post)
   public static function login($conn){

    $data= json_decode(file_get_contents("php://input"), true);

    if((empty($data['email']) || empty($data['password']))) {
      http_response_code(401);
      echo json_encode(["message" => "email and password required"]);
      return;
    }
        $email = $data['email'];
        $password = $data['password'];

        // Create user model instance
        $userModel = new User($conn);

        // Find user by email
        $user = $userModel->findByEmail($email);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid email or password"]);
            return;
        }

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Generate token
            $token = $userModel->generateToken($user['id']);
            
            http_response_code(200);
            echo json_encode([
                "message" => "Login successful",
                "token" => $token,
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "email" => $user['email']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Invalid email or password"]);
        }

   }
   

}




