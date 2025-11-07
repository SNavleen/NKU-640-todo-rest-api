<?php

namespace TodoApi\Controllers;

use TodoApi\Models\Task;
use TodoApi\Models\TodoList;
use TodoApi\Services\Validator;
use TodoApi\Router;
use TodoApi\Logger;

class TaskController
{
    private Task $taskModel;
    private TodoList $listModel;
    private Validator $validator;
    private Router $router;
    private Logger $logger;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->listModel = new TodoList();
        $this->validator = new Validator();
        $this->router = new Router();
        $this->logger = Logger::getInstance();
    }

    public function getAllByList(array $params): void
    {
        $listId = $params['listId'] ?? null;

        // Validate UUID format
        if (!$this->isValidUuid($listId)) {
            $this->router->sendError(400, 'INVALID_UUID', 'Invalid list ID format');
            return;
        }

        try {
            // Check if list exists
            $list = $this->listModel->findById($listId);
            if (!$list) {
                $this->router->sendError(404, 'NOT_FOUND', 'List not found');
                return;
            }

            $tasks = $this->taskModel->findAllByListId($listId);
            $this->router->sendJson($tasks);

        } catch (\Exception $e) {
            $this->logger->error('Error in getAllByList', [
                'listId' => $listId,
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to retrieve tasks');
        }
    }

    public function getById(array $params): void
    {
        $id = $params['id'] ?? null;

        // Validate UUID format
        if (!$this->isValidUuid($id)) {
            $this->router->sendError(400, 'INVALID_UUID', 'Invalid task ID format');
            return;
        }

        try {
            $task = $this->taskModel->findById($id);

            if (!$task) {
                $this->router->sendError(404, 'NOT_FOUND', 'Task not found');
                return;
            }

            $this->router->sendJson($task);

        } catch (\Exception $e) {
            $this->logger->error('Error in getById', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to retrieve task');
        }
    }

    public function create(array $params): void
    {
        $listId = $params['listId'] ?? null;

        // Validate UUID format
        if (!$this->isValidUuid($listId)) {
            $this->router->sendError(400, 'INVALID_UUID', 'Invalid list ID format');
            return;
        }

        // Validate Content-Type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            $this->router->sendError(415, 'UNSUPPORTED_MEDIA_TYPE', 'Content-Type must be application/json');
            return;
        }

        // Parse JSON body
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->router->sendError(400, 'INVALID_JSON', 'Invalid JSON in request body');
            return;
        }

        // Sanitize input
        $data['title'] = Validator::sanitizeString($data['title'] ?? null);
        $data['description'] = Validator::sanitizeString($data['description'] ?? null);
        $data['priority'] = Validator::sanitizeString($data['priority'] ?? null);
        if (isset($data['categories'])) {
            $data['categories'] = Validator::sanitizeArray($data['categories']);
        }

        // Validate input
        $rules = [
            'title' => [
                'required' => true,
                'string' => true,
                'maxLength' => 255,
                'notEmpty' => true,
            ],
            'description' => [
                'string' => true,
                'maxLength' => 2000,
            ],
            'completed' => [
                'boolean' => true,
            ],
            'dueDate' => [
                'datetime' => true,
            ],
            'priority' => [
                'enum' => ['low', 'medium', 'high'],
            ],
            'categories' => [
                'array' => true,
                'maxItems' => 10,
                'arrayItemMaxLength' => 50,
            ],
        ];

        if (!$this->validator->validate($data, $rules)) {
            $this->router->sendError(400, 'VALIDATION_ERROR', $this->validator->getFirstError(), [
                'errors' => $this->validator->getErrors(),
            ]);
            return;
        }

        try {
            // Check if list exists
            $list = $this->listModel->findById($listId);
            if (!$list) {
                $this->router->sendError(404, 'NOT_FOUND', 'List not found');
                return;
            }

            $task = $this->taskModel->create($listId, $data);
            $this->router->sendJson($task, 201);

        } catch (\Exception $e) {
            $this->logger->error('Error in create', [
                'listId' => $listId,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to create task');
        }
    }

    public function update(array $params): void
    {
        $id = $params['id'] ?? null;

        // Validate UUID format
        if (!$this->isValidUuid($id)) {
            $this->router->sendError(400, 'INVALID_UUID', 'Invalid task ID format');
            return;
        }

        // Validate Content-Type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            $this->router->sendError(415, 'UNSUPPORTED_MEDIA_TYPE', 'Content-Type must be application/json');
            return;
        }

        // Parse JSON body
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->router->sendError(400, 'INVALID_JSON', 'Invalid JSON in request body');
            return;
        }

        // Ensure at least one field is provided
        if (empty($data)) {
            $this->router->sendError(400, 'VALIDATION_ERROR', 'At least one field must be provided');
            return;
        }

        // Sanitize input
        if (isset($data['title'])) {
            $data['title'] = Validator::sanitizeString($data['title']);
        }
        if (isset($data['description'])) {
            $data['description'] = Validator::sanitizeString($data['description']);
        }
        if (isset($data['priority'])) {
            $data['priority'] = Validator::sanitizeString($data['priority']);
        }
        if (isset($data['categories'])) {
            $data['categories'] = Validator::sanitizeArray($data['categories']);
        }

        // Validate input
        $rules = [];
        if (isset($data['title'])) {
            $rules['title'] = [
                'string' => true,
                'maxLength' => 255,
                'notEmpty' => true,
            ];
        }
        if (isset($data['description'])) {
            $rules['description'] = [
                'string' => true,
                'maxLength' => 2000,
            ];
        }
        if (isset($data['completed'])) {
            $rules['completed'] = [
                'boolean' => true,
            ];
        }
        if (array_key_exists('dueDate', $data) && $data['dueDate'] !== null) {
            $rules['dueDate'] = [
                'datetime' => true,
            ];
        }
        if (array_key_exists('priority', $data) && $data['priority'] !== null) {
            $rules['priority'] = [
                'enum' => ['low', 'medium', 'high'],
            ];
        }
        if (isset($data['categories'])) {
            $rules['categories'] = [
                'array' => true,
                'maxItems' => 10,
                'arrayItemMaxLength' => 50,
            ];
        }

        if (!empty($rules) && !$this->validator->validate($data, $rules)) {
            $this->router->sendError(400, 'VALIDATION_ERROR', $this->validator->getFirstError(), [
                'errors' => $this->validator->getErrors(),
            ]);
            return;
        }

        try {
            $task = $this->taskModel->update($id, $data);

            if (!$task) {
                $this->router->sendError(404, 'NOT_FOUND', 'Task not found');
                return;
            }

            $this->router->sendJson($task);

        } catch (\Exception $e) {
            $this->logger->error('Error in update', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to update task');
        }
    }

    public function delete(array $params): void
    {
        $id = $params['id'] ?? null;

        // Validate UUID format
        if (!$this->isValidUuid($id)) {
            $this->router->sendError(400, 'INVALID_UUID', 'Invalid task ID format');
            return;
        }

        try {
            $deleted = $this->taskModel->delete($id);

            if (!$deleted) {
                $this->router->sendError(404, 'NOT_FOUND', 'Task not found');
                return;
            }

            http_response_code(204);

        } catch (\Exception $e) {
            $this->logger->error('Error in delete', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to delete task');
        }
    }

    private function isValidUuid(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }
}
