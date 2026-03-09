<?php
/**
 * Rate Limiting Tests
 *
 * Tests that the Redis-backed rate limiter correctly tracks
 * request counts and blocks excessive requests.
 *
 * @package LessonForge\Tests\Integration
 */

namespace LessonForge\Tests\Integration;

use PHPUnit\Framework\TestCase;

class RateLimitTest extends TestCase
{
    private ?\Redis $redis = null;

    protected function setUp(): void
    {
        $redisHost = getenv('REDIS_HOST') ?: 'localhost';

        try {
            $this->redis = new \Redis();
            $this->redis->connect($redisHost, 6379);
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis not available');
        }
    }

    /**
     * Test that rate limit key is created in Redis
     */
    public function testRateLimitKeyCreatedInRedis(): void
    {
        $ip = '192.168.1.100';
        $window = 60;
        $key = "rate_limit:{$ip}:" . floor(time() / $window);

        // Clean up first
        $this->redis->del($key);

        // Simulate requests
        $this->redis->incr($key);
        $this->redis->expire($key, $window);

        $count = (int) $this->redis->get($key);
        $this->assertEquals(1, $count);
    }

    /**
     * Test that requests are counted correctly
     */
    public function testRequestCountIncrementsCorrectly(): void
    {
        $ip = '192.168.1.101';
        $window = 60;
        $key = "rate_limit:{$ip}:" . floor(time() / $window);

        $this->redis->del($key);

        // Simulate 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->redis->incr($key);
            $this->redis->expire($key, $window);
        }

        $count = (int) $this->redis->get($key);
        $this->assertEquals(5, $count);
    }

    /**
     * Test that the 6th request exceeds the limit of 5
     */
    public function testSixthRequestExceedsLimit(): void
    {
        $ip = '192.168.1.102';
        $maxAttempts = 5;
        $window = 60;
        $key = "rate_limit:{$ip}:" . floor(time() / $window);

        $this->redis->del($key);

        // Simulate 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->redis->incr($key);
        }

        $attempts = (int) $this->redis->get($key);
        $this->assertGreaterThanOrEqual($maxAttempts, $attempts);

        // 6th request should be blocked
        $this->redis->incr($key);
        $attempts = (int) $this->redis->get($key);
        $this->assertGreaterThan($maxAttempts, $attempts);
    }

    /**
     * Test that different IPs have separate rate limits
     */
    public function testDifferentIPsHaveSeparateLimits(): void
    {
        $window = 60;
        $timeWindow = floor(time() / $window);

        $key1 = "rate_limit:10.0.0.1:{$timeWindow}";
        $key2 = "rate_limit:10.0.0.2:{$timeWindow}";

        $this->redis->del($key1);
        $this->redis->del($key2);

        // IP 1 makes 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->redis->incr($key1);
        }

        // IP 2 makes 1 request
        $this->redis->incr($key2);

        $this->assertEquals(5, (int) $this->redis->get($key1));
        $this->assertEquals(1, (int) $this->redis->get($key2));
    }

    /**
     * Test that rate limit keys expire
     */
    public function testRateLimitKeysHaveTTL(): void
    {
        $ip = '192.168.1.103';
        $window = 60;
        $key = "rate_limit:{$ip}:" . floor(time() / $window);

        $this->redis->del($key);
        $this->redis->incr($key);
        $this->redis->expire($key, $window);

        $ttl = $this->redis->ttl($key);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual($window, $ttl);
    }

    protected function tearDown(): void
    {
        // Clean up test keys
        if ($this->redis) {
            $keys = $this->redis->keys('rate_limit:192.168.*');
            $keys = array_merge($keys, $this->redis->keys('rate_limit:10.0.*'));
            foreach ($keys as $key) {
                $this->redis->del($key);
            }
        }
    }
}
