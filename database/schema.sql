-- HCOS LessonForge Database Schema
-- Designed for MariaDB 10.11+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Users table (teachers and students)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('teacher', 'student', 'admin') NOT NULL DEFAULT 'student',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lessons table
CREATE TABLE IF NOT EXISTS `lessons` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `subject` VARCHAR(100),
    `grade_level` VARCHAR(50),
    `is_published` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_teacher` (`teacher_id`),
    INDEX `idx_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lesson blocks (content units within lessons)
CREATE TABLE IF NOT EXISTS `lesson_blocks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lesson_id` INT UNSIGNED NOT NULL,
    `block_type` ENUM('text', 'quiz', 'video', 'image', 'scripture') NOT NULL,
    `content` JSON NOT NULL,
    `order_index` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
    INDEX `idx_lesson` (`lesson_id`),
    INDEX `idx_order` (`lesson_id`, `order_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student progress tracking
CREATE TABLE IF NOT EXISTS `student_progress` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT UNSIGNED NOT NULL,
    `lesson_id` INT UNSIGNED NOT NULL,
    `block_id` INT UNSIGNED,
    `status` ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    `score` DECIMAL(5,2),
    `time_spent_seconds` INT UNSIGNED DEFAULT 0,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`block_id`) REFERENCES `lesson_blocks`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_progress` (`student_id`, `lesson_id`, `block_id`),
    INDEX `idx_student` (`student_id`),
    INDEX `idx_lesson_progress` (`lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily verses table (faith integration)
CREATE TABLE IF NOT EXISTS `daily_verses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `verse_reference` VARCHAR(100) NOT NULL,
    `verse_text` TEXT NOT NULL,
    `theme` VARCHAR(100),
    `display_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_date` (`display_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- GAMIFICATION TABLES
-- ============================================

-- Badge definitions
CREATE TABLE IF NOT EXISTS `badges` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `badge_key` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `icon` VARCHAR(10) NOT NULL DEFAULT '🏆',
    `category` ENUM('learning', 'scripture', 'consistency', 'achievement') NOT NULL,
    `xp_reward` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User earned badges
CREATE TABLE IF NOT EXISTS `user_badges` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `badge_id` INT UNSIGNED NOT NULL,
    `earned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`badge_id`) REFERENCES `badges`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_badge` (`user_id`, `badge_id`),
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User gamification stats (XP, level, streaks)
CREATE TABLE IF NOT EXISTS `user_gamification` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `xp` INT UNSIGNED DEFAULT 0,
    `level` INT UNSIGNED DEFAULT 1,
    `current_streak` INT UNSIGNED DEFAULT 0,
    `longest_streak` INT UNSIGNED DEFAULT 0,
    `last_activity_date` DATE,
    `lessons_completed` INT UNSIGNED DEFAULT 0,
    `quizzes_passed` INT UNSIGNED DEFAULT 0,
    `perfect_scores` INT UNSIGNED DEFAULT 0,
    `verses_memorized` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FLASHCARD / VERSE MEMORIZATION TABLES
-- ============================================

-- Memory verses for flashcard system
CREATE TABLE IF NOT EXISTS `memory_verses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reference` VARCHAR(100) NOT NULL,
    `text` TEXT NOT NULL,
    `category` VARCHAR(50),
    `difficulty` ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_difficulty` (`difficulty`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User verse progress (spaced repetition tracking)
CREATE TABLE IF NOT EXISTS `verse_progress` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `verse_id` INT UNSIGNED NOT NULL,
    `mastery_level` TINYINT UNSIGNED DEFAULT 0,
    `last_review` TIMESTAMP NULL,
    `next_review` TIMESTAMP NULL,
    `correct_count` INT UNSIGNED DEFAULT 0,
    `incorrect_count` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verse_id`) REFERENCES `memory_verses`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_verse` (`user_id`, `verse_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_next_review` (`next_review`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
