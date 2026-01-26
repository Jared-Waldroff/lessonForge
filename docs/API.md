# LessonForge API Documentation

## Overview

LessonForge provides a RESTful API for managing lessons, users, progress tracking, and daily scripture verses. All endpoints return JSON responses.

## Base URL

```
http://localhost:8080/api
```

## Authentication

Currently uses session-based authentication with token support for future JWT implementation.

---

## Endpoints

### Authentication

#### Register User
```http
POST /api/auth/register
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123",
    "name": "Jane Doe",
    "role": "student"
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "name": "Jane Doe",
        "role": "student"
    }
}
```

#### Login
```http
POST /api/auth/login
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "name": "Jane Doe",
        "role": "student"
    },
    "token": "abc123..."
}
```

---

### Lessons

#### List All Lessons
```http
GET /api/lessons
```

**Query Parameters:**
- `teacher_id` (optional): Filter by teacher
- `published` (optional): `true` for published only

**Response:**
```json
{
    "success": true,
    "lessons": [
        {
            "id": 1,
            "title": "Introduction to Fractions",
            "description": "Learn the basics of fractions...",
            "subject": "Mathematics",
            "grade_level": "Grade 4-5",
            "is_published": true,
            "teacher_name": "Sarah Johnson"
        }
    ],
    "count": 1
}
```

#### Get Lesson Details
```http
GET /api/lessons/{id}
```

**Response:**
```json
{
    "success": true,
    "lesson": {
        "id": 1,
        "title": "Introduction to Fractions",
        "description": "Learn the basics...",
        "subject": "Mathematics",
        "grade_level": "Grade 4-5",
        "is_published": true,
        "teacher_name": "Sarah Johnson",
        "blocks": [
            {
                "id": 1,
                "block_type": "text",
                "content": {
                    "title": "What is a Fraction?",
                    "body": "A fraction represents..."
                },
                "order_index": 1
            }
        ]
    }
}
```

#### Create Lesson
```http
POST /api/lessons
```

**Request Body:**
```json
{
    "teacher_id": 1,
    "title": "New Lesson",
    "description": "Lesson description",
    "subject": "Science",
    "grade_level": "Grade 3-4",
    "is_published": false
}
```

#### Update Lesson
```http
PUT /api/lessons/{id}
```

#### Delete Lesson
```http
DELETE /api/lessons/{id}
```

---

### Lesson Blocks

#### Add Block to Lesson
```http
POST /api/lessons/{id}/blocks
```

**Block Types:**
- `text` - Text content with title and body
- `quiz` - Multiple choice question
- `video` - Embedded video
- `image` - Image with caption
- `scripture` - Bible verse with reflection

**Text Block:**
```json
{
    "block_type": "text",
    "content": {
        "title": "Section Title",
        "body": "The content text..."
    }
}
```

**Quiz Block:**
```json
{
    "block_type": "quiz",
    "content": {
        "question": "What is 2 + 2?",
        "options": ["3", "4", "5", "6"],
        "correct": 1
    }
}
```

**Scripture Block:**
```json
{
    "block_type": "scripture",
    "content": {
        "reference": "Proverbs 9:10",
        "text": "The fear of the Lord is the beginning of wisdom...",
        "reflection": "How can we apply this today?"
    }
}
```

---

### Progress Tracking

#### Get Student Progress
```http
GET /api/progress/{userId}
```

#### Get Student Stats
```http
GET /api/progress/{userId}/stats
```

**Response:**
```json
{
    "success": true,
    "stats": {
        "lessons_started": 5,
        "lessons_completed": 3,
        "average_score": 85.5,
        "total_time_seconds": 7200,
        "total_time_formatted": "2h 0m"
    }
}
```

#### Record Progress
```http
POST /api/progress
```

**Request Body:**
```json
{
    "student_id": 2,
    "lesson_id": 1,
    "block_id": 3,
    "status": "completed",
    "score": 100
}
```

---

### Daily Verse

#### Get Today's Verse
```http
GET /api/verse
```

**Response:**
```json
{
    "success": true,
    "verse": {
        "id": 1,
        "verse_reference": "Proverbs 1:5",
        "verse_text": "Let the wise listen and add to their learning...",
        "theme": "Learning"
    }
}
```

#### Get Verses by Theme
```http
GET /api/verses/theme/{theme}
```

#### Get Available Themes
```http
GET /api/verses/themes
```

---

## Error Responses

All errors return appropriate HTTP status codes:

```json
{
    "error": true,
    "message": "Description of what went wrong"
}
```

| Status Code | Description |
|-------------|-------------|
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Authentication required |
| 404 | Not Found - Resource doesn't exist |
| 500 | Internal Server Error |

---

## CORS

The API supports Cross-Origin Resource Sharing (CORS) with the following headers:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```
