<?php
class OtpModel {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($user_id, $code_hash, $purpose, $expires_at) {
        $stmt = $this->conn->prepare("INSERT INTO otps (user_id, code_hash, purpose, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $code_hash, $purpose, $expires_at);
        return $stmt->execute();
    }

    // Get active (not used, not expired) OTPs for user+purpose ordered newest first
    public function getActiveForUser($user_id, $purpose) {
        $stmt = $this->conn->prepare("SELECT id, code_hash, expires_at, used FROM otps WHERE user_id = ? AND purpose = ? AND used = 0 AND expires_at >= NOW() ORDER BY created_at DESC");
        $stmt->bind_param("is", $user_id, $purpose);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public function markUsed($otp_id) {
        $stmt = $this->conn->prepare("UPDATE otps SET used = 1 WHERE id = ?");
        $stmt->bind_param("i", $otp_id);
        return $stmt->execute();
    }
}
