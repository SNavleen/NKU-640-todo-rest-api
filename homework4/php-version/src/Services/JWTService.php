<?php

namespace TodoApi\Services;

use TodoApi\Config;
use TodoApi\Logger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTService
{
    private string $secret;
    private int $expiry;
    private Logger $logger;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->secret = $config->get('JWT_SECRET', 'default-secret-change-me');
        $this->expiry = (int) $config->get('JWT_EXPIRY', 3600);
        $this->logger = Logger::getInstance();
    }

    public function generateToken(string $userId, string $username): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expiry;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $userId,
            'username' => $username,
        ];

        try {
            $token = JWT::encode($payload, $this->secret, 'HS256');
            $this->logger->info('JWT token generated', ['user_id' => $userId]);
            return $token;
        } catch (\Exception $e) {
            $this->logger->error('JWT generation failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to generate token');
        }
    }

    public function validateToken(string $token): ?object
    {
        try {
            // Check if token is blacklisted
            $blacklist = TokenBlacklist::getInstance();
            if ($blacklist->isBlacklisted($token)) {
                $this->logger->warning('JWT token is blacklisted');
                return null;
            }

            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $this->logger->debug('JWT token validated', ['user_id' => $decoded->sub]);
            return $decoded;
        } catch (\Firebase\JWT\ExpiredException $e) {
            $this->logger->warning('JWT token expired', ['error' => $e->getMessage()]);
            return null;
        } catch (\Exception $e) {
            $this->logger->warning('JWT validation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function extractTokenFromHeader(): ?string
    {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            return null;
        }

        $authHeader = $headers['Authorization'];

        // Extract token from "Bearer <token>"
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
