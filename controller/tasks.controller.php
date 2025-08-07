<?php
// /controller/tasks.controller.php

require_once __DIR__ . '/../helpers/utility.helper.php';
require_once __DIR__ . '/../data/crud.data.php';

function createTask() {
    $userId = authenticate();
    $input = json_decode(file_get_contents("php://input"), true);

    $requiredFields = ['title', 'description', 'status'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            sendJsonResponse(['error' => "$field is required"], 400);
            return;
        }
    }

    if (!in_array($input['status'], ['pending', 'completed'])) {
        sendJsonResponse(['error' => 'Invalid status'], 400);
        return;
    }

    if (isset($input['expiry_date']) && strtotime($input['expiry_date']) < time()) {
        sendJsonResponse(['error' => 'Expiry date cannot be in the past'], 400);
        return;
    }

    $task = [
        'user_id' => $userId,
        'title' => $input['title'],
        'description' => $input['description'],
        'status' => $input['status'],
        'expiry_date' => $input['expiry_date'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];

    Crud::insert('tasks', $task);
    sendJsonResponse(['message' => 'Task created successfully'], 201);
}

function getTasks() {
    $userId = authenticate();
    $status = $_GET['status'] ?? null;
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = ($page - 1) * $limit;

    $conditions = ['user_id' => $userId];
    if ($status) {
        $conditions['status'] = $status;
    }

    $tasks = Crud::select('tasks', $conditions, ['limit' => $limit, 'offset' => $offset]);
    $total = Crud::count('tasks', $conditions);

    sendJsonResponse([
        'data' => $tasks,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]
    ]);
}

function getTaskById($id) {
    $userId = authenticate();
    $task = Crud::select('tasks', ['id' => $id, 'user_id' => $userId]);
    if (empty($task)) {
        sendJsonResponse(['error' => 'Task not found'], 404);
        return;
    }
    sendJsonResponse($task[0]);
}

function updateTask($id) {
    $userId = authenticate();
    $input = json_decode(file_get_contents("php://input"), true);

    $existing = Crud::select('tasks', ['id' => $id, 'user_id' => $userId]);
    if (empty($existing)) {
        sendJsonResponse(['error' => 'Task not found'], 404);
        return;
    }

    if (isset($input['status']) && !in_array($input['status'], ['pending', 'completed'])) {
        sendJsonResponse(['error' => 'Invalid status'], 400);
        return;
    }

    if (isset($input['expiry_date']) && strtotime($input['expiry_date']) < time()) {
        sendJsonResponse(['error' => 'Expiry date cannot be in the past'], 400);
        return;
    }

    Crud::update('tasks', $input, ['id' => $id, 'user_id' => $userId]);
    sendJsonResponse(['message' => 'Task updated successfully']);
}

function deleteTask($id) {
    $userId = authenticate();
    $task = Crud::select('tasks', ['id' => $id, 'user_id' => $userId]);
    if (empty($task)) {
        sendJsonResponse(['error' => 'Task not found'], 404);
        return;
    }

    Crud::delete('tasks', ['id' => $id, 'user_id' => $userId]);
    sendJsonResponse(['message' => 'Task deleted']);
}
