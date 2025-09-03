<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/AuthController.php';

function authRoutes($route, $data, $method) {
	$conn = require __DIR__ . '/../config/db.php';
	$ctrl = new AuthController($conn);

	if ($method === 'POST' && $route === 'register') { $ctrl->register(); }
	if ($method === 'POST' && $route === 'verify-otp') { $ctrl->verifyOtp(); }
	if ($method === 'POST' && $route === 'login') { $ctrl->login(); }
	if ($method === 'POST' && $route === 'logout') { $ctrl->logout(); }

	return json_encode(['success' => false, 'error' => 'Invalid auth route']);
} 