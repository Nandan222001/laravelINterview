# API Documentation

## Base URL
```
http://localhost:8000
```

## Authentication

All protected endpoints require a Bearer token obtained from the login endpoint.

### Headers
```
Authorization: Bearer {your_token}
Content-Type: application/json
Accept: application/json
```

---

## Authentication Endpoints

### Register User

Create a new user account.

**Endpoint:** `POST /register`

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response:** `201 Created`
```json
{
    "message": "User registered successfully",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

---

### Login

Authenticate and receive an API token.

**Endpoint:** `POST /login`

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123",
    "remember": true
}
```

**Response:** `200 OK`
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": "2024-01-01T00:00:00.000000Z",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Error Response:** `422 Unprocessable Entity`
```json
{
    "message": "The email field is required.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

---

### Logout

Invalidate the current API token.

**Endpoint:** `POST /logout`

**Headers:** 
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
    "message": "Logout successful"
}
```

---

### Forgot Password

Request a password reset link.

**Endpoint:** `POST /forgot-password`

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

**Response:** `200 OK`
```json
{
    "message": "We have emailed your password reset link."
}
```

---

### Reset Password

Reset password using the token from email.

**Endpoint:** `POST /reset-password`

**Request Body:**
```json
{
    "token": "reset_token_from_email",
    "email": "john@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response:** `200 OK`
```json
{
    "message": "Your password has been reset."
}
```

---

### Verify Email

Verify email address using the link from email.

**Endpoint:** `GET /verify-email/{id}/{hash}`

**Headers:** 
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
    "message": "Email verified successfully"
}
```

---

### Resend Verification Email

Request a new email verification link.

**Endpoint:** `POST /email/verification-notification`

**Headers:** 
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
    "message": "Verification link sent"
}
```

---

## User Management Endpoints

### Get Authenticated User

Get the currently authenticated user's information.

**Endpoint:** `GET /api/user`

**Headers:** 
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

---

### List Users

Get a paginated list of all users.

**Endpoint:** `GET /api/users`

**Headers:** 
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "email_verified_at": "2024-01-01T00:00:00.000000Z",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "meta": {
        "total": 1,
        "timestamp": "2024-01-01T00:00:00Z"
    }
}
```

---

### Get User by ID

Get a specific user's information.

**Endpoint:** `GET /api/users/{id}`

**Headers:** 
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "User retrieved successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": "2024-01-01T00:00:00.000000Z",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    },
    "meta": {
        "timestamp": "2024-01-01T00:00:00Z"
    }
}
```

**Error Response:** `404 Not Found`
```json
{
    "success": false,
    "message": "User not found"
}
```

---

### Update User

Update a user's information.

**Endpoint:** `PUT /api/users/{id}` or `PATCH /api/users/{id}`

**Headers:** 
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "John Updated",
    "email": "john.updated@example.com"
}
```

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "User updated successfully",
    "data": {
        "id": 1,
        "name": "John Updated",
        "email": "john.updated@example.com",
        "email_verified_at": "2024-01-01T00:00:00.000000Z",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:01.000000Z"
    },
    "meta": {
        "timestamp": "2024-01-01T00:00:01Z"
    }
}
```

---

### Delete User

Delete a user account.

**Endpoint:** `DELETE /api/users/{id}`

**Headers:** 
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "User deleted successfully"
}
```

**Error Response:** `404 Not Found`
```json
{
    "success": false,
    "message": "User not found"
}
```

---

## Error Responses

### Validation Error
**Status:** `422 Unprocessable Entity`
```json
{
    "message": "The email field is required.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

### Authentication Error
**Status:** `401 Unauthorized`
```json
{
    "success": false,
    "message": "Unauthorized"
}
```

### Not Found Error
**Status:** `404 Not Found`
```json
{
    "success": false,
    "message": "Resource not found"
}
```

### Server Error
**Status:** `500 Internal Server Error`
```json
{
    "success": false,
    "message": "Internal server error"
}
```

---

## Rate Limiting

Authentication endpoints are rate-limited:
- Login: 5 attempts per minute per IP
- Password reset: 6 requests per minute
- Email verification: 6 requests per minute

After exceeding the limit, you'll receive:

**Status:** `429 Too Many Requests`
```json
{
    "message": "Too many login attempts. Please try again in 60 seconds."
}
```

---

## Example Usage with cURL

### Register
```bash
curl -X POST http://localhost:8000/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Get Users (Authenticated)
```bash
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

### Update User
```bash
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Authorization: Bearer {your_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Updated"
  }'
```

### Logout
```bash
curl -X POST http://localhost:8000/logout \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

---

## Postman Collection

Import the following environment variables in Postman:
- `base_url`: `http://localhost:8000`
- `token`: `{your_auth_token}`

Use `{{base_url}}` and `{{token}}` in your requests.
