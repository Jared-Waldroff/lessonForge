<?php
/**
 * Lesson Controller
 * 
 * Handles lesson CRUD operations and block management.
 * 
 * @package LessonForge\Controllers
 */

namespace LessonForge\Controllers;

use LessonForge\Models\Lesson;
use LessonForge\Models\LessonBlock;
use LessonForge\Router;

class LessonController
{
    private Lesson $lessonModel;
    private LessonBlock $blockModel;

    public function __construct()
    {
        $this->lessonModel = new Lesson();
        $this->blockModel = new LessonBlock();
    }

    /**
     * Get all lessons
     * GET /api/lessons
     */
    public function index(): array
    {
        $teacherId = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : null;
        $publishedOnly = isset($_GET['published']) && $_GET['published'] === 'true';

        $lessons = $this->lessonModel->getAll($teacherId, $publishedOnly);

        return [
            'success' => true,
            'lessons' => $lessons,
            'count' => count($lessons)
        ];
    }

    /**
     * Get a single lesson with blocks
     * GET /api/lessons/{id}
     */
    public function show(array $params): array
    {
        $lesson = $this->lessonModel->find((int) $params['id']);

        if (!$lesson) {
            http_response_code(404);
            return ['error' => true, 'message' => 'Lesson not found'];
        }

        return [
            'success' => true,
            'lesson' => $lesson
        ];
    }

    /**
     * Create a new lesson
     * POST /api/lessons
     */
    public function store(): array
    {
        $data = Router::getJsonBody();

        $result = $this->lessonModel->create($data);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        http_response_code(201);
        return [
            'success' => true,
            'message' => 'Lesson created successfully',
            'lesson' => $result['lesson']
        ];
    }

    /**
     * Update a lesson
     * PUT /api/lessons/{id}
     */
    public function update(array $params): array
    {
        $data = Router::getJsonBody();
        $result = $this->lessonModel->update((int) $params['id'], $data);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        return [
            'success' => true,
            'message' => 'Lesson updated successfully',
            'lesson' => $result['lesson']
        ];
    }

    /**
     * Delete a lesson
     * DELETE /api/lessons/{id}
     */
    public function destroy(array $params): array
    {
        $result = $this->lessonModel->delete((int) $params['id']);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        return [
            'success' => true,
            'message' => 'Lesson deleted successfully'
        ];
    }

    /**
     * Get lesson statistics for a teacher
     * GET /api/lessons/stats/{teacherId}
     */
    public function stats(array $params): array
    {
        $stats = $this->lessonModel->getStats((int) $params['teacherId']);

        return [
            'success' => true,
            'stats' => $stats
        ];
    }

    /**
     * Get blocks for a lesson
     * GET /api/lessons/{id}/blocks
     */
    public function getBlocks(array $params): array
    {
        $blocks = $this->blockModel->getByLessonId((int) $params['id']);

        return [
            'success' => true,
            'blocks' => $blocks,
            'count' => count($blocks)
        ];
    }

    /**
     * Add a block to a lesson
     * POST /api/lessons/{id}/blocks
     */
    public function addBlock(array $params): array
    {
        $data = Router::getJsonBody();
        $data['lesson_id'] = (int) $params['id'];

        $result = $this->blockModel->create($data);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        http_response_code(201);
        return [
            'success' => true,
            'message' => 'Block added successfully',
            'block' => $result['block']
        ];
    }

    /**
     * Update a block
     * PUT /api/blocks/{id}
     */
    public function updateBlock(array $params): array
    {
        $data = Router::getJsonBody();
        $result = $this->blockModel->update((int) $params['id'], $data);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        return [
            'success' => true,
            'message' => 'Block updated successfully',
            'block' => $result['block']
        ];
    }

    /**
     * Delete a block
     * DELETE /api/blocks/{id}
     */
    public function deleteBlock(array $params): array
    {
        $result = $this->blockModel->delete((int) $params['id']);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        return [
            'success' => true,
            'message' => 'Block deleted successfully'
        ];
    }

    /**
     * Reorder a block
     * POST /api/blocks/{id}/reorder
     */
    public function reorderBlock(array $params): array
    {
        $data = Router::getJsonBody();

        if (!isset($data['position'])) {
            http_response_code(400);
            return ['error' => true, 'message' => 'Position is required'];
        }

        $result = $this->blockModel->reorder((int) $params['id'], (int) $data['position']);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        return [
            'success' => true,
            'blocks' => $result['blocks']
        ];
    }
}
