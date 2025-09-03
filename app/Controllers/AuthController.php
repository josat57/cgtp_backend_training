<?php
namespace App\Controllers;

require '../../config/db.php';
require '../../vendor/autoload.php';
require '../../config/jwt.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {

    // ---------------- Register ----------------
    public function register() {
        global $pdo;

        $data = $_POST; // or json_decode(file_get_contents('php://input'), true)
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role_name = $data['role'] ?? 'reviewer';
        $method = $data['verification_method'] ?? 'link';

        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Please provide name, email, and password.']);
            return;
        }

        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already registered']);
                return;
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Get role_id
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
            $stmt->execute([$role_name]);
            $role = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$role) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid role']);
                return;
            }
            $role_id = $role['id'];

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = '127.0.0.1';
            $mail->Port = 1025;
            $mail->SMTPAuth = false;

            $mail->setFrom('no-reply@cinforex.local', 'Cinforex User Management');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);

            if ($method === 'otp') {
                $otp = rand(100000, 999999);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id, email_verified, email_otp) VALUES (?, ?, ?, ?, 0, ?)");
                $stmt->execute([$name, $email, $hashedPassword, $role_id, $otp]);
                $userId = $pdo->lastInsertId();

                $mail->Subject = 'Your Verification OTP';
                $mail->Body = "Hi $name,<br><br>Your email verification OTP is: <b>$otp</b>";
            } else {
                $token = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id, email_verified, verification_token) VALUES (?, ?, ?, ?, 0, ?)");
                $stmt->execute([$name, $email, $hashedPassword, $role_id, $token]);
                $userId = $pdo->lastInsertId();

                $mail->Subject = 'Verify Your Email';
                $mail->Body = "Hi $name,<br><br>Click this link to verify your email:<br>
                    <a href='http://localhost/adams-raphael_user-management-api/public/verify_email.php?token=$token'>Verify Email</a>";
            }

            $mail->send();

            // Log registration
            $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
            $stmtLog->execute([$userId, "Registered new account using $method"]);

            echo json_encode([
                'success' => true,
                'message' => "User registered! Check MailHog for verification.",
                'user_id' => $userId
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => "Could not send email: " . $mail->ErrorInfo]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => "Database error: " . $e->getMessage()]);
        }
    }

    // ---------------- Login ----------------
    public function login() {
        global $pdo;

        $data = $_POST;
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }

        $stmt = $pdo->prepare("SELECT id, name, email, password, role_id, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        if (!$user['email_verified']) {
            http_response_code(403);
            echo json_encode(['error' => 'Email not verified']);
            return;
        }

        // ---------------- JWT with role name ----------------
        $stmtRole = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
        $stmtRole->execute([$user['role_id']]);
        $role = $stmtRole->fetchColumn();

        $payload = [
            'iat' => time(),
            'exp' => time() + (60*60),
            'data' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $role
            ]
        ];

        $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');

        // Log login
        $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$user['id'], "Logged in"]);

        echo json_encode(['success' => true, 'token' => $jwt]);
    }

    // ---------------- Verify Email OTP / Link ----------------
    public function verifyEmail() {
        global $pdo;

        $data = $_POST;
        $userId = $data['user_id'] ?? null;
        $otp = $data['otp'] ?? null;
        $token = $data['token'] ?? null;

        if ($otp) {
            $stmt = $pdo->prepare("SELECT email_otp FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user || $user['email_otp'] != $otp) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid OTP']);
                return;
            }

            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, email_otp = NULL WHERE id = ?");
            $stmt->execute([$userId]);

        } elseif ($token) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid verification link']);
                return;
            }

            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'OTP or token is required']);
            return;
        }

        // Log verification
        $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmtLog->execute([$user['id'] ?? $userId, "Verified email"]);

        echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
    }
}