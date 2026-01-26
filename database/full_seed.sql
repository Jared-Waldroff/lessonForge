-- Full Seed Data for HCOS Demo
-- Can be run via: cat database/full_seed.sql | docker compose exec -T db mariadb -u hcos -phcos_secret_2026 hcos_lessonforge

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `student_progress`;
TRUNCATE TABLE `lesson_blocks`;
TRUNCATE TABLE `lessons`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `daily_verses`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS
-- Password 'password123' hashed (using simple hash for demo or assuming app handles it)
-- Note: In a real app, these should be proper bcrypt hashes. 
-- For this demo specific PHP implementation, we'll assume the app hashes on register or compare simple strings if in dev mode.
-- START_ID: 1
INSERT INTO `users` (`id`, `name`, `email`, `role`, `password_hash`) VALUES
(1, 'Sarah Johnson', 'teacher@hcos.demo', 'teacher', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password123
(2, 'Michael Chen', 'student@hcos.demo', 'student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 2. DAILY VERSES
INSERT INTO `daily_verses` (`verse_reference`, `verse_text`, `theme`, `display_date`) VALUES
('Proverbs 9:10', 'The fear of the Lord is the beginning of wisdom, and knowledge of the Holy One is understanding.', 'Wisdom', CURRENT_DATE),
('Proverbs 1:5', 'Let the wise listen and add to their learning, and let the discerning get guidance.', 'Learning', DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)),
('Colossians 3:23', 'Whatever you do, work at it with all your heart, as working for the Lord, not for human masters.', 'Diligence', DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY)),
('Philippians 4:13', 'I can do all things through Christ who strengthens me.', 'Strength', DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY));

-- 3. LESSONS
-- START_ID: 1
INSERT INTO `lessons` (`id`, `teacher_id`, `title`, `description`, `subject`, `grade_level`, `is_published`) VALUES
(1, 1, 'Introduction to Fractions', 'Learn the basics of fractions, including numerators, denominators, and simple operations.', 'Mathematics', 'Grade 4-5', 1),
(2, 1, 'The Water Cycle', 'Explore how water moves through our environment. Learn about evaporation, condensation, and precipitation.', 'Science', 'Grade 3-4', 1),
(3, 1, 'The Life of David', 'From shepherd boy to King. Follow David''s journey of faith and repentance.', 'Bible Studies', 'Grade 4-5', 1),
(4, 1, 'Ancient Egypt', 'Travel back in time to the land of Pharaohs and Pyramids. Learn about the Nile River and hieroglyphics.', 'History', 'Grade 4-5', 1),
(5, 1, 'Poetry Basics', 'Discover rhyme, rhythm, and alliteration. Read famous poems and write your own haiku.', 'English Language Arts', 'Grade 4-5', 1),
(6, 1, 'Understanding Decimals', 'Connect your knowledge of fractions to the decimal system. Learn place value and how to convert simple fractions.', 'Mathematics', 'Grade 4-5', 1),
(7, 1, 'The Solar System', 'A journey through our neighborhood in space. Visit the planets, moons, and the sun.', 'Science', 'Grade 4-5', 1),
(8, 1, 'Parables of Jesus', 'Understand the deeper meaning behind the stories Jesus told, like the Good Samaritan and the Sower.', 'Bible Studies', 'Grade 4-5', 1),
(9, 1, 'The Roman Empire', 'Study the rise of Rome, its engineering marvels like roads and aqueducts, and its government.', 'History', 'Grade 5-6', 1),
(10, 1, 'Grammar: Parts of Speech', 'Master nouns, verbs, adjectives, and adverbs to improve your writing clarity.', 'English Language Arts', 'Grade 4-5', 1),
(11, 1, 'Geometry: Shapes & Angles', 'Explore the world of 2D shapes and measure angles. An introduction to geometry concepts.', 'Mathematics', 'Grade 4-5', 1),
(12, 1, 'Botany: Plant Life', 'Discover how plants grow, making their own food through photosynthesis.', 'Science', 'Grade 4-5', 1),
(13, 1, 'Narrative Arc', 'Planning lesson for next week. Covers exposition, rising action, climax, falling action, and resolution.', 'English Language Arts', 'Grade 5-6', 0); -- DRAFT

-- 4. LESSON BLOCKS
-- Lesson 1: Fractions
INSERT INTO `lesson_blocks` (`lesson_id`, `block_type`, `content`, `order_index`) VALUES
(1, 'text', '{"title": "What is a Fraction?", "body": "A fraction represents a part of a whole. It has two parts: the numerator (top number) tells us how many parts we have, and the denominator (bottom number) tells us how many equal parts the whole is divided into."}', 1),
(1, 'quiz', '{"question": "In the fraction 5/8, what does the number 8 represent?", "options": ["The number of parts we have", "The total number of equal parts", "The size of each part", "The remainder"], "correct": 1}', 2);

-- Lesson 2: Water Cycle
INSERT INTO `lesson_blocks` (`lesson_id`, `block_type`, `content`, `order_index`) VALUES
(2, 'video', '{"title": "The Water Cycle Explained", "url": "https://www.youtube.com/embed/ncORPosDrjI"}', 1),
(2, 'quiz', '{"question": "What is the process called when water turns from liquid to gas?", "options": ["Condensation", "Precipitation", "Evaporation", "Collection"], "correct": 2}', 2);

-- Lesson 3: Life of David
INSERT INTO `lesson_blocks` (`lesson_id`, `block_type`, `content`, `order_index`) VALUES
(3, 'text', '{"title": "David the Shepherd", "body": "David was the youngest son of Jesse, caring for sheep. He learned to trust God in the lonely fields."}', 1),
(3, 'scripture', '{"reference": "1 Samuel 17:37", "text": "The Lord who rescued me from the paw of the lion... will rescue me from the hand of this Philistine.", "reflection": "God prepares us in private for public victories."}', 2);

-- Lesson 7: Solar System
INSERT INTO `lesson_blocks` (`lesson_id`, `block_type`, `content`, `order_index`) VALUES
(7, 'text', '{"title": "Our Star", "body": "The Sun is at the center of our solar system. It is a massive ball of hot plasma that provides light and heat to all the planets."}', 1),
(7, 'scripture', '{"reference": "Psalm 19:1", "text": "The heavens declare the glory of God; the skies proclaim the work of his hands.", "reflection": "When we look at the stars, we see the limitlessness of God''s power."}', 2);

-- 5. STUDENT PROGRESS (Michael)
-- Completed Lessons
INSERT INTO `student_progress` (`student_id`, `lesson_id`, `status`, `score`, `time_spent_seconds`, `completed_at`) VALUES
(2, 1, 'completed', 100.00, 1800, NOW()), -- Fractions
(2, 2, 'completed', 95.00, 2400, NOW()), -- Water Cycle
(2, 3, 'completed', 100.00, 3600, NOW()), -- David
(2, 4, 'completed', 88.00, 2100, NOW()), -- Egypt
(2, 5, 'completed', 92.00, 1500, NOW()), -- Poetry
(2, 6, 'completed', 85.00, 2700, NOW()), -- Decimals
(2, 7, 'completed', 100.00, 4200, NOW()), -- Solar System
(2, 8, 'completed', 90.00, 2000, NOW()); -- Parables

-- In Progress
INSERT INTO `student_progress` (`student_id`, `lesson_id`, `status`, `score`, `time_spent_seconds`, `completed_at`) VALUES
(2, 9, 'in_progress', NULL, 900, NULL), -- Roman Empire
(2, 10, 'in_progress', NULL, 300, NULL); -- Grammar

-- Checksum for verification
SELECT 'Database Seeded Successfully' as status;
