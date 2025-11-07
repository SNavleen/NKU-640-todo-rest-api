<?php

namespace TodoApi\Models;

use TodoApi\Services\Database;
use TodoApi\Logger;
use PDO;

class TodoList
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
    }

    public function findAll(): array
    {
        try {
            $stmt = $this->db->query('
                SELECT id, name, description, created_at, updated_at
                FROM lists
                ORDER BY created_at DESC
            ');

            return $stmt->fetchAll();

        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch all lists', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to fetch lists');
        }
    }

    public function findById(string $id): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, name, description, created_at, updated_at
                FROM lists
                WHERE id = :id
            ');

            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch();

            return $result ?: null;

        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch list by ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to fetch list');
        }
    }

    public function create(array $data): array
    {
        try {
            $id = $this->generateUuid();
            $now = $this->getCurrentTimestamp();

            $stmt = $this->db->prepare('
                INSERT INTO lists (id, name, description, created_at)
                VALUES (:id, :name, :description, :created_at)
            ');

            $stmt->execute([
                'id' => $id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'created_at' => $now,
            ]);

            return $this->findById($id);

        } catch (\PDOException $e) {
            $this->logger->error('Failed to create list', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to create list');
        }
    }

    public function update(string $id, array $data): ?array
    {
        try {
            // Check if list exists
            if (!$this->findById($id)) {
                return null;
            }

            $fields = [];
            $params = ['id' => $id];

            if (isset($data['name'])) {
                $fields[] = 'name = :name';
                $params['name'] = $data['name'];
            }

            if (isset($data['description'])) {
                $fields[] = 'description = :description';
                $params['description'] = $data['description'];
            }

            if (empty($fields)) {
                return $this->findById($id);
            }

            $fields[] = 'updated_at = :updated_at';
            $params['updated_at'] = $this->getCurrentTimestamp();

            $sql = 'UPDATE lists SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $this->findById($id);

        } catch (\PDOException $e) {
            $this->logger->error('Failed to update list', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to update list');
        }
    }

    public function delete(string $id): bool
    {
        try {
            // Check if list exists
            if (!$this->findById($id)) {
                return false;
            }

            $stmt = $this->db->prepare('DELETE FROM lists WHERE id = :id');
            $stmt->execute(['id' => $id]);

            $this->logger->info('List deleted', ['id' => $id]);
            return true;

        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete list', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to delete list');
        }
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
