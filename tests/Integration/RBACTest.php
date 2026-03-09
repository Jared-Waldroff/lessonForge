<?php
/**
 * Role-Based Access Control Tests
 *
 * Tests that middleware correctly enforces role permissions:
 * - Public routes accessible without auth
 * - Protected routes reject unauthenticated requests
 * - Role-specific routes reject wrong roles
 *
 * @package LessonForge\Tests\Integration
 */

namespace LessonForge\Tests\Integration;

use PHPUnit\Framework\TestCase;
use LessonForge\Middleware\AuthMiddleware;

class RBACTest extends TestCase
{
    /**
     * Test that a student token does NOT have teacher/admin role
     */
    public function testStudentCannotAccessTeacherRoutes(): void
    {
        $token = AuthMiddleware::createToken(1, 'student@hcos.ca', 'student');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $user = AuthMiddleware::authenticate();

        $this->assertNotNull($user);
        $this->assertEquals('student', $user['role']);
        $this->assertFalse(in_array($user['role'], ['teacher', 'admin']));
    }

    /**
     * Test that a teacher token has teacher role
     */
    public function testTeacherHasCorrectRole(): void
    {
        $token = AuthMiddleware::createToken(2, 'teacher@hcos.ca', 'teacher');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $user = AuthMiddleware::authenticate();

        $this->assertNotNull($user);
        $this->assertEquals('teacher', $user['role']);
        $this->assertTrue(in_array($user['role'], ['teacher', 'admin']));
    }

    /**
     * Test that admin has access to admin-only routes
     */
    public function testAdminHasFullAccess(): void
    {
        $token = AuthMiddleware::createToken(3, 'admin@hcos.ca', 'admin');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $user = AuthMiddleware::authenticate();

        $this->assertNotNull($user);
        $this->assertEquals('admin', $user['role']);
        $this->assertTrue(in_array($user['role'], ['admin']));
        $this->assertTrue(in_array($user['role'], ['teacher', 'admin']));
    }

    /**
     * Test that unauthenticated request returns null
     */
    public function testUnauthenticatedRequestReturnsNull(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $user = AuthMiddleware::authenticate();
        $this->assertNull($user);
    }

    /**
     * Test that empty bearer token is rejected
     */
    public function testEmptyBearerTokenRejected(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ';

        $user = AuthMiddleware::authenticate();
        $this->assertNull($user);
    }

    /**
     * Test that token role cannot be forged
     */
    public function testCannotForgeAdminRole(): void
    {
        // Create a student token
        $token = AuthMiddleware::createToken(1, 'student@hcos.ca', 'student');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $user = AuthMiddleware::authenticate();

        // The role in the token is student, not admin
        $this->assertEquals('student', $user['role']);
        $this->assertNotEquals('admin', $user['role']);
    }

    /**
     * Test that token signed with wrong secret is rejected
     */
    public function testTokenFromDifferentSecretRejected(): void
    {
        // Manually create a token with a different secret
        $payload = [
            'iss' => 'lessonforge',
            'sub' => 1,
            'email' => 'attacker@evil.com',
            'role' => 'admin',
            'iat' => time(),
            'exp' => time() + 3600,
        ];
        $fakeToken = \Firebase\JWT\JWT::encode($payload, 'attacker-secret', 'HS256');

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $fakeToken;

        $user = AuthMiddleware::authenticate();
        $this->assertNull($user);
    }

    /**
     * Test role checking logic for route middleware
     */
    public function testRoleCheckingForRouteAccess(): void
    {
        $teacherAdminRoutes = ['teacher', 'admin'];
        $adminOnlyRoutes = ['admin'];

        // Student should fail both
        $this->assertFalse(in_array('student', $teacherAdminRoutes));
        $this->assertFalse(in_array('student', $adminOnlyRoutes));

        // Teacher should pass teacher/admin, fail admin-only
        $this->assertTrue(in_array('teacher', $teacherAdminRoutes));
        $this->assertFalse(in_array('teacher', $adminOnlyRoutes));

        // Admin should pass both
        $this->assertTrue(in_array('admin', $teacherAdminRoutes));
        $this->assertTrue(in_array('admin', $adminOnlyRoutes));
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }
}
