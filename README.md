User Management API
Author

Adams Raphael Muzan
Graduate Trainee, 2025 Graduate Trainee Program
Cinfores Ltd.

📌 Project Overview

This project implements a User Management API for handling user registration, authentication, role assignment, and access management. It provides a foundation for secure role-based access control within an application.

The API is built with PHP and uses a MySQL database (via phpMyAdmin) for data persistence. JWT (JSON Web Token) is used for authentication and authorization.


✨ Key Features

Users can register as either Reviewer, Author, or Super Admin.

Upon registration, users must verify their email via either:

A verification link, or

A one-time password (OTP), both sent via email.

Roles are stored in the database under the column role_id, with assigned numerical values:

1 → Reviewer

2 → Author

3 → Super Admin

⚙️ Setup Instructions
Requirements

PHP >= 8.0

Composer (for dependencies)

MySQL database (phpMyAdmin recommended)

Web server (Apache/Nginx or PHP built-in server)

Installation

Clone the repository:

git clone <repo-url>
cd user-management-api


Install dependencies:

composer install


Configure the database in config/db.php:

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'user_management');


Run migrations or import the provided SQL schema (/database/schema.sql) into phpMyAdmin.

Start the server:

php -S localhost:8000 -t public

🔑 Endpoints
Method	Endpoint	Description	Role Access
POST	/auth/register	Register a new user (Reviewer, Author, or Super Admin)	Public
POST	/auth/login	Authenticate user and return JWT	Public
POST	/auth/verify-email	Verify email via OTP or link	Public
GET	/users	Get list of all users	Super Admin
GET	/users/{id}	Get details of a single user	Reviewer / Author (self) or Admin
PUT	/users/{id}	Update user details	Reviewer / Author (self) or Admin
DELETE	/users/{id}	Delete a user	Super Admin
POST	/reviews	Create a review	Reviewer
GET	/reviews	Get all reviews	Reviewer / Author / Admin
POST	/invite	Send invitation to reviewer	Author / Admin
🚀 Future Improvements

Add password reset functionality.

Add rate limiting for security.

Move invite.php and reviews.php logic fully under controllers for better MVC structure.

Improve test coverage with PHPUnit.