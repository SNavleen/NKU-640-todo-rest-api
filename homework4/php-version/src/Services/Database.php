<?php

namespace TodoApi\Services;

use TodoApi\Config;
use TodoApi\Logger;
use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private Logger $logger;
    private string $dbPath;

    private function __construct()
    {
        $config = Config::getInstance();
        $this->logger = Logger::getInstance();
        $this->dbPath = $config->getDatabasePath();

        $this->connect();
        $this->initializeTables();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        try {
            // Ensure database directory exists
            $dbDir = dirname($this->dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            $this->connection = new PDO(
                'sqlite:' . $this->dbPath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            // Enable foreign keys in SQLite
            $this->connection->exec('PRAGMA foreign_keys = ON');

            $this->logger->info('Database connection established', ['path' => $this->dbPath]);

        } catch (PDOException $e) {
            $this->logger->error('Database connection failed', [
                'error' => $e->getMessage(),
                'path' => $this->dbPath,
            ]);
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    private function initializeTables(): void
    {
        try {
            // Create lists table
            $this->connection->exec('
                CREATE TABLE IF NOT EXISTS lists (
                    id TEXT PRIMARY KEY,
                    name TEXT NOT NULL,
                    description TEXT,
                    created_at TEXT NOT NULL,
                    updated_at TEXT
                )
            ');

            // Create tasks table
            $this->connection->exec('
                CREATE TABLE IF NOT EXISTS tasks (
                    id TEXT PRIMARY KEY,
                    list_id TEXT NOT NULL,
                    title TEXT NOT NULL,
                    description TEXT,
                    completed INTEGER DEFAULT 0,
                    due_date TEXT,
                    priority TEXT,
                    categories TEXT,
                    created_at TEXT NOT NULL,
                    updated_at TEXT,
                    FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
                )
            ');

            // Create index on list_id for faster queries
            $this->connection->exec('
                CREATE INDEX IF NOT EXISTS idx_tasks_list_id ON tasks(list_id)
            ');

            // Create users table
            $this->connection->exec('
                CREATE TABLE IF NOT EXISTS users (
                    id TEXT PRIMARY KEY,
                    username TEXT NOT NULL UNIQUE,
                    email TEXT NOT NULL UNIQUE,
                    password_hash TEXT NOT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT
                )
            ');

            // Create index on username and email for faster lookups
            $this->connection->exec('
                CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)
            ');
            $this->connection->exec('
                CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)
            ');

            $this->logger->info('Database tables initialized');

        } catch (PDOException $e) {
            $this->logger->error('Table initialization failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Table initialization failed: ' . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}
