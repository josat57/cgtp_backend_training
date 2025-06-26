<?php

/**
 * Database Schema class
 * 
 * * This class provides methods to create and manage database schemas.
 * * It includes methods to create tables and manage relationships.
 * * @package Schema
 * * @version 1.0
 * * @author Your Name
 * * @license MIT
 * * @link    
 * * @since   1.0
 * 
 */
class Schema {
    private $conn;

    /**
     * Class Constructor
     * 
     * @return void
     */
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Create the users table
     * 
     * @return bool True on success, false on failure
     */
    public function createUsersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(15) NOT NULL,
            password VARCHAR(255) NOT NULL
        )";
        return $this->conn->query($sql);
    }

    /**
     * Create the posts table
     * 
     * @return bool True on success, false on failure
     */
    public function createPostsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS posts (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        return $this->conn->query($sql);
    }

    /**
     * Create the comments table
     * 
     * @return bool True on success, false on failure
     */
    public function createCommentsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS comments (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            post_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        return $this->conn->query($sql);    
    }

    /**
     * Create the likes table
     * 
     * @return bool True on success, false on failure
     */
    public function createLikesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS likes (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            post_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        return $this->conn->query($sql);
    }
}