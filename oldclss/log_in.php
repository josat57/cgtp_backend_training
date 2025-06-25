<?php
require_once "database/config.php";
$method = $_SERVER["REQUEST_METHOD"];
$conn = connectToDatabase();
$msg = "<p style='color:crimson'>Please enter ";
if ($method == "POST") {
    $data = $_POST;
    if (isset($data["email"]) && $data["email"] == "") {
        echo $msg . "email </p>";
    } else if (isset($data["password"]) && $data["password"] == "") {
        echo $msg . "password </p>";
    } else {
        $data = (object) $data;
        if ($conn) {
            $login = logInUser($conn, $data);
            if ($login) {
                session_start();
                $result = getUserByEmail($conn, $data->email);
                $token = password_hash($result["email"], PASSWORD_DEFAULT);
                $_SESSION["user_id"] = base64_encode($result["id"]);
                $_SESSION["seesion_id"] = session_id();
                $_SESSION["token"] = $token;
                if (is_array($result)) {
                    include_once "dashboard.php";
                } else {
                    echo "<p style='color:crimson;'>Invalid email or password</p>";
                }
            }
        } else {
            echo "<p style='color:crimson;'>Database connection failed.</p>";
        }
    }
}