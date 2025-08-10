<?php
//used to establish connection to the mysql database
$host = "localhost";
$username = "root";
$password = "";
$database = "book_review_db";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['error' =>'Database connection failed']));

}
?>