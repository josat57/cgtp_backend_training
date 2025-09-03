<?php
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../helpers/utility.php';
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $conn;
    private $userModel;
    private $cfg;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->userModel = new User($conn);
        $this->cfg = require __DIR__ . '/../config/config.php';
    }

    // GET /api/users/me
    public function me() {
        $token = get_bearer_token_from_header();
        if (!$token) send_json(['success' => false, 'error' => 'Missing token'], 401);
        if (token_is_blacklisted($this->conn, $token)) send_json(['success' => false, 'error' => 'Token revoked'], 401);
        $decoded = decode_jwt_or_null($token);
        if (!$decoded) send_json(['success' => false, 'error' => 'Invalid token'], 401);
        $user_id = $decoded->sub ?? null;
        if (!$user_id) send_json(['success' => false, 'error' => 'Invalid token payload'], 401);

        $user = $this->userModel->findById($user_id);
        if (!$user) send_json(['success' => false, 'error' => 'User not found'], 404);

        send_json(['success' => true, 'user' => $user]);
    }

    // PUT /api/users/me - update name
    public function update() {
        $token = get_bearer_token_from_header();
        if (!$token) send_json(['success' => false, 'error' => 'Missing token'], 401);
        if (token_is_blacklisted($this->conn, $token)) send_json(['success' => false, 'error' => 'Token revoked'], 401);
        $decoded = decode_jwt_or_null($token);
        if (!$decoded) send_json(['success' => false, 'error' => 'Invalid token'], 401);
        $user_id = $decoded->sub ?? null;

        $body = json_decode(file_get_contents('php://input'), true);
        $fullname = trim($body['fullname'] ?? '');
        if (!$fullname) send_json(['success' => false, 'error' => 'fullname is required'], 400);

        $stmt = $this->conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt->bind_param("si", $fullname, $user_id);
        if ($stmt->execute()) {
            record_activity($this->conn, (int)$user_id, 'user.profile_updated', 'user', (int)$user_id);
            send_json(['success' => true, 'message' => 'Profile updated']);
        } else {
            send_json(['success' => false, 'error' => 'Could not update profile'], 500);
        }
    }

    // POST /api/users/me/avatar - multipart/form-data upload
    public function uploadAvatar() {
        $token = get_bearer_token_from_header();
        if (!$token) send_json(['success' => false, 'error' => 'Missing token'], 401);
        if (token_is_blacklisted($this->conn, $token)) send_json(['success' => false, 'error' => 'Token revoked'], 401);
        $decoded = decode_jwt_or_null($token);
        if (!$decoded) send_json(['success' => false, 'error' => 'Invalid token'], 401);
        $user_id = $decoded->sub ?? null;

        // Ensure upload dir exists
        $upload_dir = $this->cfg['upload_dir'];
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        if (!isset($_FILES['avatar'])) {
            send_json(['success' => false, 'error' => 'No file uploaded. Use field name "avatar"'], 400);
        }

        $file = $_FILES['avatar'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            send_json(['success' => false, 'error' => 'Upload error code: ' . $file['error']], 400);
        }

        // basic validation
        $allowed = ['image/jpeg', 'image/png'];
        if (!in_array($file['type'], $allowed)) {
            send_json(['success' => false, 'error' => 'Allowed types: jpg, png'], 400);
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            send_json(['success' => false, 'error' => 'Max size 2MB'], 400);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetFilename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
        $targetPath = rtrim($upload_dir, '/') . '/' . $targetFilename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            send_json(['success' => false, 'error' => 'Failed to move uploaded file'], 500);
        }

        // update DB (avatar_path stores relative path)
        $avatar_path = $this->cfg['upload_url'] . '/' . $targetFilename;
        $stmt = $this->conn->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
        $stmt->bind_param("si", $avatar_path, $user_id);
        if ($stmt->execute()) {
            record_activity($this->conn, (int)$user_id, 'user.avatar_uploaded', 'user', (int)$user_id, ['avatar_path' => $avatar_path]);
            send_json(['success' => true, 'message' => 'Avatar uploaded', 'avatar_path' => $avatar_path]);
        } else {
            send_json(['success' => false, 'error' => 'DB error updating avatar'], 500);
        }
    }
}
