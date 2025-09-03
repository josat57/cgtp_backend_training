-- MySQL schema for Book Review API
-- Run in your MySQL instance (e.g., via phpMyAdmin or mysql CLI)
-- Ensure database `book_review_api` exists or adjust below

CREATE DATABASE IF NOT EXISTS `book_review_api` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `book_review_api`;

-- Users: author or reviewer
CREATE TABLE IF NOT EXISTS `users` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`fullname` VARCHAR(150) NOT NULL,
	`email` VARCHAR(190) NOT NULL,
	`password_hash` VARCHAR(255) NOT NULL,
	`role` ENUM('author','reviewer','super_admin') NOT NULL DEFAULT 'reviewer',
	`avatar_path` VARCHAR(255) DEFAULT NULL,
	`is_verified` TINYINT(1) NOT NULL DEFAULT 0,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uniq_users_email` (`email`),
	KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- OTPs for email verification
CREATE TABLE IF NOT EXISTS `otps` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT UNSIGNED NOT NULL,
	`code_hash` VARCHAR(255) NOT NULL,
	`purpose` VARCHAR(50) NOT NULL,
	`expires_at` DATETIME NOT NULL,
	`used` TINYINT(1) NOT NULL DEFAULT 0,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `idx_otps_user_purpose` (`user_id`,`purpose`),
	CONSTRAINT `fk_otps_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Token blacklist for logout
CREATE TABLE IF NOT EXISTS `token_blacklist` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`token_hash` CHAR(64) NOT NULL,
	`expires_at` DATETIME NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uniq_token_hash` (`token_hash`),
	KEY `idx_token_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Books uploaded by authors
CREATE TABLE IF NOT EXISTS `books` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`author_id` INT UNSIGNED NOT NULL,
	`title` VARCHAR(200) NOT NULL,
	`description` TEXT,
	`file_path` VARCHAR(255) NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `idx_books_author` (`author_id`),
	CONSTRAINT `fk_books_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews on books by reviewers
CREATE TABLE IF NOT EXISTS `reviews` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`book_id` INT UNSIGNED NOT NULL,
	`reviewer_id` INT UNSIGNED NOT NULL,
	`comment` TEXT NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `idx_reviews_book` (`book_id`),
	KEY `idx_reviews_reviewer` (`reviewer_id`),
	CONSTRAINT `fk_reviews_book` FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_reviews_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invitations created by authors for reviewers
CREATE TABLE IF NOT EXISTS `invitations` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`book_id` INT UNSIGNED NOT NULL,
	`author_id` INT UNSIGNED NOT NULL,
	`reviewer_email` VARCHAR(190) NOT NULL,
	`reviewer_id` INT UNSIGNED DEFAULT NULL,
	`status` ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
	`token` VARCHAR(64) NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uniq_invitations_token` (`token`),
	KEY `idx_invitations_book` (`book_id`),
	KEY `idx_invitations_author` (`author_id`),
	KEY `idx_invitations_status` (`status`),
	CONSTRAINT `fk_inv_book` FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_inv_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_inv_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

-- Activity logs (for audit and super admin visibility)
CREATE TABLE IF NOT EXISTS `activity_logs` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`actor_user_id` INT UNSIGNED DEFAULT NULL,
	`action` VARCHAR(100) NOT NULL,
	`entity_type` VARCHAR(100) DEFAULT NULL,
	`entity_id` INT UNSIGNED DEFAULT NULL,
	`meta` JSON DEFAULT NULL,
	`ip_address` VARCHAR(45) DEFAULT NULL,
	`user_agent` VARCHAR(255) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `idx_activity_actor` (`actor_user_id`),
	KEY `idx_activity_action` (`action`),
	KEY `idx_activity_entity` (`entity_type`,`entity_id`),
	CONSTRAINT `fk_activity_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;