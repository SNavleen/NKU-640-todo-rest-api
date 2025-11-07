<?php

namespace TodoApi\Controllers;

use TodoApi\Models\TodoList;
use TodoApi\Services\Validator;
use TodoApi\Router;
use TodoApi\Logger;

class ListController
{
    private TodoList $listModel;
    private Validator $validator;
    private Router $router;
    private Logger $logger;

    public function __construct()
    {
        $this->listModel = new TodoList();
        $this->validator = new Validator();
        $this->router = new Router();
        $this->logger = Logger::getInstance();
    }

    public function getAll(array $params): void
    {
        try {
            $lists = $this->listModel->findAll();

            // Convert snake_case to camelCase for consistency
            $lists = array_map(function ($list) {
                return [
                    'id' => $list['id'],
                    'name' => $list['name'],
                    'description' => $list['description'],
                    'createdAt' => $list['created_at'],
                    'updatedAt' => $list['updated_at'],
                ];
            }, $lists);

            $this->router->sendJson($lists);

        } catch (\Exception $e) {
            $this->logger->error('Error in getAll', ['error' => $e->getMessage()]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to retrieve lists');
        }
    }

    public function getById(array $params): void
    {
        $id = $params['id'] ?? null;

        // Validate UUID format
        if (!$this->isValidUuid($id)) {
            $this->router->sendError(400, 'INVALID_UUID', 'Invalid list ID format');
            return;
        }

        try {
            $list = $this->listModel->findById($id);

            if (!$list) {
                $this->router->sendError(404, 'NOT_FOUND', 'List not found');
                return;
            }

            // Convert snake_case to camelCase
            $response = [
                'id' => $list['id'],
                'name' => $list['name'],
                'description' => $list['description'],
                'createdAt' => $list['created_at'],
                'updatedAt' => $list['updated_at'],
            ];

            $this->router->sendJson($response);

        } catch (\Exception $e) {
            $this->logger->error('Error in getById', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to retrieve list');
        }
    }

    public function create(array $params): void
    {
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
        $data['name'] = Validator::sanitizeString($data['name'] ?? null);
        $data['description'] = Validator::sanitizeString($data['description'] ?? null);

        // Validate input
        $rules = [
            'name' => [
                'required' => true,
                'string' => true,
                'maxLength' => 255,
                'notEmpty' => true,
            ],
            'description' => [
                'string' => true,
                'maxLength' => 1000,
            ],
        ];

        if (!$this->validator->validate($data, $rules)) {
            $this->router->sendError(400, 'VALIDATION_ERROR', $this->validator->getFirstError(), [
                'errors' => $this->validator->getErrors(),
            ]);
            return;
        }

        try {
            $list = $this->listModel->create($data);

            // Convert snake_case to camelCase
            $response = [
                'id' => $list['id'],
                'name' => $list['name'],
                'description' => $list['description'],
                'createdAt' => $list['created_at'],
                'updatedAt' => $list['updated_at'],
            ];

            $this->router->sendJson($response, 201);

        } catch (\Exception $e) {
            $this->logger->error('Error in create', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to create list');
        }
    }

    public function update(array $params): void
    {
        $id = $params['id'] ?? null;

        // Validate UUID format
        if (!$this->isValidUuid($id)) {
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

        // Ensure at least one field is provided
        if (empty($data)) {
            $this->router->sendError(400, 'VALIDATION_ERROR', 'At least one field must be provided');
            return;
        }

        // Sanitize input
        if (isset($data['name'])) {
            $data['name'] = Validator::sanitizeString($data['name']);
        }
        if (isset($data['description'])) {
            $data['description'] = Validator::sanitizeString($data['description']);
        }

        // Validate input
        $rules = [];
        if (isset($data['name'])) {
            $rules['name'] = [
                'string' => true,
                'maxLength' => 255,
                'notEmpty' => true,
            ];
        }
        if (isset($data['description'])) {
            $rules['description'] = [
                'string' => true,
                'maxLength' => 1000,
            ];
        }

        if (!empty($rules) && !$this->validator->validate($data, $rules)) {
            $this->router->sendError(400, 'VALIDATION_ERROR', $this->validator->getFirstError(), [
                'errors' => $this->validator->getErrors(),
            ]);
            return;
        }

        try {
            $list = $this->listModel->update($id, $data);

            if (!$list) {
                $this->router->sendError(404, 'NOT_FOUND', 'List not found');
                return;
            }

            // Convert snake_case to camelCase
            $response = [
                'id' => $list['id'],
                'name' => $list['name'],
                'description' => $list['description'],
                'createdAt' => $list['created_at'],
                'updatedAt' => $list['updated_at'],
            ];

            $this->router->sendJson($response);

        } catch (\Exception $e) {
            $this->logger->error('Error in update', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to update list');
        }
    }

    public function delete(array $params): void
    {
        $id = $params['id'] ?? null;

        // Validate UUID format
        if (!$this->isValidUuid($id)) {
            $this->router->sendError(400, 'INVALID_UUID', 'Invalid list ID format');
            return;
        }

        try {
            $deleted = $this->listModel->delete($id);

            if (!$deleted) {
                $this->router->sendError(404, 'NOT_FOUND', 'List not found');
                return;
            }

            http_response_code(204);

        } catch (\Exception $e) {
            $this->logger->error('Error in delete', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to delete list');
        }
    }

    private function isValidUuid(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }
}
