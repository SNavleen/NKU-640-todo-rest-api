# TODO REST API

This project is a TODO list REST API intended as a personal task manager. The API supports creating, retrieving, updating, and deleting tasks and lists.

##### Github Page: [NKU-640-todo-rest-api](https://snavleen.github.io/NKU-640/)

## Plan

The project plan is available here: [HW4 Plan (stage 1)](HW4_plan_stage1.md)

## Core features

- Create, read, update and delete (CRUD) tasks
- Mark tasks as complete/incomplete
- Set due dates for tasks
- Assign priorities to tasks (e.g. low, medium, high)
- Assign categories or tags to tasks
- Support multiple TODO lists (e.g. "groceries", "work")

## Possible future features

- User authentication and per-user lists
- Recurring tasks and reminders
- Search and filtering (by due date, priority, category)
- Import/export of tasks (CSV/JSON)
- Web UI and integrations (voice assistant, calendar)

## Data model (example)

##### List

- `id` — `string` (UUID)
- `name` — `string`
- `description` — `string` (optional)

##### Task

- `id` — `string` (UUID)
- `listId` — `string` (UUID) — id of the list this task belongs to
- `title` — `string`
- `description` — `string` (optional)
- `completed` — `boolean`
- `dueDate` — `string` (ISO 8601, optional)
- `priority` — `string` (optional, e.g. `low`, `medium`, `high`)
- `categories` — `string[]` (optional)
- `createdAt` — `string` (ISO 8601)
- `updatedAt` — `string` (ISO 8601, optional)

## Example REST endpoints (grouped by HTTP method)

### GET endpoints

- **GET /lists** — Return all lists.
  
  **Response** (200):

  ```json
  [ { "id": "a1b2c3d4", "name": "Groceries", "description": "Weekly shopping list", "createdAt": "2025-10-31T12:00:00Z" } ]
  ```

- **GET /lists/:id** — Get a single list by id.
  
  **Response** (200):

  ```json
  { "id": "a1b2c3d4", "name": "Groceries", "description": "Weekly shopping list" }
  ```

- **GET /lists/:listId/tasks** — Return tasks in a list.
  
  **Response** (200):

  ```json
  [ { "id": "t123", "listId": "a1b2c3d4", "title": "Buy milk", "completed": false, "dueDate": "2025-11-01T18:00:00Z", "priority": "medium" } ]
  ```

- **GET /tasks/:id** — Get a single task.
  
  **Response** (200):

  ```json
  { "id": "t123", "listId": "a1b2c3d4", "title": "Buy milk", "completed": false, "dueDate": "2025-11-01T18:00:00Z", "priority": "medium" }
  ```

- **GET /users/:id** — Get user profile (protected; requires bearer token).
  
  **Response** (200):

  ```json
  { "id": "u1", "username": "alice", "email": "alice@example.com" }
  ```

### POST endpoints

- **POST /lists** — Create a new list.
  
  **Request** (JSON):

  ```json
  { "name": "Groceries", "description": "Weekly shopping list" }
  ```

  **Response** (201):

  ```json
  { "id": "a1b2c3d4", "name": "Groceries", "description": "Weekly shopping list", "createdAt": "2025-10-31T12:00:00Z" }
  ```

- **POST /lists/:listId/tasks** — Create a task in a list.
  
  **Request** (JSON):

  ```json
  {
    "title": "Buy milk",
    "description": "2 liters, skim",
    "dueDate": "2025-11-01T18:00:00Z",
    "priority": "medium",
    "categories": ["groceries"]
  }
  ```

  **Response** (201):

  ```json
  { "id": "t123", "listId": "a1b2c3d4", "title": "Buy milk", "description": "2 liters, skim", "completed": false, "dueDate": "2025-11-01T18:00:00Z", "priority": "medium", "categories": ["groceries"], "createdAt": "2025-10-31T12:05:00Z" }
  ```

- **POST /users** — Create a new user (signup).
  
  **Request** (JSON):

  ```json
  { "username": "alice", "password": "s3cr3t", "email": "alice@example.com" }
  ```

  **Response** (201):

  ```json
  { "id": "u1", "username": "alice", "email": "alice@example.com", "createdAt": "2025-10-31T12:10:00Z" }
  ```

- **POST /auth/login** — Authenticate and obtain a token/session.
  
  **Request** (JSON):

  ```json
  { "username": "alice", "password": "s3cr3t" }
  ```

  **Response** (200):

  ```json
  { "token": "<jwt-or-session-id>", "user": { "id": "u1", "username": "alice", "email": "alice@example.com" } }
  ```

- **POST /auth/logout** — Invalidate current session/token (optional).
  
  **Response** (204): No Content

### PATCH endpoints

- **PATCH /lists/:id** — Update a list.
  
  **Request** (JSON):

  ```json
  { "description": "Updated description" }
  ```

  **Response** (200):

  ```json
  { "id": "a1b2c3d4", "name": "Groceries", "description": "Updated description", "updatedAt": "2025-10-31T13:00:00Z" }
  ```

- **PATCH /tasks/:id** — Update a task (mark complete, change due date, etc.).
  
  **Request** (JSON):

  ```json
  { "completed": true }
  ```

  **Response** (200):

  ```json
  { "id": "t123", "completed": true, "updatedAt": "2025-10-31T12:15:00Z" }
  ```

- **PATCH /users/:id** — Update user profile (protected; requires bearer token).
  
  **Request** (JSON):

  ```json
  { "email": "new@example.com" }
  ```

  **Response** (200):

  ```json
  { "id": "u1", "username": "alice", "email": "new@example.com", "updatedAt": "2025-10-31T12:20:00Z" }
  ```

### DELETE endpoints

- **DELETE /lists/:id** — Delete a list.

  **Response** (204): No Content

- **DELETE /tasks/:id** — Delete a task.

  **Response** (204): No Content

Notes:

- Protect user and auth endpoints with authentication (JWT or session) and use HTTPS in production.
- Store passwords hashed with a strong algorithm (for example, bcrypt) and never return plaintext passwords in responses.
