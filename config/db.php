<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

function getMongoDB() {
    $mongoClient = new Client('mongodb://localhost:27017'); // Update with your connection string
    return $mongoClient->selectDatabase('book_api'); // Update with your DB name
} 