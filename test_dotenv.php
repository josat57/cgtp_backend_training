<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "JWT_SECRET: " . getenv('JWT_SECRET') . PHP_EOL;
print_r($_ENV);
