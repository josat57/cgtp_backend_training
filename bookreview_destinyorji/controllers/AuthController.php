<?php
require_once __DIR__ . "/../models/Models.php"; // Correct case-sensitive filename
require_once __DIR__ . "/../helpers/Utility.helper.php";
require_once __DIR__ . "/../config/jwt.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    private static $data;
    private $model;
    private $utility;

    public function __construct($data = []) {
        self::$data = $data;
        $this->model = new Models();
        $this->utility = new UtilityHelper();
    }

    /**
     * Register a new user
     */
    public function register(): string|array
    {
        // Debug information
        error_log('Register method called');
        error_log('Data received: ' . json_encode(self::$data));
        
        $require = ["first_name", "last_name", "email", "phone", "password"];
        $validate = $this->utility->validateFields(self::$data, $require);

        if ($validate["error"]) {
            error_log('Validation error: ' . $validate['error_msg']);
            return ['statuscode' => 401, 'status' => $validate['error_msg'], 'data' => []];
        }

        $data = $validate["data"];

        // Check if email exists
        $existingUser = $this->model->findUserByEmail($data['email']);
        if ($existingUser) {
            return $this->utility->jsonResponse(409, "Email already exists", []);
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['role'] = 'user';

        if ($this->model->createUser($data)) {
            return $this->utility->jsonResponse(201, "User registered successfully", []);
        }

        return $this->utility->jsonResponse(500, "User registration failed", []);
    }

    /**
     * Log in and return JWT token
     */
    public function login(): string|array
    {
        $require = ["email", "password"];
        $validate = $this->utility->validateFields(self::$data, $require);

        if ($validate["error"]) {
            return ['statuscode' => 401, 'status' => $validate['error_msg'], 'data' => []];
        }

        $data = $validate["data"];
        $user = $this->model->findUserByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            return $this->utility->jsonResponse(401, "Invalid email or password", []);
        }

        $jwtConfig = require __DIR__ . "/../config/jwt.php";

        $payload = [
            'id' => (string)$user['_id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'exp' => time() + $jwtConfig['expiration'],
        ];

        $token = JWT::encode($payload, $jwtConfig['secret'], 'HS256');

        return $this->utility->jsonResponse(200, "Login successful", ['token' => $token]);
    }
}
