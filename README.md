# LessonForge 📚

**Interactive Lesson Builder & Progress Tracker for Christian Education**

A full-stack web application designed to help online educators create engaging, faith-integrated lessons and track student progress.

![PHP 8.2](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-10.11-003545?logo=mariadb&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-7-DC382D?logo=redis&logoColor=white)

---

## ✨ Features

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
- **Christian-Inspired Design** - Thoughtful, reverent aesthetic

---

## 🛠️ Tech Stack

| Technology | Purpose |
|------------|---------|
| **PHP 8.2** | Backend API with OOP patterns |
| **MariaDB 10.11** | Relational database |
| **Redis 7** | Caching layer |
| **Docker Compose** | Container orchestration |
| **Apache** | Web server |
| **Vanilla JavaScript** | Frontend interactivity |
| **PHPUnit** | Unit testing |

---

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose installed
- Git

### Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/lessonforge.git
cd lessonforge

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

## 📁 Project Structure

```
lessonforge/
├── api/                    # API entry point
│   └── index.php           # Route definitions
├── src/                    # PHP source code
│   ├── Database.php        # Database singleton
│   ├── Router.php          # REST router
│   ├── Controllers/        # Request handlers
│   └── Models/             # Data models
├── public/                 # Frontend assets
│   ├── index.html          # Main HTML
│   ├── css/styles.css      # Styling
│   └── js/app.js           # JavaScript
├── database/               # Database files
│   ├── schema.sql          # Table definitions
│   └── seed.sql            # Sample data
├── tests/                  # PHPUnit tests
├── docs/                   # Documentation
│   ├── API.md              # API reference
│   └── USER_GUIDE.md       # User documentation
├── docker-compose.yml      # Docker configuration
├── Dockerfile              # PHP container
└── composer.json           # PHP dependencies
```

---

## 🔌 API Overview

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/auth/register` | POST | Register new user |
| `/api/auth/login` | POST | Authenticate user |
| `/api/lessons` | GET | List all lessons |
| `/api/lessons` | POST | Create lesson |
| `/api/lessons/{id}` | GET | Get lesson details |
| `/api/lessons/{id}/blocks` | POST | Add content block |
| `/api/progress` | POST | Record progress |
| `/api/verse` | GET | Get daily verse |

See [docs/API.md](docs/API.md) for complete API documentation.

---

## 🧪 Running Tests

```bash
# Run all tests
docker-compose exec app ./vendor/bin/phpunit tests/

# Run specific test file
docker-compose exec app ./vendor/bin/phpunit tests/UserTest.php

# Run with coverage
docker-compose exec app ./vendor/bin/phpunit --coverage-text
```

---

## 🐳 Docker Commands

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
docker-compose exec db mariadb -u lessonforge -p lessonforge
```

---

## 🔧 Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | db | Database hostname |
| `DB_NAME` | lessonforge | Database name |
| `DB_USER` | lessonforge | Database username |
| `DB_PASS` | lessonforge_secret | Database password |
| `REDIS_HOST` | redis | Redis hostname |

---

## 📄 License

MIT License - Feel free to use this project for learning and portfolio purposes.

---

## 👤 Author

**Jared**

Built as a portfolio project demonstrating:
- PHP 8 with Object-Oriented Programming
- MySQL/MariaDB database design
- RESTful API development
- Modern frontend development
- Docker containerization
- Unit testing with PHPUnit
- Technical documentation

---

## 🙏 Acknowledgments

- Heritage Christian Online School for the inspiration
- The open-source community
- [Inter Font](https://fonts.google.com/specimen/Inter)
- [Playfair Display](https://fonts.google.com/specimen/Playfair+Display)

---

*"The fear of the Lord is the beginning of wisdom, and knowledge of the Holy One is understanding." — Proverbs 9:10*
