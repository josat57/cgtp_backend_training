<?php
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class AdminController {
    private $conn;
    private $logger;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = new ActivityLog($conn);
    }

    private function requireSuperAdmin() {
        $token = get_bearer_token_from_header();
        if (!$token) send_json(['success' => false, 'error' => 'Missing token'], 401);
        if (token_is_blacklisted($this->conn, $token)) send_json(['success' => false, 'error' => 'Token revoked'], 401);
        $decoded = decode_jwt_or_null($token);
        if (!$decoded) send_json(['success' => false, 'error' => 'Invalid token'], 401);
        if (($decoded->role ?? '') !== 'super_admin') send_json(['success' => false, 'error' => 'Forbidden'], 403);
        return $decoded;
    }

    public function listActivityLogs() {
        $this->requireSuperAdmin();
        $limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 100;
        $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
        $rows = $this->logger->listAll($limit, $offset);
        send_json(['success' => true, 'logs' => $rows]);
    }
}


