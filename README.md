# User Management API

A RESTful API for managing users with JWT authentication, built with Slim PHP and MongoDB.

## Features

- User registration and authentication with JWT
- CRUD operations for users
- Role-based access control
- Input validation
- CORS support
- Environment-based configuration

## Requirements

- PHP 8.0 or higher
- MongoDB extension for PHP
- Composer
- MongoDB server (local or remote)

## Installation

1. Clone the repository:
   ```bash
   git clone [your-repo-url]
   cd user-management-api
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Copy the `.env.example` to `.env` and update the configuration:
   ```bash
   cp .env.example .env
   ```

4. Generate a secure JWT secret key:
   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```
   Add the generated key to your `.env` file as `JWT_SECRET`.

5. Update the database configuration in `.env`:
   ```
   DB_DSN=mongodb://localhost:27017
   DB_NAME=user_management
   ```

## Running the Application

Start the PHP development server:
```bash
php -S localhost:8000 -t public
```

The API will be available at `http://localhost:8000`

## API Endpoints

### Authentication

- `POST /api/auth/register` - Register a new user
- `POST /api/auth/login` - Login and get JWT token
- `GET /api/auth/me` - Get current user profile (protected)

### Users

- `GET /api/users` - List all users (protected)
- `POST /api/users` - Create a new user (protected)
- `GET /api/users/{id}` - Get user by ID (protected)
- `PUT /api/users/{id}` - Update user (protected)
- `DELETE /api/users/{id}` - Delete user (protected)

## Example Requests

### Register a new user
```http
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "securepassword123"
}
```

### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "securepassword123"
}
```

### Get current user profile
```http
GET /api/auth/me
Authorization: Bearer your-jwt-token
```

## Error Responses

The API returns JSON responses with the following structure for errors:

```json
{
    "success": false,
    "error": {
        "message": "Error message",
        "code": 400
    }
}
```

## Testing

You can test the API using tools like Postman or cURL. Make sure to include the `Content-Type: application/json` header for all requests that include a body.

## Security

- Always use HTTPS in production
- Keep your JWT_SECRET secure
- Implement rate limiting in production
- Use strong passwords
- Keep dependencies up to date

## License

MIT
