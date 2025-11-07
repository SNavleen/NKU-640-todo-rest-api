<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TodoApi\Router;
use TodoApi\Controllers\ListController;
use TodoApi\Controllers\TaskController;
use TodoApi\Controllers\AuthController;

// Enable error reporting in development
$config = \TodoApi\Config::getInstance();
if ($config->isDebugMode()) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// CORS headers (for future web UI)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$router = new Router();
$apiVersion = $config->getApiVersion();

// List routes
$listController = new ListController();
$router->get("/api/$apiVersion/lists", [$listController, 'getAll']);
$router->get("/api/$apiVersion/lists/:id", [$listController, 'getById']);
$router->post("/api/$apiVersion/lists", [$listController, 'create']);
$router->patch("/api/$apiVersion/lists/:id", [$listController, 'update']);
$router->delete("/api/$apiVersion/lists/:id", [$listController, 'delete']);

// Task routes
$taskController = new TaskController();
$router->get("/api/$apiVersion/lists/:listId/tasks", [$taskController, 'getAllByList']);
$router->get("/api/$apiVersion/tasks/:id", [$taskController, 'getById']);
$router->post("/api/$apiVersion/lists/:listId/tasks", [$taskController, 'create']);
$router->patch("/api/$apiVersion/tasks/:id", [$taskController, 'update']);
$router->delete("/api/$apiVersion/tasks/:id", [$taskController, 'delete']);

// Auth routes
$authController = new AuthController();
$router->post("/api/$apiVersion/auth/signup", [$authController, 'signup']);
$router->post("/api/$apiVersion/auth/login", [$authController, 'login']);
$router->post("/api/$apiVersion/auth/logout", [$authController, 'logout']);
$router->get("/api/$apiVersion/users/profile", [$authController, 'getProfile']);

// Dispatch the request
$router->dispatch();
