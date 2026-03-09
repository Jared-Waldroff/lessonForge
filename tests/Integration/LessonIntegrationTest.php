<?php
/**
 * Lesson Integration Tests
 *
 * Tests lesson CRUD operations, block management, and cascade behaviors
 * against a real MariaDB database.
 *
 * @package LessonForge\Tests\Integration
 */

namespace LessonForge\Tests\Integration;

class LessonIntegrationTest extends DatabaseTestCase
{
    /**
     * Test creating a lesson with required fields
     */
    public function testCreateLesson(): void
    {
        $teacherId = $this->insertUser();
        $lessonId = $this->insertLesson($teacherId, 'Creation Story');

        $this->assertGreaterThan(0, $lessonId);

        $stmt = $this->pdo->prepare("SELECT * FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch();

        $this->assertEquals('Creation Story', $lesson['title']);
        $this->assertEquals($teacherId, (int) $lesson['teacher_id']);
        $this->assertEquals('Bible', $lesson['subject']);
    }

    /**
     * Test that blocks are associated with lessons correctly
     */
    public function testLessonWithBlocks(): void
    {
        $teacherId = $this->insertUser();
        $lessonId = $this->insertLesson($teacherId);

        // Add three blocks
        $blockId1 = $this->insertBlock($lessonId, 'text', 0);
        $blockId2 = $this->insertBlock($lessonId, 'quiz', 1);
        $blockId3 = $this->insertBlock($lessonId, 'scripture', 2);

        // Verify all blocks retrieved in order
        $stmt = $this->pdo->prepare(
            "SELECT * FROM lesson_blocks WHERE lesson_id = ? ORDER BY order_index"
        );
        $stmt->execute([$lessonId]);
        $blocks = $stmt->fetchAll();

        $this->assertCount(3, $blocks);
        $this->assertEquals('text', $blocks[0]['block_type']);
        $this->assertEquals('quiz', $blocks[1]['block_type']);
        $this->assertEquals('scripture', $blocks[2]['block_type']);
    }

    /**
     * Test that deleting a lesson cascades to blocks
     */
    public function testDeleteLessonCascadesBlocks(): void
    {
        $teacherId = $this->insertUser();
        $lessonId = $this->insertLesson($teacherId);
        $this->insertBlock($lessonId, 'text', 0);
        $this->insertBlock($lessonId, 'quiz', 1);

        // Delete the lesson
        $stmt = $this->pdo->prepare("DELETE FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);

        // Verify blocks are also deleted
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as cnt FROM lesson_blocks WHERE lesson_id = ?");
        $stmt->execute([$lessonId]);
        $row = $stmt->fetch();

        $this->assertEquals(0, (int) $row['cnt']);
    }

    /**
     * Test updating a lesson's publish status
     */
    public function testUpdateLessonPublishStatus(): void
    {
        $teacherId = $this->insertUser();
        $lessonId = $this->insertLesson($teacherId);

        $stmt = $this->pdo->prepare("UPDATE lessons SET is_published = ? WHERE id = ?");
        $stmt->execute([0, $lessonId]);

        $stmt = $this->pdo->prepare("SELECT is_published FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        $row = $stmt->fetch();

        $this->assertEquals(0, (int) $row['is_published']);
    }

    /**
     * Test filtering lessons by teacher
     */
    public function testFilterLessonsByTeacher(): void
    {
        $teacher1 = $this->insertUser('teacher1@example.com', 'teacher');
        $teacher2 = $this->insertUser('teacher2@example.com', 'teacher');

        $this->insertLesson($teacher1, 'Teacher 1 Lesson A');
        $this->insertLesson($teacher1, 'Teacher 1 Lesson B');
        $this->insertLesson($teacher2, 'Teacher 2 Lesson');

        $stmt = $this->pdo->prepare("SELECT * FROM lessons WHERE teacher_id = ?");
        $stmt->execute([$teacher1]);
        $lessons = $stmt->fetchAll();

        $this->assertCount(2, $lessons);
    }

    /**
     * Test all five block types are accepted
     */
    public function testAllBlockTypesAccepted(): void
    {
        $teacherId = $this->insertUser();
        $lessonId = $this->insertLesson($teacherId);

        $types = ['text', 'quiz', 'video', 'image', 'scripture'];
        foreach ($types as $i => $type) {
            $blockId = $this->insertBlock($lessonId, $type, $i);
            $this->assertGreaterThan(0, $blockId);
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) as cnt FROM lesson_blocks WHERE lesson_id = ?");
        $stmt->execute([$lessonId]);
        $row = $stmt->fetch();

        $this->assertEquals(5, (int) $row['cnt']);
    }

    /**
     * Test block content is stored as JSON
     */
    public function testBlockContentStoredAsJson(): void
    {
        $teacherId = $this->insertUser();
        $lessonId = $this->insertLesson($teacherId);

        $content = json_encode([
            'question' => 'Who created the heavens and the earth?',
            'options' => ['God', 'Moses', 'Abraham'],
            'correct_index' => 0
        ]);

        $stmt = $this->pdo->prepare(
            "INSERT INTO lesson_blocks (lesson_id, block_type, content, order_index) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$lessonId, 'quiz', $content, 0]);
        $blockId = (int) $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("SELECT content FROM lesson_blocks WHERE id = ?");
        $stmt->execute([$blockId]);
        $row = $stmt->fetch();

        $decoded = json_decode($row['content'], true);
        $this->assertEquals('Who created the heavens and the earth?', $decoded['question']);
        $this->assertCount(3, $decoded['options']);
        $this->assertEquals(0, $decoded['correct_index']);
    }
}
