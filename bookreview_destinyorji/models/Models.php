<?php
require_once __DIR__ . '/../config/db.php';

class Models
{
    private $db;

    public function __construct()
    {
        $this->db = require __DIR__ . '/../config/db.php';
    }

    // =======================
    // ✅ USER FUNCTIONS
    // =======================

    public function findUserByEmail($email)
    {
        return $this->db->users->findOne(['email' => $email]);
    }

    public function createUser($user)
    {
        $insert = $this->db->users->insertOne($user);
        return $insert->getInsertedCount() === 1;
    }

    public function findUserById($id)
    {
        return $this->db->users->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    }

    // =======================
    // ✅ BOOK FUNCTIONS
    // =======================

    public function createBook($book)
    {
        return $this->db->books->insertOne($book);
    }

    public function getAllBooks()
    {
        return $this->db->books->find();
    }

    public function findBookById($id)
    {
        return $this->db->books->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    }

    public function updateBook($id, $data)
    {
        return $this->db->books->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($id)],
            ['$set' => $data]
        );
    }

    public function deleteBook($id)
    {
        return $this->db->books->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    }

    // =======================
    // ✅ REVIEW FUNCTIONS
    // =======================

    public function addReview($review)
    {
        return $this->db->reviews->insertOne($review);
    }

    public function getReviewsByBookId($bookId)
    {
        return $this->db->reviews->find([
            'book_id' => new MongoDB\BSON\ObjectId($bookId)
        ]);
    }

    public function deleteReview($reviewId)
    {
        return $this->db->reviews->deleteOne(['_id' => new MongoDB\BSON\ObjectId($reviewId)]);
    }

    public function findReviewById($reviewId)
    {
        return $this->db->reviews->findOne(['_id' => new MongoDB\BSON\ObjectId($reviewId)]);
    }

    // Add these methods to the Models class

    public function getAllUsers()
    {
        return $this->db->users->find();
    }

    public function updateUserRole($userId, $role)
    {
        return $this->db->users->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($userId)],
            ['$set' => ['role' => $role]]
        );
    }

    public function deleteUser($userId)
    {
        return $this->db->users->deleteOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
    }
}
