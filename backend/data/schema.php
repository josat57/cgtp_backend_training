<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

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
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('Author','Reviewer') NOT NULL DEFAULT 'Reviewer',
            name VARCHAR(150) NULL,
            profile_pic VARCHAR(255) NULL,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        return $this->conn->query($sql);
    }

    /**
     * Create the posts table
     * 
     * @return bool True on success, false on failure
     */

    // Books table
    public function createCarsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS books (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            author_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            content MEDIUMTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        return $this->conn->query($sql);
    }

    /**
     * Create the comments table
     * 
     * @return bool True on success, false on failure
     */
    public function createTestimonialsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS reviews (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            reviewer_id INT(11) NOT NULL,
            book_id INT(11) NOT NULL,
            comment TEXT NOT NULL,
            rating TINYINT(1) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        return $this->conn->query($sql);    
    }

    /**
     * Create the likes table
     * 
     * @return bool True on success, false on failure
     */
    public function createLikesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS invitations (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            book_id INT(11) NOT NULL,
            invitee_email VARCHAR(150) NOT NULL,
            invited_by INT(11) NOT NULL,
            status ENUM('pending','accepted','declined') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE
        )";
        return $this->conn->query($sql);
    }

    /**
     * Session table creation
     * * @return bool True on success, false on failure
     */
    public function createSessionsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS sessions (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NULL,
            session_id VARCHAR(255) NOT NULL,
            session_start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            session_end_time TIMESTAMP NULL DEFAULT NULL,
            session_token VARCHAR(255) NOT NULL,
            session_status ENUM('active', 'inactive') DEFAULT 'active',
            UNIQUE (session_id),
            UNIQUE (session_token),
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $ok1 = $this->conn->query($sql);

        $sqlOtp = "CREATE TABLE IF NOT EXISTS otp (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            code VARCHAR(10) NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $ok2 = $this->conn->query($sqlOtp);
        return $ok1 && $ok2;
    }
}