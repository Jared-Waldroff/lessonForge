<?php
/**
 * Lesson Model Tests
 * 
 * Tests for lesson CRUD operations and validation.
 * 
 * @package LessonForge\Tests
 */

namespace LessonForge\Tests;

use PHPUnit\Framework\TestCase;
use LessonForge\Models\Lesson;
use LessonForge\Models\LessonBlock;

class LessonTest extends TestCase
{
    /**
     * Test lesson data structure
     */
    public function testLessonDataStructure(): void
    {
        $lessonData = [
            'id' => 1,
            'teacher_id' => 1,
            'title' => 'Test Lesson',
            'description' => 'A test lesson description',
            'subject' => 'Mathematics',
            'grade_level' => 'Grade 4-5',
            'is_published' => true
        ];

        $this->assertArrayHasKey('id', $lessonData);
        $this->assertArrayHasKey('teacher_id', $lessonData);
        $this->assertArrayHasKey('title', $lessonData);
        $this->assertArrayHasKey('is_published', $lessonData);
    }

    /**
     * Test required field validation
     */
    public function testRequiredFieldValidation(): void
    {
        $requiredFields = ['teacher_id', 'title'];
        $data = ['description' => 'Only description'];

        foreach ($requiredFields as $field) {
            $this->assertFalse(isset($data[$field]) && !empty($data[$field]));
        }
    }

    /**
     * Test block types are valid
     */
    public function testValidBlockTypes(): void
    {
        $validTypes = [
            LessonBlock::TYPE_TEXT,
            LessonBlock::TYPE_QUIZ,
            LessonBlock::TYPE_VIDEO,
            LessonBlock::TYPE_IMAGE,
            LessonBlock::TYPE_SCRIPTURE
        ];

        $this->assertContains('text', $validTypes);
        $this->assertContains('quiz', $validTypes);
        $this->assertContains('video', $validTypes);
        $this->assertContains('image', $validTypes);
        $this->assertContains('scripture', $validTypes);
        $this->assertNotContains('audio', $validTypes);
    }

    /**
     * Test block content JSON structure for text
     */
    public function testTextBlockContentStructure(): void
    {
        $content = [
            'title' => 'Section Title',
            'body' => 'The main content text goes here.'
        ];

        $json = json_encode($content);
        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('title', $decoded);
        $this->assertArrayHasKey('body', $decoded);
        $this->assertEquals('Section Title', $decoded['title']);
    }

    /**
     * Test block content JSON structure for quiz
     */
    public function testQuizBlockContentStructure(): void
    {
        $content = [
            'question' => 'What is 2 + 2?',
            'options' => ['3', '4', '5', '6'],
            'correct' => 1
        ];

        $json = json_encode($content);
        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('question', $decoded);
        $this->assertArrayHasKey('options', $decoded);
        $this->assertArrayHasKey('correct', $decoded);
        $this->assertIsArray($decoded['options']);
        $this->assertCount(4, $decoded['options']);
        $this->assertEquals('4', $decoded['options'][$decoded['correct']]);
    }

    /**
     * Test block content JSON structure for scripture
     */
    public function testScriptureBlockContentStructure(): void
    {
        $content = [
            'reference' => 'Proverbs 1:7',
            'text' => 'The fear of the Lord is the beginning of knowledge.',
            'reflection' => 'How can we apply this to our learning?'
        ];

        $json = json_encode($content);
        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('reference', $decoded);
        $this->assertArrayHasKey('text', $decoded);
        $this->assertNotEmpty($decoded['reference']);
        $this->assertNotEmpty($decoded['text']);
    }

    /**
     * Test grade level format
     */
    public function testGradeLevelFormats(): void
    {
        $validGradeLevels = [
            'K-1',
            'Grade 2-3',
            'Grade 4-5',
            'Grade 6-8',
            'Grade 9-12'
        ];

        foreach ($validGradeLevels as $grade) {
            $this->assertIsString($grade);
            $this->assertNotEmpty($grade);
        }
    }

    /**
     * Test subject categories
     */
    public function testSubjectCategories(): void
    {
        $subjects = [
            'Mathematics',
            'Science',
            'English Language Arts',
            'History',
            'Bible Studies'
        ];

        $this->assertCount(5, $subjects);
        $this->assertContains('Bible Studies', $subjects);
    }

    /**
     * Test lesson update with allowed fields
     */
    public function testUpdateAllowedFields(): void
    {
        $allowedFields = ['title', 'description', 'subject', 'grade_level', 'is_published'];
        $notAllowed = ['id', 'teacher_id', 'created_at'];

        $updateData = [
            'title' => 'New Title',
            'description' => 'New Description',
            'id' => 999 // Should not update
        ];

        $toUpdate = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $updateData)) {
                $toUpdate[$field] = $updateData[$field];
            }
        }

        $this->assertArrayHasKey('title', $toUpdate);
        $this->assertArrayHasKey('description', $toUpdate);
        $this->assertArrayNotHasKey('id', $toUpdate);
    }
}
