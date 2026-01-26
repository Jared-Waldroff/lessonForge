<?php
/**
 * Progress Model
 * 
 * Tracks student progress through lessons and quiz scores.
 * 
 * @package LessonForge\Models
 */

namespace LessonForge\Models;

use LessonForge\Database;

class Progress
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all progress for a student
     */
    public function getByStudentId(int $studentId): array
    {
        $stmt = $this->db->query(
            "SELECT p.*, l.title as lesson_title, l.subject 
             FROM student_progress p
             JOIN lessons l ON p.lesson_id = l.id
             WHERE p.student_id = ?
             ORDER BY p.updated_at DESC",
            [$studentId]
        );

        return $stmt->fetchAll();
    }

    /**
     * Get progress for a specific lesson
     */
    public function getByLessonId(int $lessonId, ?int $studentId = null): array
    {
        if ($studentId) {
            $stmt = $this->db->query(
                "SELECT * FROM student_progress WHERE lesson_id = ? AND student_id = ?",
                [$lessonId, $studentId]
            );
        } else {
            $stmt = $this->db->query(
                "SELECT p.*, u.name as student_name 
                 FROM student_progress p
                 JOIN users u ON p.student_id = u.id
                 WHERE p.lesson_id = ?",
                [$lessonId]
            );
        }

        return $stmt->fetchAll();
    }

    /**
     * Record or update progress
     */
    public function record(array $data): array
    {
        $required = ['student_id', 'lesson_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Missing required field: {$field}"];
            }
        }

        // Check if progress exists
        $stmt = $this->db->query(
            "SELECT id FROM student_progress 
             WHERE student_id = ? AND lesson_id = ? AND (block_id = ? OR (block_id IS NULL AND ? IS NULL))",
            [
                $data['student_id'],
                $data['lesson_id'],
                $data['block_id'] ?? null,
                $data['block_id'] ?? null
            ]
        );

        $existing = $stmt->fetch();

        try {
            if ($existing) {
                // Update existing progress
                $updates = [];
                $params = [];

                if (isset($data['status'])) {
                    $updates[] = "status = ?";
                    $params[] = $data['status'];

                    if ($data['status'] === 'completed') {
                        $updates[] = "completed_at = CURRENT_TIMESTAMP";
                    }
                }

                if (isset($data['score'])) {
                    $updates[] = "score = ?";
                    $params[] = $data['score'];
                }

                if (isset($data['time_spent_seconds'])) {
                    $updates[] = "time_spent_seconds = time_spent_seconds + ?";
                    $params[] = $data['time_spent_seconds'];
                }

                if (!empty($updates)) {
                    $params[] = $existing['id'];
                    $sql = "UPDATE student_progress SET " . implode(', ', $updates) . " WHERE id = ?";
                    $this->db->query($sql, $params);
                }

                return ['success' => true, 'progress_id' => $existing['id'], 'updated' => true];
            } else {
                // Create new progress
                $this->db->query(
                    "INSERT INTO student_progress (student_id, lesson_id, block_id, status, score, time_spent_seconds) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $data['student_id'],
                        $data['lesson_id'],
                        $data['block_id'] ?? null,
                        $data['status'] ?? 'in_progress',
                        $data['score'] ?? null,
                        $data['time_spent_seconds'] ?? 0
                    ]
                );

                $progressId = (int) $this->db->getConnection()->lastInsertId();
                return ['success' => true, 'progress_id' => $progressId, 'created' => true];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to record progress'];
        }
    }

    /**
     * Get lesson completion percentage for a student
     */
    public function getLessonCompletion(int $studentId, int $lessonId): array
    {
        // Get total blocks in lesson
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM lesson_blocks WHERE lesson_id = ?",
            [$lessonId]
        );
        $total = $stmt->fetch()['total'];

        // Get completed blocks
        $stmt = $this->db->query(
            "SELECT COUNT(*) as completed FROM student_progress 
             WHERE student_id = ? AND lesson_id = ? AND status = 'completed' AND block_id IS NOT NULL",
            [$studentId, $lessonId]
        );
        $completed = $stmt->fetch()['completed'];

        // Get average quiz score
        $stmt = $this->db->query(
            "SELECT AVG(score) as avg_score FROM student_progress 
             WHERE student_id = ? AND lesson_id = ? AND score IS NOT NULL",
            [$studentId, $lessonId]
        );
        $avgScore = $stmt->fetch()['avg_score'];

        return [
            'total_blocks' => (int) $total,
            'completed_blocks' => (int) $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'average_score' => $avgScore ? round($avgScore, 1) : null
        ];
    }

    /**
     * Get student dashboard statistics
     */
    public function getStudentStats(int $studentId): array
    {
        // Lessons started
        $stmt = $this->db->query(
            "SELECT COUNT(DISTINCT lesson_id) as started FROM student_progress WHERE student_id = ?",
            [$studentId]
        );
        $started = $stmt->fetch()['started'];

        // Lessons completed (all blocks done)
        $stmt = $this->db->query(
            "SELECT COUNT(*) as completed FROM (
                SELECT p.lesson_id 
                FROM student_progress p
                JOIN lesson_blocks lb ON p.lesson_id = lb.lesson_id
                WHERE p.student_id = ? AND p.status = 'completed' AND p.block_id IS NOT NULL
                GROUP BY p.lesson_id
                HAVING COUNT(DISTINCT p.block_id) = COUNT(DISTINCT lb.id)
            ) as completed_lessons",
            [$studentId]
        );
        $completed = $stmt->fetch()['completed'];

        // Average score
        $stmt = $this->db->query(
            "SELECT AVG(score) as avg_score FROM student_progress 
             WHERE student_id = ? AND score IS NOT NULL",
            [$studentId]
        );
        $avgScore = $stmt->fetch()['avg_score'];

        // Total time spent
        $stmt = $this->db->query(
            "SELECT SUM(time_spent_seconds) as total_time FROM student_progress WHERE student_id = ?",
            [$studentId]
        );
        $totalTime = $stmt->fetch()['total_time'];

        return [
            'lessons_started' => (int) $started,
            'lessons_completed' => (int) $completed,
            'average_score' => $avgScore ? round($avgScore, 1) : null,
            'total_time_seconds' => (int) $totalTime,
            'total_time_formatted' => $this->formatTime((int) $totalTime)
        ];
    }

    /**
     * Format seconds into human-readable time
     */
    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }
}
