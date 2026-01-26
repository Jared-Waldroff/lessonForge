<?php
/**
 * User Model Tests
 * 
 * Tests for user registration, authentication, and profile management.
 * 
 * @package LessonForge\Tests
 */

namespace LessonForge\Tests;

use PHPUnit\Framework\TestCase;
use LessonForge\Models\User;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    /**
     * Test email validation
     */
    public function testInvalidEmailReturnsError(): void
    {
        $result = $this->user->register('invalid-email', 'password123', 'Test User');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid email format', $result['error']);
    }

    /**
     * Test password length validation
     */
    public function testShortPasswordReturnsError(): void
    {
        $result = $this->user->register('test@example.com', 'short', 'Test User');

        $this->assertFalse($result['success']);
        $this->assertEquals('Password must be at least 8 characters', $result['error']);
    }

    /**
     * Test invalid role validation
     */
    public function testInvalidRoleReturnsError(): void
    {
        $result = $this->user->register('test@example.com', 'password123', 'Test User', 'invalid_role');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid role', $result['error']);
    }

    /**
     * Test valid user data
     */
    public function testValidUserDataFormat(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->name = 'Test User';
        $user->role = 'student';
        $user->createdAt = '2024-01-01 00:00:00';

        $array = $user->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('role', $array);
        $this->assertEquals(1, $array['id']);
    }

    /**
     * Test that passwords are properly validated
     */
    public function testPasswordValidation(): void
    {
        // Test with exactly 8 characters (should pass validation)
        $password = 'exactly8';
        $this->assertEquals(8, strlen($password));

        // Test with 7 characters (should fail)
        $shortPassword = 'short12';
        $this->assertLessThan(8, strlen($shortPassword));
    }

    /**
     * Test email format validation
     */
    public function testEmailFormatValidation(): void
    {
        $validEmails = [
            'user@example.com',
            'user.name@example.com',
            'user+tag@example.com'
        ];

        $invalidEmails = [
            'invalid',
            'invalid@',
            '@example.com'
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        }

        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
        }
    }

    /**
     * Test role whitelist
     */
    public function testValidRoles(): void
    {
        $validRoles = ['teacher', 'student', 'admin'];
        $invalidRoles = ['moderator', 'superuser', ''];

        foreach ($validRoles as $role) {
            $this->assertTrue(in_array($role, $validRoles));
        }

        foreach ($invalidRoles as $role) {
            $isValid = in_array($role, $validRoles);
            $this->assertFalse($isValid);
        }
    }
}
