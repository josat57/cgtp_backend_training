<?php
require_once __DIR__ . '/../controllers/UserController.php';

function usersRoutes($route, $data, $method) {
	$conn = require __DIR__ . '/../config/db.php';
	$ctrl = new UserController($conn);

	if ($method === 'GET' && $route === 'me') { $ctrl->me(); }
	if ($method === 'PUT' && $route === 'me') { $ctrl->update(); }
	if ($method === 'POST' && $route === 'me/avatar') { $ctrl->uploadAvatar(); }

	return json_encode(['success' => false, 'error' => 'Invalid users route']);
} 