<?php
use MongoDB\BSON\ObjectId;
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../utils/Response.php';

class ReviewController {
    public static function add($bookId, $user) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['content'])) {
            Response::json(['error' => 'Missing review content'], 400);
        }
        $db = getMongoDB();
        // Check if book exists
        $book = $db->books->findOne(['_id' => new ObjectId($bookId)]);
        if (!$book) {
            Response::json(['error' => 'Book not found'], 404);
        }
        $review = [
            'book_id' => $bookId,
            'user_id' => $user['sub'],
            'username' => $user['username'],
            'content' => $input['content'],
            'created_at' => date('c')
        ];
        $result = $db->reviews->insertOne($review);
        $review['_id'] = (string)$result->getInsertedId();
        Response::json($review, 201);
    }

    public static function getAll($bookId) {
        $db = getMongoDB();
        // Check if book exists
        $book = $db->books->findOne(['_id' => new ObjectId($bookId)]);
        if (!$book) {
            Response::json(['error' => 'Book not found'], 404);
        }
        $reviews = $db->reviews->find(['book_id' => $bookId]);
        $list = [];
        foreach ($reviews as $review) {
            $review['_id'] = (string)$review['_id'];
            $list[] = $review;
        }
        Response::json($list);
    }

    public static function delete($reviewId, $user) {
        $db = getMongoDB();
        $review = $db->reviews->findOne(['_id' => new ObjectId($reviewId)]);
        if (!$review) {
            Response::json(['error' => 'Review not found'], 404);
        }
        if ($review['user_id'] !== $user['sub'] && $user['role'] !== 'admin') {
            Response::json(['error' => 'Forbidden'], 403);
        }
        $db->reviews->deleteOne(['_id' => new ObjectId($reviewId)]);
        Response::json(['message' => 'Review deleted']);
    }
} 