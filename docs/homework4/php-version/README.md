# TODO REST API - PHP

A secure REST API for managing TODO lists and tasks with JWT authentication.

## Table of Contents

- [Quick Start](#quick-start)
- [Installation](#installation)
- [Configuration](#configuration)
- [Testing](#testing)
- [API Endpoints](#api-endpoints)
- [Features](#features)
- [Development](#development)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)

---

## Quick Start

### Prerequisites

```bash
# Install PHP 8.1+ and Composer
brew install php composer
```

### Installation

```bash
cd ./docs/homework4/php-version
composer install
```

### Configuration

1. Generate a JWT secret:

```bash
openssl rand -base64 32
```

2. Update `.env`:

```bash
JWT_SECRET=your-generated-secret-here
JWT_EXPIRY=3600
DEBUG_MODE=true
```

### Run Server

**Option 1: Using Composer (recommended)**

```bash
composer serve
```

**Option 2: Using PHP built-in server**

```bash
php -S localhost:8000 -t public
```

API available at: `http://localhost:8000`

### Verify Server

```bash
# Test with a simple GET request
curl http://localhost:8000/api/v1/lists
# Should return: []
```

---

## Testing

### Option 1: Bruno (Recommended)

1. Download Bruno from <https://www.usebruno.com/>
2. Open Bruno and import collection from `bruno/` folder
3. Select "local" environment
4. Run requests in order:
   - Auth > Signup (copy the token)
   - Auth > Get Profile (paste token)
   - Lists > Create List (copy list ID)
   - Tasks > Create Task (use list ID)

### Option 2: curl Examples

#### Authentication Flow

**1. Create Account:**

```bash
curl -X POST http://localhost:8000/api/v1/auth/signup \
  -H "Content-Type: application/json" \
  -d '{
    "username": "alice",
    "email": "alice@example.com",
    "password": "password123"
  }'
```

**Expected Response (201):**

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

**Save the token for next steps!**

**2. Login:**

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "alice",
    "password": "password123"
  }'
```

**3. Get Profile (Protected):**

```bash
TOKEN="your-token-here"
curl -X GET http://localhost:8000/api/v1/users/profile \
  -H "Authorization: Bearer $TOKEN"
```

**4. Logout (Blacklist Token):**

```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer $TOKEN"
```

After logout, the token cannot be used again!

#### List and Task Operations

**1. Create List:**

```bash
curl -X POST http://localhost:8000/api/v1/lists \
  -H "Content-Type: application/json" \
  -d '{"name":"Groceries","description":"Weekly shopping"}'
```

**Save the returned list ID!**

**2. Get All Lists:**

```bash
curl http://localhost:8000/api/v1/lists
```

**3. Create Task:**

```bash
# Replace LIST_ID with actual ID
curl -X POST http://localhost:8000/api/v1/lists/LIST_ID/tasks \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Buy milk",
    "priority": "high",
    "dueDate": "2025-11-07T18:00:00Z"
  }'
```

**4. Update Task:**

```bash
# Replace TASK_ID with actual ID
curl -X PATCH http://localhost:8000/api/v1/tasks/TASK_ID \
  -H "Content-Type: application/json" \
  -d '{"completed":true}'
```

**5. Delete Task:**

```bash
curl -X DELETE http://localhost:8000/api/v1/tasks/TASK_ID
```

**6. Delete List:**

```bash
curl -X DELETE http://localhost:8000/api/v1/lists/LIST_ID
```

### Automated Test Script

Save as `test_api.sh`:

```bash
#!/bin/bash

echo "=== Testing TODO REST API ==="

# 1. Sign up
echo "1. Signing up..."
SIGNUP=$(curl -s -X POST http://localhost:8000/api/v1/auth/signup \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","email":"alice@test.com","password":"pass123456"}')

TOKEN=$(echo $SIGNUP | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
echo "Token: ${TOKEN:0:20}..."

# 2. Create list
echo "2. Creating list..."
LIST=$(curl -s -X POST http://localhost:8000/api/v1/lists \
  -H "Content-Type: application/json" \
  -d '{"name":"Test List"}')

LIST_ID=$(echo $LIST | grep -o '"id":"[^"]*"' | cut -d'"' -f4)
echo "List ID: $LIST_ID"

# 3. Create task
echo "3. Creating task..."
TASK=$(curl -s -X POST http://localhost:8000/api/v1/lists/$LIST_ID/tasks \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Task","priority":"high"}')

TASK_ID=$(echo $TASK | grep -o '"id":"[^"]*"' | cut -d'"' -f4)
echo "Task ID: $TASK_ID"

# 4. Update task
echo "4. Updating task..."
curl -s -X PATCH http://localhost:8000/api/v1/tasks/$TASK_ID \
  -H "Content-Type: application/json" \
  -d '{"completed":true}' | jq

# 5. Cleanup
echo "5. Cleaning up..."
curl -s -X DELETE http://localhost:8000/api/v1/tasks/$TASK_ID
curl -s -X DELETE http://localhost:8000/api/v1/lists/$LIST_ID

echo "=== Test Complete ==="
```

Run with:

```bash
chmod +x test_api.sh
./test_api.sh
```

### Run Unit Tests

```bash
composer test
```

**Expected:** 39 tests should pass

---

## API Endpoints

See [API.md](API.md) for complete endpoint documentation.

### Quick Reference

| Method | Endpoint | Protected | Description |
|--------|----------|-----------|-------------|
| **Authentication** ||||
| POST | `/api/v1/auth/signup` | No | Create account |
| POST | `/api/v1/auth/login` | No | Login |
| POST | `/api/v1/auth/logout` | Yes | Logout (blacklist token) |
| GET | `/api/v1/users/profile` | Yes | Get profile |
| **Lists** ||||
| GET | `/api/v1/lists` | No | Get all lists |
| GET | `/api/v1/lists/:id` | No | Get list by ID |
| POST | `/api/v1/lists` | No | Create list |
| PATCH | `/api/v1/lists/:id` | No | Update list |
| DELETE | `/api/v1/lists/:id` | No | Delete list |
| **Tasks** ||||
| GET | `/api/v1/lists/:listId/tasks` | No | Get tasks in list |
| GET | `/api/v1/tasks/:id` | No | Get task by ID |
| POST | `/api/v1/lists/:listId/tasks` | No | Create task |
| PATCH | `/api/v1/tasks/:id` | No | Update task |
| DELETE | `/api/v1/tasks/:id` | No | Delete task |

**Protected endpoints require:** `Authorization: Bearer <token>` header

---

## Features

### Security

- ✅ **Bcrypt password hashing** (cost: 12)
- ✅ **JWT authentication** with 1-hour expiry
- ✅ **Token blacklisting** on logout
- ✅ **SQL injection prevention** (prepared statements)
- ✅ **XSS protection** (HTML escaping)
- ✅ **Input validation** on all endpoints

### Database

- SQLite file-based database
- Foreign key constraints
- Cascade deletes
- Automatic timestamps

### Logging

- Request/response logging
- Error tracking
- Debug mode toggle

---

## Project Structure

```text
php-version/
├── public/index.php      # Entry point
├── src/
│   ├── Controllers/      # Request handlers
│   ├── Models/           # Database operations
│   └── Services/         # Business logic
├── bruno/                # API test collection (14 requests)
├── tests/                # Unit tests (39 tests)
├── data/                 # SQLite database
├── logs/                 # API logs
└── .env                  # Configuration
```

---

## Development

### View Logs

```bash
# Real-time monitoring
tail -f logs/api.log

# View last 50 lines
tail -n 50 logs/api.log

# Search for errors
grep ERROR logs/api.log
```

**Log Format:**

```text
[2025-11-06T10:00:00Z] INFO: GET /api/v1/lists - 200 OK - 15ms
[2025-11-06T10:01:00Z] ERROR: Failed to fetch list - Error details...
```

### Inspect Database

```bash
sqlite3 data/todo.db

# View tables
.tables

# View lists
SELECT * FROM lists;

# View tasks
SELECT * FROM tasks;

# View users
SELECT * FROM users;

# View blacklisted tokens
SELECT * FROM token_blacklist;

# Exit
.quit
```

### Reset Database

```bash
rm data/todo.db
# Will be recreated on next request
```

---

## Deployment

### For Production

1. **Update `.env`:**

```bash
DEBUG_MODE=false
LOG_LEVEL=error
JWT_SECRET=strong-random-secret-here
```

2. **Generate strong JWT secret:**

```bash
openssl rand -base64 32
```

3. **Configure Nginx:**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/php-version/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

4. **Use HTTPS** (required for production)

5. **Set proper permissions:**

```bash
chmod 755 logs data
chmod 644 .env
```

---

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DEBUG_MODE` | `true` | Show detailed errors |
| `LOG_LEVEL` | `debug` | Logging level (debug/info/warning/error) |
| `DATABASE_PATH` | `data/todo.db` | SQLite database path |
| `API_VERSION` | `v1` | API version |
| `JWT_SECRET` | Required | Secret key for JWT signing |
| `JWT_EXPIRY` | `3600` | Token lifetime in seconds |

---

## Troubleshooting

### Installation Issues

**Issue: Composer not found**

```bash
brew install composer
```

**Issue: PHP not found**

```bash
brew install php
```

**Issue: SQLite extension not loaded**

```bash
# Verify SQLite is available
php -m | grep sqlite

# If missing, reinstall PHP
brew reinstall php
```

**Issue: Permission denied on logs/ or data/**

```bash
chmod 755 logs data
```

### Server Issues

**Issue: Port 8000 in use**

```bash
# Use a different port
php -S localhost:8080 -t public

# Update Bruno environment to http://localhost:8080
```

**Issue: Database locked**

```bash
# Check for other processes using the database
lsof data/todo.db

# If needed, restart the server
```

### Authentication Issues

**Issue: Token validation fails**

Causes and solutions:

1. **JWT_SECRET not set in `.env`**
   - Verify `.env` file exists
   - Check `JWT_SECRET` is not empty

2. **Token expired**
   - Tokens expire after 1 hour
   - Login again to get a new token

3. **Token blacklisted**
   - Tokens are blacklisted after logout
   - Login again to get a new token

4. **Wrong Authorization header format**
   - Must be: `Authorization: Bearer TOKEN`
   - Not: `Authorization: TOKEN`

**Issue: 401 Unauthorized**

```bash
# Ensure token is in Authorization header
curl -H "Authorization: Bearer YOUR_TOKEN" ...
```

**Issue: 409 Conflict (duplicate username/email)**

Use a different username or email

### Testing Issues

**Issue: Unit tests fail**

```bash
# Reset test database
rm data/test_todo.db
composer test
```

**Issue: curl commands don't work**

```bash
# Verify server is running
curl http://localhost:8000/api/v1/lists

# Check for syntax errors in curl command
# Ensure proper escaping of quotes
```

---

## Testing Checklist

### Authentication

- [ ] Sign up with valid data
- [ ] Sign up with duplicate username (should fail)
- [ ] Sign up with invalid email (should fail)
- [ ] Sign up with short password (should fail)
- [ ] Login with correct credentials
- [ ] Login with wrong password (should fail)
- [ ] Access profile with valid token
- [ ] Access profile without token (should fail)
- [ ] Access profile with expired token (should fail)
- [ ] Logout and verify token is blacklisted

### Lists & Tasks

- [ ] Create a list
- [ ] Get all lists
- [ ] Update a list
- [ ] Create a task in the list
- [ ] Mark task as completed
- [ ] Delete task
- [ ] Delete list (should cascade delete tasks)

### Error Cases

- [ ] Invalid UUID format (should return 400)
- [ ] Missing required field (should return 400)
- [ ] Invalid Content-Type (should return 415)
- [ ] Non-existent resource (should return 404)
- [ ] Invalid enum value (should return 400)

---

## Documentation

- **API.md** - Complete API reference with all endpoints
- **TOKEN_BLACKLIST.md** - Token blacklisting explanation and JWT_SECRET clarification
- **IMPLEMENTATION_SUMMARY.md** - Project implementation summary

---

## Support

- **API Reference:** [API.md](API.md)
- **Logs:** `logs/api.log`
- **Database:** `data/todo.db`
- **Unit Tests:** `composer test`

---

## License

NKU-640 Coursework Project
