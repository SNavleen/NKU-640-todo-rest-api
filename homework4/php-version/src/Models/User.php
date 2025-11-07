<?php

namespace TodoApi\Models;

use TodoApi\Services\Database;
use TodoApi\Logger;
use PDO;

class User
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
    }

    public function findByUsername(string $username): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, username, email, password_hash, created_at, updated_at
                FROM users
                WHERE username = :username
            ');

            $stmt->execute(['username' => $username]);
            $result = $stmt->fetch();

            return $result ?: null;

        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch user by username', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to fetch user');
        }
    }

    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, username, email, password_hash, created_at, updated_at
                FROM users
                WHERE email = :email
            ');

            $stmt->execute(['email' => $email]);
            $result = $stmt->fetch();

            return $result ?: null;

        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch user by email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to fetch user');
        }
    }

    public function findById(string $id): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, username, email, created_at, updated_at
                FROM users
                WHERE id = :id
            ');

            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch();

            return $result ?: null;

        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch user by ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to fetch user');
        }
    }

    public function create(array $data): array
    {
        try {
            $id = $this->generateUuid();
            $now = $this->getCurrentTimestamp();

            // Hash password with bcrypt
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $this->db->prepare('
                INSERT INTO users (id, username, email, password_hash, created_at)
                VALUES (:id, :username, :email, :password_hash, :created_at)
            ');

            $stmt->execute([
                'id' => $id,
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => $passwordHash,
                'created_at' => $now,
            ]);

            $this->logger->info('User created', ['id' => $id, 'username' => $data['username']]);

            return $this->findById($id);

        } catch (\PDOException $e) {
            // Check for unique constraint violation
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $this->logger->warning('User creation failed - duplicate', [
                    'username' => $data['username'] ?? null,
                    'email' => $data['email'] ?? null,
                ]);
                throw new \RuntimeException('Username or email already exists');
            }

            $this->logger->error('Failed to create user', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to create user');
        }
    }

    public function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    public function update(string $id, array $data): ?array
    {
        try {
            // Check if user exists
            if (!$this->findById($id)) {
                return null;
            }

            $fields = [];
            $params = ['id' => $id];

            if (isset($data['email'])) {
                $fields[] = 'email = :email';
                $params['email'] = $data['email'];
            }

            if (empty($fields)) {
                return $this->findById($id);
            }

            $fields[] = 'updated_at = :updated_at';
            $params['updated_at'] = $this->getCurrentTimestamp();

            $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $this->findById($id);

        } catch (\PDOException $e) {
            $this->logger->error('Failed to update user', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to update user');
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
