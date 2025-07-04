# Simple Book Review API

A RESTful API for managing a collection of books and their reviews.

## Features
- User registration and login (JWT-based authentication)
- Book management (CRUD)
- Review system (add, view, delete reviews)
- Admin role (optional)
- Pagination and search (optional)

## Tech Stack
- Core PHP
- MongoDB
- JWT (firebase/php-jwt)

## Setup
1. Clone the repo
2. Run `composer install`
3. Configure MongoDB connection in `config/db.php`
4. Serve `public/index.php` via your web server

## Endpoints
- `POST /register` — Register
- `POST /login` — Login
- `POST /books` — Add book
- `GET /books` — List books
- `GET /books/{id}` — Get book
- `PUT /books/{id}` — Update book
- `DELETE /books/{id}` — Delete book
- `POST /books/{id}/reviews` — Add review
- `GET /books/{id}/reviews` — List reviews
- `DELETE /reviews/{id}` — Delete review

---

## Postman Collection
(Will be provided after API implementation) 