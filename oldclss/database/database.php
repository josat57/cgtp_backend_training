<?php

$database_name = "dbmeetme";
$host = "localhost";
$username = "root";
$password = "root";
$conn = new mysqli($host, $username, $password, $database_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully";
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

// createTable($conn);

function insertUser($conn, $data) {
    $stmt = $conn->prepare(
        "INSERT INTO users (first_name, last_name, email, phone, password)
        VALUES (?, ?, ?, ?, ?)"
    );
    $password = password_hash($data->password, PASSWORD_DEFAULT);
    $stmt->bind_param(
        "sssss",
        $data->first_name,
        $data->last_name,
        $data->email,
        $data->phone,
        $password
    );
    if ($stmt->execute()) {
        $res = ['statuscode'=>0, 'status'=>"New record created successfully"];
    } else {
        $res = ['statuscode'-1, 'status'=>"Error: " . $stmt->error];
    }
    $stmt->close();
    return $res;
}

function getUsers($conn) {
    $data = [];
    $stmt = $conn->prepare("SELECT * FROM users");
    $res;
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            while ($row = $result->fetch_assoc()) {
                // $tr = "<tr>
                //     <td>{$row['id']}</td>
                //     <td>{$row['first_name']}</td>
                //     <td>{$row['last_name']}</td>
                //     <td>{$row['email']}</td>
                //     <td>{$row['phone']}</td>
                // </tr>";
                array_push($data, $row);
            }
            $res = ['statuscode' => 0, 'status' => "Found $result->num_rows Result", 'data' => $data];
        } else {
            $res = ["statuscode" => -1, 'status' => "No records found"];
        }
    } else {
        $res =['statuscode' => -1, 'status' => "Could not fetch records"];
    }
    $stmt->close();
    return $res;
}

function getUserByEmail($conn, $data) {
    $stmt = $conn->prepare("SELECT first_name, last_name, id, phone, email FROM users WHERE email = ?");
    $stmt->bind_param('s', $data["email"]);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $res = ['statuscode' => 0, 'status' => "Found $result->num_rows Result", 'data' => $row];
        } else {
            $res = ["statuscode" => -1, 'status' => "No records found"];
        }
    } else {
        $res =['statuscode' => -1, 'status' => "Could not fetch records"];
    }
    return json_encode($res);
}



