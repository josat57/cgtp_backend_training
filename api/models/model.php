<?php
require_once "../data/Crud.data.php";

class Models
{
    public $table;
    private $conn;
    protected static $crud;

    public function __construct()
    {
        self::$crud = new Crud();
    }
    
    public function createtUser($data) {
        $password = password_hash($data["password"], PASSWORD_DEFAULT);
        $result = self::$crud->create("users", [
            "first_name" => $data["first_name"],
            "last_name" => $data["last_name"],
            "email" => $data["email"],
            "phone" => $data["phone"],
            "password" => $password
        ]);
        return $result;
    }
    
    public function getUserByEmail($email) {
        $result = self::$crud->findByEmail("users", $email);
        if ($result) {
            return $result;
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