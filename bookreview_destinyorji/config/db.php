<?php
require __DIR__ . '/../vendor/autoload.php'; // Load Composer packages

$mongoClient = new MongoDB\Client("mongodb://localhost:27017"); // Connect to MongoDB

$db = $mongoClient->book_review; // Select database (auto-created if it doesn’t exist)
return $db;
