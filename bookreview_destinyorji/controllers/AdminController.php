<?php
require_once __DIR__ . '/../models/Models.php';
require_once __DIR__ . '/../helpers/Utility.helper.php';

/**
 * Get all users
 */
function getUsers()
{
    $model = new Models();
    $utility = new UtilityHelper();
    
    $users = $model->getAllUsers();
    $usersArray = [];
    
    foreach ($users as $user) {
        $usersArray[] = [
            '_id' => (string) $user['_id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user'
        ];
    }
    
    echo json_encode($utility->jsonResponse(200, 'Users retrieved successfully', $usersArray));
}

/**
 * Update user role
 */
function updateUserRole($userId)
{
    $model = new Models();
    $utility = new UtilityHelper();
    
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($input['role']) || !in_array($input['role'], ['user', 'admin'])) {
        echo json_encode($utility->jsonResponse(400, 'Invalid role', []));
        return;
    }
    
    $result = $model->updateUserRole($userId, $input['role']);
    
    if ($result->getModifiedCount() > 0) {
        echo json_encode($utility->jsonResponse(200, 'User role updated successfully', []));
    } else {
        echo json_encode($utility->jsonResponse(404, 'User not found or role not changed', []));
    }
}

/**
 * Delete user
 */
function deleteUser($userId)
{
    $model = new Models();
    $utility = new UtilityHelper();
    
    $result = $model->deleteUser($userId);
    
    if ($result->getDeletedCount() > 0) {
        echo json_encode($utility->jsonResponse(200, 'User deleted successfully', []));
    } else {
        echo json_encode($utility->jsonResponse(404, 'User not found', []));
    }
}