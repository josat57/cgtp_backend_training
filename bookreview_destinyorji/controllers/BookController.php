<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

function createBook($user)
{
    $db = require __DIR__ . '/../config/db.php';
    $books = $db->books;

    $input = json_decode(file_get_contents("php://input"), true);
    $required = ['title', 'author', 'description'];

    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['message' => "$field is required"]);
            return;
        }
    }

    $book = [
        'title' => $input['title'],
        'author' => $input['author'],
        'description' => $input['description'],
        'created_by' => $user['id'],
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $result = $books->insertOne($book);

    http_response_code(201);
    echo json_encode([
        'message' => 'Book created',
        'book_id' => (string) $result->getInsertedId()
    ]);
}

function getAllBooks()
{
    $db = require __DIR__ . '/../config/db.php';
    $books = $db->books;

    $query = [];
    $options = [];

    // 🔍 Search
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $query['$or'] = [
            ['title' => new MongoDB\BSON\Regex($search, 'i')],
            ['author' => new MongoDB\BSON\Regex($search, 'i')]
        ];
    }

    // 📄 Pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
    $skip = ($page - 1) * $limit;

    $options['limit'] = $limit;
    $options['skip'] = $skip;

    $cursor = $books->find($query, $options);
    $results = [];

    foreach ($cursor as $doc) {
        $doc['_id'] = (string) $doc['_id'];
        $results[] = $doc;
    }

    echo json_encode([
        'page' => $page,
        'limit' => $limit,
        'results' => $results
    ]);
}

function getBookById($id)
{
    $db = require __DIR__ . '/../config/db.php';
    $books = $db->books;

    $book = $books->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);

    if (!$book) {
        http_response_code(404);
        echo json_encode(['message' => 'Book not found']);
        return;
    }

    $book['_id'] = (string) $book['_id'];
    echo json_encode($book);
}

function updateBook($id, $user)
{
    $db = require __DIR__ . '/../config/db.php';
    $books = $db->books;

    $book = $books->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    if (!$book) {
        http_response_code(404);
        echo json_encode(['message' => 'Book not found']);
        return;
    }

    if ((string)$book['created_by'] !== $user['id']) {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden – not the book owner']);
        return;
    }

    $input = json_decode(file_get_contents("php://input"), true);
    $updateFields = [];

    foreach (['title', 'author', 'description'] as $field) {
        if (!empty($input[$field])) {
            $updateFields[$field] = $input[$field];
        }
    }

    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['message' => 'Nothing to update']);
        return;
    }

    $books->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($id)],
        ['$set' => $updateFields]
    );

    echo json_encode(['message' => 'Book updated']);
}

function deleteBook($id, $user)
{
    $db = require __DIR__ . '/../config/db.php';
    $books = $db->books;

    $book = $books->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    if (!$book) {
        http_response_code(404);
        echo json_encode(['message' => 'Book not found']);
        return;
    }

    if ($user['role'] !== 'admin' && (string)$book['created_by'] !== $user['id']) {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden – only owner or admin can delete']);
        return;
    }

    $books->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);

    echo json_encode(['message' => 'Book deleted']);
}
