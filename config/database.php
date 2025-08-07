<?php

$host ='localhost';
$username = 'root';
$password = '';
$database = 'task_api_db';

$conn= mysqli_connect($host, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(['message' => 'database connection failed'. $conn->connect_error]));
}