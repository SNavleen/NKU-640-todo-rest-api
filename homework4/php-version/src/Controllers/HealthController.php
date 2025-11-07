<?php

namespace TodoApi\Controllers;

use TodoApi\Services\Database;
use TodoApi\Config;

class HealthController
{
    private Database $db;
    private Config $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
    }

    public function getHealth(): void
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'service' => 'PHP TODO REST API',
            'version' => $this->config->getApiVersion(),
            'checks' => []
        ];

        // Database check
        try {
            $this->db->getConnection()->query('SELECT 1');
            $health['checks']['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }

        // PHP version check
        $health['checks']['php'] = [
            'status' => 'healthy',
            'version' => PHP_VERSION
        ];

        // Disk space check (data directory)
        $dataPath = $this->config->getDatabasePath();
        $dataDir = dirname($dataPath);

        if (is_dir($dataDir) && is_writable($dataDir)) {
            $diskFree = disk_free_space($dataDir);
            $diskTotal = disk_total_space($dataDir);
            $diskUsedPercent = (($diskTotal - $diskFree) / $diskTotal) * 100;

            $health['checks']['disk'] = [
                'status' => $diskUsedPercent < 90 ? 'healthy' : 'warning',
                'free_space_mb' => round($diskFree / 1024 / 1024, 2),
                'used_percent' => round($diskUsedPercent, 2)
            ];

            if ($diskUsedPercent >= 90) {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks']['disk'] = [
                'status' => 'warning',
                'message' => 'Data directory not writable'
            ];
        }

        // Memory check
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);

        $health['checks']['memory'] = [
            'status' => 'healthy',
            'memory_limit' => $memoryLimit,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2)
        ];

        // Set HTTP status code based on health status
        $statusCode = match($health['status']) {
            'healthy' => 200,
            'warning' => 200,
            'unhealthy' => 503,
            default => 500
        };

        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($health, JSON_PRETTY_PRINT);
    }
}
