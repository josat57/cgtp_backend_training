<?php
$host = "localhost";   // or 127.0.0.1
$dbname = "user_management";  // your database name
$username = "root";    // default in XAMPP
$password = "";        // default in XAMPP (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set error mode to exception (good practice)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>
