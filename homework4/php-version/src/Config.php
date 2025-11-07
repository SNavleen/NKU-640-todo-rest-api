<?php

namespace TodoApi;

class Config
{
    private static ?Config $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->loadEnv();
    }

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    private function loadEnv(): void
    {
        $envFile = __DIR__ . '/../.env';

        if (!file_exists($envFile)) {
            // Use defaults if .env doesn't exist
            $this->config = [
                'DEBUG_MODE' => false,
                'LOG_LEVEL' => 'error',
                'DATABASE_PATH' => __DIR__ . '/../data/todo.db',
                'API_VERSION' => 'v1',
            ];
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Parse key=value pairs
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Convert string booleans to actual booleans
                if (strtolower($value) === 'true') {
                    $value = true;
                } elseif (strtolower($value) === 'false') {
                    $value = false;
                }

                $this->config[$key] = $value;
            }
        }

        // Set absolute path for database
        if (isset($this->config['DATABASE_PATH']) && !str_starts_with($this->config['DATABASE_PATH'], '/')) {
            $this->config['DATABASE_PATH'] = __DIR__ . '/../' . $this->config['DATABASE_PATH'];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function isDebugMode(): bool
    {
        return (bool) $this->get('DEBUG_MODE', false);
    }

    public function getLogLevel(): string
    {
        return $this->get('LOG_LEVEL', 'error');
    }

    public function getDatabasePath(): string
    {
        return $this->get('DATABASE_PATH', __DIR__ . '/../data/todo.db');
    }

    public function getApiVersion(): string
    {
        return $this->get('API_VERSION', 'v1');
    }
}
