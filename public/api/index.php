<?php
/**
 * LessonForge API Entry Point
 * 
 * Routes all API requests to appropriate controllers.
 * 
 * @package LessonForge
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

use LessonForge\Router;
use LessonForge\Controllers\AuthController;
use LessonForge\Controllers\LessonController;
use LessonForge\Controllers\ProgressController;
use LessonForge\Controllers\VerseController;

// Create router instance
$router = new Router();

// ============================================
// Authentication Routes
// ============================================
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->get('/api/users', [AuthController::class, 'getUsers']);
$router->get('/api/users/{id}', [AuthController::class, 'getUser']);

// ============================================
// Lesson Routes
// ============================================
$router->get('/api/lessons', [LessonController::class, 'index']);
$router->post('/api/lessons', [LessonController::class, 'store']);
$router->get('/api/lessons/{id}', [LessonController::class, 'show']);
$router->put('/api/lessons/{id}', [LessonController::class, 'update']);
$router->delete('/api/lessons/{id}', [LessonController::class, 'destroy']);
$router->get('/api/lessons/stats/{teacherId}', [LessonController::class, 'stats']);

// Lesson Blocks
$router->get('/api/lessons/{id}/blocks', [LessonController::class, 'getBlocks']);
$router->post('/api/lessons/{id}/blocks', [LessonController::class, 'addBlock']);
$router->put('/api/blocks/{id}', [LessonController::class, 'updateBlock']);
$router->delete('/api/blocks/{id}', [LessonController::class, 'deleteBlock']);
$router->post('/api/blocks/{id}/reorder', [LessonController::class, 'reorderBlock']);

// ============================================
// Progress Routes
// ============================================
$router->get('/api/progress/{userId}', [ProgressController::class, 'getByStudent']);
$router->get('/api/progress/{userId}/stats', [ProgressController::class, 'getStats']);
$router->get('/api/progress/{userId}/lessons/{lessonId}', [ProgressController::class, 'getLessonCompletion']);
$router->get('/api/lessons/{lessonId}/progress', [ProgressController::class, 'getByLesson']);
$router->post('/api/progress', [ProgressController::class, 'record']);

// ============================================
// Daily Verse Routes
// ============================================
$router->get('/api/verse', [VerseController::class, 'getToday']);
$router->get('/api/verses', [VerseController::class, 'getAll']);
$router->get('/api/verses/themes', [VerseController::class, 'getThemes']);
$router->get('/api/verses/theme/{theme}', [VerseController::class, 'getByTheme']);
$router->post('/api/verses', [VerseController::class, 'store']);
$router->put('/api/verses/{id}', [VerseController::class, 'update']);
$router->delete('/api/verses/{id}', [VerseController::class, 'destroy']);

// ============================================
// API Info Route
// ============================================
$router->get('/api', function () {
    return [
        'name' => 'LessonForge API',
        'version' => '1.0.0',
        'description' => 'Interactive Lesson Builder & Progress Tracker',
        'endpoints' => [
            'auth' => [
                'POST /api/auth/register' => 'Register a new user',
                'POST /api/auth/login' => 'Login and get token'
            ],
            'lessons' => [
                'GET /api/lessons' => 'List all lessons',
                'POST /api/lessons' => 'Create a lesson',
                'GET /api/lessons/{id}' => 'Get lesson details',
                'PUT /api/lessons/{id}' => 'Update a lesson',
                'DELETE /api/lessons/{id}' => 'Delete a lesson'
            ],
            'blocks' => [
                'POST /api/lessons/{id}/blocks' => 'Add a block to lesson',
                'PUT /api/blocks/{id}' => 'Update a block',
                'DELETE /api/blocks/{id}' => 'Delete a block'
            ],
            'progress' => [
                'GET /api/progress/{userId}' => 'Get student progress',
                'POST /api/progress' => 'Record progress'
            ],
            'verse' => [
                'GET /api/verse' => 'Get today\'s scripture verse'
            ]
        ]
    ];
});

// Dispatch the request
$router->dispatch();
