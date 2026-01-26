<?php
/**
 * Verse Controller
 * 
 * Handles daily scripture verse API.
 * 
 * @package LessonForge\Controllers
 */

namespace LessonForge\Controllers;

use LessonForge\Models\DailyVerse;
use LessonForge\Router;

class VerseController
{
    private DailyVerse $verseModel;

    public function __construct()
    {
        $this->verseModel = new DailyVerse();
    }

    /**
     * Get today's verse
     * GET /api/verse
     */
    public function getToday(): array
    {
        $verse = $this->verseModel->getToday();

        return [
            'success' => true,
            'verse' => $verse
        ];
    }

    /**
     * Get all verses
     * GET /api/verses
     */
    public function getAll(): array
    {
        $verses = $this->verseModel->getAll();

        return [
            'success' => true,
            'verses' => $verses,
            'count' => count($verses)
        ];
    }

    /**
     * Get verses by theme
     * GET /api/verses/theme/{theme}
     */
    public function getByTheme(array $params): array
    {
        $verses = $this->verseModel->getByTheme($params['theme']);

        return [
            'success' => true,
            'theme' => $params['theme'],
            'verses' => $verses,
            'count' => count($verses)
        ];
    }

    /**
     * Get available themes
     * GET /api/verses/themes
     */
    public function getThemes(): array
    {
        $themes = $this->verseModel->getThemes();

        return [
            'success' => true,
            'themes' => $themes
        ];
    }

    /**
     * Create a new verse
     * POST /api/verses
     */
    public function store(): array
    {
        $data = Router::getJsonBody();

        $result = $this->verseModel->create($data);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        http_response_code(201);
        return [
            'success' => true,
            'message' => 'Verse created successfully',
            'verse' => $result['verse']
        ];
    }

    /**
     * Update a verse
     * PUT /api/verses/{id}
     */
    public function update(array $params): array
    {
        $data = Router::getJsonBody();
        $result = $this->verseModel->update((int) $params['id'], $data);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        return [
            'success' => true,
            'message' => 'Verse updated successfully',
            'verse' => $result['verse']
        ];
    }

    /**
     * Delete a verse
     * DELETE /api/verses/{id}
     */
    public function destroy(array $params): array
    {
        $result = $this->verseModel->delete((int) $params['id']);

        if (!$result['success']) {
            http_response_code(400);
            return ['error' => true, 'message' => $result['error']];
        }

        return [
            'success' => true,
            'message' => 'Verse deleted successfully'
        ];
    }
}
