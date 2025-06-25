<?php

function dbSettings() {
    $settings = [
        "db_host" => "localhost",
        "db_name" => "dbmeetme",
        "db_user" => "root",
        "db_password" => "root"
    ];
    return (object) $settings;
}

function connectToDatabase() {
    $settings = dbSettings();
    $conn = new mysqli($settings->db_host, $settings->db_user, $settings->db_password, $settings->db_name);

    if ($conn->connect_error) {
        return "Connection failed: " . $conn->connect_error;
    }
    return $conn;
}
function closeDatabaseConnection($conn) {
    $conn->close();
}
function createTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(15) NOT NULL,
        password VARCHAR(255) NOT NULL
    )";

    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        return false;
    }
}
function insertUser($conn, $data) {
    $password = password_hash($data->password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $data->first_name, $data->last_name, $data->email, $data->phone, $password);
    $result = $stmt->execute();
    if ($result) {
        return "New record created successfully";
    } else {
        return "Error: " . $stmt->error;
    }
}

function getUserByEmail($conn, $email) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

function logInUser($conn, $data) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $data->email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if(password_verify($data->password, $row["password"])) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}