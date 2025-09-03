<?php

namespace App\Models;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Collection;

class Review
{
    protected $collection = 'reviews';
    protected $db;
    
    protected $fillable = [
        'book_id',
        'user_id',
        'rating',
        'title',
        'comment',
        'status'
    ];

    public function __construct()
    {
        $dsn = getenv('DB_DSN') ?: 'mongodb://localhost:27017';
        $database = getenv('DB_NAME') ?: 'user_management';
        
        $this->db = (new Client($dsn))->selectDatabase($database);
    }

    // Get collection instance
    protected function getCollection(): \MongoDB\Collection
    {
        return $this->db->selectCollection($this->collection);
    }

    // Find a review by ID
    public function find($id)
    {
        return $this->getCollection()->findOne(['_id' => new ObjectId($id)]);
    }

    // Create a new review
    public function create(array $data)
    {
        $data['created_at'] = new UTCDateTime();
        $data['updated_at'] = new UTCDateTime();
        
        if (isset($data['book_id'])) {
            $data['book_id'] = new ObjectId($data['book_id']);
        }
        
        if (isset($data['user_id'])) {
            $data['user_id'] = new ObjectId($data['user_id']);
        }

        $result = $this->getCollection()->insertOne($data);
        return $this->find($result->getInsertedId());
    }

    // Update a review
    public function update($id, array $data)
    {
        $data['updated_at'] = new UTCDateTime();
        
        if (isset($data['book_id'])) {
            $data['book_id'] = new ObjectId($data['book_id']);
        }
        
        if (isset($data['user_id'])) {
            $data['user_id'] = new ObjectId($data['user_id']);
        }

        $this->getCollection()->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );
        
        return $this->find($id);
    }

    // Delete a review
    public function delete($id)
    {
        return $this->getCollection()->deleteOne(['_id' => new ObjectId($id)]);
    }

    // Get reviews with pagination and filters
    public function paginate($filters = [], $page = 1, $limit = 10, $sort = ['created_at' => -1])
    {
        $options = [
            'skip' => ($page - 1) * $limit,
            'limit' => $limit,
            'sort' => $sort
        ];

        $cursor = $this->getCollection()->find($filters, $options);
        return [
            'data' => $cursor->toArray(),
            'total' => $this->getCollection()->countDocuments($filters),
            'page' => $page,
            'limit' => $limit
        ];
    }

    // Get reviews for a specific book
    public function getByBook($bookId, $page = 1, $limit = 10)
    {
        return $this->paginate(
            ['book_id' => new ObjectId($bookId)],
            $page,
            $limit,
            ['created_at' => -1]
        );
    }

    // Get reviews by a specific user
    public function getByUser($userId, $page = 1, $limit = 10)
    {
        return $this->paginate(
            ['user_id' => new ObjectId($userId)],
            $page,
            $limit,
            ['created_at' => -1]
        );
    }
}
