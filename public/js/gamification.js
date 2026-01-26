/**
 * LessonForge Gamification System
 * 
 * Achievement badges, learning streaks, and XP progression
 */

const Gamification = {
    // Badge definitions
    badges: [
        {
            id: 'first_lesson',
            name: 'First Steps',
            description: 'Complete your first lesson',
            icon: '🎯',
            category: 'learning',
            requirement: { type: 'lessons_completed', count: 1 }
        },
        {
            id: 'curious_learner',
            name: 'Curious Learner',
            description: 'Complete 5 lessons',
            icon: '📚',
            category: 'learning',
            requirement: { type: 'lessons_completed', count: 5 }
        },
        {
            id: 'dedicated_student',
            name: 'Dedicated Student',
            description: 'Complete 10 lessons',
            icon: '🎓',
            category: 'learning',
            requirement: { type: 'lessons_completed', count: 10 }
        },
        {
            id: 'perfect_score',
            name: 'Perfect Score',
            description: 'Get 100% on a quiz',
            icon: '⭐',
            category: 'excellence',
            requirement: { type: 'perfect_quiz', count: 1 }
        },
        {
            id: 'quiz_master',
            name: 'Quiz Master',
            description: 'Get 5 perfect quiz scores',
            icon: '🏆',
            category: 'excellence',
            requirement: { type: 'perfect_quiz', count: 5 }
        },
        {
            id: 'streak_3',
            name: 'On Fire',
            description: 'Maintain a 3-day learning streak',
            icon: '🔥',
            category: 'consistency',
            requirement: { type: 'streak', count: 3 }
        },
        {
            id: 'streak_7',
            name: 'Weekly Warrior',
            description: 'Maintain a 7-day learning streak',
            icon: '💪',
            category: 'consistency',
            requirement: { type: 'streak', count: 7 }
        },
        {
            id: 'streak_30',
            name: 'Monthly Champion',
            description: 'Maintain a 30-day learning streak',
            icon: '👑',
            category: 'consistency',
            requirement: { type: 'streak', count: 30 }
        },
        {
            id: 'scripture_scholar',
            name: 'Scripture Scholar',
            description: 'Read 10 scripture passages',
            icon: '📖',
            category: 'faith',
            requirement: { type: 'scriptures_read', count: 10 }
        },
        {
            id: 'verse_keeper',
            name: 'Verse Keeper',
            description: 'Memorize 5 memory verses',
            icon: '💝',
            category: 'faith',
            requirement: { type: 'verses_memorized', count: 5 }
        },
        {
            id: 'early_bird',
            name: 'Early Bird',
            description: 'Complete a lesson before 8 AM',
            icon: '🌅',
            category: 'special',
            requirement: { type: 'early_lesson', count: 1 }
        },
        {
            id: 'night_owl',
            name: 'Night Owl',
            description: 'Complete a lesson after 9 PM',
            icon: '🦉',
            category: 'special',
            requirement: { type: 'late_lesson', count: 1 }
        }
    ],

    // Level definitions
    levels: [
        { level: 1, name: 'Beginner', xpRequired: 0, icon: '🌱' },
        { level: 2, name: 'Learner', xpRequired: 100, icon: '🌿' },
        { level: 3, name: 'Student', xpRequired: 300, icon: '🌳' },
        { level: 4, name: 'Scholar', xpRequired: 600, icon: '📚' },
        { level: 5, name: 'Expert', xpRequired: 1000, icon: '🎓' },
        { level: 6, name: 'Master', xpRequired: 1500, icon: '👨‍🎓' },
        { level: 7, name: 'Sage', xpRequired: 2500, icon: '🧙' },
        { level: 8, name: 'Wisdom Keeper', xpRequired: 4000, icon: '👑' }
    ],

    // XP rewards
    xpRewards: {
        lesson_complete: 25,
        quiz_correct: 5,
        quiz_perfect: 50,
        streak_day: 10,
        badge_earned: 100,
        verse_memorized: 30
    },

    // Storage key
    STORAGE_KEY: 'lessonforge_gamification',

    // State
    state: {
        xp: 0,
        level: 1,
        earnedBadges: [],
        currentStreak: 0,
        longestStreak: 0,
        lastActiveDate: null,
        lessonsCompleted: 0,
        perfectQuizzes: 0,
        scripturesRead: 0,
        versesMemorized: 0
    },

    /**
     * Initialize gamification system
     */
    init() {
        this.loadState();
        this.checkStreak();
        this.renderAchievementsPreview();
        console.log('🎮 Gamification system initialized');
    },

    /**
     * Load state from localStorage
     */
    loadState() {
        const saved = localStorage.getItem(this.STORAGE_KEY);
        if (saved) {
            try {
                this.state = { ...this.state, ...JSON.parse(saved) };
            } catch (e) {
                console.warn('Could not load gamification state');
            }
        }
    },

    /**
     * Save state to localStorage
     */
    saveState() {
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.state));
    },

    /**
     * Check and update streak
     */
    checkStreak() {
        const today = new Date().toDateString();
        const lastActive = this.state.lastActiveDate;

        if (!lastActive) {
            this.state.currentStreak = 0;
        } else if (lastActive === today) {
            // Already active today
        } else {
            const lastDate = new Date(lastActive);
            const todayDate = new Date(today);
            const diffDays = Math.floor((todayDate - lastDate) / (1000 * 60 * 60 * 24));

            if (diffDays === 1) {
                // Consecutive day - streak continues
            } else if (diffDays > 1) {
                // Streak broken
                this.state.currentStreak = 0;
            }
        }
    },

    /**
     * Record activity for today
     */
    recordActivity() {
        const today = new Date().toDateString();

        if (this.state.lastActiveDate !== today) {
            this.state.currentStreak++;
            this.state.lastActiveDate = today;

            if (this.state.currentStreak > this.state.longestStreak) {
                this.state.longestStreak = this.state.currentStreak;
            }

            // Award streak XP
            this.awardXP(this.xpRewards.streak_day, 'Daily streak bonus');

            // Check streak badges
            this.checkBadges();
        }

        this.saveState();
    },

    /**
     * Award XP to user
     */
    awardXP(amount, reason = '') {
        const oldLevel = this.getCurrentLevel();
        this.state.xp += amount;
        const newLevel = this.getCurrentLevel();

        this.saveState();
        this.showXPNotification(amount, reason);

        if (newLevel.level > oldLevel.level) {
            this.showLevelUpNotification(newLevel);
        }

        this.updateXPDisplay();
    },

    /**
     * Get current level based on XP
     */
    getCurrentLevel() {
        let currentLevel = this.levels[0];
        for (const level of this.levels) {
            if (this.state.xp >= level.xpRequired) {
                currentLevel = level;
            } else {
                break;
            }
        }
        return currentLevel;
    },

    /**
     * Get XP progress to next level
     */
    getXPProgress() {
        const current = this.getCurrentLevel();
        const currentIndex = this.levels.indexOf(current);
        const next = this.levels[currentIndex + 1];

        if (!next) {
            return { current: this.state.xp, required: current.xpRequired, percentage: 100 };
        }

        const xpInLevel = this.state.xp - current.xpRequired;
        const xpForLevel = next.xpRequired - current.xpRequired;
        const percentage = Math.round((xpInLevel / xpForLevel) * 100);

        return { current: xpInLevel, required: xpForLevel, percentage };
    },

    /**
     * Record lesson completion
     */
    recordLessonComplete() {
        this.state.lessonsCompleted++;
        this.recordActivity();
        this.awardXP(this.xpRewards.lesson_complete, 'Lesson completed');
        this.checkBadges();
        this.saveState();
    },

    /**
     * Record quiz answer
     */
    recordQuizAnswer(isCorrect, isPerfect = false) {
        if (isCorrect) {
            this.awardXP(this.xpRewards.quiz_correct, 'Correct answer');
        }
        if (isPerfect) {
            this.state.perfectQuizzes++;
            this.awardXP(this.xpRewards.quiz_perfect, 'Perfect quiz!');
            this.checkBadges();
        }
        this.saveState();
    },

    /**
     * Record scripture reading
     */
    recordScriptureRead() {
        this.state.scripturesRead++;
        this.checkBadges();
        this.saveState();
    },

    /**
     * Record verse memorization
     */
    recordVerseMemorized() {
        this.state.versesMemorized++;
        this.awardXP(this.xpRewards.verse_memorized, 'Verse memorized');
        this.checkBadges();
        this.saveState();
    },

    /**
     * Check for new badges
     */
    checkBadges() {
        for (const badge of this.badges) {
            if (this.state.earnedBadges.includes(badge.id)) continue;

            let earned = false;
            const req = badge.requirement;

            switch (req.type) {
                case 'lessons_completed':
                    earned = this.state.lessonsCompleted >= req.count;
                    break;
                case 'perfect_quiz':
                    earned = this.state.perfectQuizzes >= req.count;
                    break;
                case 'streak':
                    earned = this.state.currentStreak >= req.count;
                    break;
                case 'scriptures_read':
                    earned = this.state.scripturesRead >= req.count;
                    break;
                case 'verses_memorized':
                    earned = this.state.versesMemorized >= req.count;
                    break;
                case 'early_lesson':
                    earned = new Date().getHours() < 8;
                    break;
                case 'late_lesson':
                    earned = new Date().getHours() >= 21;
                    break;
            }

            if (earned) {
                this.earnBadge(badge);
            }
        }
    },

    /**
     * Award a badge
     */
    earnBadge(badge) {
        this.state.earnedBadges.push(badge.id);
        this.awardXP(this.xpRewards.badge_earned, `Badge: ${badge.name}`);
        this.showBadgeNotification(badge);
        this.saveState();
        this.renderAchievementsPreview();
    },

    /**
     * Show XP notification
     */
    showXPNotification(amount, reason) {
        const toast = document.createElement('div');
        toast.className = 'xp-notification';
        toast.innerHTML = `
            <span class="xp-amount">+${amount} XP</span>
            <span class="xp-reason">${reason}</span>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }, 100);
    },

    /**
     * Show level up notification
     */
    showLevelUpNotification(level) {
        const modal = document.createElement('div');
        modal.className = 'level-up-modal';
        modal.innerHTML = `
            <div class="level-up-content">
                <div class="level-up-icon">${level.icon}</div>
                <h2>Level Up!</h2>
                <p>You've reached <strong>Level ${level.level}: ${level.name}</strong></p>
                <button class="btn btn-primary" onclick="this.closest('.level-up-modal').remove()">
                    Continue
                </button>
            </div>
        `;
        document.body.appendChild(modal);

        setTimeout(() => modal.classList.add('show'), 100);
    },

    /**
     * Show badge earned notification
     */
    showBadgeNotification(badge) {
        const modal = document.createElement('div');
        modal.className = 'badge-earned-modal';
        modal.innerHTML = `
            <div class="badge-earned-content">
                <div class="badge-earned-icon">${badge.icon}</div>
                <h2>Badge Earned!</h2>
                <h3>${badge.name}</h3>
                <p>${badge.description}</p>
                <button class="btn btn-primary" onclick="this.closest('.badge-earned-modal').remove()">
                    Awesome!
                </button>
            </div>
        `;
        document.body.appendChild(modal);

        setTimeout(() => modal.classList.add('show'), 100);
    },

    /**
     * Update XP display in UI
     */
    updateXPDisplay() {
        const level = this.getCurrentLevel();
        const progress = this.getXPProgress();

        const xpDisplay = document.getElementById('xp-display');
        if (xpDisplay) {
            xpDisplay.innerHTML = `
                <span class="level-badge">${level.icon} Lv.${level.level}</span>
                <div class="xp-bar">
                    <div class="xp-bar-fill" style="width: ${progress.percentage}%"></div>
                </div>
                <span class="xp-text">${progress.current}/${progress.required} XP</span>
            `;
        }
    },

    /**
     * Render achievements preview in sidebar or dashboard
     */
    renderAchievementsPreview() {
        const container = document.getElementById('achievements-preview');
        if (!container) return;

        const earned = this.state.earnedBadges
            .map(id => this.badges.find(b => b.id === id))
            .filter(Boolean)
            .slice(-3);

        container.innerHTML = `
            <div class="achievements-header">
                <h4>Achievements</h4>
                <span class="badge-count">${this.state.earnedBadges.length}/${this.badges.length}</span>
            </div>
            <div class="recent-badges">
                ${earned.length > 0
                ? earned.map(b => `<span class="badge-icon" title="${b.name}">${b.icon}</span>`).join('')
                : '<span class="no-badges">Start learning to earn badges!</span>'
            }
            </div>
            <div class="streak-display">
                <span class="streak-icon">🔥</span>
                <span class="streak-count">${this.state.currentStreak} day streak</span>
            </div>
        `;
    },

    /**
     * Get all badges grouped by category
     */
    getBadgesByCategory() {
        const categories = {
            learning: { name: 'Learning', badges: [] },
            excellence: { name: 'Excellence', badges: [] },
            consistency: { name: 'Consistency', badges: [] },
            faith: { name: 'Faith', badges: [] },
            special: { name: 'Special', badges: [] }
        };

        for (const badge of this.badges) {
            const earned = this.state.earnedBadges.includes(badge.id);
            categories[badge.category].badges.push({ ...badge, earned });
        }

        return categories;
    },

    /**
     * Render full achievements page
     */
    renderAchievementsPage() {
        const categories = this.getBadgesByCategory();
        const level = this.getCurrentLevel();
        const progress = this.getXPProgress();

        return `
            <div class="achievements-page">
                <!-- Level & XP Section -->
                <div class="level-section">
                    <div class="level-display">
                        <div class="level-icon">${level.icon}</div>
                        <div class="level-info">
                            <h2>Level ${level.level}: ${level.name}</h2>
                            <p>${this.state.xp} Total XP</p>
                        </div>
                    </div>
                    <div class="level-progress">
                        <div class="level-progress-bar">
                            <div class="level-progress-fill" style="width: ${progress.percentage}%"></div>
                        </div>
                        <span>${progress.current}/${progress.required} XP to next level</span>
                    </div>
                </div>

                <!-- Streak Section -->
                <div class="streak-section">
                    <div class="streak-card">
                        <span class="streak-emoji">🔥</span>
                        <div class="streak-info">
                            <span class="streak-value">${this.state.currentStreak}</span>
                            <span class="streak-label">Day Streak</span>
                        </div>
                    </div>
                    <div class="streak-card">
                        <span class="streak-emoji">🏆</span>
                        <div class="streak-info">
                            <span class="streak-value">${this.state.longestStreak}</span>
                            <span class="streak-label">Best Streak</span>
                        </div>
                    </div>
                    <div class="streak-card">
                        <span class="streak-emoji">📚</span>
                        <div class="streak-info">
                            <span class="streak-value">${this.state.lessonsCompleted}</span>
                            <span class="streak-label">Lessons Done</span>
                        </div>
                    </div>
                </div>

                <!-- Badges Grid -->
                ${Object.entries(categories).map(([key, cat]) => `
                    <div class="badge-category">
                        <h3>${cat.name}</h3>
                        <div class="badges-grid">
                            ${cat.badges.map(badge => `
                                <div class="badge-item ${badge.earned ? 'earned' : 'locked'}">
                                    <span class="badge-icon">${badge.earned ? badge.icon : '🔒'}</span>
                                    <span class="badge-name">${badge.name}</span>
                                    <span class="badge-desc">${badge.description}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
};

// Export for global access
window.Gamification = Gamification;
