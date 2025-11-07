<?php

namespace TodoApi\Models;

use TodoApi\Services\Database;
use TodoApi\Logger;
use PDO;

class Task
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
    }

    public function findAllByListId(string $listId): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, list_id, title, description, completed, due_date,
                       priority, categories, created_at, updated_at
                FROM tasks
                WHERE list_id = :list_id
                ORDER BY created_at DESC
            ');

            $stmt->execute(['list_id' => $listId]);
            $tasks = $stmt->fetchAll();

            // Decode categories from JSON
            return array_map([$this, 'formatTask'], $tasks);

        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch tasks by list ID', [
                'list_id' => $listId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to fetch tasks');
        }
    }

    public function findById(string $id): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, list_id, title, description, completed, due_date,
                       priority, categories, created_at, updated_at
                FROM tasks
                WHERE id = :id
            ');

            $stmt->execute(['id' => $id]);
            $task = $stmt->fetch();

            if (!$task) {
                return null;
            }

            return $this->formatTask($task);

        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch task by ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to fetch task');
        }
    }

    public function create(string $listId, array $data): array
    {
        try {
            $id = $this->generateUuid();
            $now = $this->getCurrentTimestamp();

            $stmt = $this->db->prepare('
                INSERT INTO tasks (
                    id, list_id, title, description, completed,
                    due_date, priority, categories, created_at
                )
                VALUES (
                    :id, :list_id, :title, :description, :completed,
                    :due_date, :priority, :categories, :created_at
                )
            ');

            $stmt->execute([
                'id' => $id,
                'list_id' => $listId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'completed' => $data['completed'] ?? false,
                'due_date' => $data['dueDate'] ?? null,
                'priority' => $data['priority'] ?? null,
                'categories' => isset($data['categories']) ? json_encode($data['categories']) : null,
                'created_at' => $now,
            ]);

            return $this->findById($id);

        } catch (\PDOException $e) {
            $this->logger->error('Failed to create task', [
                'list_id' => $listId,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to create task');
        }
    }

    public function update(string $id, array $data): ?array
    {
        try {
            // Check if task exists
            if (!$this->findById($id)) {
                return null;
            }

            $fields = [];
            $params = ['id' => $id];

            if (isset($data['title'])) {
                $fields[] = 'title = :title';
                $params['title'] = $data['title'];
            }

            if (isset($data['description'])) {
                $fields[] = 'description = :description';
                $params['description'] = $data['description'];
            }

            if (isset($data['completed'])) {
                $fields[] = 'completed = :completed';
                $params['completed'] = $data['completed'];
            }

            if (array_key_exists('dueDate', $data)) {
                $fields[] = 'due_date = :due_date';
                $params['due_date'] = $data['dueDate'];
            }

            if (array_key_exists('priority', $data)) {
                $fields[] = 'priority = :priority';
                $params['priority'] = $data['priority'];
            }

            if (isset($data['categories'])) {
                $fields[] = 'categories = :categories';
                $params['categories'] = json_encode($data['categories']);
            }

            if (empty($fields)) {
                return $this->findById($id);
            }

            $fields[] = 'updated_at = :updated_at';
            $params['updated_at'] = $this->getCurrentTimestamp();

            $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $this->findById($id);

        } catch (\PDOException $e) {
            $this->logger->error('Failed to update task', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to update task');
        }
    }

    public function delete(string $id): bool
    {
        try {
            // Check if task exists
            if (!$this->findById($id)) {
                return false;
            }

            $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = :id');
            $stmt->execute(['id' => $id]);

            $this->logger->info('Task deleted', ['id' => $id]);
            return true;

        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete task', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to delete task');
        }
    }

    private function formatTask(array $task): array
    {
        // Convert completed from integer to boolean
        $task['completed'] = (bool) $task['completed'];

        // Decode categories from JSON
        if ($task['categories']) {
            $task['categories'] = json_decode($task['categories'], true);
        }

        // Convert snake_case to camelCase for API response
        return [
            'id' => $task['id'],
            'listId' => $task['list_id'],
            'title' => $task['title'],
            'description' => $task['description'],
            'completed' => $task['completed'],
            'dueDate' => $task['due_date'],
            'priority' => $task['priority'],
            'categories' => $task['categories'],
            'createdAt' => $task['created_at'],
            'updatedAt' => $task['updated_at'],
        ];
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    private function getCurrentTimestamp(): string
    {
        return date('c'); // ISO 8601 format
    }
}
