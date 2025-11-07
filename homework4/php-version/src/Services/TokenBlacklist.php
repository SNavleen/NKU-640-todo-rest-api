<?php

namespace TodoApi\Services;

use TodoApi\Services\Database;
use TodoApi\Logger;
use PDO;

class TokenBlacklist
{
    private static ?TokenBlacklist $instance = null;
    private PDO $db;
    private Logger $logger;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
        $this->initializeTable();
    }

    public static function getInstance(): TokenBlacklist
    {
        if (self::$instance === null) {
            self::$instance = new TokenBlacklist();
        }
        return self::$instance;
    }

    private function initializeTable(): void
    {
        try {
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS token_blacklist (
                    token TEXT PRIMARY KEY,
                    user_id TEXT NOT NULL,
                    blacklisted_at TEXT NOT NULL,
                    expires_at TEXT NOT NULL
                )
            ');

            // Create index for faster lookups
            $this->db->exec('
                CREATE INDEX IF NOT EXISTS idx_token_blacklist_token ON token_blacklist(token)
            ');

            // Create index for cleanup queries
            $this->db->exec('
                CREATE INDEX IF NOT EXISTS idx_token_blacklist_expires ON token_blacklist(expires_at)
            ');

        } catch (\PDOException $e) {
            $this->logger->error('Token blacklist table initialization failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function addToken(string $token, string $userId, int $expiresAt): bool
    {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO token_blacklist (token, user_id, blacklisted_at, expires_at)
                VALUES (:token, :user_id, :blacklisted_at, :expires_at)
            ');

            $stmt->execute([
                'token' => $token,
                'user_id' => $userId,
                'blacklisted_at' => date('c'),
                'expires_at' => date('c', $expiresAt),
            ]);

            $this->logger->info('Token blacklisted', [
                'user_id' => $userId,
                'token_prefix' => substr($token, 0, 20) . '...',
            ]);

            return true;

        } catch (\PDOException $e) {
            $this->logger->error('Failed to blacklist token', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function isBlacklisted(string $token): bool
    {
        try {
            $stmt = $this->db->prepare('
                SELECT COUNT(*) as count
                FROM token_blacklist
                WHERE token = :token
            ');

            $stmt->execute(['token' => $token]);
            $result = $stmt->fetch();

            return $result['count'] > 0;

        } catch (\PDOException $e) {
            $this->logger->error('Failed to check token blacklist', [
                'error' => $e->getMessage(),
            ]);
            // Fail safe: if we can't check, treat as not blacklisted
            return false;
        }
    }

    public function cleanupExpiredTokens(): int
    {
        try {
            $stmt = $this->db->prepare('
                DELETE FROM token_blacklist
                WHERE expires_at < :now
            ');

            $stmt->execute(['now' => date('c')]);
            $deletedCount = $stmt->rowCount();

            if ($deletedCount > 0) {
                $this->logger->info('Cleaned up expired blacklisted tokens', [
                    'count' => $deletedCount,
                ]);
            }

            return $deletedCount;

        } catch (\PDOException $e) {
            $this->logger->error('Failed to cleanup expired tokens', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    public function getBlacklistedTokenCount(): int
    {
        try {
            $stmt = $this->db->query('SELECT COUNT(*) as count FROM token_blacklist');
            $result = $stmt->fetch();
            return (int) $result['count'];

        } catch (\PDOException $e) {
            $this->logger->error('Failed to get blacklist count', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
