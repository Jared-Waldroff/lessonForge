<?php
/**
 * Lesson Model
 * 
 * Handles lesson CRUD operations and relationships with blocks.
 * 
 * @package LessonForge\Models
 */

namespace LessonForge\Models;

use LessonForge\Database;

class Lesson
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all lessons (optionally filtered)
     */
    public function getAll(?int $teacherId = null, bool $publishedOnly = false): array
    {
        $sql = "SELECT l.*, u.name as teacher_name 
                FROM lessons l 
                JOIN users u ON l.teacher_id = u.id";
        $params = [];
        $conditions = [];

        if ($teacherId !== null) {
            $conditions[] = "l.teacher_id = ?";
            $params[] = $teacherId;
        }

        if ($publishedOnly) {
            $conditions[] = "l.is_published = 1";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY l.created_at DESC";

        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get a single lesson by ID with its blocks
     */
    public function find(int $id): ?array
    {
        // Try cache first
        $cacheKey = "lesson:{$id}";
        $cached = $this->db->getCached($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Get lesson
        $stmt = $this->db->query(
            "SELECT l.*, u.name as teacher_name 
             FROM lessons l 
             JOIN users u ON l.teacher_id = u.id 
             WHERE l.id = ?",
            [$id]
        );

        $lesson = $stmt->fetch();

        if (!$lesson) {
            return null;
        }

        // Get blocks
        $stmt = $this->db->query(
            "SELECT * FROM lesson_blocks WHERE lesson_id = ? ORDER BY order_index",
            [$id]
        );

        $blocks = $stmt->fetchAll();

        // Decode JSON content in blocks
        foreach ($blocks as &$block) {
            $block['content'] = json_decode($block['content'], true);
        }

        $lesson['blocks'] = $blocks;

        // Cache for 5 minutes
        $this->db->cache($cacheKey, $lesson, 300);

        return $lesson;
    }

    /**
     * Create a new lesson
     */
    public function create(array $data): array
    {
        $required = ['teacher_id', 'title'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Missing required field: {$field}"];
            }
        }

        try {
            $this->db->query(
                "INSERT INTO lessons (teacher_id, title, description, subject, grade_level, is_published) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $data['teacher_id'],
                    $data['title'],
                    $data['description'] ?? null,
                    $data['subject'] ?? null,
                    $data['grade_level'] ?? null,
                    $data['is_published'] ?? false
                ]
            );

            $lessonId = (int) $this->db->getConnection()->lastInsertId();

            return [
                'success' => true,
                'lesson' => $this->find($lessonId)
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to create lesson'];
        }
    }

    /**
     * Update a lesson
     */
    public function update(int $id, array $data): array
    {
        $allowedFields = ['title', 'description', 'subject', 'grade_level', 'is_published'];
        $updates = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }

        $params[] = $id;
        $sql = "UPDATE lessons SET " . implode(', ', $updates) . " WHERE id = ?";

        try {
            $this->db->query($sql, $params);

            // Invalidate cache
            $this->db->cache("lesson:{$id}", null, 0);

            return [
                'success' => true,
                'lesson' => $this->find($id)
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to update lesson'];
        }
    }

    /**
     * Delete a lesson
     */
    public function delete(int $id): array
    {
        try {
            $this->db->query("DELETE FROM lessons WHERE id = ?", [$id]);

            // Invalidate cache
            $this->db->cache("lesson:{$id}", null, 0);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to delete lesson'];
        }
    }

    /**
     * Get lesson statistics
     */
    public function getStats(int $teacherId): array
    {
        $stmt = $this->db->query(
            "SELECT 
                COUNT(*) as total_lessons,
                SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) as drafts
             FROM lessons 
             WHERE teacher_id = ?",
            [$teacherId]
        );

        return $stmt->fetch() ?: ['total_lessons' => 0, 'published' => 0, 'drafts' => 0];
    }
}
