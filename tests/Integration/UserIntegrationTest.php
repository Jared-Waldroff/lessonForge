<?php
/**
 * User Integration Tests
 *
 * Tests user registration, authentication, and data persistence
 * against a real MariaDB database.
 *
 * @package LessonForge\Tests\Integration
 */

namespace LessonForge\Tests\Integration;

class UserIntegrationTest extends DatabaseTestCase
{
    /**
     * Test that registering a user creates a row in the database
     */
    public function testRegisterCreatesUserRow(): void
    {
        $email = 'newuser@example.com';
        $hash = password_hash('securepass123', PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$email, $hash, 'New User', 'student']);

        $userId = (int) $this->pdo->lastInsertId();
        $this->assertGreaterThan(0, $userId);

        // Verify the row exists
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        $this->assertNotFalse($user);
        $this->assertEquals($email, $user['email']);
        $this->assertEquals('student', $user['role']);
        $this->assertEquals('New User', $user['name']);
    }

    /**
     * Test that duplicate emails are rejected by the unique constraint
     */
    public function testDuplicateEmailRejected(): void
    {
        $this->insertUser('duplicate@example.com');

        $this->expectException(\PDOException::class);

        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute(['duplicate@example.com', $hash, 'Duplicate', 'student']);
    }

    /**
     * Test that password_hash and password_verify work correctly
     */
    public function testPasswordHashAndVerify(): void
    {
        $password = 'my_secure_password';
        $userId = $this->insertUser('auth@example.com', 'teacher', $password);

        // Retrieve the stored hash
        $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        // Verify the password matches
        $this->assertTrue(password_verify($password, $row['password_hash']));
        // Verify wrong password fails
        $this->assertFalse(password_verify('wrong_password', $row['password_hash']));
    }

    /**
     * Test that all three roles can be inserted
     */
    public function testAllRolesAccepted(): void
    {
        $roles = ['teacher', 'student', 'admin'];

        foreach ($roles as $i => $role) {
            $userId = $this->insertUser("user{$i}@example.com", $role);
            $this->assertGreaterThan(0, $userId);
        }

        // Verify count
        $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM users");
        $row = $stmt->fetch();
        $this->assertEquals(3, (int) $row['cnt']);
    }

    /**
     * Test that user profile can be updated
     */
    public function testUserProfileUpdate(): void
    {
        $userId = $this->insertUser('update@example.com');

        $stmt = $this->pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute(['Updated Name', $userId]);

        $stmt = $this->pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        $this->assertEquals('Updated Name', $row['name']);
    }
}
