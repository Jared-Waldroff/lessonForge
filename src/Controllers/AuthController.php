<?php
/**
 * Authentication Controller
 * 
 * Handles user registration and login.
 * 
 * @package LessonForge\Controllers
 */

namespace LessonForge\Controllers;

use LessonForge\Models\User;
use LessonForge\Router;

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Register a new user
     * POST /api/auth/register
     */
    public function register(): array
    {
        $data = Router::getJsonBody();

        $required = ['email', 'password', 'name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                return ['error' => true, 'message' => "Missing required field: {$field}"];
            }
        }

        $result = $this->userModel->register(
            $data['email'],
            $data['password'],
            $data['name'],
            $data['role'] ?? 'student'
        );

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        http_response_code(201);
        return [
            'success' => true,
            'message' => 'User registered successfully',
            'user' => $result['user']
        ];
    }

    /**
     * Login a user
     * POST /api/auth/login
     */
    public function login(): array
    {
        $data = Router::getJsonBody();

        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            return ['error' => true, 'message' => 'Email and password are required'];
        }

        $result = $this->userModel->authenticate($data['email'], $data['password']);

        if (!$result['success']) {
            http_response_code(401);
            return ['error' => true, 'message' => $result['error']];
        }

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $result['user'],
            'token' => $result['token']
        ];
    }

    /**
     * Get all users (admin only in production)
     * GET /api/users
     */
    public function getUsers(): array
    {
        $role = $_GET['role'] ?? null;
        $users = $this->userModel->getAll($role);

        return [
            'success' => true,
            'users' => $users
        ];
    }

    /**
     * Get a single user
     * GET /api/users/{id}
     */
    public function getUser(array $params): array
    {
        $user = $this->userModel->find((int) $params['id']);

        if (!$user) {
            http_response_code(404);
            return ['error' => true, 'message' => 'User not found'];
        }

        return [
            'success' => true,
            'user' => $user->toArray()
        ];
    }
}
