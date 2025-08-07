<?php

//interacts with the users table in th database
class User {
    private $conn;

    //constructor to pass database connection
public function __construct($db) {
    $this->conn = $db;
}

// to check if user already exist(the logic is in authcontroller.php)
public function findByEmail($email) {
    $sql = "SELECT * FROM users WHERE email= ?";
    $stmt= $this->conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc(); //returns null if not found

}

    
   //to create new user
   public function create ($name, $email, $password) {
    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $this->conn->prepare($sql);
    $stmt ->bind_param("sss", $name, $email, $password);
    return $stmt->execute();
   }

   // Generate a simple token (in production, use JWT)
   public function generateToken($user_id) {
       $token = bin2hex(random_bytes(32));
       $sql = "UPDATE users SET token = ? WHERE id = ?";
       $stmt = $this->conn->prepare($sql);
       $stmt->bind_param("si", $token, $user_id);
       $stmt->execute();
       return $token;
   }

   // Find user by token
   public function findByToken($token) {
       $sql = "SELECT id, name, email FROM users WHERE token = ?";
       $stmt = $this->conn->prepare($sql);
       $stmt->bind_param("s", $token);
       $stmt->execute();
       $result = $stmt->get_result();
       return $result->fetch_assoc();
   }

}

  

