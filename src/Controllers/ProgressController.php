<?php
/**
 * Progress Controller
 * 
 * Handles student progress tracking and statistics.
 * 
 * @package LessonForge\Controllers
 */

namespace LessonForge\Controllers;

use LessonForge\Models\Progress;
use LessonForge\Router;

class ProgressController
{
    private Progress $progressModel;

    public function __construct()
    {
        $this->progressModel = new Progress();
    }

    /**
     * Get progress for a student
     * GET /api/progress/{userId}
     */
    public function getByStudent(array $params): array
    {
        $progress = $this->progressModel->getByStudentId((int) $params['userId']);

        return [
            'success' => true,
            'progress' => $progress,
            'count' => count($progress)
        ];
    }

    /**
     * Get progress for a specific lesson
     * GET /api/lessons/{lessonId}/progress
     */
    public function getByLesson(array $params): array
    {
        $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : null;
        $progress = $this->progressModel->getByLessonId((int) $params['lessonId'], $studentId);

        return [
            'success' => true,
            'progress' => $progress
        ];
    }

    /**
     * Record progress
     * POST /api/progress
     */
    public function record(): array
    {
        $data = Router::getJsonBody();

        $result = $this->progressModel->record($data);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        $status = isset($result['created']) ? 201 : 200;
        http_response_code($status);

        return [
            'success' => true,
            'message' => 'Progress recorded',
            'progress_id' => $result['progress_id']
        ];
    }

    /**
     * Get lesson completion for a student
     * GET /api/progress/{userId}/lessons/{lessonId}
     */
    public function getLessonCompletion(array $params): array
    {
        $completion = $this->progressModel->getLessonCompletion(
            (int) $params['userId'],
            (int) $params['lessonId']
        );

        return [
            'success' => true,
            'completion' => $completion
        ];
    }

    /**
     * Get student dashboard statistics
     * GET /api/progress/{userId}/stats
     */
    public function getStats(array $params): array
    {
        $stats = $this->progressModel->getStudentStats((int) $params['userId']);

        return [
            'success' => true,
            'stats' => $stats
        ];
    }
}
