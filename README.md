# LessonForge

**Interactive Lesson Builder & Progress Tracker for Christian Education**

A full-stack web application designed to help online educators create engaging, faith-integrated lessons and track student progress.

![PHP 8.2](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-10.11-003545?logo=mariadb&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-7-DC382D?logo=redis&logoColor=white)
![CI](https://github.com/jared-waldroff/LessonForge/actions/workflows/ci.yml/badge.svg)

---

## Features

### For Teachers
- **Lesson Builder** - Create interactive lessons with drag-and-drop blocks
- **Multiple Block Types** - Text, quizzes, videos, images, and scripture
- **Draft/Publish Workflow** - Save drafts and publish when ready
- **Progress Analytics** - See how students are performing

### For Students
- **Interactive Lessons** - Engaging content with immediate feedback
- **Quiz System** - Test knowledge with instant scoring
- **Progress Tracking** - Track completion and scores
- **Daily Verse** - Scripture-based encouragement for learning

### Faith Integration
- **Daily Scripture** - Rotating verses about wisdom, learning, and growth
- **Scripture Blocks** - Embed Bible verses directly in lessons
- **Verse Memorization** - Flashcard system with spaced repetition

---

## Tech Stack

| Technology | Purpose |
|------------|---------|
| **PHP 8.2** | Backend API with OOP patterns |
| **MariaDB 10.11** | Relational database (11 tables) |
| **Redis 7** | Caching layer + rate limiting |
| **Docker Compose** | Container orchestration |
| **Apache** | Web server |
| **Vanilla JavaScript** | Frontend interactivity |
| **PHPUnit 10** | Unit + integration testing |
| **GitHub Actions** | CI/CD pipeline |
| **firebase/php-jwt** | JWT authentication |

---

## Architecture

```
Browser (SPA) --> /api/index.php --> Router --> Middleware --> Controller --> Model --> Database
                                                  |                                      |
                                           AuthMiddleware                              MariaDB
                                           RateLimitMiddleware                         Redis
                                           RoleMiddleware
```

### Security

- **JWT Authentication** - Tokens signed with HS256, 1-hour expiration, user claims (ID, email, role)
- **Role-Based Access Control** - Middleware-enforced per route (public, authenticated, teacher/admin, admin-only)
- **Rate Limiting** - Redis-backed IP rate limiting (5 requests/minute) on authentication endpoints
- **CORS Whitelist** - Environment-configurable origin whitelist with `Vary: Origin` header
- **Password Hashing** - bcrypt via `password_hash()` with automatic salt
- **SQL Injection Prevention** - All queries use PDO prepared statements with native parameter binding
- **Input Validation** - Email format, password strength, role whitelist, block type enum validation

---

## Quick Start

### Prerequisites
- Docker & Docker Compose installed
- Git

### Installation

```bash
# Clone the repository
git clone https://github.com/jared-waldroff/LessonForge.git
cd LessonForge

# Start containers
docker-compose up -d --build

# Install PHP dependencies (first time only)
docker-compose exec app composer install
```

### Access the Application

Open your browser to: **http://localhost:8080**

### Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Teacher | teacher@lessonforge.demo | password123 |
| Student | student@lessonforge.demo | password123 |
| Admin | admin@lessonforge.demo | password123 |

---

## Project Structure

```
lessonforge/
├── src/                        # PHP source code
│   ├── Database.php            # Singleton DB + Redis connection
│   ├── Router.php              # REST router with per-route middleware
│   ├── Controllers/            # Request handlers
│   │   ├── AuthController.php      # Registration & login
│   │   ├── LessonController.php    # Lesson & block CRUD
│   │   ├── ProgressController.php  # Student progress tracking
│   │   └── VerseController.php     # Daily scripture verses
│   ├── Middleware/              # Request middleware
│   │   ├── AuthMiddleware.php      # JWT authentication & RBAC
│   │   └── RateLimitMiddleware.php # Redis-backed rate limiting
│   └── Models/                 # Data models
│       ├── User.php                # Auth & profile management
│       ├── Lesson.php              # Lesson CRUD with caching
│       ├── LessonBlock.php         # Content blocks & reordering
│       ├── Progress.php            # Progress tracking & stats
│       └── DailyVerse.php          # Verse rotation & themes
├── public/                     # Frontend assets
│   ├── index.html              # SPA entry point
│   ├── api/index.php           # API route definitions
│   ├── css/                    # Stylesheets
│   └── js/                     # JavaScript modules
│       ├── app.js                  # Main app + API client
│       ├── dragdrop.js             # Drag-and-drop lesson builder
│       ├── flashcards.js           # Verse memorization
│       ├── gamification.js         # Badges & streaks
│       ├── accessibility.js        # ARIA & keyboard navigation
│       └── analytics.js            # Learning analytics
├── database/                   # Database files
│   ├── schema.sql              # Table definitions (11 tables)
│   ├── seed.sql                # Sample data
│   └── full_seed.sql           # Complete seed with lessons
├── tests/                      # PHPUnit tests
│   ├── UserTest.php            # User validation tests
│   ├── LessonTest.php          # Lesson structure tests
│   ├── ApiTest.php             # API response tests
│   └── Integration/            # Integration tests (requires DB)
│       ├── DatabaseTestCase.php        # Base class with transactions
│       ├── UserIntegrationTest.php     # User DB operations
│       ├── LessonIntegrationTest.php   # Lesson + block DB operations
│       └── AuthMiddlewareTest.php      # JWT creation & validation
├── deployment/                 # Deployment scripts
│   ├── vps-setup.sh            # Ubuntu VPS Docker setup
│   └── deploy.sh               # Production deployment
├── docs/                       # Documentation
│   ├── API.md                  # API reference
│   └── USER_GUIDE.md           # User manual
├── .github/workflows/ci.yml   # GitHub Actions CI pipeline
├── docker-compose.yml          # Dev environment
├── docker-compose.prod.yml     # Production overrides
├── Dockerfile                  # PHP 8.2 Apache container
├── composer.json               # PHP dependencies
└── phpunit.xml                 # Test configuration
```

---

## API Overview

All write endpoints require JWT authentication. Tokens are obtained via `/api/auth/login`.

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/auth/register` | POST | Public (rate limited) | Register new user |
| `/api/auth/login` | POST | Public (rate limited) | Authenticate and get JWT |
| `/api/lessons` | GET | Public | List all lessons |
| `/api/lessons` | POST | Teacher/Admin | Create lesson |
| `/api/lessons/{id}` | GET | Public | Get lesson with blocks |
| `/api/lessons/{id}` | PUT | Teacher/Admin | Update lesson |
| `/api/lessons/{id}` | DELETE | Teacher/Admin | Delete lesson |
| `/api/lessons/{id}/blocks` | POST | Teacher/Admin | Add content block |
| `/api/blocks/{id}/reorder` | POST | Teacher/Admin | Reorder block |
| `/api/progress/{userId}` | GET | Authenticated | Get student progress |
| `/api/progress` | POST | Authenticated | Record progress |
| `/api/verse` | GET | Public | Get daily verse |
| `/api/verses` | POST | Admin | Create verse |
| `/api/users` | GET | Admin | List all users |

See [docs/API.md](docs/API.md) for complete API documentation.

---

## Running Tests

```bash
# Run unit tests
docker-compose exec app vendor/bin/phpunit --testsuite Unit

# Run integration tests (requires database)
docker-compose exec app vendor/bin/phpunit --testsuite Integration

# Run all tests
docker-compose exec app vendor/bin/phpunit

# Run a specific test file
docker-compose exec app vendor/bin/phpunit tests/Integration/AuthMiddlewareTest.php
```

Tests also run automatically via GitHub Actions on every push and pull request.

---

## CI/CD Pipeline

The GitHub Actions workflow (`.github/workflows/ci.yml`) runs on every push to `main` and on pull requests:

1. **Sets up PHP 8.2** with required extensions (pdo_mysql, redis, mbstring, bcmath)
2. **Starts MariaDB 10.11** and **Redis 7** service containers
3. **Installs Composer dependencies**
4. **Initializes test database** from `database/schema.sql`
5. **Runs unit tests** (validation logic, response structures)
6. **Runs integration tests** (database operations, JWT auth, cascading deletes)

---

## Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f app

# Access PHP container
docker-compose exec app bash

# Access database
docker-compose exec db mariadb -u hcos -phcos_secret_2026 hcos_lessonforge
```

---

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | db | Database hostname |
| `DB_NAME` | hcos_lessonforge | Database name |
| `DB_USER` | hcos | Database username |
| `DB_PASS` | hcos_secret_2026 | Database password |
| `REDIS_HOST` | redis | Redis hostname |
| `JWT_SECRET` | (dev default) | Secret key for JWT signing |
| `ALLOWED_ORIGINS` | * | Comma-separated CORS whitelist |
| `APP_ENV` | development | Application environment |
| `APP_DEBUG` | true | Enable error display |

---

## License

MIT License - Feel free to use this project for learning and portfolio purposes.

---

## Author

**Jared Waldroff**

Built as a portfolio project demonstrating:
- PHP 8 with Object-Oriented Programming and MVC architecture
- MySQL/MariaDB database design with proper normalization
- RESTful API development with JWT authentication
- Role-Based Access Control with middleware enforcement
- Redis caching and rate limiting
- Docker containerization with multi-service orchestration
- CI/CD pipeline with GitHub Actions
- Unit and integration testing with PHPUnit
- Modern frontend with drag-and-drop interactivity

---

*"The fear of the Lord is the beginning of wisdom, and knowledge of the Holy One is understanding." — Proverbs 9:10*
