<?php
require_once "../models/model.php";
require_once "../helpers/Utility.helper.php";

/**
 * Authentication Controller class
 */
class AuthController {
    private static $data;
    private $model;
    private $utility;

    /**
     * Class Constructor
     * 
     * @return void
     */
    public function __construct($data = []) {
        self::$data = $data;
        $this->model = new Models();
        $this->utility = new UtilityHelper();
    }

    public function register():string|array
    {
        $require = ["first_name", "last_name", "email", "phone", "password"];
        $validate = $this->utility->validateFields(self::$data, $require);
        if ($validate["error"]) {
            return ['statuscode' => 401, 'status' => $validate['error_msg'], 'data' => []];
        } else {
            $data = $validate["data"];
            if ($this->model->createtUser($data)) {
                $respons = $this->utility->jsonResponse(
                    200,
                    "User registered successfully.",
                    $data
                );
            } else {
                $respons = $this->utility->jsonResponse(
                    404,
                    "Failed to register user.",
                    []
                );
            }
        }
        return $respons;
    }

    public function login():string|array
    {
        $require = ["email", "password"];
        $validate = $this->utility->validateFields(self::$data, $require);
        if ($validate["error"]) {
            $response = ['statuscode' => 401, 'status' => $validate['error_msg'], 'data' => []];
        } else {
            $data = $validate["data"];
            $result = $this->model->loginUser($data);
            if ($result["statuscode"] === 200) {
                if (!$this->startSession($result)) {
                    $response = $this->utility->jsonResponse(
                        500,
                        "Failed to login session.",
                        []
                    );
                } else {
                    $session_data = [
                        'id' => $_SESSION['id'],
                        'first_name' => $_SESSION['first_name'],
                        'last_name' => $_SESSION['last_name'],
                        'email' => $_SESSION['email'],
                        'phone' => $_SESSION['phone'],
                        'session_id' => $_SESSION['session_id'],
                        'session_start_time' => time(),
                    ];
                    $response = $this->utility->jsonResponse(
                        200,
                        "Login successful.",
                        $session_data
                    );
                }
            } else {
                $response = $this->utility->jsonResponse(
                    404,
                    "Invalid email or password.",
                    []
                );
            }
        }
        return $response;
    }

    /**
     * Start session
     * @return string|array
     */
    public function startSession($session_data): string|array
    {
        $result = $this->model->startSession("sessions", $session_data);
        if (!$result) {
            return false;
        } else {
            session_start();
            $_SESSION = [
                'id' => $session_data['data']['id'],
                'first_name' => $session_data['data']['first_name'],
                'last_name' => $session_data['data']['last_name'],
                'email' => $session_data['data']['email'],
                'phone' => $session_data['data']['phone'],
                'session_id' => session_id(),
            ];
            return true;
        }
    }
}