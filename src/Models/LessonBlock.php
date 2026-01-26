<?php
/**
 * LessonBlock Model
 * 
 * Manages content blocks within lessons (text, quiz, video, image, scripture).
 * 
 * @package LessonForge\Models
 */

namespace LessonForge\Models;

use LessonForge\Database;

class LessonBlock
{
    private Database $db;

    // Valid block types
    public const TYPE_TEXT = 'text';
    public const TYPE_QUIZ = 'quiz';
    public const TYPE_VIDEO = 'video';
    public const TYPE_IMAGE = 'image';
    public const TYPE_SCRIPTURE = 'scripture';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all blocks for a lesson
     */
    public function getByLessonId(int $lessonId): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM lesson_blocks WHERE lesson_id = ? ORDER BY order_index",
            [$lessonId]
        );

        $blocks = $stmt->fetchAll();

        // Decode JSON content
        foreach ($blocks as &$block) {
            $block['content'] = json_decode($block['content'], true);
        }

        return $blocks;
    }

    /**
     * Get a single block by ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->query(
            "SELECT * FROM lesson_blocks WHERE id = ?",
            [$id]
        );

        $block = $stmt->fetch();

        if ($block) {
            $block['content'] = json_decode($block['content'], true);
        }

        return $block ?: null;
    }

    /**
     * Create a new block
     */
    public function create(array $data): array
    {
        // Validate required fields
        if (empty($data['lesson_id']) || empty($data['block_type']) || !isset($data['content'])) {
            return ['success' => false, 'error' => 'Missing required fields'];
        }

        // Validate block type
        $validTypes = [self::TYPE_TEXT, self::TYPE_QUIZ, self::TYPE_VIDEO, self::TYPE_IMAGE, self::TYPE_SCRIPTURE];
        if (!in_array($data['block_type'], $validTypes)) {
            return ['success' => false, 'error' => 'Invalid block type'];
        }

        // Get next order index
        $stmt = $this->db->query(
            "SELECT COALESCE(MAX(order_index), 0) + 1 as next_order FROM lesson_blocks WHERE lesson_id = ?",
            [$data['lesson_id']]
        );
        $orderIndex = $data['order_index'] ?? $stmt->fetch()['next_order'];

        try {
            $content = is_array($data['content']) ? json_encode($data['content']) : $data['content'];

            $this->db->query(
                "INSERT INTO lesson_blocks (lesson_id, block_type, content, order_index) VALUES (?, ?, ?, ?)",
                [$data['lesson_id'], $data['block_type'], $content, $orderIndex]
            );

            $blockId = (int) $this->db->getConnection()->lastInsertId();

            // Invalidate lesson cache
            $this->db->cache("lesson:{$data['lesson_id']}", null, 0);

            return [
                'success' => true,
                'block' => $this->find($blockId)
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to create block'];
        }
    }

    /**
     * Update a block
     */
    public function update(int $id, array $data): array
    {
        $block = $this->find($id);
        if (!$block) {
            return ['success' => false, 'error' => 'Block not found'];
        }

        $updates = [];
        $params = [];

        if (isset($data['content'])) {
            $updates[] = "content = ?";
            $params[] = is_array($data['content']) ? json_encode($data['content']) : $data['content'];
        }

        if (isset($data['order_index'])) {
            $updates[] = "order_index = ?";
            $params[] = $data['order_index'];
        }

        if (isset($data['block_type'])) {
            $validTypes = [self::TYPE_TEXT, self::TYPE_QUIZ, self::TYPE_VIDEO, self::TYPE_IMAGE, self::TYPE_SCRIPTURE];
            if (!in_array($data['block_type'], $validTypes)) {
                return ['success' => false, 'error' => 'Invalid block type'];
            }
            $updates[] = "block_type = ?";
            $params[] = $data['block_type'];
        }

        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }

        $params[] = $id;
        $sql = "UPDATE lesson_blocks SET " . implode(', ', $updates) . " WHERE id = ?";

        try {
            $this->db->query($sql, $params);

            // Invalidate lesson cache
            $this->db->cache("lesson:{$block['lesson_id']}", null, 0);

            return [
                'success' => true,
                'block' => $this->find($id)
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to update block'];
        }
    }

    /**
     * Delete a block
     */
    public function delete(int $id): array
    {
        $block = $this->find($id);
        if (!$block) {
            return ['success' => false, 'error' => 'Block not found'];
        }

        try {
            $this->db->query("DELETE FROM lesson_blocks WHERE id = ?", [$id]);

            // Reorder remaining blocks
            $this->reorderBlocks($block['lesson_id']);

            // Invalidate lesson cache
            $this->db->cache("lesson:{$block['lesson_id']}", null, 0);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to delete block'];
        }
    }

    /**
     * Reorder blocks after deletion
     */
    private function reorderBlocks(int $lessonId): void
    {
        $blocks = $this->getByLessonId($lessonId);

        foreach ($blocks as $index => $block) {
            if ($block['order_index'] !== $index + 1) {
                $this->db->query(
                    "UPDATE lesson_blocks SET order_index = ? WHERE id = ?",
                    [$index + 1, $block['id']]
                );
            }
        }
    }

    /**
     * Move a block to a new position
     */
    public function reorder(int $blockId, int $newPosition): array
    {
        $block = $this->find($blockId);
        if (!$block) {
            return ['success' => false, 'error' => 'Block not found'];
        }

        $blocks = $this->getByLessonId($block['lesson_id']);
        $oldPosition = $block['order_index'];

        if ($newPosition < 1 || $newPosition > count($blocks)) {
            return ['success' => false, 'error' => 'Invalid position'];
        }

        try {
            if ($newPosition > $oldPosition) {
                // Moving down
                $this->db->query(
                    "UPDATE lesson_blocks SET order_index = order_index - 1 
                     WHERE lesson_id = ? AND order_index > ? AND order_index <= ?",
                    [$block['lesson_id'], $oldPosition, $newPosition]
                );
            } else {
                // Moving up
                $this->db->query(
                    "UPDATE lesson_blocks SET order_index = order_index + 1 
                     WHERE lesson_id = ? AND order_index >= ? AND order_index < ?",
                    [$block['lesson_id'], $newPosition, $oldPosition]
                );
            }

            $this->db->query(
                "UPDATE lesson_blocks SET order_index = ? WHERE id = ?",
                [$newPosition, $blockId]
            );

            // Invalidate lesson cache
            $this->db->cache("lesson:{$block['lesson_id']}", null, 0);

            return ['success' => true, 'blocks' => $this->getByLessonId($block['lesson_id'])];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to reorder blocks'];
        }
    }
}
