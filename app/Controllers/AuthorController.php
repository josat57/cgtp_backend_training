<?php

namespace App\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Utils\Response;
use App\Utils\Validator;
use Psr\Http\Message\ServerRequestInterface as Request;
use MongoDB\BSON\ObjectId;

class AuthorController
{
    private $bookModel;
    private $userModel;

    public function __construct()
    {
        $this->bookModel = new Book();
        $this->userModel = new User();
    }

    public function getProfile(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');

        if (($currentUser['role'] ?? '') !== 'author') {
            return Response::error('Only authors can access this endpoint', 403);
        }

        $author = $this->userModel->findById($currentUser['id']);
        if (!$author) {
            return Response::error('Author not found', 404);
        }

        $stats = $this->getAuthorStats($currentUser['id']);
        $recentBooks = $this->bookModel->getAuthorBooks($currentUser['id'], 1, 5);

        $profile = array_merge($author, [
            'stats' => $stats,
            'recent_books' => $recentBooks
        ]);

        return Response::success($profile);
    }

    public function getPublicProfile(Request $request, $response, $args)
    {
        $authorId = $args['id'] ?? null;
        if (!$authorId) {
            return Response::error('Author ID is required', 400);
        }

        $author = $this->userModel->findById($authorId);
        if (!$author || ($author['role'] ?? '') !== 'author') {
            return Response::error('Author not found', 404);
        }

        $publicProfile = [
            'id' => (string)($author['_id'] ?? ''),
            'name' => $author['name'] ?? '',
            'avatar' => $author['avatar'] ?? null,
            'bio' => $author['bio'] ?? null,
            'website' => $author['website'] ?? null,
            'social_links' => $author['social_links'] ?? null,
            'created_at' => $author['created_at'] ?? null,
        ];

        $stats = $this->getAuthorStats($authorId, true);
        $books = $this->bookModel->getAuthorBooks($authorId, 1, 10, 'published');

        $profile = array_merge($publicProfile, [
            'stats' => $stats,
            'books' => $books
        ]);

        return Response::success($profile);
    }

    public function updateProfile(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');
        $data = $request->getParsedBody();

        if (($currentUser['role'] ?? '') !== 'author') {
            return Response::error('Only authors can update their profile', 403);
        }

        $validation = Validator::validate($data, [
            'name' => 'string|min:2|max:100',
            'bio' => 'string|max:1000|nullable',
            'website' => 'url|nullable',
            'avatar' => 'url|nullable',
            'social_links' => 'array|nullable',
            'social_links.*' => 'url',
            'location' => 'string|max:100|nullable',
            'genres' => 'array|nullable',
            'genres.*' => 'string|max:50',
            'accepting_requests' => 'boolean'
        ]);

        if ($validation->fails()) {
            return Response::error('Validation failed', 422, $validation->errors());
        }

        $allowedFields = [
            'name', 'bio', 'website', 'avatar', 'social_links',
            'location', 'genres', 'accepting_requests'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return Response::error('No valid fields to update', 400);
        }

        $result = $this->userModel->update($currentUser['id'], $updateData);
        if (!$result) {
            return Response::error('Failed to update profile', 500);
        }

        $updatedProfile = $this->userModel->findById($currentUser['id']);

        \App\Controllers\AuditController::logAction(
            $currentUser['id'],
            'author_profile_updated',
            'author',
            $currentUser['id'],
            ['updated_fields' => array_keys($updateData)]
        );

        return Response::success($updatedProfile, 'Profile updated successfully');
    }

    public function getBooks(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');
        $queryParams = $request->getQueryParams();
        $page = max(1, (int)($queryParams['page'] ?? 1));
        $limit = max(1, (int)($queryParams['limit'] ?? 10));
        $status = $queryParams['status'] ?? null;
        $authorId = $args['id'] ?? $currentUser['id'];

        if ($authorId !== $currentUser['id'] && ($currentUser['role'] ?? '') !== 'admin') {
            return Response::error('You are not authorized to view these books', 403);
        }

        $filters = $status ? ['status' => $status] : [];
        $books = $this->bookModel->getAuthorBooks($authorId, $page, $limit, $filters);
        $total = $this->bookModel->countAuthorBooks($authorId, $filters);

        return Response::success([
            'books' => $books,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function getBookReviews(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');
        $bookId = $args['book_id'] ?? null;

        if (!$bookId) {
            return Response::error('Book ID is required', 400);
        }

        $queryParams = $request->getQueryParams();
        $page = max(1, (int)($queryParams['page'] ?? 1));
        $limit = max(1, (int)($queryParams['limit'] ?? 10));

        $book = $this->bookModel->findById($bookId);
        if (!$book) {
            return Response::error('Book not found', 404);
        }

        if (($book['author_id'] ?? '') !== $currentUser['id'] && ($currentUser['role'] ?? '') !== 'admin') {
            return Response::error('You are not authorized to view these reviews', 403);
        }

        $reviews = $this->bookModel->getBookReviews($bookId, $page, $limit);
        $total = $this->bookModel->countBookReviews($bookId);

        return Response::success([
            'reviews' => $reviews,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function getPendingInvitations(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');
        if (($currentUser['role'] ?? '') !== 'author') {
            return Response::error('Only authors can view review invitations', 403);
        }

        $queryParams = $request->getQueryParams();
        $page = max(1, (int)($queryParams['page'] ?? 1));
        $limit = max(1, (int)($queryParams['limit'] ?? 10));

        $invitations = $this->bookModel->getAuthorPendingInvitations($currentUser['id'], $page, $limit);
        $total = $this->bookModel->countAuthorPendingInvitations($currentUser['id']);

        return Response::success([
            'invitations' => $invitations,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    private function getAuthorStats(string $authorId, bool $public = false): array
    {
        $stats = [
            'total_books' => $this->bookModel->countAuthorBooks($authorId, $public ? ['status' => 'published'] : []),
            'total_reviews' => $this->bookModel->countAuthorBookReviews($authorId, $public),
            'average_rating' => $this->bookModel->getAuthorAverageRating($authorId, $public),
            'books_by_status' => $this->bookModel->countBooksByStatus($authorId, $public)
        ];

        if (!$public) {
            $stats['pending_invitations'] = $this->bookModel->countAuthorPendingInvitations($authorId);
        }

        return $stats;
    }
}
