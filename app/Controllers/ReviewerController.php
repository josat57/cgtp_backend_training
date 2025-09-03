<?php

namespace App\Controllers;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Utils\Response;
use App\Utils\Validator;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReviewerController
{
    private $bookModel;
    private $reviewModel;
    private $userModel;

    public function __construct()
    {
        $this->bookModel = new Book();
        $this->reviewModel = new Review();
        $this->userModel = new User();
    }

    public function getProfile(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');
        
        // Only reviewers can view their profile through this endpoint
        if ($currentUser['role'] !== 'reviewer') {
            return Response::error('Only reviewers can access this endpoint', 403);
        }
        
        // Get the full reviewer profile with additional stats
        $reviewer = $this->userModel->findById($currentUser['id']);
        
        if (!$reviewer) {
            return Response::error('Reviewer not found', 404);
        }
        
        // Get reviewer stats
        $stats = $this->getReviewerStats($currentUser['id']);
        
        // Get recent reviews
        $recentReviews = $this->reviewModel->getReviews(1, 5, [
            'reviewer_id' => $currentUser['id']
        ]);
        
        $profile = array_merge($reviewer, [
            'stats' => $stats,
            'recent_reviews' => $recentReviews
        ]);
        
        return Response::success($profile);
    }
    
    public function getPublicProfile(Request $request, $response, $args)
    {
        $reviewerId = $args['id'];
        
        // Get reviewer profile
        $reviewer = $this->userModel->findById($reviewerId);
        
        if (!$reviewer || $reviewer['role'] !== 'reviewer') {
            return Response::error('Reviewer not found', 404);
        }
        
        // Only include public information
        $publicProfile = [
            'id' => $reviewer['id'],
            'name' => $reviewer['name'],
            'avatar' => $reviewer['avatar'] ?? null,
            'bio' => $reviewer['bio'] ?? null,
            'website' => $reviewer['website'] ?? null,
            'social_links' => $reviewer['social_links'] ?? null,
            'genres' => $reviewer['genres'] ?? [],
            'accepting_requests' => $reviewer['accepting_requests'] ?? true,
            'created_at' => $reviewer['created_at']
        ];
        
        // Get public stats
        $stats = $this->getReviewerStats($reviewerId, true);
        
        // Get recent published reviews
        $recentReviews = $this->reviewModel->getReviews(1, 5, [
            'reviewer_id' => $reviewerId,
            'status' => 'published'
        ]);
        
        $profile = array_merge($publicProfile, [
            'stats' => $stats,
            'recent_reviews' => $recentReviews
        ]);
        
        return Response::success($profile);
    }
    
    public function updateProfile(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');
        $data = $request->getParsedBody();
        
        // Only reviewers can update their profile through this endpoint
        if ($currentUser['role'] !== 'reviewer') {
            return Response::error('Only reviewers can update their profile', 403);
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
            'accepting_requests' => 'boolean',
            'review_preferences' => 'array|nullable',
            'review_preferences.genres' => 'array',
            'review_preferences.formats' => 'array',
            'review_preferences.response_time' => 'integer|min:1|max:30'
        ]);
        
        if ($validation->fails()) {
            return Response::error('Validation failed', 422, $validation->errors());
        }
        
        $updateData = [];
        $allowedFields = [
            'name', 'bio', 'website', 'avatar', 'social_links', 
            'location', 'genres', 'accepting_requests', 'review_preferences'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return Response::error('No valid fields to update', 400);
        }
        
        $result = $this->userModel->updateUser($currentUser['id'], $updateData);
        
        if (!$result) {
            return Response::error('Failed to update profile', 500);
        }
        
        // Get the updated profile
        $updatedProfile = $this->userModel->findById($currentUser['id']);
        
        // Log the action
        \App\Controllers\AuditController::logAction(
            $currentUser['id'],
            'reviewer_profile_updated',
            'reviewer',
            $currentUser['id'],
            ['updated_fields' => array_keys($updateData)]
        );
        
        return Response::success($updatedProfile, 'Profile updated successfully');
    }
    
    public function getPendingInvitations(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 10;
        
        // Only reviewers can view their pending invitations
        if ($currentUser['role'] !== 'reviewer') {
            return Response::error('Only reviewers can view review invitations', 403);
        }
        
        $invitations = $this->bookModel->getPendingInvitations($currentUser['id'], $page, $limit);
        $total = $this->bookModel->countPendingInvitations($currentUser['id']);
        
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
    
    public function respondToInvitation(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');
        $invitationId = $args['id'];
        $data = $request->getParsedBody();
        
        // Only reviewers can respond to invitations
        if ($currentUser['role'] !== 'reviewer') {
            return Response::error('Only reviewers can respond to review invitations', 403);
        }
        
        $validation = Validator::validate($data, [
            'status' => 'required|in:accepted,rejected',
            'message' => 'string|max:500|nullable'
        ]);
        
        if ($validation->fails()) {
            return Response::error('Validation failed', 422, $validation->errors());
        }
        
        $status = $data['status'];
        $message = $data['message'] ?? null;
        
        // Update the invitation status
        $result = $this->bookModel->respondToInvitation(
            $invitationId, 
            $currentUser['id'], 
            $status,
            $message
        );
        
        if (!$result) {
            return Response::error('Failed to update invitation status. The invitation may not exist or has already been processed.', 400);
        }
        
        // If accepted, create a draft review
        if ($status === 'accepted') {
            // Get the invitation details
            $invitation = $this->bookModel->getInvitation($invitationId);
            
            if ($invitation) {
                // Check if a review already exists for this book and reviewer
                $existingReview = $this->reviewModel->findByBookAndReviewer(
                    $invitation['book_id'],
                    $currentUser['id']
                );
                
                if (!$existingReview) {
                    // Create a draft review
                    $reviewData = [
                        'book_id' => $invitation['book_id'],
                        'reviewer_id' => $currentUser['id'],
                        'status' => 'draft',
                        'created_at' => new \MongoDB\BSON\UTCDateTime(),
                        'updated_at' => new \MongoDB\BSON\UTCDateTime()
                    ];
                    
                    $this->reviewModel->create($reviewData);
                }
            }
        }
        
        // Log the action
        \App\Controllers\AuditController::logAction(
            $currentUser['id'],
            'review_invitation_' . $status,
            'review_invitation',
            $invitationId,
            ['status' => $status]
        );
        
        return Response::success(null, "Invitation {$status} successfully");
    }
    
    public function getMyReviews(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 10;
        $status = $queryParams['status'] ?? null;
        
        // Only reviewers can view their reviews
        if ($currentUser['role'] !== 'reviewer') {
            return Response::error('Only reviewers can view their reviews', 403);
        }
        
        $filters = [
            'reviewer_id' => $currentUser['id']
        ];
        
        if ($status) {
            $filters['status'] = $status;
        }
        
        $reviews = $this->reviewModel->getReviews($page, $limit, $filters);
        $total = $this->reviewModel->countReviews($filters);
        
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
    
    public function getReviewStats(Request $request, $response, $args)
    {
        $currentUser = $request->getAttribute('user');
        
        // Only reviewers can view their review stats
        if ($currentUser['role'] !== 'reviewer') {
            return Response::error('Only reviewers can view review stats', 403);
        }
        
        $stats = $this->getReviewerStats($currentUser['id']);
        
        return Response::success($stats);
    }
    
    private function getReviewerStats(string $reviewerId, bool $public = false): array
    {
        $stats = [
            'total_reviews' => $this->reviewModel->countReviews([
                'reviewer_id' => $reviewerId,
                'status' => $public ? 'published' : ['$in' => ['draft', 'published']]
            ]),
            'published_reviews' => $this->reviewModel->countReviews([
                'reviewer_id' => $reviewerId,
                'status' => 'published'
            ]),
            'average_rating' => $this->getReviewerAverageRating($reviewerId, $public)
        ];
        
        if (!$public) {
            $stats['pending_invitations'] = $this->bookModel->countPendingInvitations($reviewerId);
            $stats['draft_reviews'] = $this->reviewModel->countReviews([
                'reviewer_id' => $reviewerId,
                'status' => 'draft'
            ]);
        }
        
        return $stats;
    }
    
    private function getReviewerAverageRating(string $reviewerId, bool $public = false): ?float
    {
        $pipeline = [
            ['$match' => [
                'reviewer_id' => $reviewerId,
                'status' => $public ? 'published' : ['$in' => ['published']],
                'rating' => ['$exists' => true, '$ne' => null]
            ]],
            ['$group' => [
                '_id' => null,
                'average' => ['$avg' => '$rating']
            ]]
        ];
        
        $result = $this->reviewModel->aggregate($pipeline)->toArray();
        
        if (empty($result)) {
            return null;
        }
        
        return round($result[0]['average'] ?? 0, 1);
    }
}