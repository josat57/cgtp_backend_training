<?php
class User {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($fullname, $email, $password_hash, $role = 'reviewer') {
        $stmt = $this->conn->prepare("INSERT INTO users (fullname, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullname, $email, $password_hash, $role);
        if (!$stmt->execute()) {
            return false;
        }
        return $this->conn->insert_id;
    }

    public function findByEmail($email) {
        $stmt = $this->conn->prepare("SELECT id, fullname, email, password_hash, role, avatar_path, is_verified, created_at FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT id, fullname, email, role, avatar_path, is_verified, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    public function setVerified($id) {
        $stmt = $this->conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
