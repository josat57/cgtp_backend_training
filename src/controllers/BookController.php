<?php
use MongoDB\BSON\ObjectId;
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class BookController {
    public static function create($user) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['title'], $input['author'], $input['description'])) {
            Response::json(['error' => 'Missing fields'], 400);
        }
        $db = getMongoDB();
        $books = $db->books;
        $book = [
            'title' => $input['title'],
            'author' => $input['author'],
            'description' => $input['description'],
            'created_by' => $user['sub']
        ];
        $result = $books->insertOne($book);
        $book['_id'] = (string)$result->getInsertedId();
        Response::json($book, 201);
    }

    public static function getAll() {
        $db = getMongoDB();
        $books = $db->books;
        $result = $books->find();
        $list = [];
        foreach ($result as $book) {
            $book['_id'] = (string)$book['_id'];
            $list[] = $book;
        }
        Response::json($list);
    }

    public static function getOne($id) {
        $db = getMongoDB();
        $books = $db->books;
        try {
            $book = $books->findOne(['_id' => new ObjectId($id)]);
        } catch (Exception $e) {
            Response::json(['error' => 'Invalid book ID'], 400);
        }
        if (!$book) {
            Response::json(['error' => 'Book not found'], 404);
        }
        $book['_id'] = (string)$book['_id'];
        Response::json($book);
    }

    public static function update($id, $user) {
        $input = json_decode(file_get_contents('php://input'), true);
        $db = getMongoDB();
        $books = $db->books;
        try {
            $book = $books->findOne(['_id' => new ObjectId($id)]);
        } catch (Exception $e) {
            Response::json(['error' => 'Invalid book ID'], 400);
        }
        if (!$book) {
            Response::json(['error' => 'Book not found'], 404);
        }
        if ($book['created_by'] !== $user['sub'] && $user['role'] !== 'admin') {
            Response::json(['error' => 'Forbidden'], 403);
        }
        $update = [];
        foreach (['title', 'author', 'description'] as $field) {
            if (isset($input[$field])) {
                $update[$field] = $input[$field];
            }
        }
        if (empty($update)) {
            Response::json(['error' => 'No fields to update'], 400);
        }
        $books->updateOne(['_id' => new ObjectId($id)], ['$set' => $update]);
        $book = $books->findOne(['_id' => new ObjectId($id)]);
        $book['_id'] = (string)$book['_id'];
        Response::json($book);
    }

    public static function delete($id, $user) {
        $db = getMongoDB();
        $books = $db->books;
        try {
            $book = $books->findOne(['_id' => new ObjectId($id)]);
        } catch (Exception $e) {
            Response::json(['error' => 'Invalid book ID'], 400);
        }
        if (!$book) {
            Response::json(['error' => 'Book not found'], 404);
        }
        if ($book['created_by'] !== $user['sub'] && $user['role'] !== 'admin') {
            Response::json(['error' => 'Forbidden'], 403);
        }
        $books->deleteOne(['_id' => new ObjectId($id)]);
        Response::json(['message' => 'Book deleted']);
    }
} 