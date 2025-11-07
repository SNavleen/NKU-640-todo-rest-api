# TODO REST API - Complete API Reference

## Overview

This document defines the complete API contract for the TODO REST API, including all endpoint specifications, authentication, request/response schemas, validation rules, and security guidelines.

**Base URL:** `http://localhost:8000/api/v1`

**Content-Type:** `application/json`

---

## Table of Contents

- [Data Models](#data-models)
- [Authentication](#authentication)
- [List Endpoints](#list-endpoints)
- [Task Endpoints](#task-endpoints)
- [Error Responses](#error-responses)
- [Security Guidelines](#security-guidelines)

---

## Data Models

### User

```json
{
  "id": "string (UUID v4)",
  "username": "string (3-50 chars, unique)",
  "email": "string (valid email, unique)",
  "createdAt": "string (ISO 8601 datetime)",
  "updatedAt": "string (ISO 8601 datetime, optional)"
}
```

**Note:** Passwords are never returned in responses.

### List

```json
{
  "id": "string (UUID v4)",
  "name": "string (required, max 255 chars)",
  "description": "string (optional, max 1000 chars)",
  "createdAt": "string (ISO 8601 datetime)",
  "updatedAt": "string (ISO 8601 datetime, optional)"
}
```

### Task

```json
{
  "id": "string (UUID v4)",
  "listId": "string (UUID v4, required)",
  "title": "string (required, max 255 chars)",
  "description": "string (optional, max 2000 chars)",
  "completed": "boolean (default: false)",
  "dueDate": "string (ISO 8601 datetime, optional)",
  "priority": "string (enum: 'low', 'medium', 'high', optional)",
  "categories": "array of strings (optional, max 10 items, each max 50 chars)",
  "createdAt": "string (ISO 8601 datetime)",
  "updatedAt": "string (ISO 8601 datetime, optional)"
}
```

---

## Authentication

The API uses **JWT (JSON Web Token)** for authentication:
- Passwords are hashed with **bcrypt** (cost factor 12)
- Tokens expire after **1 hour** (configurable via `JWT_EXPIRY`)
- Tokens are sent as `Authorization: Bearer <token>` header
- Tokens are blacklisted on logout to prevent reuse

### POST /api/v1/auth/signup

Create a new user account.

**Request Body:**
```json
{
  "username": "alice",
  "email": "alice@example.com",
  "password": "password123"
}
```

**Validation Rules:**
- `username`: 3-50 characters, unique
- `email`: Valid email format, unique
- `password`: Minimum 8 characters

**Success Response (201):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": "a1b2c3d4-...",
    "username": "alice",
    "email": "alice@example.com",
    "createdAt": "2025-11-06T10:00:00Z"
  }
}
```

**Error Responses:**
- `400 Bad Request` - Validation error
- `409 Conflict` - Username or email already exists
- `500 Internal Server Error` - Server error

**curl Example:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/signup \
  -H "Content-Type: application/json" \
  -d '{
    "username": "alice",
    "email": "alice@example.com",
    "password": "password123"
  }'
```

---

### POST /api/v1/auth/login

Authenticate and receive a JWT token.

**Request Body:**
```json
{
  "username": "alice",
  "password": "password123"
}
```

**Success Response (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": "a1b2c3d4-...",
    "username": "alice",
    "email": "alice@example.com",
    "createdAt": "2025-11-06T10:00:00Z"
  }
}
```

**Error Responses:**
- `400 Bad Request` - Validation error
- `401 Unauthorized` - Invalid username or password
- `500 Internal Server Error` - Server error

**curl Example:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "alice",
    "password": "password123"
  }'
```

---

### GET /api/v1/users/profile

Get current user profile (requires authentication).

**Headers Required:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response (200):**
```json
{
  "id": "a1b2c3d4-...",
  "username": "alice",
  "email": "alice@example.com",
  "createdAt": "2025-11-06T10:00:00Z",
  "updatedAt": null
}
```

**Error Responses:**
- `401 Unauthorized` - Missing, invalid, expired, or blacklisted token
- `404 Not Found` - User not found
- `500 Internal Server Error` - Server error

**curl Example:**
```bash
TOKEN="your-jwt-token-here"
curl -X GET http://localhost:8000/api/v1/users/profile \
  -H "Authorization: Bearer $TOKEN"
```

---

### POST /api/v1/auth/logout

Logout and blacklist the current token (requires authentication).

**Headers Required:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response (204):**
- No content
- Token is added to blacklist and can no longer be used

**curl Example:**
```bash
TOKEN="your-jwt-token-here"
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer $TOKEN"
```

---

## List Endpoints

### GET /api/v1/lists

Retrieve all lists.

**Request:**
- Method: `GET`
- Headers: None required
- Body: None

**Response (200 OK):**
```json
[
  {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Groceries",
    "description": "Weekly shopping list",
    "createdAt": "2025-11-06T10:00:00Z",
    "updatedAt": null
  }
]
```

**Error Responses:**
- `500 Internal Server Error` - Database error

---

### GET /api/v1/lists/:id

Retrieve a single list by ID.

**Request:**
- Method: `GET`
- Headers: None required
- URL Parameters: `id` (UUID v4)

**Response (200 OK):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "name": "Groceries",
  "description": "Weekly shopping list",
  "createdAt": "2025-11-06T10:00:00Z",
  "updatedAt": null
}
```

**Error Responses:**
- `400 Bad Request` - Invalid UUID format
- `404 Not Found` - List not found
- `500 Internal Server Error` - Database error

---

### POST /api/v1/lists

Create a new list.

**Request:**
- Method: `POST`
- Headers: `Content-Type: application/json`
- Body:
```json
{
  "name": "Groceries",
  "description": "Weekly shopping list"
}
```

**Validation Rules:**
- `name`: Required, string, 1-255 characters, cannot be only whitespace
- `description`: Optional, string, max 1000 characters

**Response (201 Created):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "name": "Groceries",
  "description": "Weekly shopping list",
  "createdAt": "2025-11-06T10:00:00Z",
  "updatedAt": null
}
```

**Error Responses:**
- `400 Bad Request` - Validation error (invalid/missing fields)
- `415 Unsupported Media Type` - Invalid Content-Type
- `500 Internal Server Error` - Database error

---

### PATCH /api/v1/lists/:id

Update an existing list.

**Request:**
- Method: `PATCH`
- Headers: `Content-Type: application/json`
- URL Parameters: `id` (UUID v4)
- Body (all fields optional):
```json
{
  "name": "Updated Groceries",
  "description": "Updated description"
}
```

**Validation Rules:**
- `name`: Optional, string, 1-255 characters if provided
- `description`: Optional, string, max 1000 characters if provided
- At least one field must be provided

**Response (200 OK):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "name": "Updated Groceries",
  "description": "Updated description",
  "createdAt": "2025-11-06T10:00:00Z",
  "updatedAt": "2025-11-06T11:00:00Z"
}
```

**Error Responses:**
- `400 Bad Request` - Invalid UUID format or validation error
- `404 Not Found` - List not found
- `415 Unsupported Media Type` - Invalid Content-Type
- `500 Internal Server Error` - Database error

---

### DELETE /api/v1/lists/:id

Delete a list and all associated tasks.

**Request:**
- Method: `DELETE`
- Headers: None required
- URL Parameters: `id` (UUID v4)

**Response (204 No Content):**
- Empty body

**Error Responses:**
- `400 Bad Request` - Invalid UUID format
- `404 Not Found` - List not found
- `500 Internal Server Error` - Database error

---

## Task Endpoints

### GET /api/v1/lists/:listId/tasks

Retrieve all tasks in a specific list.

**Request:**
- Method: `GET`
- Headers: None required
- URL Parameters: `listId` (UUID v4)

**Response (200 OK):**
```json
[
  {
    "id": "660e8400-e29b-41d4-a716-446655440001",
    "listId": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Buy milk",
    "description": "2 liters, skim",
    "completed": false,
    "dueDate": "2025-11-07T18:00:00Z",
    "priority": "medium",
    "categories": ["groceries", "dairy"],
    "createdAt": "2025-11-06T10:05:00Z",
    "updatedAt": null
  }
]
```

**Error Responses:**
- `400 Bad Request` - Invalid UUID format
- `404 Not Found` - List not found
- `500 Internal Server Error` - Database error

---

### GET /api/v1/tasks/:id

Retrieve a single task by ID.

**Request:**
- Method: `GET`
- Headers: None required
- URL Parameters: `id` (UUID v4)

**Response (200 OK):**
```json
{
  "id": "660e8400-e29b-41d4-a716-446655440001",
  "listId": "550e8400-e29b-41d4-a716-446655440000",
  "title": "Buy milk",
  "description": "2 liters, skim",
  "completed": false,
  "dueDate": "2025-11-07T18:00:00Z",
  "priority": "medium",
  "categories": ["groceries", "dairy"],
  "createdAt": "2025-11-06T10:05:00Z",
  "updatedAt": null
}
```

**Error Responses:**
- `400 Bad Request` - Invalid UUID format
- `404 Not Found` - Task not found
- `500 Internal Server Error` - Database error

---

### POST /api/v1/lists/:listId/tasks

Create a new task in a specific list.

**Request:**
- Method: `POST`
- Headers: `Content-Type: application/json`
- URL Parameters: `listId` (UUID v4)
- Body:
```json
{
  "title": "Buy milk",
  "description": "2 liters, skim",
  "dueDate": "2025-11-07T18:00:00Z",
  "priority": "medium",
  "categories": ["groceries", "dairy"]
}
```

**Validation Rules:**
- `title`: Required, string, 1-255 characters, cannot be only whitespace
- `description`: Optional, string, max 2000 characters
- `completed`: Optional, boolean (default: false)
- `dueDate`: Optional, valid ISO 8601 datetime string
- `priority`: Optional, enum ('low', 'medium', 'high')
- `categories`: Optional, array of strings, max 10 items, each max 50 characters

**Response (201 Created):**
```json
{
  "id": "660e8400-e29b-41d4-a716-446655440001",
  "listId": "550e8400-e29b-41d4-a716-446655440000",
  "title": "Buy milk",
  "description": "2 liters, skim",
  "completed": false,
  "dueDate": "2025-11-07T18:00:00Z",
  "priority": "medium",
  "categories": ["groceries", "dairy"],
  "createdAt": "2025-11-06T10:05:00Z",
  "updatedAt": null
}
```

**Error Responses:**
- `400 Bad Request` - Invalid UUID format or validation error
- `404 Not Found` - List not found
- `415 Unsupported Media Type` - Invalid Content-Type
- `500 Internal Server Error` - Database error

---

### PATCH /api/v1/tasks/:id

Update an existing task.

**Request:**
- Method: `PATCH`
- Headers: `Content-Type: application/json`
- URL Parameters: `id` (UUID v4)
- Body (all fields optional):
```json
{
  "title": "Buy organic milk",
  "completed": true,
  "priority": "high"
}
```

**Validation Rules:**
- `title`: Optional, string, 1-255 characters if provided
- `description`: Optional, string, max 2000 characters if provided
- `completed`: Optional, boolean
- `dueDate`: Optional, valid ISO 8601 datetime string or null
- `priority`: Optional, enum ('low', 'medium', 'high') or null
- `categories`: Optional, array of strings, max 10 items, each max 50 characters
- At least one field must be provided

**Response (200 OK):**
```json
{
  "id": "660e8400-e29b-41d4-a716-446655440001",
  "listId": "550e8400-e29b-41d4-a716-446655440000",
  "title": "Buy organic milk",
  "description": "2 liters, skim",
  "completed": true,
  "dueDate": "2025-11-07T18:00:00Z",
  "priority": "high",
  "categories": ["groceries", "dairy"],
  "createdAt": "2025-11-06T10:05:00Z",
  "updatedAt": "2025-11-06T11:30:00Z"
}
```

**Error Responses:**
- `400 Bad Request` - Invalid UUID format or validation error
- `404 Not Found` - Task not found
- `415 Unsupported Media Type` - Invalid Content-Type
- `500 Internal Server Error` - Database error

---

### DELETE /api/v1/tasks/:id

Delete a task.

**Request:**
- Method: `DELETE`
- Headers: None required
- URL Parameters: `id` (UUID v4)

**Response (204 No Content):**
- Empty body

**Error Responses:**
- `400 Bad Request` - Invalid UUID format
- `404 Not Found` - Task not found
- `500 Internal Server Error` - Database error

---

## Error Responses

All errors return JSON with the following format:

```json
{
  "error": "Error message",
  "code": "ERROR_CODE",
  "details": {}
}
```

### HTTP Status Codes

- `200 OK` - Successful GET/PATCH request
- `201 Created` - Successful POST request
- `204 No Content` - Successful DELETE request
- `400 Bad Request` - Invalid request (validation error, malformed UUID, etc.)
- `401 Unauthorized` - Missing, invalid, expired, or blacklisted token
- `404 Not Found` - Resource not found
- `409 Conflict` - Duplicate username or email
- `415 Unsupported Media Type` - Invalid Content-Type
- `500 Internal Server Error` - Server/database error

### Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_ERROR` | Input validation failed |
| `INVALID_UUID` | Invalid UUID format |
| `INVALID_JSON` | Malformed JSON |
| `NOT_FOUND` | Resource not found |
| `CONFLICT` | Duplicate resource |
| `UNAUTHORIZED` | Missing or invalid authentication |
| `UNSUPPORTED_MEDIA_TYPE` | Wrong Content-Type header |
| `INTERNAL_ERROR` | Server error |

---

## Security Guidelines

### Authentication Security

1. **Password Hashing:**
   - Bcrypt with cost factor 12
   - Passwords never stored or returned in plain text
   - Example: `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`

2. **JWT Token Management:**
   - Tokens expire after 1 hour
   - Signature verification with secret key
   - Token blacklisting on logout
   - Invalid/expired/blacklisted tokens return `401 Unauthorized`

3. **Token Blacklisting:**
   - Tokens added to blacklist on logout
   - Blacklist checked before token validation
   - Automatic cleanup of expired tokens
   - Prevents token reuse after logout

### Input Validation

1. **UUID Validation:**
   - All IDs must be valid UUID v4 format
   - Reject malformed UUIDs with `400 Bad Request`

2. **String Sanitization:**
   - Trim whitespace from all string inputs
   - Reject strings that are only whitespace when required
   - Enforce maximum length limits
   - Escape HTML entities to prevent XSS attacks

3. **Data Type Validation:**
   - Validate JSON structure and data types
   - Reject invalid JSON with `400 Bad Request`
   - Validate enums (e.g., priority values)

4. **Date Validation:**
   - Validate ISO 8601 format for dates
   - Reject invalid date formats with `400 Bad Request`

### Database Security

1. **SQL Injection Prevention:**
   - Use prepared statements (PDO with parameterized queries) for ALL database operations
   - NEVER concatenate user input into SQL queries
   - Validate and sanitize all input before database operations

2. **Transaction Safety:**
   - Use transactions for operations affecting multiple tables
   - Implement proper rollback on errors

### Content-Type Validation

1. **Request Validation:**
   - Validate `Content-Type: application/json` for POST/PATCH requests
   - Return `415 Unsupported Media Type` for invalid content types

2. **Response Headers:**
   - Always return `Content-Type: application/json`
   - Include proper HTTP status codes

### Environment Configuration

Set in `.env` file:

```bash
# Debug mode (show detailed errors)
DEBUG_MODE=true

# Log level (error, warning, info, debug)
LOG_LEVEL=debug

# Database path
DATABASE_PATH=data/todo.db

# JWT configuration (CHANGE IN PRODUCTION!)
JWT_SECRET=your-secret-key-change-in-production
JWT_EXPIRY=3600
```

**Production Recommendations:**
- Set `DEBUG_MODE=false` in production
- Use a strong, random `JWT_SECRET` (32+ characters)
- Keep `JWT_SECRET` secure (never commit to git)
- Consider shorter expiry for sensitive applications
- Use HTTPS for all endpoints
- Implement rate limiting to prevent abuse

---

## Complete Authentication Flow Example

```bash
# 1. Sign up
SIGNUP=$(curl -s -X POST http://localhost:8000/api/v1/auth/signup \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","email":"alice@test.com","password":"pass123456"}')

TOKEN=$(echo $SIGNUP | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
echo "Token: $TOKEN"

# 2. Get profile (requires token)
curl -X GET http://localhost:8000/api/v1/users/profile \
  -H "Authorization: Bearer $TOKEN"

# 3. Logout (blacklist token)
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer $TOKEN"

# 4. Try to use token again (should fail with 401)
curl -X GET http://localhost:8000/api/v1/users/profile \
  -H "Authorization: Bearer $TOKEN"

# 5. Login to get new token
LOGIN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","password":"pass123456"}')

NEW_TOKEN=$(echo $LOGIN | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
echo "New token: $NEW_TOKEN"
```

---

## Endpoint Summary

| Method | Endpoint | Protected | Description |
|--------|----------|-----------|-------------|
| **Authentication** ||||
| POST | `/auth/signup` | No | Create account |
| POST | `/auth/login` | No | Authenticate user |
| POST | `/auth/logout` | Yes | Logout and blacklist token |
| GET | `/users/profile` | Yes | Get current user profile |
| **Lists** ||||
| GET | `/lists` | No | Get all lists |
| POST | `/lists` | No | Create list |
| GET | `/lists/:id` | No | Get list by ID |
| PATCH | `/lists/:id` | No | Update list |
| DELETE | `/lists/:id` | No | Delete list |
| **Tasks** ||||
| GET | `/lists/:listId/tasks` | No | Get tasks in list |
| POST | `/lists/:listId/tasks` | No | Create task |
| GET | `/tasks/:id` | No | Get task by ID |
| PATCH | `/tasks/:id` | No | Update task |
| DELETE | `/tasks/:id` | No | Delete task |

**Protected endpoints require:** `Authorization: Bearer <token>` header

---

## Token Blacklisting

### Overview

The API implements **token blacklisting** to properly handle logout. When a user logs out, their JWT token is added to a blacklist database, preventing it from being used even if it hasn't expired yet.

### Why JWT_SECRET is Needed

**Question:** Why do we need `JWT_SECRET` in `.env` if signup/login give tokens?

**Answer:**
- `JWT_SECRET` is the **master key** used by the server to sign and verify ALL tokens
- User tokens are **signed using this secret** and given to users
- When users send their token, the server uses `JWT_SECRET` to verify it's authentic

**Analogy:**
- `JWT_SECRET` = The stamp used by a notary to certify documents
- User tokens = Certified documents given to citizens
- The notary (server) uses the stamp to verify documents are real

### How Blacklisting Works

**Without Blacklist:**
1. User logs in → Gets token (valid for 1 hour)
2. User logs out → Client removes token
3. **Problem:** If someone copied the token, they can still use it for up to 1 hour!

**With Blacklist:**
1. User logs in → Gets token (valid for 1 hour)
2. User logs out → Token added to blacklist database
3. **Solution:** Even if someone has the token, server rejects it because it's blacklisted!

### Implementation

**Database Table:**

```sql
CREATE TABLE token_blacklist (
    token TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    blacklisted_at TEXT NOT NULL,
    expires_at TEXT NOT NULL
);
```

**Token Validation Flow:**

1. Extract token from `Authorization: Bearer <token>` header
2. Check if token is in blacklist → Reject if blacklisted
3. Verify JWT signature with `JWT_SECRET`
4. Check token expiration → Reject if expired
5. Allow request if all checks pass

**Automatic Cleanup:**

- Expired tokens are automatically removed from blacklist on every logout
- Keeps database size manageable
- No manual cleanup needed

### Testing Token Blacklisting

**Test Flow:**

```bash
# 1. Sign up and get token
RESPONSE=$(curl -s -X POST http://localhost:8000/api/v1/auth/signup \
  -H "Content-Type: application/json" \
  -d '{"username":"test","email":"test@test.com","password":"pass1234"}')

TOKEN=$(echo $RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
echo "Token: $TOKEN"

# 2. Verify token works
curl -X GET http://localhost:8000/api/v1/users/profile \
  -H "Authorization: Bearer $TOKEN"
# Should return user profile

# 3. Logout (blacklist the token)
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer $TOKEN"
# Should return 204 No Content

# 4. Try to use token again
curl -X GET http://localhost:8000/api/v1/users/profile \
  -H "Authorization: Bearer $TOKEN"
# Should return 401 Unauthorized (token is blacklisted!)
```

### Security Benefits

**Before Blacklisting:**
- ❌ Stolen tokens work until expiry (up to 1 hour)
- ❌ User can't invalidate their own token
- ❌ No way to force logout across devices

**After Blacklisting:**
- ✅ Logout immediately invalidates token
- ✅ Stolen tokens can be blocked
- ✅ Can implement "logout all devices" feature
- ✅ Admin can blacklist compromised tokens

### Database Queries

View blacklisted tokens:

```bash
sqlite3 data/todo.db "SELECT * FROM token_blacklist;"
```

Count blacklisted tokens:

```bash
sqlite3 data/todo.db "SELECT COUNT(*) FROM token_blacklist;"
```

Manually cleanup expired tokens:

```bash
sqlite3 data/todo.db "DELETE FROM token_blacklist WHERE expires_at < datetime('now');"
```

### Performance Impact

- Every protected endpoint checks blacklist (adds ~1-5ms)
- Indexed database lookup is very fast
- Negligible impact for small to medium applications

### FAQ

**Q: What happens if blacklist check fails (database error)?**

A: The token validation continues with normal JWT validation. This prevents a database outage from breaking authentication entirely.

**Q: How long are tokens kept in the blacklist?**

A: Until they expire. Once a token's expiry time passes, it's automatically removed during cleanup.

**Q: Can I manually clear the blacklist?**

A: Yes, you can delete the `token_blacklist` table or specific entries via SQL.

