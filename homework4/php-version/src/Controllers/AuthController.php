<?php

namespace TodoApi\Controllers;

use TodoApi\Models\User;
use TodoApi\Services\Validator;
use TodoApi\Services\JWTService;
use TodoApi\Services\TokenBlacklist;
use TodoApi\Router;
use TodoApi\Logger;

class AuthController
{
    private User $userModel;
    private Validator $validator;
    private JWTService $jwtService;
    private Router $router;
    private Logger $logger;

    public function __construct()
    {
        $this->userModel = new User();
        $this->validator = new Validator();
        $this->jwtService = new JWTService();
        $this->router = new Router();
        $this->logger = Logger::getInstance();
    }

    public function signup(array $params): void
    {
        // Validate Content-Type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            $this->router->sendError(415, 'UNSUPPORTED_MEDIA_TYPE', 'Content-Type must be application/json');
            return;
        }

        // Parse JSON body
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->router->sendError(400, 'INVALID_JSON', 'Invalid JSON in request body');
            return;
        }

        // Sanitize input
        $data['username'] = Validator::sanitizeString($data['username'] ?? null);
        $data['email'] = Validator::sanitizeString($data['email'] ?? null);

        // Validate input
        $rules = [
            'username' => [
                'required' => true,
                'string' => true,
                'minLength' => 3,
                'maxLength' => 50,
                'notEmpty' => true,
            ],
            'email' => [
                'required' => true,
                'string' => true,
                'maxLength' => 255,
                'notEmpty' => true,
            ],
            'password' => [
                'required' => true,
                'string' => true,
                'minLength' => 8,
                'maxLength' => 100,
            ],
        ];

        if (!$this->validator->validate($data, $rules)) {
            $this->router->sendError(400, 'VALIDATION_ERROR', $this->validator->getFirstError(), [
                'errors' => $this->validator->getErrors(),
            ]);
            return;
        }

        // Additional email format validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->router->sendError(400, 'VALIDATION_ERROR', 'Invalid email format');
            return;
        }

        try {
            // Create user (password will be hashed in model)
            $user = $this->userModel->create($data);

            // Generate JWT token
            $token = $this->jwtService->generateToken($user['id'], $user['username']);

            // Return user info and token (never return password)
            $response = [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'createdAt' => $user['created_at'],
                ],
            ];

            $this->router->sendJson($response, 201);

        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $this->router->sendError(409, 'CONFLICT', 'Username or email already exists');
            } else {
                $this->logger->error('Signup failed', [
                    'username' => $data['username'],
                    'error' => $e->getMessage(),
                ]);
                $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to create user');
            }
        }
    }

    public function login(array $params): void
    {
        // Validate Content-Type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            $this->router->sendError(415, 'UNSUPPORTED_MEDIA_TYPE', 'Content-Type must be application/json');
            return;
        }

        // Parse JSON body
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->router->sendError(400, 'INVALID_JSON', 'Invalid JSON in request body');
            return;
        }

        // Sanitize input
        $data['username'] = Validator::sanitizeString($data['username'] ?? null);

        // Validate input
        $rules = [
            'username' => [
                'required' => true,
                'string' => true,
                'notEmpty' => true,
            ],
            'password' => [
                'required' => true,
                'string' => true,
            ],
        ];

        if (!$this->validator->validate($data, $rules)) {
            $this->router->sendError(400, 'VALIDATION_ERROR', $this->validator->getFirstError(), [
                'errors' => $this->validator->getErrors(),
            ]);
            return;
        }

        try {
            // Find user by username
            $user = $this->userModel->findByUsername($data['username']);

            if (!$user) {
                $this->logger->warning('Login failed - user not found', ['username' => $data['username']]);
                $this->router->sendError(401, 'UNAUTHORIZED', 'Invalid username or password');
                return;
            }

            // Verify password
            if (!$this->userModel->verifyPassword($user, $data['password'])) {
                $this->logger->warning('Login failed - invalid password', ['username' => $data['username']]);
                $this->router->sendError(401, 'UNAUTHORIZED', 'Invalid username or password');
                return;
            }

            // Generate JWT token
            $token = $this->jwtService->generateToken($user['id'], $user['username']);

            // Return user info and token
            $response = [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'createdAt' => $user['created_at'],
                ],
            ];

            $this->logger->info('User logged in', ['user_id' => $user['id']]);
            $this->router->sendJson($response);

        } catch (\Exception $e) {
            $this->logger->error('Login failed', [
                'username' => $data['username'],
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Login failed');
        }
    }

    public function getProfile(array $params): void
    {
        // Extract and validate token
        $token = $this->jwtService->extractTokenFromHeader();

        if (!$token) {
            $this->router->sendError(401, 'UNAUTHORIZED', 'Missing or invalid authorization token');
            return;
        }

        $decoded = $this->jwtService->validateToken($token);

        if (!$decoded) {
            $this->router->sendError(401, 'UNAUTHORIZED', 'Invalid or expired token');
            return;
        }

        try {
            $user = $this->userModel->findById($decoded->sub);

            if (!$user) {
                $this->router->sendError(404, 'NOT_FOUND', 'User not found');
                return;
            }

            // Return user profile (never return password)
            $response = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'createdAt' => $user['created_at'],
                'updatedAt' => $user['updated_at'],
            ];

            $this->router->sendJson($response);

        } catch (\Exception $e) {
            $this->logger->error('Get profile failed', [
                'user_id' => $decoded->sub,
                'error' => $e->getMessage(),
            ]);
            $this->router->sendError(500, 'INTERNAL_ERROR', 'Failed to retrieve profile');
        }
    }

    public function logout(array $params): void
    {
        // Extract and validate token
        $token = $this->jwtService->extractTokenFromHeader();

        if (!$token) {
            // If no token provided, still return success (user already logged out client-side)
            $this->logger->info('Logout called without token');
            http_response_code(204);
            return;
        }

        // Decode token to get user ID and expiry
        $decoded = $this->jwtService->validateToken($token);

        if (!$decoded) {
            // Token already invalid/expired, no need to blacklist
            $this->logger->info('Logout called with invalid/expired token');
            http_response_code(204);
            return;
        }

        try {
            // Add token to blacklist
            $blacklist = TokenBlacklist::getInstance();
            $blacklist->addToken($token, $decoded->sub, $decoded->exp);

            $this->logger->info('User logged out and token blacklisted', [
                'user_id' => $decoded->sub,
            ]);

            // Cleanup expired tokens (optional, can also run as cron job)
            $blacklist->cleanupExpiredTokens();

            http_response_code(204);

        } catch (\Exception $e) {
            $this->logger->error('Logout failed', [
                'user_id' => $decoded->sub ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            // Still return success to user even if blacklisting fails
            http_response_code(204);
        }
    }
}
