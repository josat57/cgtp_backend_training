<?php
// /controller/auth.controller.php

require_once __DIR__ . '/../data/crud.data.php';
require_once __DIR__ . '/../helpers/jwt.helper.php';
require_once __DIR__ . '/../helpers/utility.helper.php';

function registerUser() {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!isset($input['email'], $input['password'])) {
        sendJsonResponse(['error' => 'Email and password required'], 400);
        return;
    }

    $email = $input['email'];
    $password = password_hash($input['password'], PASSWORD_BCRYPT);

    $existing = Crud::select('users', ['email' => $email]);
    if (!empty($existing)) {
        sendJsonResponse(['error' => 'User already exists'], 409);
        return;
    }

    Crud::insert('users', ['email' => $email, 'password' => $password]);
    sendJsonResponse(['message' => 'User registered successfully'], 201);
}

function loginUser() {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!isset($input['email'], $input['password'])) {
        sendJsonResponse(['error' => 'Email and password required'], 400);
        return;
    }

    $email = $input['email'];
    $user = Crud::select('users', ['email' => $email]);

    if (empty($user) || !password_verify($input['password'], $user[0]['password'])) {
        sendJsonResponse(['error' => 'Invalid credentials'], 401);
        return;
    }

    $token = generateJwt($user[0]['id']);
    sendJsonResponse(['token' => $token]);
}
