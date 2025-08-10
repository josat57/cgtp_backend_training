<?php
require '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';

    if ($action === 'register') {
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$username || !$email || !$password) {
            echo json_encode(['error' => 'Missing fields']);
            exit;
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password_hash);

        if ($stmt->execute()) {
            echo json_encode(['message' => 'User registered']);
        } else {
            echo json_encode(['error' => 'Registration failed']);
        }
        $stmt->close();
    }

    if ($action === 'login') {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $password_hash);
            $stmt->fetch();
            if (password_verify($password, $password_hash)) {
                echo json_encode(['message' => 'Login successful', 'user_id' => $id]);
            } else {
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } else {
            echo json_encode(['error' => 'Invalid credentials']);
        }
        $stmt->close();
    }
}
?>        