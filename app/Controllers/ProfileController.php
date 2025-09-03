<?php
namespace App\Controllers;

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

use App\Middleware\AuthMiddleware;
use PDO;

class ProfileController {
    private $pdo;
    private $currentUser;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->authenticate();
    }

    private function authenticate() {
        // Decode JWT for any logged-in user (minimum = reviewer)
        $this->currentUser = AuthMiddleware::checkRole('reviewer');
        // currentUser contains ->id, ->role_id, etc.
    }

    // --------------------
    // Get own profile
    // --------------------
    public function getProfile() {
        $stmt = $this->pdo->prepare("SELECT id, name, email, phone, avatar, role_id 
                                     FROM users WHERE id = ?");
        $stmt->execute([$this->currentUser->id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        echo json_encode($user);
    }

    // --------------------
    // List all users (Super Admin only)
    // --------------------
    public function listUsers() {
        if ($this->currentUser->role_id != 1) { // 1 = Super Admin
            http_response_code(403);
            echo json_encode(['error' => 'Access denied: only Super Admin can view all users']);
            return;
        }

        $stmt = $this->pdo->query("SELECT id, name, email, phone, avatar, role_id 
                                   FROM users ORDER BY id ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Log action
        $stmtLog = $this->pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$this->currentUser->id, "Viewed all users"]);

        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    }

    // --------------------
    // Update profile
    // --------------------
    public function updateProfile($data) {
        $targetId = $data['target_id'] ?? $this->currentUser->id;

        // Only super admin can update other users
        if ($this->currentUser->role_id != 1 && $targetId != $this->currentUser->id) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        $fields = [];
        $values = [];
        foreach (['name', 'phone', 'avatar'] as $field) {
            if (!empty($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            echo json_encode(['error' => 'No fields provided to update']);
            return;
        }

        $values[] = $targetId;

        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        // Log the update
        $stmtLog = $this->pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$this->currentUser->id, "Updated profile for user ID $targetId"]);

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    }

    // --------------------
    // Delete profile
    // --------------------
    public function deleteProfile($targetId = null) {
        $targetId = $targetId ?? $this->currentUser->id;

        // Only super admin can delete other users
        if ($this->currentUser->role_id != 1 && $targetId != $this->currentUser->id) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$targetId]);

        // Log deletion
        $stmtLog = $this->pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$this->currentUser->id, "Deleted user ID $targetId"]);

        echo json_encode(['success' => true, 'message' => 'User account deleted successfully']);
    }
}
