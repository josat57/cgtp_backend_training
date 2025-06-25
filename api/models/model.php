<?php
require_once "../config/config.php";

class Models extends DBConfig
{
    public $table;
    private $conn;

    public function __construct()
    {
        $this->conn = $this->connectToDatabase();
    }

    public function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(15) NOT NULL,
            password VARCHAR(255) NOT NULL
        )";
    
        if ($this->conn->query($sql) === TRUE) {
            return true;
        } else {
            return false;
        }
    }
    
    public function insertUser($data) {
        $password = password_hash($data["password"], PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $data["first_name"], $data["last_name"], $data["email"], $data["phone"], $password);
        $result = $stmt->execute();
        if ($result) {
            return "New record created successfully";
        } else {
            return "Error: " . $stmt->error;
        }
    }
    
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    }
    
    public function logInUser($conn, $data) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $data->email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if(password_verify($data->password, $row["password"])) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}