<?php
require_once __DIR__ . '/../controllers/AdminController.php';

function adminRoutes($route, $data, $method) {
    $conn = require __DIR__ . '/../config/db.php';
    $ctrl = new AdminController($conn);

    if ($method === 'GET' && $route === 'activity-logs') { $ctrl->listActivityLogs(); }

    return json_encode(['success' => false, 'error' => 'Invalid admin route']);
}


