<?php
/**
 * Rate Limiting Middleware
 *
 * Protects endpoints from brute-force attacks using Redis-backed
 * sliding window rate limiting per IP address.
 *
 * @package LessonForge\Middleware
 */

namespace LessonForge\Middleware;

use LessonForge\Database;

class RateLimitMiddleware
{
    /**
     * Check if the current request exceeds the rate limit
     *
     * @param int $maxAttempts Maximum requests allowed in the window
     * @param int $windowSeconds Time window in seconds
     * @return bool True if request is allowed, exits with 429 if rate limited
     */
    public static function check(int $maxAttempts = 5, int $windowSeconds = 60): bool
    {
        $db = Database::getInstance();
        $redis = $db->getRedis();

        // Graceful degradation: skip rate limiting if Redis is unavailable
        if ($redis === null) {
            return true;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit:{$ip}:" . floor(time() / $windowSeconds);

        $attempts = (int) $redis->get($key);

        if ($attempts >= $maxAttempts) {
            http_response_code(429);
            header('Content-Type: application/json');
            header('Retry-After: ' . $windowSeconds);
            echo json_encode([
                'error' => true,
                'message' => 'Too many requests. Please try again later.',
            ]);
            exit;
        }

        $redis->incr($key);
        $redis->expire($key, $windowSeconds);

        return true;
    }
}
