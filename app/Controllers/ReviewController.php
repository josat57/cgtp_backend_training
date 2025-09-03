<?php

namespace App\Controllers;

use App\Models\Review;
use App\Models\Book;
use App\Models\User;
use App\Utils\Response as ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MongoDB\BSON\ObjectId;

class ReviewController
{
    private $reviewModel;
    private $bookModel;
    private $userModel;

    public function __construct()
    {
        $this->reviewModel = new Review();
        $this->bookModel = new Book();
        $this->userModel = new User();
    }

    // Get all reviews (paginated)
    public function index(Request $request, Response $response, array $args)
    {
        try {
            $query = $request->getQueryParams();
            $page = max(1, (int)($query['page'] ?? 1));
            $limit = max(1, min(50, (int)($query['limit'] ?? 10)));
            
            $filters = [];
            
            // Apply filters if provided
            if (isset($query['book_id'])) {
                $filters['book_id'] = new ObjectId($query['book_id']);
            }
            
            if (isset($query['user_id'])) {
                $filters['user_id'] = new ObjectId($query['user_id']);
            }
            
            if (isset($query['status'])) {
                $filters['status'] = $query['status'];
            }

            $result = $this->reviewModel->paginate($filters, $page, $limit);
            
            // Convert MongoDB documents to array
            $reviews = [];
            foreach ($result['data'] as $doc) {
                $reviews[] = $this->formatReview($doc);
            }
            
            return ApiResponse::success($response, [
                'reviews' => $reviews,
                'meta' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total_pages' => ceil($result['total'] / $result['limit'])
                ]
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::error($response, $e->getMessage(), 500);
        }
    }

    // Get a single review
    public function show(Request $request, Response $response, array $args)
    {
        try {
            $review = $this->reviewModel->find($args['id']);
            
            if (!$review) {
                return ApiResponse::error($response, 'Review not found', 404);
            }
            
            return ApiResponse::success($response, [
                'review' => $this->formatReview($review)
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::error($response, $e->getMessage(), 500);
        }
    }

    // Create a new review
    public function store(Request $request, Response $response, array $args)
    {
        try {
            $data = $request->getParsedBody();
            
            // Validate required fields
            $required = ['book_id', 'user_id', 'rating', 'title'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ApiResponse::error($response, "Field '$field' is required", 400);
                }
            }
            
            // Validate rating
            $data['rating'] = (int)$data['rating'];
            if ($data['rating'] < 1 || $data['rating'] > 5) {
                return ApiResponse::error($response, 'Rating must be between 1 and 5', 400);
            }
            
            // Create review
            $review = $this->reviewModel->create($data);
            
            return ApiResponse::success($response, [
                'review' => $this->formatReview($review)
            ], 201);
            
        } catch (\Exception $e) {
            return ApiResponse::error($response, $e->getMessage(), 500);
        }
    }

    // Update a review
    public function update(Request $request, Response $response, array $args)
    {
        try {
            $data = $request->getParsedBody();
            $reviewId = $args['id'];
            
            // Check if review exists
            $review = $this->reviewModel->find($reviewId);
            if (!$review) {
                return ApiResponse::error($response, 'Review not found', 404);
            }
            
            // Validate rating if provided
            if (isset($data['rating'])) {
                $data['rating'] = (int)$data['rating'];
                if ($data['rating'] < 1 || $data['rating'] > 5) {
                    return ApiResponse::error($response, 'Rating must be between 1 and 5', 400);
                }
            }
            
            // Update review
            $updatedReview = $this->reviewModel->update($reviewId, $data);
            
            return ApiResponse::success($response, [
                'review' => $this->formatReview($updatedReview)
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::error($response, $e->getMessage(), 500);
        }
    }

    // Delete a review
    public function delete(Request $request, Response $response, array $args)
    {
        try {
            $reviewId = $args['id'];
            
            // Check if review exists
            $review = $this->reviewModel->find($reviewId);
            if (!$review) {
                return ApiResponse::error($response, 'Review not found', 404);
            }
            
            // Delete review
            $this->reviewModel->delete($reviewId);
            
            return ApiResponse::success($response, null, 204);
            
        } catch (\Exception $e) {
            return ApiResponse::error($response, $e->getMessage(), 500);
        }
    }

    // Get reviews for a specific book
    public function getBookReviews(Request $request, Response $response, array $args)
    {
        try {
            $query = $request->getQueryParams();
            $page = max(1, (int)($query['page'] ?? 1));
            $limit = max(1, min(50, (int)($query['limit'] ?? 10)));
            
            $bookId = $args['bookId'];
            $result = $this->reviewModel->getByBook($bookId, $page, $limit);
            
            // Convert MongoDB documents to array
            $reviews = [];
            foreach ($result['data'] as $doc) {
                $reviews[] = $this->formatReview($doc);
            }
            
            return ApiResponse::success($response, [
                'reviews' => $reviews,
                'meta' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total_pages' => ceil($result['total'] / $result['limit'])
                ]
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::error($response, $e->getMessage(), 500);
        }
    }
    
    // Format review document for response
    private function formatReview($review)
    {
        if (is_array($review)) {
            $review = (object)$review;
        }
        
        return [
            'id' => (string)$review->_id,
            'book_id' => isset($review->book_id) ? (string)$review->book_id : null,
            'user_id' => isset($review->user_id) ? (string)$review->user_id : null,
            'rating' => $review->rating ?? null,
            'title' => $review->title ?? null,
            'comment' => $review->comment ?? null,
            'status' => $review->status ?? 'pending',
            'created_at' => isset($review->created_at) ? $review->created_at->toDateTime()->format('c') : null,
            'updated_at' => isset($review->updated_at) ? $review->updated_at->toDateTime()->format('c') : null
        ];
    }
}
