-- HCOS LessonForge Seed Data
-- Sample data for demonstration

SET NAMES utf8mb4;

-- Insert demo users (passwords are hashed 'password123')
INSERT INTO `users` (`email`, `password_hash`, `name`, `role`) VALUES
('teacher@hcos.demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', 'teacher'),
('student@hcos.demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael Chen', 'student'),
('admin@hcos.demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin');

-- Insert sample lessons
INSERT INTO `lessons` (`teacher_id`, `title`, `description`, `subject`, `grade_level`, `is_published`) VALUES
(1, 'Introduction to Fractions', 'Learn the basics of fractions, including numerators, denominators, and simple operations.', 'Mathematics', 'Grade 4-5', TRUE),
(1, 'The Water Cycle', 'Explore how water moves through our environment in this interactive science lesson.', 'Science', 'Grade 3-4', TRUE),
(1, 'Creative Writing: Story Starters', 'Develop your imagination with fun story prompts and writing exercises.', 'English Language Arts', 'Grade 5-6', FALSE);

-- Insert sample lesson blocks
INSERT INTO `lesson_blocks` (`lesson_id`, `block_type`, `content`, `order_index`) VALUES
-- Fractions lesson
(1, 'text', '{"title": "What is a Fraction?", "body": "A fraction represents a part of a whole. It has two parts: the numerator (top number) tells us how many parts we have, and the denominator (bottom number) tells us how many equal parts the whole is divided into."}', 1),
(1, 'image', '{"url": "/assets/images/fraction-circle.png", "alt": "A circle divided into fractions", "caption": "This circle is divided into 4 equal parts. Each part is 1/4 of the whole."}', 2),
(1, 'quiz', '{"question": "What does the denominator in a fraction tell us?", "options": ["How many parts we have", "How many equal parts the whole is divided into", "The size of each part", "The total amount"], "correct": 1}', 3),
(1, 'scripture', '{"reference": "Proverbs 9:10", "text": "The fear of the Lord is the beginning of wisdom, and knowledge of the Holy One is understanding.", "reflection": "As we learn about fractions, we grow in knowledge. Let us thank God for the gift of learning!"}', 4),

-- Water Cycle lesson
(2, 'text', '{"title": "The Amazing Water Cycle", "body": "Water is constantly moving around our planet in a process called the water cycle. This cycle has no beginning or end - water just keeps going around and around!"}', 1),
(2, 'video', '{"url": "https://www.youtube.com/embed/ncORPosDrjI", "title": "The Water Cycle Song"}', 2),
(2, 'quiz', '{"question": "What is the process called when water turns from liquid to gas?", "options": ["Condensation", "Precipitation", "Evaporation", "Collection"], "correct": 2}', 3);

-- Insert daily verses (curated for learning/education themes)
INSERT INTO `daily_verses` (`verse_reference`, `verse_text`, `theme`, `display_date`) VALUES
('Proverbs 1:5', 'Let the wise listen and add to their learning, and let the discerning get guidance.', 'Learning', CURDATE()),
('Proverbs 9:10', 'The fear of the Lord is the beginning of wisdom, and knowledge of the Holy One is understanding.', 'Wisdom', DATE_ADD(CURDATE(), INTERVAL 1 DAY)),
('Colossians 3:23', 'Whatever you do, work at it with all your heart, as working for the Lord, not for human masters.', 'Diligence', DATE_ADD(CURDATE(), INTERVAL 2 DAY)),
('Philippians 4:13', 'I can do all things through Christ who strengthens me.', 'Strength', DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
('James 1:5', 'If any of you lacks wisdom, you should ask God, who gives generously to all without finding fault, and it will be given to you.', 'Wisdom', DATE_ADD(CURDATE(), INTERVAL 4 DAY)),
('Psalm 32:8', 'I will instruct you and teach you in the way you should go; I will counsel you with my loving eye on you.', 'Guidance', DATE_ADD(CURDATE(), INTERVAL 5 DAY)),
('2 Timothy 2:15', 'Do your best to present yourself to God as one approved, a worker who does not need to be ashamed and who correctly handles the word of truth.', 'Excellence', DATE_ADD(CURDATE(), INTERVAL 6 DAY)),
('Proverbs 18:15', 'The heart of the discerning acquires knowledge, for the ears of the wise seek it out.', 'Knowledge', DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
('Joshua 1:8', 'Keep this Book of the Law always on your lips; meditate on it day and night, so that you may be careful to do everything written in it.', 'Study', DATE_ADD(CURDATE(), INTERVAL 8 DAY)),
('Proverbs 22:6', 'Train up a child in the way he should go; even when he is old he will not depart from it.', 'Education', DATE_ADD(CURDATE(), INTERVAL 9 DAY));

-- Insert sample progress data
INSERT INTO `student_progress` (`student_id`, `lesson_id`, `block_id`, `status`, `score`, `time_spent_seconds`) VALUES
(2, 1, 1, 'completed', NULL, 120),
(2, 1, 2, 'completed', NULL, 45),
(2, 1, 3, 'completed', 100.00, 90),
(2, 2, 5, 'completed', NULL, 180),
(2, 2, 6, 'in_progress', NULL, 60);

-- ============================================
-- GAMIFICATION SEED DATA
-- ============================================

-- Insert badge definitions
INSERT INTO `badges` (`badge_key`, `name`, `description`, `icon`, `category`, `xp_reward`) VALUES
('first_lesson', 'First Steps', 'Complete your first lesson', '🎯', 'learning', 50),
('lesson_master', 'Lesson Master', 'Complete 10 lessons', '📚', 'learning', 200),
('perfect_score', 'Perfect Score', 'Get 100% on a quiz', '⭐', 'achievement', 100),
('quiz_champion', 'Quiz Champion', 'Pass 25 quizzes', '🏆', 'achievement', 500),
('week_streak', 'Week Warrior', 'Maintain a 7-day learning streak', '🔥', 'consistency', 150),
('month_streak', 'Monthly Master', 'Maintain a 30-day learning streak', '💎', 'consistency', 1000),
('verse_learner', 'Scripture Student', 'Memorize your first verse', '📖', 'scripture', 75),
('verse_master', 'Verse Master', 'Master 10 memory verses', '✝️', 'scripture', 300),
('early_bird', 'Early Bird', 'Study before 8 AM', '🌅', 'consistency', 50),
('night_owl', 'Night Owl', 'Study after 9 PM', '🦉', 'consistency', 50);

-- Insert initial gamification stats for demo student
INSERT INTO `user_gamification` (`user_id`, `xp`, `level`, `current_streak`, `longest_streak`, `lessons_completed`, `quizzes_passed`, `perfect_scores`, `verses_memorized`, `last_activity_date`) VALUES
(2, 350, 2, 3, 5, 2, 2, 1, 0, CURDATE());

-- Award first badge to demo student
INSERT INTO `user_badges` (`user_id`, `badge_id`) VALUES
(2, 1);

-- ============================================
-- MEMORY VERSES SEED DATA
-- ============================================

INSERT INTO `memory_verses` (`reference`, `text`, `category`, `difficulty`) VALUES
('John 3:16', 'For God so loved the world that he gave his one and only Son, that whoever believes in him shall not perish but have eternal life.', 'Salvation', 'beginner'),
('Proverbs 3:5-6', 'Trust in the Lord with all your heart and lean not on your own understanding; in all your ways submit to him, and he will make your paths straight.', 'Trust', 'intermediate'),
('Philippians 4:13', 'I can do all this through him who gives me strength.', 'Strength', 'beginner'),
('Romans 8:28', 'And we know that in all things God works for the good of those who love him, who have been called according to his purpose.', 'Purpose', 'intermediate'),
('Psalm 23:1-3', 'The Lord is my shepherd, I lack nothing. He makes me lie down in green pastures, he leads me beside quiet waters, he refreshes my soul.', 'Comfort', 'intermediate'),
('Jeremiah 29:11', 'For I know the plans I have for you," declares the Lord, "plans to prosper you and not to harm you, plans to give you hope and a future.', 'Hope', 'beginner'),
('Isaiah 41:10', 'So do not fear, for I am with you; do not be dismayed, for I am your God. I will strengthen you and help you; I will uphold you with my righteous right hand.', 'Courage', 'advanced'),
('Matthew 28:19-20', 'Therefore go and make disciples of all nations, baptizing them in the name of the Father and of the Son and of the Holy Spirit, and teaching them to obey everything I have commanded you.', 'Mission', 'advanced'),
('Psalm 119:105', 'Your word is a lamp for my feet, a light on my path.', 'Guidance', 'beginner'),
('2 Timothy 3:16-17', 'All Scripture is God-breathed and is useful for teaching, rebuking, correcting and training in righteousness, so that the servant of God may be thoroughly equipped for every good work.', 'Scripture', 'advanced');
