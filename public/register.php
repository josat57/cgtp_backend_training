<?php
require '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get POST data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role_name = $_POST['role'] ?? 'reviewer';
$method = $_POST['verification_method'] ?? 'link'; // 'link' or 'otp'

// Basic validation
if (empty($name) || empty($email) || empty($password)) {
    die("❌ Please provide name, email, and password.");
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) die("❌ Email already registered");

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Get role_id
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$role_name]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$role) die("❌ Invalid role");
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
        // OTP method
        $otp = rand(100000, 999999);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role_id, email_verified, email_otp)
            VALUES (?, ?, ?, ?, 0, ?)
        ");
        $stmt->execute([$name, $email, $hashedPassword, $role_id, $otp]);

        $mail->Subject = 'Your Verification OTP';
        $mail->Body = "Hi $name,<br><br>Your email verification OTP is: <b>$otp</b>";

    } else {
        // Link method
        $token = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role_id, email_verified, verification_token)
            VALUES (?, ?, ?, ?, 0, ?)
        ");
        $stmt->execute([$name, $email, $hashedPassword, $role_id, $token]);

        $mail->Subject = 'Verify Your Email';
        $mail->Body = "Hi $name,<br><br>Click this link to verify your email:<br>
            <a href='http://localhost/adams-raphael_user-management-api/public/verify_email.php?token=$token'>Verify Email</a>";
    }

    $mail->send();
    echo "✅ User registered! Check MailHog for verification.";

} catch (Exception $e) {
    die("❌ Could not send email: {$mail->ErrorInfo}");
} catch (PDOException $e) {
    die("❌ Database error: " . $e->getMessage());
}
