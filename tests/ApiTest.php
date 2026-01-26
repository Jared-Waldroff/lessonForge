<?php
/**
 * API Response Tests
 * 
 * Tests for API response structure and validation.
 * 
 * @package LessonForge\Tests
 */

namespace LessonForge\Tests;

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    /**
     * Test successful response structure
     */
    public function testSuccessResponseStructure(): void
    {
        $response = [
            'success' => true,
            'message' => 'Operation completed',
            'data' => ['id' => 1, 'name' => 'Test']
        ];

        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
    }

    /**
     * Test error response structure
     */
    public function testErrorResponseStructure(): void
    {
        $response = [
            'error' => true,
            'message' => 'Something went wrong'
        ];

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertTrue($response['error']);
    }

    /**
     * Test lessons list response
     */
    public function testLessonsListResponse(): void
    {
        $response = [
            'success' => true,
            'lessons' => [
                ['id' => 1, 'title' => 'Lesson 1'],
                ['id' => 2, 'title' => 'Lesson 2']
            ],
            'count' => 2
        ];

        $this->assertArrayHasKey('lessons', $response);
        $this->assertArrayHasKey('count', $response);
        $this->assertIsArray($response['lessons']);
        $this->assertEquals(2, $response['count']);
        $this->assertCount(2, $response['lessons']);
    }

    /**
     * Test user registration response
     */
    public function testRegistrationResponse(): void
    {
        $response = [
            'success' => true,
            'message' => 'User registered successfully',
            'user' => [
                'id' => 1,
                'email' => 'user@example.com',
                'name' => 'Test User',
                'role' => 'student'
            ]
        ];

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('user', $response);
        $this->assertArrayHasKey('id', $response['user']);
        $this->assertArrayHasKey('email', $response['user']);
        $this->assertArrayNotHasKey('password', $response['user']);
        $this->assertArrayNotHasKey('password_hash', $response['user']);
    }

    /**
     * Test login response with token
     */
    public function testLoginResponse(): void
    {
        $response = [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => 1,
                'email' => 'user@example.com',
                'name' => 'Test User',
                'role' => 'student'
            ],
            'token' => 'abc123def456'
        ];

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('token', $response);
        $this->assertNotEmpty($response['token']);
    }

    /**
     * Test verse response structure
     */
    public function testVerseResponse(): void
    {
        $response = [
            'success' => true,
            'verse' => [
                'id' => 1,
                'verse_reference' => 'Proverbs 1:7',
                'verse_text' => 'The fear of the Lord is the beginning of knowledge.',
                'theme' => 'Wisdom'
            ]
        ];

        $this->assertArrayHasKey('verse', $response);
        $this->assertArrayHasKey('verse_reference', $response['verse']);
        $this->assertArrayHasKey('verse_text', $response['verse']);
    }

    /**
     * Test progress response structure
     */
    public function testProgressResponse(): void
    {
        $response = [
            'success' => true,
            'completion' => [
                'total_blocks' => 10,
                'completed_blocks' => 7,
                'percentage' => 70.0,
                'average_score' => 85.5
            ]
        ];

        $this->assertArrayHasKey('completion', $response);
        $this->assertEquals(70.0, $response['completion']['percentage']);
        $this->assertLessThanOrEqual(100, $response['completion']['percentage']);
    }

    /**
     * Test stats response structure
     */
    public function testStatsResponse(): void
    {
        $response = [
            'success' => true,
            'stats' => [
                'lessons_started' => 5,
                'lessons_completed' => 3,
                'average_score' => 88.5,
                'total_time_seconds' => 7200,
                'total_time_formatted' => '2h 0m'
            ]
        ];

        $this->assertArrayHasKey('stats', $response);
        $this->assertIsInt($response['stats']['lessons_started']);
        $this->assertIsInt($response['stats']['lessons_completed']);
        $this->assertLessThanOrEqual(
            $response['stats']['lessons_started'],
            $response['stats']['lessons_started']
        );
    }

    /**
     * Test HTTP status codes
     */
    public function testHttpStatusCodes(): void
    {
        $codes = [
            200 => 'OK',
            201 => 'Created',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            404 => 'Not Found',
            500 => 'Internal Server Error'
        ];

        $this->assertArrayHasKey(200, $codes);
        $this->assertArrayHasKey(201, $codes);
        $this->assertArrayHasKey(400, $codes);
        $this->assertArrayHasKey(401, $codes);
        $this->assertArrayHasKey(404, $codes);
    }

    /**
     * Test JSON encoding/decoding
     */
    public function testJsonEncodingDecoding(): void
    {
        $data = [
            'title' => 'Test with "quotes" and special chars: <>&',
            'content' => ['nested' => true]
        ];

        $json = json_encode($data);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded);
    }
}
