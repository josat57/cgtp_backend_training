<?php
require_once __DIR__ . '/../controllers/BookController.php';

function booksRoutes($route, $data, $method) {
	$conn = require __DIR__ . '/../config/db.php';
	$ctrl = new BookController($conn);

	// Match patterns like: '', 'mine', '{id}/reviews', '{id}/invite', '{id}/invitations'
	$segments = explode('/', $route);

	// POST /api/books (upload)
	if ($method === 'POST' && ($route === '' || $route === null)) { $ctrl->uploadBook(); }

	// GET /api/books (list)
	if ($method === 'GET' && ($route === '' || $route === null)) { $ctrl->listBooks(); }

	// GET /api/books/mine (author)
	if ($method === 'GET' && $route === 'mine') { $ctrl->listMyBooks(); }

	// POST /api/books/{id}/reviews
	if ($method === 'POST' && count($segments) === 2 && is_numeric($segments[0]) && $segments[1] === 'reviews') {
		$ctrl->addReview((int)$segments[0]);
	}

	// GET /api/books/{id}/reviews
	if ($method === 'GET' && count($segments) === 2 && is_numeric($segments[0]) && $segments[1] === 'reviews') {
		$ctrl->listBookReviews((int)$segments[0]);
	}

	// POST /api/books/{id}/invite
	if ($method === 'POST' && count($segments) === 2 && is_numeric($segments[0]) && $segments[1] === 'invite') {
		$ctrl->inviteReviewer((int)$segments[0]);
	}

	// GET /api/books/{id}/invitations
	if ($method === 'GET' && count($segments) === 2 && is_numeric($segments[0]) && $segments[1] === 'invitations') {
		$ctrl->listInvitations((int)$segments[0]);
	}

	// GET /api/reviews/mine
	if ($method === 'GET' && $route === 'reviews/mine') { $ctrl->listMyReviews(); }

	return json_encode(['success' => false, 'error' => 'Invalid books route']);
} 