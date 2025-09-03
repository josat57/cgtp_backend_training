<?php

namespace App\Models;

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class Model
{
    protected $collection;
    protected $client;
    protected $database;
    protected $collectionName;

    public function __construct()
    {
        // Use getenv() instead of env() for better compatibility
        $dsn = getenv('DB_DSN') ?: 'mongodb://localhost:27017';
        $dbName = getenv('DB_NAME') ?: 'user_management';

        // Initialize MongoDB client and select database
        $this->client = new Client($dsn);
        $this->database = $this->client->selectDatabase($dbName);

        // Get the collection name from the child class
        $collectionName = $this->getTable();
        if (!$collectionName) {
            throw new \RuntimeException('Collection name not specified. Please set $collectionName in your model.');
        }

        // Assign collection
        $this->collection = $this->database->selectCollection($collectionName);
    }

    // Returns the name of the collection; to be overridden in child class
    public function getTable()
    {
        return $this->collectionName ?? null;
    }

    // Add common database methods that can be used by all models
    public function find($id)
    {
        return $this->collection->findOne(['_id' => new ObjectId($id)]);
    }

    public function create(array $data)
    {
        $data['created_at'] = new UTCDateTime();
        $data['updated_at'] = new UTCDateTime();
        
        $result = $this->collection->insertOne($data);
        return $this->find($result->getInsertedId());
    }

    public function update($id, array $data)
    {
        $data['updated_at'] = new UTCDateTime();
        
        $this->collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );
        
        return $this->find($id);
    }

    public function delete($id)
    {
        return $this->collection->deleteOne(['_id' => new ObjectId($id)]);
    }
}

class User extends Model
{
    protected $collectionName = 'users';
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'email_verified_at',
        'last_login_at',
        'status',
        'profile_picture'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'status' => 'string'
    ];

    // Relationships (stubbed for MongoDB, implement as needed)
    public function role()
    {
        // Example: fetch role document by role_id
        return $this->collection->findOne(['_id' => new ObjectId($this->role_id ?? '')]);
    }

    public function create(array $data)
    {
        $result = $this->collection->insertOne($data);
        return $this->findById($result->getInsertedId());
    }

    public function findById($id)
    {
        if (is_string($id)) {
            $id = new ObjectId($id);
        }
        return $this->collection->findOne(['_id' => $id]);
    }

    public function findByEmail(string $email)
    {
        return $this->collection->findOne(['email' => $email]);
    }

    public function update($id, array $data)
    {
        $updateData = ['$set' => array_merge($data, ['updated_at' => new UTCDateTime()])];
        $result = $this->collection->updateOne(['_id' => new ObjectId($id)], $updateData);
        return $result->getModifiedCount() > 0;
    }

    public function delete($id)
    {
        $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
        return $result->getDeletedCount() > 0;
    }

    public function all()
    {
        return $this->collection->find()->toArray();
    }

    public function hasPermission($permission)
    {
        $role = $this->role();
        return $role && in_array($permission, $role->permissions ?? []);
    }

    public function isAdmin()
    {
        $role = $this->role();
        return $role && ($role['name'] ?? '') === 'admin';
    }
}
