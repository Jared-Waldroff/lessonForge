# Security & CI/CD Gaps Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Close 6 resume gaps — JWT auth, RBAC middleware, rate limiting, CORS whitelist, GitHub Actions CI/CD, and integration tests.

**Architecture:** Add a `src/Middleware/` layer between Router and Controllers. JWT tokens replace hex tokens. Redis powers rate limiting (already in stack). GitHub Actions runs tests on push.

**Tech Stack:** PHP 8.2, firebase/php-jwt, Redis, PHPUnit 10, GitHub Actions

---

### Task 1: Add firebase/php-jwt dependency

**Files:**
- Modify: `composer.json`

**Step 1: Add JWT library to composer.json**

Add `"firebase/php-jwt": "^6.10"` to the `require` section of `composer.json`.

**Step 2: Commit**

```bash
git add composer.json
git commit -m "feat: add firebase/php-jwt dependency for JWT authentication"
```

---

### Task 2: Create AuthMiddleware with JWT validation

**Files:**
- Create: `src/Middleware/AuthMiddleware.php`

**Step 1: Create the middleware**

Create `src/Middleware/AuthMiddleware.php` that:
- Extracts `Authorization: Bearer <token>` from headers
- Decodes JWT using `firebase/php-jwt` with HS256
- Validates expiration
- Returns decoded payload (sub, role, email) or null
- Has static methods: `authenticate()` returns user array or null, `requireAuth()` returns user array or sends 401, `requireRole(array $roles)` returns user or sends 403
- Uses `JWT_SECRET` env var (fallback to a dev default)

```php
<?php
namespace LessonForge\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    private static function getSecret(): string
    {
        return getenv('JWT_SECRET') ?: 'lessonforge-dev-secret-change-in-production';
    }

    public static function createToken(int $userId, string $email, string $role): string
    {
        $payload = [
            'iss' => 'lessonforge',
            'sub' => $userId,
            'email' => $email,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour
        ];
        return JWT::encode($payload, self::getSecret(), 'HS256');
    }

    public static function authenticate(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }
        try {
            $decoded = JWT::decode($matches[1], new Key(self::getSecret(), 'HS256'));
            return [
                'id' => $decoded->sub,
                'email' => $decoded->email,
                'role' => $decoded->role,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function requireAuth(): array
    {
        $user = self::authenticate();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => true, 'message' => 'Authentication required']);
            exit;
        }
        return $user;
    }

    public static function requireRole(array $roles): array
    {
        $user = self::requireAuth();
        if (!in_array($user['role'], $roles)) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Insufficient permissions']);
            exit;
        }
        return $user;
    }
}
```

**Step 2: Commit**

```bash
git add src/Middleware/AuthMiddleware.php
git commit -m "feat: add JWT authentication middleware with role enforcement"
```

---

### Task 3: Update User model to issue JWT tokens

**Files:**
- Modify: `src/Models/User.php`

**Step 1: Update authenticate() method**

Replace the `bin2hex(random_bytes(32))` token with `AuthMiddleware::createToken()`.

In `User::authenticate()`, change:
```php
$token = bin2hex(random_bytes(32));
```
to:
```php
$token = \LessonForge\Middleware\AuthMiddleware::createToken(
    (int) $userData['id'],
    $userData['email'],
    $userData['role']
);
```

**Step 2: Commit**

```bash
git add src/Models/User.php
git commit -m "feat: issue JWT tokens on login instead of random hex strings"
```

---

### Task 4: Create RateLimitMiddleware

**Files:**
- Create: `src/Middleware/RateLimitMiddleware.php`

**Step 1: Create rate limiter using Redis**

```php
<?php
namespace LessonForge\Middleware;

use LessonForge\Database;

class RateLimitMiddleware
{
    public static function check(int $maxAttempts = 5, int $windowSeconds = 60): bool
    {
        $db = Database::getInstance();
        $redis = $db->getRedis();

        if ($redis === null) {
            return true; // Skip rate limiting if Redis unavailable
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit:{$ip}:" . floor(time() / $windowSeconds);

        $attempts = (int) $redis->get($key);

        if ($attempts >= $maxAttempts) {
            http_response_code(429);
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
```

**Step 2: Commit**

```bash
git add src/Middleware/RateLimitMiddleware.php
git commit -m "feat: add Redis-backed rate limiting middleware"
```

---

### Task 5: Update Router for CORS whitelist and per-route middleware

**Files:**
- Modify: `src/Router.php`

**Step 1: Update sendCorsHeaders() for origin whitelist**

Replace the wildcard CORS with environment-based whitelist:

```php
private function sendCorsHeaders(): void
{
    $allowedOrigins = getenv('ALLOWED_ORIGINS') ?: '*';
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($allowedOrigins === '*') {
        header('Access-Control-Allow-Origin: *');
    } else {
        $origins = array_map('trim', explode(',', $allowedOrigins));
        if (in_array($origin, $origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}
```

**Step 2: Add per-route middleware support**

Update `addRoute()` to accept an optional middleware array, and update `dispatch()` to run per-route middleware before calling the handler.

**Step 3: Commit**

```bash
git add src/Router.php
git commit -m "feat: add CORS whitelist and per-route middleware to Router"
```

---

### Task 6: Wire up middleware to API routes

**Files:**
- Modify: `public/api/index.php`
- Modify: `docker-compose.yml`
- Modify: `docker-compose.prod.yml`

**Step 1: Add use statements and apply middleware to routes**

Add `use LessonForge\Middleware\AuthMiddleware;` and `use LessonForge\Middleware\RateLimitMiddleware;` to index.php.

Wrap route handlers to call middleware before the controller:
- Auth routes: Add rate limiting to register/login
- Lesson write routes (POST/PUT/DELETE): Require teacher or admin role
- Progress routes: Require authentication
- Verse write routes (POST/PUT/DELETE): Require admin role
- Read-only public routes: No middleware

**Step 2: Add environment variables to docker-compose files**

Add `JWT_SECRET` and `ALLOWED_ORIGINS` to both docker-compose files.

**Step 3: Commit**

```bash
git add public/api/index.php docker-compose.yml docker-compose.prod.yml
git commit -m "feat: wire JWT auth, RBAC, and rate limiting to all API routes"
```

---

### Task 7: Create GitHub Actions CI/CD pipeline

**Files:**
- Create: `.github/workflows/ci.yml`

**Step 1: Create CI workflow**

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mariadb:
        image: mariadb:10.11
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: hcos_lessonforge_test
          MYSQL_USER: hcos
          MYSQL_PASSWORD: hcos_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="healthcheck.sh --connect --innodb_initialized"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_mysql, redis, mbstring, bcmath
          coverage: none

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Initialize test database
        run: mysql -h 127.0.0.1 -u hcos -phcos_test hcos_lessonforge_test < database/schema.sql

      - name: Run unit tests
        run: vendor/bin/phpunit --testsuite Unit

      - name: Run integration tests
        env:
          DB_HOST: 127.0.0.1
          DB_NAME: hcos_lessonforge_test
          DB_USER: hcos
          DB_PASS: hcos_test
          REDIS_HOST: 127.0.0.1
          JWT_SECRET: ci-test-secret
        run: vendor/bin/phpunit --testsuite Integration
```

**Step 2: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "feat: add GitHub Actions CI pipeline with MariaDB and Redis services"
```

---

### Task 8: Create integration test infrastructure

**Files:**
- Create: `tests/Integration/DatabaseTestCase.php`
- Modify: `phpunit.xml`

**Step 1: Create base test class**

```php
<?php
namespace LessonForge\Tests\Integration;

use PHPUnit\Framework\TestCase;
use LessonForge\Database;

abstract class DatabaseTestCase extends TestCase
{
    protected \PDO $pdo;

    protected function setUp(): void
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'hcos_lessonforge_test';
        $user = getenv('DB_USER') ?: 'hcos';
        $pass = getenv('DB_PASS') ?: 'hcos_test';

        $this->pdo = new \PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $user, $pass,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    protected function insertUser(string $email = 'test@example.com', string $role = 'teacher'): int
    {
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $this->pdo->prepare("INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)")
            ->execute([$email, $hash, 'Test User', $role]);
        return (int) $this->pdo->lastInsertId();
    }
}
```

**Step 2: Update phpunit.xml with Integration suite**

Add a second testsuite:
```xml
<testsuite name="Integration">
    <directory>tests/Integration</directory>
</testsuite>
```

**Step 3: Commit**

```bash
git add tests/Integration/DatabaseTestCase.php phpunit.xml
git commit -m "feat: add integration test infrastructure with transaction rollback"
```

---

### Task 9: Write integration tests

**Files:**
- Create: `tests/Integration/UserIntegrationTest.php`
- Create: `tests/Integration/LessonIntegrationTest.php`
- Create: `tests/Integration/AuthMiddlewareTest.php`

**Step 1: Write UserIntegrationTest**

Tests: register creates user row, duplicate email rejected, authenticate returns JWT, invalid password fails.

**Step 2: Write LessonIntegrationTest**

Tests: create lesson, read lesson with blocks, update lesson, delete cascades blocks.

**Step 3: Write AuthMiddlewareTest**

Tests: createToken returns valid JWT, authenticate decodes valid token, authenticate rejects expired token, authenticate rejects tampered token, requireRole allows matching role, requireRole rejects wrong role.

**Step 4: Commit**

```bash
git add tests/Integration/
git commit -m "feat: add integration tests for users, lessons, and auth middleware"
```

---

### Task 10: Add auth_tokens table for token revocation

**Files:**
- Modify: `database/schema.sql`

**Step 1: Add auth_tokens table**

```sql
CREATE TABLE IF NOT EXISTS `auth_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `revoked` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token_hash` (`token_hash`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Step 2: Commit**

```bash
git add database/schema.sql
git commit -m "feat: add auth_tokens table for JWT token revocation tracking"
```
