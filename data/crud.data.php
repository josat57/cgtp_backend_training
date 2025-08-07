<?php
// /data/crud.data.php

require_once __DIR__ . '/../config/config.php';

class Crud {
    public static function insert($table, $data) {
        $pdo = getDbConnection();
        
        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new Exception('Invalid table name');
        }
        
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $stmt = $pdo->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");
        
        try {
            $stmt->execute($data);
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception('Database insert failed: ' . $e->getMessage());
        }
    }

    public static function select($table, $conditions = [], $options = []) {
        $pdo = getDbConnection();
        
        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new Exception('Invalid table name');
        }
        
        $sql = "SELECT * FROM $table";
        $params = [];

        if ($conditions) {
            $wheres = [];
            foreach ($conditions as $key => $val) {
                $wheres[] = "$key = :$key";
                $params[$key] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }

        if (isset($options['limit'])) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params['limit'] = (int)$options['limit'];
            $params['offset'] = (int)($options['offset'] ?? 0);
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Database select failed: ' . $e->getMessage());
        }
    }

    public static function update($table, $data, $conditions) {
        $pdo = getDbConnection();
        
        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new Exception('Invalid table name');
        }
        
        $setParts = [];
        foreach ($data as $key => $val) {
            $setParts[] = "$key = :$key";
        }

        $whereParts = [];
        foreach ($conditions as $key => $val) {
            $whereParts[] = "$key = :cond_$key";
        }

        $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE " . implode(' AND ', $whereParts);
        $stmt = $pdo->prepare($sql);

        foreach ($data as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        foreach ($conditions as $key => $val) {
            $stmt->bindValue(":cond_$key", $val);
        }

        try {
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('Database update failed: ' . $e->getMessage());
        }
    }

    public static function delete($table, $conditions) {
        $pdo = getDbConnection();
        
        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new Exception('Invalid table name');
        }
        
        $whereParts = [];
        foreach ($conditions as $key => $val) {
            $whereParts[] = "$key = :$key";
        }

        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereParts);
        $stmt = $pdo->prepare($sql);

        foreach ($conditions as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }

        try {
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('Database delete failed: ' . $e->getMessage());
        }
    }

    public static function count($table, $conditions = []) {
        $pdo = getDbConnection();
        $sql = "SELECT COUNT(*) FROM $table";
        $params = [];

        if ($conditions) {
            $wheres = [];
            foreach ($conditions as $key => $val) {
                $wheres[] = "$key = :$key";
                $params[$key] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
