# TODO REST API

This project is a TODO list REST API intended as a personal task manager. The API supports creating, retrieving, updating, and deleting tasks and lists.

## GitHub Pages

- [NKU-640-todo-rest-api](http://todo.snavleen.com/)

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

## Example REST endpoints

##### Lists

- **GET** /lists — return all lists
- **POST** /lists — create a new list
- **GET** /lists/:id — get a single list
- **PATCH** /lists/:id — update a list
- **DELETE** /lists/:id — delete a list

##### Tasks

- **GET** /lists/:listId/tasks — return tasks in a list
- **POST** /lists/:listId/tasks — create a task in a list
- **GET** /tasks/:id — get a single task
- **PATCH** /tasks/:id — update a task (mark complete, change due date, etc.)
- **DELETE** /tasks/:id — delete a task
