<?php
/**
 * Base Test Case for Integration Tests
 *
 * Provides database connection and transaction management.
 * Each test runs inside a transaction that is rolled back on teardown,
 * ensuring a clean state without needing to truncate tables.
 *
 * @package LessonForge\Tests\Integration
 */

namespace LessonForge\Tests\Integration;

use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected \PDO $pdo;

    protected function setUp(): void
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'hcos_lessonforge_test';
        $user = getenv('DB_USER') ?: 'hcos';
        $pass = getenv('DB_PASS') ?: 'hcos_test';

        $this->pdo = new \PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Insert a test user and return their ID
     */
    protected function insertUser(
        string $email = 'test@example.com',
        string $role = 'teacher',
        string $password = 'password123'
    ): int {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$email, $hash, 'Test User', $role]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a test lesson and return its ID
     */
    protected function insertLesson(int $teacherId, string $title = 'Test Lesson'): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO lessons (teacher_id, title, description, subject, grade_level, is_published) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$teacherId, $title, 'A test lesson', 'Bible', 'Grade 5', true]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a test lesson block and return its ID
     */
    protected function insertBlock(int $lessonId, string $type = 'text', int $order = 0): int
    {
        $content = json_encode(['body' => 'Test content']);
        $stmt = $this->pdo->prepare(
            "INSERT INTO lesson_blocks (lesson_id, block_type, content, order_index) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$lessonId, $type, $content, $order]);
        return (int) $this->pdo->lastInsertId();
    }
}
