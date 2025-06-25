<?php
require_once "../models/model.php";

/**
 * Authentication Controller class
 */
class AuthController {
    private static $data;
    private $model;

    /**
     * Class Constructor
     * 
     * @return void
     */
    public function __construct($data = []) {
        self::$data = $data;
        $this->model = new Models();
    }

    public function register():array
    {
        $require = ["first_name", "last_name", "email", "phone", "password"];
        $validate = $this->validate(self::$data, $require);
        if ($validate) {
            return $validate;
        } else {
            $this->model->insertUser(self::$data);
        }
    }

    private function validate($data, $require)
    {
        $error = [];
        foreach ($data as $key => $values) {
            if (in_array($key, $require) && empty($values)) {
                $error[$key] = $key . " is required <br />";
            }
        }

        if (empty($error)) {
            $data = htmlspecialchars($data);
            return ["error" => false, "error_msg" => "Clean", "data"=>$data];
        } else {
            return ["error" => true, "error_msg" => $error, "data"=>""];
        }
    }
}