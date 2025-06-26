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
}