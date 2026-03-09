<?php
/**
 * LessonForge API Entry Point
 *
 * Routes all API requests to appropriate controllers.
 * Applies JWT authentication, RBAC, and rate limiting middleware.
 *
 * @package LessonForge
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === 'false' ? '0' : '1');

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

use LessonForge\Router;
use LessonForge\Controllers\AuthController;
use LessonForge\Controllers\LessonController;
use LessonForge\Controllers\ProgressController;
use LessonForge\Controllers\VerseController;
use LessonForge\Middleware\AuthMiddleware;
use LessonForge\Middleware\RateLimitMiddleware;

// Create router instance
$router = new Router();

// Middleware closures for reuse
$rateLimit = fn() => RateLimitMiddleware::check(5, 60);
$auth = fn() => AuthMiddleware::requireAuth();
$teacherOrAdmin = fn() => AuthMiddleware::requireRole(['teacher', 'admin']);
$adminOnly = fn() => AuthMiddleware::requireRole(['admin']);

// ============================================
// Public Routes (no auth required)
// ============================================
$router->post('/api/auth/register', [AuthController::class, 'register'], [$rateLimit]);
$router->post('/api/auth/login', [AuthController::class, 'login'], [$rateLimit]);

// ============================================
// User Routes (admin only)
// ============================================
$router->get('/api/users', [AuthController::class, 'getUsers'], [$adminOnly]);
$router->get('/api/users/{id}', [AuthController::class, 'getUser'], [$auth]);

// ============================================
// Lesson Routes
// ============================================
// Public read access for published lessons
$router->get('/api/lessons', [LessonController::class, 'index']);
$router->get('/api/lessons/{id}', [LessonController::class, 'show']);
$router->get('/api/lessons/stats/{teacherId}', [LessonController::class, 'stats'], [$auth]);

// Teacher/Admin write access
$router->post('/api/lessons', [LessonController::class, 'store'], [$teacherOrAdmin]);
$router->put('/api/lessons/{id}', [LessonController::class, 'update'], [$teacherOrAdmin]);
$router->delete('/api/lessons/{id}', [LessonController::class, 'destroy'], [$teacherOrAdmin]);

// Lesson Blocks
$router->get('/api/lessons/{id}/blocks', [LessonController::class, 'getBlocks']);
$router->post('/api/lessons/{id}/blocks', [LessonController::class, 'addBlock'], [$teacherOrAdmin]);
$router->put('/api/blocks/{id}', [LessonController::class, 'updateBlock'], [$teacherOrAdmin]);
$router->delete('/api/blocks/{id}', [LessonController::class, 'deleteBlock'], [$teacherOrAdmin]);
$router->post('/api/blocks/{id}/reorder', [LessonController::class, 'reorderBlock'], [$teacherOrAdmin]);

// ============================================
// Progress Routes (authenticated users)
// ============================================
$router->get('/api/progress/{userId}', [ProgressController::class, 'getByStudent'], [$auth]);
$router->get('/api/progress/{userId}/stats', [ProgressController::class, 'getStats'], [$auth]);
$router->get('/api/progress/{userId}/lessons/{lessonId}', [ProgressController::class, 'getLessonCompletion'], [$auth]);
$router->get('/api/lessons/{lessonId}/progress', [ProgressController::class, 'getByLesson'], [$auth]);
$router->post('/api/progress', [ProgressController::class, 'record'], [$auth]);

// ============================================
// Daily Verse Routes
// ============================================
// Public read access
$router->get('/api/verse', [VerseController::class, 'getToday']);
$router->get('/api/verses', [VerseController::class, 'getAll']);
$router->get('/api/verses/themes', [VerseController::class, 'getThemes']);
$router->get('/api/verses/theme/{theme}', [VerseController::class, 'getByTheme']);

// Admin write access
$router->post('/api/verses', [VerseController::class, 'store'], [$adminOnly]);
$router->put('/api/verses/{id}', [VerseController::class, 'update'], [$adminOnly]);
$router->delete('/api/verses/{id}', [VerseController::class, 'destroy'], [$adminOnly]);

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
                'POST /api/auth/login' => 'Login and get JWT token'
            ],
            'lessons' => [
                'GET /api/lessons' => 'List all lessons (public)',
                'POST /api/lessons' => 'Create a lesson (teacher/admin)',
                'GET /api/lessons/{id}' => 'Get lesson details (public)',
                'PUT /api/lessons/{id}' => 'Update a lesson (teacher/admin)',
                'DELETE /api/lessons/{id}' => 'Delete a lesson (teacher/admin)'
            ],
            'blocks' => [
                'POST /api/lessons/{id}/blocks' => 'Add a block (teacher/admin)',
                'PUT /api/blocks/{id}' => 'Update a block (teacher/admin)',
                'DELETE /api/blocks/{id}' => 'Delete a block (teacher/admin)'
            ],
            'progress' => [
                'GET /api/progress/{userId}' => 'Get student progress (auth)',
                'POST /api/progress' => 'Record progress (auth)'
            ],
            'verse' => [
                'GET /api/verse' => 'Get today\'s scripture verse (public)',
                'POST /api/verses' => 'Create verse (admin)',
                'PUT /api/verses/{id}' => 'Update verse (admin)',
                'DELETE /api/verses/{id}' => 'Delete verse (admin)'
            ]
        ]
    ];
});

// Dispatch the request
$router->dispatch();
