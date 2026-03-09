<?php
/**
 * Security Edge Case Tests
 *
 * Tests protection against common attack vectors:
 * SQL injection, XSS, missing fields, and input validation.
 *
 * @package LessonForge\Tests\Integration
 */

namespace LessonForge\Tests\Integration;

class SecurityTest extends DatabaseTestCase
{
    /**
     * Test that SQL injection in email is safely handled
     */
    public function testSQLInjectionInEmailBlocked(): void
    {
        $maliciousEmail = "'; DROP TABLE users; --";

        // filter_var should reject this
        $this->assertFalse(filter_var($maliciousEmail, FILTER_VALIDATE_EMAIL));
    }

    /**
     * Test that SQL injection via prepared statement is harmless
     */
    public function testPreparedStatementPreventsInjection(): void
    {
        $maliciousName = "Robert'; DROP TABLE users; --";
        $hash = password_hash('password123', PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute(['safe@example.com', $hash, $maliciousName, 'student']);

        $userId = (int) $this->pdo->lastInsertId();
        $stmt = $this->pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        // The malicious string is stored as data, not executed as SQL
        $this->assertEquals($maliciousName, $row['name']);

        // Users table still exists
        $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM users");
        $this->assertGreaterThan(0, (int) $stmt->fetch()['cnt']);
    }

    /**
     * Test that XSS payload in lesson title is stored as data
     */
    public function testXSSInLessonTitleStoredSafely(): void
    {
        $teacherId = $this->insertUser();
        $xssTitle = '<script>alert("xss")</script>';

        $stmt = $this->pdo->prepare(
            "INSERT INTO lessons (teacher_id, title, description, subject, grade_level, is_published) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$teacherId, $xssTitle, 'Test', 'Bible', 'Grade 5', 1]);
        $lessonId = (int) $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("SELECT title FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        $row = $stmt->fetch();

        // Stored as-is in DB (not executed)
        $this->assertEquals($xssTitle, $row['title']);

        // json_encode escapes forward slashes, breaking </script> tags
        $json = json_encode(['title' => $row['title']]);
        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringContainsString('<\/script>', $json);
    }

    /**
     * Test that password hash is never the same as plain text
     */
    public function testPasswordNeverStoredInPlaintext(): void
    {
        $password = 'mysecretpass123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->assertNotEquals($password, $hash);
        $this->assertStringStartsWith('$2y$', $hash); // bcrypt prefix
        $this->assertGreaterThan(50, strlen($hash));
    }

    /**
     * Test that same password produces different hashes (salt)
     */
    public function testPasswordHashesAreSalted(): void
    {
        $password = 'samepassword';
        $hash1 = password_hash($password, PASSWORD_DEFAULT);
        $hash2 = password_hash($password, PASSWORD_DEFAULT);

        // Same password, different hashes due to random salt
        $this->assertNotEquals($hash1, $hash2);

        // Both still verify correctly
        $this->assertTrue(password_verify($password, $hash1));
        $this->assertTrue(password_verify($password, $hash2));
    }

    /**
     * Test that special characters in JSON block content are preserved
     */
    public function testSpecialCharsInBlockContent(): void
    {
        $teacherId = $this->insertUser();
        $lessonId = $this->insertLesson($teacherId);

        $content = json_encode([
            'body' => 'He said "God\'s love is <infinite> & eternal"',
            'verse' => 'John 3:16 — "For God so loved…"'
        ]);

        $stmt = $this->pdo->prepare(
            "INSERT INTO lesson_blocks (lesson_id, block_type, content, order_index) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$lessonId, 'text', $content, 0]);
        $blockId = (int) $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("SELECT content FROM lesson_blocks WHERE id = ?");
        $stmt->execute([$blockId]);
        $row = $stmt->fetch();

        $decoded = json_decode($row['content'], true);
        $this->assertStringContainsString('"God\'s love', $decoded['body']);
        $this->assertStringContainsString('&', $decoded['body']);
    }

    /**
     * Test that foreign key constraint prevents orphan blocks
     */
    public function testCannotCreateBlockForNonexistentLesson(): void
    {
        $this->expectException(\PDOException::class);

        $content = json_encode(['body' => 'Orphan block']);
        $stmt = $this->pdo->prepare(
            "INSERT INTO lesson_blocks (lesson_id, block_type, content, order_index) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([99999, 'text', $content, 0]);
    }

    /**
     * Test that deleting a user cascades to their lessons and progress
     */
    public function testDeleteUserCascadesCompletely(): void
    {
        $teacherId = $this->insertUser('cascade@test.com', 'teacher');
        $lessonId = $this->insertLesson($teacherId);
        $this->insertBlock($lessonId, 'text', 0);

        // Delete teacher
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$teacherId]);

        // Lessons should be gone
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as cnt FROM lessons WHERE teacher_id = ?");
        $stmt->execute([$teacherId]);
        $this->assertEquals(0, (int) $stmt->fetch()['cnt']);

        // Blocks should be gone too (cascade through lessons)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as cnt FROM lesson_blocks WHERE lesson_id = ?");
        $stmt->execute([$lessonId]);
        $this->assertEquals(0, (int) $stmt->fetch()['cnt']);
    }
}
