<?php

class ActivityLog {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function record(?int $actorUserId, string $action, ?string $entityType = null, ?int $entityId = null, array $meta = []): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $metaJson = !empty($meta) ? json_encode($meta) : null;

        $stmt = $this->conn->prepare("INSERT INTO activity_logs (actor_user_id, action, entity_type, entity_id, meta, ip_address, user_agent) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param(
            "ississs",
            $actorUserId,
            $action,
            $entityType,
            $entityId,
            $metaJson,
            $ip,
            $ua
        );
        return $stmt->execute();
    }

    public function listAll(int $limit = 100, int $offset = 0): array {
        $stmt = $this->conn->prepare("SELECT id, actor_user_id, action, entity_type, entity_id, meta, ip_address, user_agent, created_at FROM activity_logs ORDER BY id DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (isset($row['meta']) && is_string($row['meta'])) {
                $decoded = json_decode($row['meta'], true);
                $row['meta'] = is_array($decoded) ? $decoded : null;
            }
            $rows[] = $row;
        }
        return $rows;
    }
}


