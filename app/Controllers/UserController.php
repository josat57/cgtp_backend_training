<?php

namespace App\Controllers;

use App\Models\User;
use App\Utils\Response as ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    private $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function index(Request $request, Response $response, array $args)
    {
        $users = $this->user->all();
        
        // Convert MongoDB objects to arrays and handle ObjectId
        $users = array_map(function($user) {
            $userArray = (array) $user;
            $userArray['_id'] = (string) $userArray['_id'];
            unset($userArray['password']); // Don't return passwords
            return $userArray;
        }, $users->toArray());

        return ApiResponse::success($users);
    }

    public function show(Request $request, Response $response, array $args)
    {
        $user = $this->user->findById($args['id']);
        
        if (!$user) {
            return ApiResponse::error('User not found', 404);
        }

        // Convert to array and handle ObjectId
        $userData = (array) $user;
        $userData['_id'] = (string) $userData['_id'];
        unset($userData['password']); // Don't return password

        return ApiResponse::success($userData);
    }

    public function store(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (empty($data['email']) || empty($data['name'])) {
            return ApiResponse::error('Name and email are required', 400);
        }

        // Check if user exists
        if ($this->user->findByEmail($data['email'])) {
            return ApiResponse::error('Email already registered', 400);
        }

        // Create user
        $user = $this->user->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'] ?? 'password', PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 'user',
            'created_at' => new \MongoDB\BSON\UTCDateTime(),
            'updated_at' => new \MongoDB\BSON\UTCDateTime()
        ]);

        // Return created user (without password)
        $userData = (array) $user;
        $userData['_id'] = (string) $userData['_id'];
        unset($userData['password']);

        return ApiResponse::success($userData, 201);
    }

    public function update(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();
        $userId = $args['id'];
        
        // Check if user exists
        $user = $this->user->findById($userId);
        if (!$user) {
            return ApiResponse::error('User not found', 404);
        }

        // Prepare update data
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'updated_at' => new \MongoDB\BSON\UTCDateTime()
        ];

        // Update password if provided
        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Update user
        $this->user->update($userId, $updateData);

        // Get updated user
        $updatedUser = $this->user->findById($userId);
        $userData = (array) $updatedUser;
        $userData['_id'] = (string) $userData['_id'];
        unset($userData['password']);

        return ApiResponse::success($userData);
    }

    public function updateProfile(Request $request, Response $response, array $args)
    {
        // Get the authenticated user's ID from the JWT token
        $userId = $request->getAttribute('user_id');
        
        // Get the user from the database
        $user = $this->user->findById($userId);
        if (!$user) {
            return ApiResponse::error('User not found', 404);
        }

        // Get and validate input data
        $data = $request->getParsedBody();
        
        // List of allowed fields that can be updated
        $allowedFields = ['name', 'email', 'password', 'phone', 'address', 'bio', 'avatar'];
        
        // Prepare update data
        $updateData = [
            'updated_at' => new \MongoDB\BSON\UTCDateTime()
        ];

        // Validate and add each allowed field if it exists in the request
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                // Special handling for password
                if ($field === 'password') {
                    if (strlen($data['password']) < 6) {
                        return ApiResponse::error('Password must be at least 6 characters long', 400);
                    }
                    $updateData[$field] = password_hash($data[$field], PASSWORD_DEFAULT);
                } 
                // Validate email format if email is being updated
                elseif ($field === 'email' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return ApiResponse::error('Invalid email format', 400);
                }
                // For all other fields
                else {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        // If email is being updated, check if it's already taken
        if (isset($updateData['email'])) {
            $existingUser = $this->user->findByEmail($updateData['email']);
            if ($existingUser && (string)$existingUser->_id !== (string)$user->_id) {
                return ApiResponse::error('Email already in use', 400);
            }
        }

        // Update the user
        $this->user->update($userId, $updateData);

        // Get the updated user data
        $updatedUser = $this->user->findById($userId);
        
        // Prepare response data
        $userData = (array) $updatedUser;
        $userData['_id'] = (string) $userData['_id'];
        unset($userData['password']); // Never return password hash

        return ApiResponse::success([
            'message' => 'Profile updated successfully',
            'user' => $userData
        ]);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        $userId = $args['id'];
        
        // Check if user exists
        $user = $this->user->findById($userId);
        if (!$user) {
            return ApiResponse::error('User not found', 404);
        }

        // Delete user
        $this->user->delete($userId);

        return ApiResponse::success(['message' => 'User deleted successfully']);
    }
}