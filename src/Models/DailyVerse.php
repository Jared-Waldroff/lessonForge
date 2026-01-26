<?php
/**
 * DailyVerse Model
 * 
 * Manages scripture verses for the daily verse feature.
 * Provides wisdom and encouragement for learners.
 * 
 * @package LessonForge\Models
 */

namespace LessonForge\Models;

use LessonForge\Database;

class DailyVerse
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get today's verse
     * Falls back to a random verse if none is set for today
     */
    public function getToday(): array
    {
        // Try cache first
        $cacheKey = "daily_verse:" . date('Y-m-d');
        $cached = $this->db->getCached($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Try to get verse for today
        $stmt = $this->db->query(
            "SELECT * FROM daily_verses WHERE display_date = CURDATE() LIMIT 1"
        );

        $verse = $stmt->fetch();

        // Fall back to random verse
        if (!$verse) {
            $stmt = $this->db->query(
                "SELECT * FROM daily_verses ORDER BY RAND() LIMIT 1"
            );
            $verse = $stmt->fetch();
        }

        // If still no verse, return default
        if (!$verse) {
            $verse = [
                'id' => 0,
                'verse_reference' => 'Proverbs 1:7',
                'verse_text' => 'The fear of the Lord is the beginning of knowledge, but fools despise wisdom and instruction.',
                'theme' => 'Wisdom'
            ];
        }

        // Cache until midnight
        $secondsUntilMidnight = strtotime('tomorrow') - time();
        $this->db->cache($cacheKey, $verse, $secondsUntilMidnight);

        return $verse;
    }

    /**
     * Get all verses
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM daily_verses ORDER BY display_date DESC, id DESC"
        );

        return $stmt->fetchAll();
    }

    /**
     * Get verses by theme
     */
    public function getByTheme(string $theme): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM daily_verses WHERE theme = ? ORDER BY RAND()",
            [$theme]
        );

        return $stmt->fetchAll();
    }

    /**
     * Add a new verse
     */
    public function create(array $data): array
    {
        if (empty($data['verse_reference']) || empty($data['verse_text'])) {
            return ['success' => false, 'error' => 'Reference and text are required'];
        }

        try {
            $this->db->query(
                "INSERT INTO daily_verses (verse_reference, verse_text, theme, display_date) 
                 VALUES (?, ?, ?, ?)",
                [
                    $data['verse_reference'],
                    $data['verse_text'],
                    $data['theme'] ?? null,
                    $data['display_date'] ?? null
                ]
            );

            $verseId = (int) $this->db->getConnection()->lastInsertId();

            return [
                'success' => true,
                'verse' => $this->find($verseId)
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to create verse'];
        }
    }

    /**
     * Find a verse by ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->query(
            "SELECT * FROM daily_verses WHERE id = ?",
            [$id]
        );

        return $stmt->fetch() ?: null;
    }

    /**
     * Update a verse
     */
    public function update(int $id, array $data): array
    {
        $allowedFields = ['verse_reference', 'verse_text', 'theme', 'display_date'];
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
        $sql = "UPDATE daily_verses SET " . implode(', ', $updates) . " WHERE id = ?";

        try {
            $this->db->query($sql, $params);
            return ['success' => true, 'verse' => $this->find($id)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to update verse'];
        }
    }

    /**
     * Delete a verse
     */
    public function delete(int $id): array
    {
        try {
            $this->db->query("DELETE FROM daily_verses WHERE id = ?", [$id]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to delete verse'];
        }
    }

    /**
     * Get available themes
     */
    public function getThemes(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT theme FROM daily_verses WHERE theme IS NOT NULL ORDER BY theme"
        );

        return array_column($stmt->fetchAll(), 'theme');
    }
}
