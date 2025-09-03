<?php
require '../config/db.php';

// Get POST data
$email = $_POST['email'] ?? '';
$otp = $_POST['otp'] ?? '';

if (empty($email) || empty($otp)) {
    die("❌ Please provide both email and OTP.");
}

try {
    // Find user with matching email + OTP
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND email_otp = ?");
    $stmt->execute([$email, $otp]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("❌ Invalid OTP or email.");
    }

    // Mark email as verified
    $stmt = $pdo->prepare("
        UPDATE users 
        SET email_verified = 1, email_otp = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);

    echo "✅ Email verified successfully! You can now log in.";

} catch (PDOException $e) {
    die("❌ Database error: " . $e->getMessage());
}
