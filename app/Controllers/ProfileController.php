<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ProfileController {
    private $pdo;
    private $userId;
    private $roleId;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->authenticate();
    }

    private function authenticate() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        $authHeader = trim($authHeader);
        $token = str_replace('Bearer ', '', $authHeader);
        $token = trim($token);

        if (!$token) die("❌ No token provided.");

        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            $this->userId = $decoded->data->id;
            $this->roleId = $decoded->data->role_id;
        } catch (Exception $e) {
            die("❌ Invalid token: " . $e->getMessage());
        }
    }

    public function getProfile() {
        $stmt = $this->pdo->prepare("SELECT id, name, email, phone, avatar, role_id FROM users WHERE id = ?");
        $stmt->execute([$this->userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) die("❌ User not found.");
        echo json_encode($user);
    }

    public function updateProfile($data) {
        $targetId = $data['target_id'] ?? $this->userId;
        if ($this->roleId != 1 && $targetId != $this->userId) die("❌ Access denied.");

        $fields = [];
        $values = [];

        foreach (['name', 'phone', 'avatar'] as $field) {
            if (!empty($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) die("❌ No fields provided to update.");
        $values[] = $targetId;

        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        // Log the update
        $stmtLog = $this->pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$this->userId, "Updated profile for user ID $targetId"]);

        echo "✅ Profile updated successfully!";
    }

    public function deleteProfile() {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$this->userId]);
        echo "✅ User account deleted successfully.";
    }
}