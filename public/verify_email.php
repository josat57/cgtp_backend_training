<?php
require '../config/db.php';

$token = $_GET['token'] ?? '';
if (!$token) die("❌ Invalid token");

// Find user with this token
$stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("❌ Token invalid or expired");

// Mark email as verified
$stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
$stmt->execute([$user['id']]);

echo "✅ Email verified successfully! You can now log in.";
