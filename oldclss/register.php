<?php
// require_once "database/config.php";
require_once "database/database.php";
$method = $_SERVER["REQUEST_METHOD"];
$msg = "<p style='color:crimson'>Please enter ";
// $conn = connectToDatabase();
if ($method == "POST") {
    $data = $_POST;
    if (isset($data["first_name"]) && $data["first_name"] == "") {
        echo $msg . "first_name </p>";
    } else if (isset($data["last_name"]) && $data["last_name"] == "") {
        echo $msg . "last_name </p>";
    } else if (isset($data["email"]) && $data["email"] == "") {
        echo $msg . "email </p>";
    } else if (isset($data["password"]) && $data["password"] == "") {
        echo $msg . "password </p>";
    } else if (isset($data["phone"]) && $data["phone"] == "") {
        echo $msg . "phone number </p>";
    } else {
        $data = (object) $data;
        if ($conn) {
            $table = createTable($conn);
            // if ($table) {
            //     $result = insertUser($conn, $data);
            //     if ($result['statuscode'] == 0) {
            //         $user = getUserByEmail($conn, $data->email);
            //         echo $result['status'];
            //         // include_once "login.php";
            //         header("Location: login.php");
            //     } else {
            //         echo $result['status'];
            //     }
            //     echo "User with this email already exists.";
            // } else {
                $result = insertUser($conn, (object)$data);
                if ($result['statuscode'] == 0) {
                    echo $result['status'];
                    header("Location: login.php");
                } else {
                    echo $result['status'];
                }
            }
            closeDatabaseConnection($conn);
        // } else {
        //     echo "Database connection failed.";
        // }
    }
}

// $users = getUsers($conn);
// $data = $users['data'];
// echo $users['status'];
// echo "<h1>Users List</h1>";
// $data = ['email' => "estherio@you.com"];
// echo getUserByEmail($conn, $data);
?>
<!-- <table>
        <thead>
            <tr>
                <th>S/N</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
            <?php
        // foreach ($data as $index => $user) {
        //         echo "<tr>
        //                 <td>" . ($index + 1) . "</td>
        //                 <td>{$user['first_name']}</td>
        //                 <td>{$user['last_name']}</td>
        //                 <td>{$user['email']}</td>
        //                 <td>{$user['phone']}</td>
        //             </tr>";
        //     }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td>Footer 1</td>
                <td>Footer 2</td>
                <td>Footer 3</td>
            </tr>
        </tfoot>
    </table> -->

