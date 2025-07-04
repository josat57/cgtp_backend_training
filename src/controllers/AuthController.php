<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;

class AuthController {
    public static function register() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['username'], $input['email'], $input['password'])) {
            Response::json(['error' => 'Missing fields'], 400);
        }
        $db = getMongoDB();
        $users = $db->users;
        // Check for existing user
        $exists = $users->findOne([
            '$or' => [
                ['email' => $input['email']],
                ['username' => $input['username']]
            ]
        ]);
        if ($exists) {
            Response::json(['error' => 'Username or email already exists'], 409);
        }
        $hashed = password_hash($input['password'], PASSWORD_BCRYPT);
        $user = [
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => $hashed,
            'role' => 'user'
        ];
        $result = $users->insertOne($user);
        $user['_id'] = $result->getInsertedId();
        unset($user['password']);
        $token = self::generateJWT($user);
        Response::json(['token' => $token, 'user' => $user], 201);
    }

    public static function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['email'], $input['password'])) {
            Response::json(['error' => 'Missing fields'], 400);
        }
        $db = getMongoDB();
        $users = $db->users;
        $user = $users->findOne(['email' => $input['email']]);
        if (!$user || !password_verify($input['password'], $user['password'])) {
            Response::json(['error' => 'Invalid credentials'], 401);
        }
        $userArr = [
            '_id' => (string)$user['_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        $token = self::generateJWT($userArr);
        Response::json(['token' => $token, 'user' => $userArr]);
    }

    private static function generateJWT($user) {
        $config = require __DIR__ . '/../../config/jwt.php';
        $payload = [
            'sub' => $user['_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + $config['expire']
        ];
        return JWT::encode($payload, $config['secret'], 'HS256');
    }
} 