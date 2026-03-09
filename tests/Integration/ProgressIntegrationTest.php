<?php
/**
 * Progress Tracking Integration Tests
 *
 * Tests the student progress workflow: recording progress,
 * calculating completion percentages, and tracking stats.
 *
 * @package LessonForge\Tests\Integration
 */

namespace LessonForge\Tests\Integration;

class ProgressIntegrationTest extends DatabaseTestCase
{
    /**
     * Test recording student progress on a block
     */
    public function testRecordProgress(): void
    {
        $teacherId = $this->insertUser('teacher@test.com', 'teacher');
        $studentId = $this->insertUser('student@test.com', 'student');
        $lessonId = $this->insertLesson($teacherId);
        $blockId = $this->insertBlock($lessonId, 'text', 0);

        $stmt = $this->pdo->prepare(
            "INSERT INTO student_progress (student_id, lesson_id, block_id, status, score, time_spent_seconds) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$studentId, $lessonId, $blockId, 'completed', 95.00, 120]);

        $stmt = $this->pdo->prepare("SELECT * FROM student_progress WHERE student_id = ? AND lesson_id = ?");
        $stmt->execute([$studentId, $lessonId]);
        $progress = $stmt->fetch();

        $this->assertNotFalse($progress);
        $this->assertEquals('completed', $progress['status']);
        $this->assertEquals(95.00, (float) $progress['score']);
        $this->assertEquals(120, (int) $progress['time_spent_seconds']);
    }

    /**
     * Test completion percentage calculation
     */
    public function testCompletionPercentage(): void
    {
        $teacherId = $this->insertUser('teacher@test.com', 'teacher');
        $studentId = $this->insertUser('student@test.com', 'student');
        $lessonId = $this->insertLesson($teacherId);

        // Create 4 blocks
        $blockIds = [];
        for ($i = 0; $i < 4; $i++) {
            $blockIds[] = $this->insertBlock($lessonId, 'text', $i);
        }

        // Complete 3 of 4 blocks
        $stmt = $this->pdo->prepare(
            "INSERT INTO student_progress (student_id, lesson_id, block_id, status) VALUES (?, ?, ?, ?)"
        );
        for ($i = 0; $i < 3; $i++) {
            $stmt->execute([$studentId, $lessonId, $blockIds[$i], 'completed']);
        }

        // Calculate completion
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM lesson_blocks WHERE lesson_id = ?");
        $stmt->execute([$lessonId]);
        $totalBlocks = (int) $stmt->fetch()['total'];

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as done FROM student_progress WHERE student_id = ? AND lesson_id = ? AND status = 'completed'"
        );
        $stmt->execute([$studentId, $lessonId]);
        $completedBlocks = (int) $stmt->fetch()['done'];

        $percentage = ($completedBlocks / $totalBlocks) * 100;

        $this->assertEquals(4, $totalBlocks);
        $this->assertEquals(3, $completedBlocks);
        $this->assertEquals(75.0, $percentage);
    }

    /**
     * Test that duplicate progress is prevented by unique constraint
     */
    public function testDuplicateProgressPrevented(): void
    {
        $teacherId = $this->insertUser('teacher@test.com', 'teacher');
        $studentId = $this->insertUser('student@test.com', 'student');
        $lessonId = $this->insertLesson($teacherId);
        $blockId = $this->insertBlock($lessonId, 'quiz', 0);

        $stmt = $this->pdo->prepare(
            "INSERT INTO student_progress (student_id, lesson_id, block_id, status) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$studentId, $lessonId, $blockId, 'completed']);

        $this->expectException(\PDOException::class);
        $stmt->execute([$studentId, $lessonId, $blockId, 'completed']);
    }

    /**
     * Test average score calculation across blocks
     */
    public function testAverageScoreCalculation(): void
    {
        $teacherId = $this->insertUser('teacher@test.com', 'teacher');
        $studentId = $this->insertUser('student@test.com', 'student');
        $lessonId = $this->insertLesson($teacherId);

        $scores = [80.00, 90.00, 100.00];
        $stmt = $this->pdo->prepare(
            "INSERT INTO student_progress (student_id, lesson_id, block_id, status, score) VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($scores as $i => $score) {
            $blockId = $this->insertBlock($lessonId, 'quiz', $i);
            $stmt->execute([$studentId, $lessonId, $blockId, 'completed', $score]);
        }

        $stmt = $this->pdo->prepare(
            "SELECT AVG(score) as avg_score FROM student_progress WHERE student_id = ? AND lesson_id = ? AND score IS NOT NULL"
        );
        $stmt->execute([$studentId, $lessonId]);
        $avg = (float) $stmt->fetch()['avg_score'];

        $this->assertEquals(90.0, $avg);
    }

    /**
     * Test time spent accumulation
     */
    public function testTimeSpentTracking(): void
    {
        $teacherId = $this->insertUser('teacher@test.com', 'teacher');
        $studentId = $this->insertUser('student@test.com', 'student');
        $lessonId = $this->insertLesson($teacherId);

        $stmt = $this->pdo->prepare(
            "INSERT INTO student_progress (student_id, lesson_id, block_id, status, time_spent_seconds) VALUES (?, ?, ?, ?, ?)"
        );

        $block1 = $this->insertBlock($lessonId, 'text', 0);
        $block2 = $this->insertBlock($lessonId, 'quiz', 1);
        $stmt->execute([$studentId, $lessonId, $block1, 'completed', 300]);
        $stmt->execute([$studentId, $lessonId, $block2, 'completed', 450]);

        $stmt = $this->pdo->prepare(
            "SELECT SUM(time_spent_seconds) as total_time FROM student_progress WHERE student_id = ? AND lesson_id = ?"
        );
        $stmt->execute([$studentId, $lessonId]);
        $totalTime = (int) $stmt->fetch()['total_time'];

        $this->assertEquals(750, $totalTime); // 12m 30s
    }
}
