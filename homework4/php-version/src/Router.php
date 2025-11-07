<?php

namespace TodoApi;

class Router
{
    private array $routes = [];
    private Logger $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    public function addRoute(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, callable $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function patch(string $pattern, callable $handler): void
    {
        $this->addRoute('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    public function dispatch(): void
    {
        $startTime = microtime(true);
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Set JSON response header
        header('Content-Type: application/json');

        try {
            foreach ($this->routes as $route) {
                if ($route['method'] !== $method) {
                    continue;
                }

                $params = $this->matchRoute($route['pattern'], $path);
                if ($params !== false) {
                    $response = call_user_func($route['handler'], $params);

                    $duration = (microtime(true) - $startTime) * 1000;
                    $this->logger->logRequest($method, $path, http_response_code(), $duration);

                    if (is_array($response)) {
                        echo json_encode($response);
                    }
                    return;
                }
            }

            // No route matched
            $this->sendError(404, 'NOT_FOUND', 'Endpoint not found');

        } catch (\Exception $e) {
            $this->logger->error('Unhandled exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->sendError(500, 'INTERNAL_ERROR', 'An internal error occurred');
        }

        $duration = (microtime(true) - $startTime) * 1000;
        $this->logger->logRequest($method, $path, http_response_code(), $duration);
    }

    private function matchRoute(string $pattern, string $path): array|false
    {
        // Convert pattern like /api/v1/lists/:id to regex
        $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $path, $matches)) {
            // Extract named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return false;
    }

    public function sendError(int $statusCode, string $errorCode, string $message, array $details = []): void
    {
        http_response_code($statusCode);

        $config = Config::getInstance();
        $response = [
            'error' => $message,
            'code' => $errorCode,
        ];

        if (!empty($details) && $config->isDebugMode()) {
            $response['details'] = $details;
        }

        echo json_encode($response);
    }

    public function sendJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data);
    }
}
