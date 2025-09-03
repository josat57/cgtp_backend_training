<?php

namespace App\Controllers;

use App\Models\User;
use App\Utils\Response;
use App\Utils\Validator;

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function register($request, $response)
    {
        $data = $request->getParsedBody();
        
        // Create validator instance and validate input
        $validator = new Validator($data);
        $isValid = $validator->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'in:author,reviewer,admin',
            'phone' => 'string|max:20',
        ]);

        if (!$isValid) {
            return Response::error('Validation failed', 422, $validator->getErrors());
        }

        // Create user
        $user = $this->userModel->create([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'role' => $data['role'] ?? 'user',
            'phone' => $data['phone'] ?? null,
        ]);

        if (!$user) {
            return Response::error('Failed to create user', 500);
        }

        // Generate token
        $token = $this->userModel->generateAuthToken($user);

        return Response::success([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login($request)
    {
        $data = $request->getParsedBody();
        
        // Validate input
        $validation = Validator::validate($data, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validation->fails()) {
            return Response::error('Validation failed', 422, $validation->errors());
        }

        // Authenticate user
        $user = $this->userModel->authenticate($data['email'], $data['password']);

        if (!$user) {
            return Response::error('Invalid credentials', 401);
        }

        // Generate token
        $token = $this->userModel->generateAuthToken($user);

        return Response::success([
            'user' => $user,
            'token' => $token
        ], 'Login successful');
    }

    public function me($request)
    {
        $user = $request->getAttribute('user');
        return Response::success($user, 'User retrieved successfully');
    }

    public function updateProfile($request)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();
        
        // Validate input
        $validation = Validator::validate($data, [
            'name' => 'string|min:2|max:100',
            'phone' => 'string|max:20',
            'avatar' => 'string',
        ]);

        if ($validation->fails()) {
            return Response::error('Validation failed', 422, $validation->errors());
        }

        $updated = $this->userModel->updateProfile($user['id'], $data);

        if (!$updated) {
            return Response::error('Failed to update profile', 500);
        }

        // Get updated user
        $updatedUser = $this->userModel->findById($user['id']);
        
        return Response::success($updatedUser, 'Profile updated successfully');
    }

    public function changePassword($request)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();
        
        // Validate input
        $validation = Validator::validate($data, [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validation->fails()) {
            return Response::error('Validation failed', 422, $validation->errors());
        }

        // Verify current password
        $userData = $this->userModel->findById($user['id']);
        if (!password_verify($data['current_password'], $userData['password'])) {
            return Response::error('Current password is incorrect', 400);
        }

        // Update password
        $updated = $this->userModel->updatePassword($user['id'], $data['new_password']);

        if (!$updated) {
            return Response::error('Failed to update password', 500);
        }

        return Response::success(null, 'Password updated successfully');
    }

    public function logout($request)
    {
        // In a stateless JWT system, logout is handled client-side by removing the token
        return Response::success(null, 'Successfully logged out');
    }
}