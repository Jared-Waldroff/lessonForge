<?php
/**
 * Database Connection Handler
 * 
 * Provides a singleton database connection using PDO.
 * Follows secure coding practices with prepared statements.
 * 
 * @package LessonForge
 */

namespace LessonForge;

class Database
{
    private static ?Database $instance = null;
    private \PDO $connection;
    private ?\Redis $redis = null;

    /**
     * Private constructor for singleton pattern
     * Establishes database connection with proper error handling
     */
    private function __construct()
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'lessonforge';
        $user = getenv('DB_USER') ?: 'lessonforge';
        $pass = getenv('DB_PASS') ?: 'lessonforge_secret';

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->connection = new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }

        // Initialize Redis if available
        $this->initializeRedis();
    }

    /**
     * Initialize Redis connection for caching
     */
    private function initializeRedis(): void
    {
        $redisHost = getenv('REDIS_HOST') ?: 'localhost';
        
        try {
            $this->redis = new \Redis();
            $this->redis->connect($redisHost, 6379);
        } catch (\Exception $e) {
            // Redis is optional - continue without caching
            $this->redis = null;
        }
    }

    /**
     * Get the singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    /**
     * Get Redis instance for caching
     */
    public function getRedis(): ?\Redis
    {
        return $this->redis;
    }

    /**
     * Execute a query with prepared statement
     * 
     * @param string $sql The SQL query with placeholders
     * @param array $params The parameters to bind
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Cache a value in Redis
     */
    public function cache(string $key, mixed $value, int $ttl = 3600): bool
    {
        if ($this->redis === null) {
            return false;
        }
        return $this->redis->setex($key, $ttl, serialize($value));
    }

    /**
     * Get a cached value from Redis
     */
    public function getCached(string $key): mixed
    {
        if ($this->redis === null) {
            return null;
        }
        $value = $this->redis->get($key);
        return $value ? unserialize($value) : null;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}
}
