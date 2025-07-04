<?php
require_once __DIR__ . '/../config/db.php';

function addReview($bookId, $user)
{
    $db = require __DIR__ . '/../config/db.php';
    $books = $db->books;
    $reviews = $db->reviews;

    //  Check book exists
    $book = $books->findOne(['_id' => new MongoDB\BSON\ObjectId($bookId)]);
    if (!$book) {
        http_response_code(404);
        echo json_encode(['message' => 'Book not found']);
        return;
    }

    // Validate input
    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input['content'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Review content is required']);
        return;
    }

    $review = [
        'book_id' => $book['_id'],
        'user_id' => $user['id'],
        'content' => $input['content'],
        'created_at' => date('Y-m-d H:i:s')
    ];

    $result = $reviews->insertOne($review);

    http_response_code(201);
    echo json_encode([
        'message' => 'Review added',
        'review_id' => (string) $result->getInsertedId()
    ]);
}

function getReviews($bookId)
{
    $db = require __DIR__ . '/../config/db.php';
    $reviews = $db->reviews;

    $cursor = $reviews->find(['book_id' => new MongoDB\BSON\ObjectId($bookId)]);
    $result = [];

    foreach ($cursor as $doc) {
        $doc['_id'] = (string) $doc['_id'];
        $doc['book_id'] = (string) $doc['book_id'];
        $doc['user_id'] = (string) $doc['user_id'];
        $result[] = $doc;
    }

    echo json_encode($result);
}

function deleteReview($reviewId, $user)
{
    $db = require __DIR__ . '/../config/db.php';
    $reviews = $db->reviews;

    // ✅ Find review
    $review = $reviews->findOne(['_id' => new MongoDB\BSON\ObjectId($reviewId)]);
    if (!$review) {
        http_response_code(404);
        echo json_encode(['message' => 'Review not found']);
        return;
    }

    $isOwner = (string) $review['user_id'] === $user['id'];
    $isAdmin = $user['role'] === 'admin';

    if (!$isOwner && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden – only the reviewer or admin can delete']);
        return;
    }

    $reviews->deleteOne(['_id' => new MongoDB\BSON\ObjectId($reviewId)]);

    echo json_encode(['message' => 'Review deleted']);
}
