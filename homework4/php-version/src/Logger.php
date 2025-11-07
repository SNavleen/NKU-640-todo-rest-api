<?php

namespace TodoApi;

class Logger
{
    private static ?Logger $instance = null;
    private string $logFile;
    private string $logLevel;
    private array $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];

    private function __construct()
    {
        $config = Config::getInstance();
        $this->logFile = __DIR__ . '/../logs/api.log';
        $this->logLevel = strtolower($config->getLogLevel());

        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }
        return self::$instance;
    }

    private function shouldLog(string $level): bool
    {
        $currentLevel = $this->levels[$this->logLevel] ?? 3;
        $messageLevel = $this->levels[$level] ?? 0;
        return $messageLevel >= $currentLevel;
    }

    private function writeLog(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $timestamp = date('c'); // ISO 8601 format
        $contextStr = !empty($context) ? ' - ' . json_encode($context) : '';
        $logEntry = sprintf("[%s] %s: %s%s\n", $timestamp, strtoupper($level), $message, $contextStr);

        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->writeLog('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->writeLog('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->writeLog('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->writeLog('error', $message, $context);
    }

    public function logRequest(string $method, string $path, int $statusCode, float $duration): void
    {
        $message = sprintf(
            "%s %s - %d - %.2fms",
            $method,
            $path,
            $statusCode,
            $duration
        );
        $this->info($message);
    }
}
