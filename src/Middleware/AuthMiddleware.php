<?php
/**
 * JWT Authentication Middleware
 *
 * Handles JWT token creation, validation, and role-based access control.
 * Tokens are signed with HS256 and include user ID, email, and role.
 *
 * @package LessonForge\Middleware
 */

namespace LessonForge\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class AuthMiddleware
{
    private static function getSecret(): string
    {
        return getenv('JWT_SECRET') ?: 'lessonforge-dev-secret-change-in-production';
    }

    /**
     * Create a JWT token for a user
     *
     * @param int $userId User's database ID
     * @param string $email User's email address
     * @param string $role User's role (teacher, student, admin)
     * @return string Encoded JWT token
     */
    public static function createToken(int $userId, string $email, string $role): string
    {
        $payload = [
            'iss' => 'lessonforge',
            'sub' => $userId,
            'email' => $email,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour expiration
        ];

        return JWT::encode($payload, self::getSecret(), 'HS256');
    }

    /**
     * Attempt to authenticate from the Authorization header
     *
     * @return array|null User data array or null if not authenticated
     */
    public static function authenticate(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        try {
            $decoded = JWT::decode($matches[1], new Key(self::getSecret(), 'HS256'));

            return [
                'id' => $decoded->sub,
                'email' => $decoded->email,
                'role' => $decoded->role,
            ];
        } catch (ExpiredException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Require authentication - returns user or sends 401
     *
     * @return array Authenticated user data
     */
    public static function requireAuth(): array
    {
        $user = self::authenticate();

        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'Authentication required']);
            exit;
        }

        return $user;
    }

    /**
     * Require specific role(s) - returns user or sends 403
     *
     * @param array $roles Allowed roles (e.g., ['teacher', 'admin'])
     * @return array Authenticated user data
     */
    public static function requireRole(array $roles): array
    {
        $user = self::requireAuth();

        if (!in_array($user['role'], $roles)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'Insufficient permissions']);
            exit;
        }

        return $user;
    }
}
