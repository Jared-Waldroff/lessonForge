<?php
/**
 * Auth Middleware Tests
 *
 * Tests JWT token creation, validation, expiration, and role enforcement.
 * These tests don't require a database connection as they test the
 * middleware logic directly.
 *
 * @package LessonForge\Tests\Integration
 */

namespace LessonForge\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LessonForge\Middleware\AuthMiddleware;

class AuthMiddlewareTest extends TestCase
{
    /**
     * Test that createToken returns a valid JWT string
     */
    public function testCreateTokenReturnsValidJwt(): void
    {
        $token = AuthMiddleware::createToken(1, 'user@example.com', 'teacher');

        $this->assertIsString($token);
        // JWT has 3 parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Test that a created token can be decoded correctly
     */
    public function testTokenContainsCorrectClaims(): void
    {
        $token = AuthMiddleware::createToken(42, 'admin@hcos.ca', 'admin');
        $secret = getenv('JWT_SECRET') ?: 'lessonforge-dev-secret-change-in-production';

        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

        $this->assertEquals(42, $decoded->sub);
        $this->assertEquals('admin@hcos.ca', $decoded->email);
        $this->assertEquals('admin', $decoded->role);
        $this->assertEquals('lessonforge', $decoded->iss);
        $this->assertGreaterThan(time() - 10, $decoded->iat);
        $this->assertGreaterThan(time(), $decoded->exp);
    }

    /**
     * Test that authenticate returns null when no Authorization header
     */
    public function testAuthenticateReturnsNullWithNoHeader(): void
    {
        // Ensure no auth header is set
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $result = AuthMiddleware::authenticate();
        $this->assertNull($result);
    }

    /**
     * Test that authenticate returns null with invalid token
     */
    public function testAuthenticateReturnsNullWithInvalidToken(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid.token.here';

        $result = AuthMiddleware::authenticate();
        $this->assertNull($result);
    }

    /**
     * Test that authenticate returns null with malformed header
     */
    public function testAuthenticateReturnsNullWithMalformedHeader(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'NotBearer sometoken';

        $result = AuthMiddleware::authenticate();
        $this->assertNull($result);
    }

    /**
     * Test that authenticate succeeds with valid token
     */
    public function testAuthenticateSucceedsWithValidToken(): void
    {
        $token = AuthMiddleware::createToken(5, 'student@hcos.ca', 'student');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $result = AuthMiddleware::authenticate();

        $this->assertNotNull($result);
        $this->assertEquals(5, $result['id']);
        $this->assertEquals('student@hcos.ca', $result['email']);
        $this->assertEquals('student', $result['role']);
    }

    /**
     * Test that an expired token is rejected
     */
    public function testExpiredTokenIsRejected(): void
    {
        $secret = getenv('JWT_SECRET') ?: 'lessonforge-dev-secret-change-in-production';

        // Create an already-expired token
        $payload = [
            'iss' => 'lessonforge',
            'sub' => 1,
            'email' => 'expired@example.com',
            'role' => 'student',
            'iat' => time() - 7200,
            'exp' => time() - 3600, // expired 1 hour ago
        ];
        $token = JWT::encode($payload, $secret, 'HS256');

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $result = AuthMiddleware::authenticate();
        $this->assertNull($result);
    }

    /**
     * Test that a token signed with wrong secret is rejected
     */
    public function testTamperedTokenIsRejected(): void
    {
        // Create token with a different secret
        $payload = [
            'iss' => 'lessonforge',
            'sub' => 1,
            'email' => 'hacker@example.com',
            'role' => 'admin',
            'iat' => time(),
            'exp' => time() + 3600,
        ];
        $token = JWT::encode($payload, 'wrong-secret-key', 'HS256');

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $result = AuthMiddleware::authenticate();
        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        // Clean up the global state
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }
}
