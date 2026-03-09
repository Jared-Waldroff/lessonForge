<?php
/**
 * User Model
 * 
 * Handles user authentication, registration, and profile management.
 * Uses secure password hashing with password_hash().
 * 
 * @package LessonForge\Models
 */

namespace LessonForge\Models;

use LessonForge\Database;
use LessonForge\Middleware\AuthMiddleware;

class User
{
    private Database $db;

    public ?int $id = null;
    public ?string $email = null;
    public ?string $name = null;
    public ?string $role = null;
    public ?string $createdAt = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a user by ID
     */
    public function find(int $id): ?self
    {
        $stmt = $this->db->query(
            "SELECT id, email, name, role, created_at FROM users WHERE id = ?",
            [$id]
        );

        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * Find a user by email
     */
    public function findByEmail(string $email): ?self
    {
        $stmt = $this->db->query(
            "SELECT id, email, name, role, created_at, password_hash FROM users WHERE email = ?",
            [$email]
        );

        return $stmt->fetch() ?: null;
    }

    /**
     * Get all users (optionally filtered by role)
     */
    public function getAll(?string $role = null): array
    {
        if ($role) {
            $stmt = $this->db->query(
                "SELECT id, email, name, role, created_at FROM users WHERE role = ?",
                [$role]
            );
        } else {
            $stmt = $this->db->query(
                "SELECT id, email, name, role, created_at FROM users"
            );
        }

        return $stmt->fetchAll();
    }

    /**
     * Register a new user
     * 
     * @param string $email User's email address
     * @param string $password Plain text password (will be hashed)
     * @param string $name User's display name
     * @param string $role User role (teacher, student, admin)
     * @return array Result with success status and user data or error
     */
    public function register(string $email, string $password, string $name, string $role = 'student'): array
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        // Check if email already exists
        $existing = $this->findByEmail($email);
        if ($existing) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        // Validate password strength
        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters'];
        }

        // Validate role
        $validRoles = ['teacher', 'student', 'admin'];
        if (!in_array($role, $validRoles)) {
            return ['success' => false, 'error' => 'Invalid role'];
        }

        // Hash password securely
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $this->db->query(
                "INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)",
                [$email, $passwordHash, $name, $role]
            );

            $userId = $this->db->getConnection()->lastInsertId();

            return [
                'success' => true,
                'user' => [
                    'id' => (int) $userId,
                    'email' => $email,
                    'name' => $name,
                    'role' => $role
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Registration failed'];
        }
    }

    /**
     * Authenticate a user with email and password
     * 
     * @param string $email User's email address
     * @param string $password Plain text password
     * @return array Result with success status and user data or error
     */
    public function authenticate(string $email, string $password): array
    {
        $userData = $this->findByEmail($email);

        if (!$userData) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        if (!password_verify($password, $userData['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Generate JWT token with user claims
        $token = AuthMiddleware::createToken(
            (int) $userData['id'],
            $userData['email'],
            $userData['role']
        );

        return [
            'success' => true,
            'user' => [
                'id' => (int) $userData['id'],
                'email' => $userData['email'],
                'name' => $userData['name'],
                'role' => $userData['role']
            ],
            'token' => $token
        ];
    }

    /**
     * Update user profile
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['name', 'email'];
        $updates = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";

        $this->db->query($sql, $params);
        return true;
    }

    /**
     * Hydrate model from database row
     */
    private function hydrate(array $row): self
    {
        $user = new self();
        $user->id = (int) $row['id'];
        $user->email = $row['email'];
        $user->name = $row['name'];
        $user->role = $row['role'];
        $user->createdAt = $row['created_at'];
        return $user;
    }

    /**
     * Convert to array for JSON response
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
            'createdAt' => $this->createdAt
        ];
    }
}
