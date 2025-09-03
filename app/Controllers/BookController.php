<?php

namespace App\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Utils\Response as ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MongoDB\BSON\ObjectId;

class BookController
{
    private $bookModel;

    public function __construct()
    {
        $this->bookModel = new Book();
    }

    // Get all books (paginated)
    public function index(Request $request, Response $response, array $args)
    {
        $query = $request->getQueryParams();
        $page = max(1, (int)($query['page'] ?? 1));
        $limit = max(1, min(50, (int)($query['limit'] ?? 10)));
        
        $filter = [];
        
        // Apply filters if provided
        if (isset($query['author_id'])) {
            $filter['author_id'] = new ObjectId($query['author_id']);
        }
        
        if (isset($query['status'])) {
            $filter['status'] = $query['status'];
        }
        
        $options = [
            'sort' => ['created_at' => -1],
            'skip' => ($page - 1) * $limit,
            'limit' => $limit
        ];
        
        $total = $this->bookModel->collection->countDocuments($filter);
        $books = $this->bookModel->collection->find($filter, $options)->toArray();
        
        // Convert ObjectId to string for JSON serialization
        $books = array_map(function($book) {
            $book['_id'] = (string)$book['_id'];
            $book['author_id'] = (string)$book['author_id'];
            return $book;
        }, $books);
        
        return ApiResponse::success([
            'data' => $books,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    // Get a single book by ID
    public function show(Request $request, Response $response, array $args)
    {
        try {
            $bookId = $args['id'] ?? null;
            if (!$bookId) {
                return ApiResponse::error('Book ID is required', 400);
            }
            
            $book = $this->bookModel->findById($bookId);
            
            if (!$book) {
                return ApiResponse::error('Book not found', 404);
            }
            
            // Convert ObjectId to string
            $book['_id'] = (string)$book['_id'];
            $book['author_id'] = (string)$book['author_id'];
            
            return ApiResponse::success($book);
            
        } catch (\Exception $e) {
            return ApiResponse::error('Error retrieving book: ' . $e->getMessage(), 500);
        }
    }

    // Create a new book
    public function store(Request $request, Response $response, array $args)
    {
        try {
            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');
            
            // Set author ID from authenticated user
            $data['author_id'] = $user['id'];
            
            // Create book
            $result = $this->bookModel->create($data);
            
            if (!$result) {
                return ApiResponse::error('Failed to create book', 500);
            }
            
            // Convert ObjectId to string
            $result['_id'] = (string)$result['_id'];
            $result['author_id'] = (string)$result['author_id'];
            
            return ApiResponse::success($result, 'Book created successfully', 201);
            
        } catch (\Exception $e) {
            return ApiResponse::error('Error creating book: ' . $e->getMessage(), 500);
        }
    }

    // Update a book
    public function update(Request $request, Response $response, array $args)
    {
        try {
            $bookId = $args['id'] ?? null;
            if (!$bookId) {
                return ApiResponse::error('Book ID is required', 400);
            }
            
            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');
            
            // Check if book exists and user is the author or admin
            $book = $this->bookModel->findById($bookId);
            if (!$book) {
                return ApiResponse::error('Book not found', 404);
            }
            
            // Only allow author or admin to update
            if ((string)$book['author_id'] !== $user['id'] && $user['role'] !== 'admin') {
                return ApiResponse::error('Unauthorized to update this book', 403);
            }
            
            // Update book
            $result = $this->bookModel->update($bookId, $data);
            
            if (!$result) {
                return ApiResponse::error('Failed to update book', 500);
            }
            
            // Get updated book
            $updatedBook = $this->bookModel->findById($bookId);
            $updatedBook['_id'] = (string)$updatedBook['_id'];
            $updatedBook['author_id'] = (string)$updatedBook['author_id'];
            
            return ApiResponse::success($updatedBook, 'Book updated successfully');
            
        } catch (\Exception $e) {
            return ApiResponse::error('Error updating book: ' . $e->getMessage(), 500);
        }
    }

    // Delete a book
    public function delete(Request $request, Response $response, array $args)
    {
        try {
            $bookId = $args['id'] ?? null;
            if (!$bookId) {
                return ApiResponse::error('Book ID is required', 400);
            }
            
            $user = $request->getAttribute('user');
            $book = $this->bookModel->findById($bookId);
            
            if (!$book) {
                return ApiResponse::error('Book not found', 404);
            }
            
            // Only allow author or admin to delete
            if ((string)$book['author_id'] !== $user['id'] && $user['role'] !== 'admin') {
                return ApiResponse::error('Unauthorized to delete this book', 403);
            }
            
            $result = $this->bookModel->delete($bookId);
            
            if (!$result) {
                return ApiResponse::error('Failed to delete book', 500);
            }
            
            return ApiResponse::success(null, 'Book deleted successfully');
            
        } catch (\Exception $e) {
            return ApiResponse::error('Error deleting book: ' . $e->getMessage(), 500);
        }
    }

    // Search books
    public function search(Request $request, Response $response, array $args)
    {
        try {
            $query = $request->getQueryParams();
            $searchTerm = $query['q'] ?? '';
            $page = max(1, (int)($query['page'] ?? 1));
            $limit = max(1, min(50, (int)($query['limit'] ?? 10)));
            
            if (empty($searchTerm)) {
                return $this->index($request, $response, $args);
            }
            
            $filter = [
                '$or' => [
                    ['title' => new \MongoDB\BSON\Regex($searchTerm, 'i')],
                    ['description' => new \MongoDB\BSON\Regex($searchTerm, 'i')],
                    ['isbn' => $searchTerm]
                ]
            ];
            
            $options = [
                'sort' => ['_id' => -1],
                'skip' => ($page - 1) * $limit,
                'limit' => $limit
            ];
            
            $total = $this->bookModel->collection->countDocuments($filter);
            $books = $this->bookModel->collection->find($filter, $options)->toArray();
            
            // Convert ObjectId to string for JSON serialization
            $books = array_map(function($book) {
                $book['_id'] = (string)$book['_id'];
                $book['author_id'] = (string)$book['author_id'];
                return $book;
            }, $books);
            
            return ApiResponse::success([
                'data' => $books,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::error('Error searching books: ' . $e->getMessage(), 500);
        }
    }
}