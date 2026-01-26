/**
 * LessonForge Memory Verse Flashcards
 * 
 * Scripture memorization with spaced repetition
 */

const Flashcards = {
    // Memory verses collection
    verses: [
        {
            id: 1,
            reference: 'John 3:16',
            text: 'For God so loved the world that he gave his one and only Son, that whoever believes in him shall not perish but have eternal life.',
            category: 'Salvation',
            difficulty: 'beginner'
        },
        {
            id: 2,
            reference: 'Proverbs 3:5-6',
            text: 'Trust in the Lord with all your heart and lean not on your own understanding; in all your ways submit to him, and he will make your paths straight.',
            category: 'Trust',
            difficulty: 'intermediate'
        },
        {
            id: 3,
            reference: 'Philippians 4:13',
            text: 'I can do all this through him who gives me strength.',
            category: 'Strength',
            difficulty: 'beginner'
        },
        {
            id: 4,
            reference: 'Romans 8:28',
            text: 'And we know that in all things God works for the good of those who love him, who have been called according to his purpose.',
            category: 'Purpose',
            difficulty: 'intermediate'
        },
        {
            id: 5,
            reference: 'Psalm 23:1-3',
            text: 'The Lord is my shepherd, I lack nothing. He makes me lie down in green pastures, he leads me beside quiet waters, he refreshes my soul.',
            category: 'Comfort',
            difficulty: 'intermediate'
        },
        {
            id: 6,
            reference: 'Jeremiah 29:11',
            text: 'For I know the plans I have for you," declares the Lord, "plans to prosper you and not to harm you, plans to give you hope and a future.',
            category: 'Hope',
            difficulty: 'beginner'
        },
        {
            id: 7,
            reference: 'Isaiah 41:10',
            text: 'So do not fear, for I am with you; do not be dismayed, for I am your God. I will strengthen you and help you; I will uphold you with my righteous right hand.',
            category: 'Courage',
            difficulty: 'advanced'
        },
        {
            id: 8,
            reference: 'Matthew 28:19-20',
            text: 'Therefore go and make disciples of all nations, baptizing them in the name of the Father and of the Son and of the Holy Spirit, and teaching them to obey everything I have commanded you.',
            category: 'Mission',
            difficulty: 'advanced'
        },
        {
            id: 9,
            reference: 'Psalm 119:105',
            text: 'Your word is a lamp for my feet, a light on my path.',
            category: 'Guidance',
            difficulty: 'beginner'
        },
        {
            id: 10,
            reference: '2 Timothy 3:16-17',
            text: 'All Scripture is God-breathed and is useful for teaching, rebuking, correcting and training in righteousness, so that the servant of God may be thoroughly equipped for every good work.',
            category: 'Scripture',
            difficulty: 'advanced'
        }
    ],

    // Storage key
    STORAGE_KEY: 'lessonforge_flashcards',

    // State
    state: {
        currentIndex: 0,
        isFlipped: false,
        progress: {}, // { verseId: { mastery: 0-5, lastReview: date, nextReview: date } }
        studySessions: 0,
        correctAnswers: 0,
        totalAttempts: 0
    },

    // Current study session
    session: {
        deck: [],
        currentIndex: 0,
        correct: 0,
        incorrect: 0
    },

    /**
     * Initialize flashcard system
     */
    init() {
        this.loadState();
        console.log('📖 Flashcard system initialized');
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
                console.warn('Could not load flashcard state');
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
     * Get mastery level for a verse
     */
    getMastery(verseId) {
        return this.state.progress[verseId]?.mastery || 0;
    },

    /**
     * Get mastery label
     */
    getMasteryLabel(level) {
        const labels = ['New', 'Learning', 'Familiar', 'Known', 'Mastered', 'Expert'];
        return labels[Math.min(level, 5)];
    },

    /**
     * Get mastery color
     */
    getMasteryColor(level) {
        const colors = ['#71717a', '#ef4444', '#f59e0b', '#3b82f6', '#5aad8a', '#438a6b'];
        return colors[Math.min(level, 5)];
    },

    /**
     * Start a study session
     */
    startSession(mode = 'all', category = null) {
        let deck = [...this.verses];

        // Filter by category
        if (category) {
            deck = deck.filter(v => v.category === category);
        }

        // Filter by mode
        if (mode === 'review') {
            // Only verses due for review
            deck = deck.filter(v => {
                const progress = this.state.progress[v.id];
                if (!progress) return true;
                return new Date() >= new Date(progress.nextReview);
            });
        } else if (mode === 'weak') {
            // Verses with low mastery
            deck = deck.filter(v => this.getMastery(v.id) < 3);
        }

        // Shuffle deck
        this.shuffleArray(deck);

        this.session = {
            deck: deck,
            currentIndex: 0,
            correct: 0,
            incorrect: 0
        };

        this.state.studySessions++;
        this.saveState();

        return deck.length > 0;
    },

    /**
     * Shuffle array (Fisher-Yates)
     */
    shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    },

    /**
     * Get current card
     */
    getCurrentCard() {
        return this.session.deck[this.session.currentIndex] || null;
    },

    /**
     * Check if session is complete
     */
    isSessionComplete() {
        return this.session.currentIndex >= this.session.deck.length;
    },

    /**
     * Record answer and advance
     */
    recordAnswer(knewIt) {
        const card = this.getCurrentCard();
        if (!card) return;

        this.state.totalAttempts++;

        if (knewIt) {
            this.session.correct++;
            this.state.correctAnswers++;
            this.increaseMastery(card.id);
        } else {
            this.session.incorrect++;
            this.decreaseMastery(card.id);
        }

        this.session.currentIndex++;
        this.saveState();

        // Check for badge
        if (typeof Gamification !== 'undefined') {
            if (this.getMastery(card.id) >= 5) {
                Gamification.recordVerseMemorized();
            }
        }
    },

    /**
     * Increase mastery level
     */
    increaseMastery(verseId) {
        if (!this.state.progress[verseId]) {
            this.state.progress[verseId] = { mastery: 0, lastReview: null, nextReview: null };
        }

        const progress = this.state.progress[verseId];
        progress.mastery = Math.min(progress.mastery + 1, 5);
        progress.lastReview = new Date().toISOString();

        // Spaced repetition: next review based on mastery
        const daysUntilReview = [1, 2, 4, 7, 14, 30][progress.mastery];
        const nextReview = new Date();
        nextReview.setDate(nextReview.getDate() + daysUntilReview);
        progress.nextReview = nextReview.toISOString();
    },

    /**
     * Decrease mastery level
     */
    decreaseMastery(verseId) {
        if (!this.state.progress[verseId]) {
            this.state.progress[verseId] = { mastery: 0, lastReview: null, nextReview: null };
        }

        const progress = this.state.progress[verseId];
        progress.mastery = Math.max(progress.mastery - 1, 0);
        progress.lastReview = new Date().toISOString();
        progress.nextReview = new Date().toISOString(); // Review tomorrow
    },

    /**
     * Get session results
     */
    getSessionResults() {
        const total = this.session.correct + this.session.incorrect;
        return {
            correct: this.session.correct,
            incorrect: this.session.incorrect,
            total: total,
            percentage: total > 0 ? Math.round((this.session.correct / total) * 100) : 0
        };
    },

    /**
     * Get all categories
     */
    getCategories() {
        const categories = [...new Set(this.verses.map(v => v.category))];
        return categories.map(cat => ({
            name: cat,
            count: this.verses.filter(v => v.category === cat).length
        }));
    },

    /**
     * Get overall progress stats
     */
    getOverallProgress() {
        const mastered = this.verses.filter(v => this.getMastery(v.id) >= 5).length;
        const learning = this.verses.filter(v => {
            const m = this.getMastery(v.id);
            return m > 0 && m < 5;
        }).length;
        const notStarted = this.verses.filter(v => this.getMastery(v.id) === 0).length;

        return {
            total: this.verses.length,
            mastered,
            learning,
            notStarted,
            percentage: Math.round((mastered / this.verses.length) * 100)
        };
    },

    /**
     * Render flashcards page
     */
    renderPage() {
        const progress = this.getOverallProgress();
        const categories = this.getCategories();

        return `
            <div class="flashcards-page">
                <!-- Progress Overview -->
                <div class="flashcards-overview">
                    <div class="overview-card main">
                        <div class="verse-stack">
                            📖
                        </div>
                        <div class="overview-info">
                            <h2>Memory Verses</h2>
                            <p>Master scripture through spaced repetition</p>
                            <div class="overview-stats">
                                <span class="stat">
                                    <strong>${progress.mastered}</strong> Mastered
                                </span>
                                <span class="stat">
                                    <strong>${progress.learning}</strong> Learning
                                </span>
                                <span class="stat">
                                    <strong>${progress.notStarted}</strong> New
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="progress-ring-container">
                        <svg class="progress-ring" viewBox="0 0 100 100">
                            <circle class="progress-ring-bg" cx="50" cy="50" r="45"/>
                            <circle class="progress-ring-fill" cx="50" cy="50" r="45"
                                stroke-dasharray="${progress.percentage * 2.83} 283"
                                transform="rotate(-90 50 50)"/>
                        </svg>
                        <div class="progress-ring-text">
                            <span class="progress-value">${progress.percentage}%</span>
                            <span class="progress-label">Progress</span>
                        </div>
                    </div>
                </div>

                <!-- Study Modes -->
                <div class="study-modes">
                    <h3>Start Studying</h3>
                    <div class="mode-buttons">
                        <button class="mode-btn" onclick="Flashcards.startStudyMode('all')">
                            <span class="mode-icon">📚</span>
                            <span class="mode-name">All Verses</span>
                            <span class="mode-count">${this.verses.length} cards</span>
                        </button>
                        <button class="mode-btn" onclick="Flashcards.startStudyMode('review')">
                            <span class="mode-icon">🔄</span>
                            <span class="mode-name">Due for Review</span>
                            <span class="mode-count">Based on schedule</span>
                        </button>
                        <button class="mode-btn" onclick="Flashcards.startStudyMode('weak')">
                            <span class="mode-icon">💪</span>
                            <span class="mode-name">Need Practice</span>
                            <span class="mode-count">Low mastery</span>
                        </button>
                    </div>
                </div>

                <!-- Categories -->
                <div class="verse-categories">
                    <h3>Browse by Topic</h3>
                    <div class="category-grid">
                        ${categories.map(cat => `
                            <button class="category-btn" onclick="Flashcards.startStudyMode('all', '${cat.name}')">
                                <span class="category-name">${cat.name}</span>
                                <span class="category-count">${cat.count} verses</span>
                            </button>
                        `).join('')}
                    </div>
                </div>

                <!-- Verse List -->
                <div class="verse-list">
                    <h3>All Verses</h3>
                    <div class="verses-grid">
                        ${this.verses.map(verse => {
            const mastery = this.getMastery(verse.id);
            return `
                                <div class="verse-card-preview" onclick="Flashcards.showVerseDetail(${verse.id})">
                                    <div class="verse-mastery" style="background: ${this.getMasteryColor(mastery)}">
                                        ${this.getMasteryLabel(mastery)}
                                    </div>
                                    <h4>${verse.reference}</h4>
                                    <p>${verse.text.substring(0, 80)}...</p>
                                    <span class="verse-category">${verse.category}</span>
                                </div>
                            `;
        }).join('')}
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Render study view (single card)
     */
    renderStudyView() {
        const card = this.getCurrentCard();

        if (!card) {
            return this.renderSessionComplete();
        }

        const progress = this.session.currentIndex + 1;
        const total = this.session.deck.length;
        const mastery = this.getMastery(card.id);

        return `
            <div class="study-view">
                <!-- Progress Bar -->
                <div class="study-progress">
                    <div class="study-progress-bar">
                        <div class="study-progress-fill" style="width: ${(progress / total) * 100}%"></div>
                    </div>
                    <span class="study-progress-text">${progress} / ${total}</span>
                </div>

                <!-- Flashcard -->
                <div class="flashcard-container">
                    <div class="flashcard ${this.state.isFlipped ? 'flipped' : ''}" onclick="Flashcards.flipCard()">
                        <div class="flashcard-front">
                            <span class="card-label">Reference</span>
                            <h2 class="card-reference">${card.reference}</h2>
                            <p class="card-hint">Tap to reveal verse</p>
                            <div class="card-mastery" style="background: ${this.getMasteryColor(mastery)}">
                                ${this.getMasteryLabel(mastery)}
                            </div>
                        </div>
                        <div class="flashcard-back">
                            <span class="card-label">${card.reference}</span>
                            <p class="card-verse">${card.text}</p>
                            <span class="card-category">${card.category}</span>
                        </div>
                    </div>
                </div>

                <!-- Answer Buttons -->
                <div class="answer-buttons ${this.state.isFlipped ? 'visible' : ''}">
                    <button class="answer-btn incorrect" onclick="Flashcards.answer(false)">
                        <span class="answer-icon">😕</span>
                        <span class="answer-text">Still Learning</span>
                    </button>
                    <button class="answer-btn correct" onclick="Flashcards.answer(true)">
                        <span class="answer-icon">😊</span>
                        <span class="answer-text">Got It!</span>
                    </button>
                </div>

                <!-- Skip/Exit -->
                <div class="study-actions">
                    <button class="btn btn-secondary" onclick="Flashcards.exitStudy()">
                        Exit Session
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Render session complete screen
     */
    renderSessionComplete() {
        const results = this.getSessionResults();
        const grade = results.percentage >= 80 ? 'excellent' : results.percentage >= 60 ? 'good' : 'keep-practicing';

        return `
            <div class="session-complete">
                <div class="complete-icon">${results.percentage >= 80 ? '🎉' : results.percentage >= 60 ? '👍' : '💪'}</div>
                <h2>Session Complete!</h2>
                
                <div class="results-circle">
                    <svg viewBox="0 0 100 100">
                        <circle class="results-bg" cx="50" cy="50" r="45"/>
                        <circle class="results-fill ${grade}" cx="50" cy="50" r="45"
                            stroke-dasharray="${results.percentage * 2.83} 283"
                            transform="rotate(-90 50 50)"/>
                    </svg>
                    <div class="results-text">
                        <span class="results-value">${results.percentage}%</span>
                    </div>
                </div>

                <div class="results-stats">
                    <div class="result-stat">
                        <span class="stat-value">${results.correct}</span>
                        <span class="stat-label">Correct</span>
                    </div>
                    <div class="result-stat">
                        <span class="stat-value">${results.incorrect}</span>
                        <span class="stat-label">Learning</span>
                    </div>
                    <div class="result-stat">
                        <span class="stat-value">${results.total}</span>
                        <span class="stat-label">Total</span>
                    </div>
                </div>

                <div class="complete-message">
                    ${results.percentage >= 80
                ? '<p>Excellent work! Your scripture knowledge is growing! 📖</p>'
                : results.percentage >= 60
                    ? '<p>Good progress! Keep reviewing to strengthen your memory.</p>'
                    : '<p>Keep practicing! Repetition is the key to memorization. 💪</p>'
            }
                </div>

                <div class="complete-actions">
                    <button class="btn btn-primary" onclick="Flashcards.startStudyMode('weak')">
                        Review Weak Verses
                    </button>
                    <button class="btn btn-secondary" onclick="app.showView('flashcards')">
                        Back to Flashcards
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Flip current card
     */
    flipCard() {
        this.state.isFlipped = !this.state.isFlipped;
        this.updateStudyView();
    },

    /**
     * Record answer and show next card
     */
    answer(knewIt) {
        this.recordAnswer(knewIt);
        this.state.isFlipped = false;
        this.updateStudyView();
    },

    /**
     * Start study mode
     */
    startStudyMode(mode, category = null) {
        if (this.startSession(mode, category)) {
            this.state.isFlipped = false;
            const container = document.getElementById('flashcards-content');
            if (container) {
                container.innerHTML = this.renderStudyView();
            }
        } else {
            app.showToast('No verses available for this study mode', 'warning');
        }
    },

    /**
     * Update study view
     */
    updateStudyView() {
        const container = document.getElementById('flashcards-content');
        if (container) {
            container.innerHTML = this.renderStudyView();
        }
    },

    /**
     * Exit study session
     */
    exitStudy() {
        const container = document.getElementById('flashcards-content');
        if (container) {
            container.innerHTML = this.renderPage();
        }
    },

    /**
     * Show verse detail modal
     */
    showVerseDetail(verseId) {
        const verse = this.verses.find(v => v.id === verseId);
        if (!verse) return;

        const mastery = this.getMastery(verse.id);
        const progress = this.state.progress[verse.id];

        const modal = document.createElement('div');
        modal.className = 'verse-detail-modal modal active';
        modal.innerHTML = `
            <div class="modal-content">
                <button class="modal-close" onclick="this.closest('.modal').remove()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
                <div class="verse-detail">
                    <div class="verse-header">
                        <span class="verse-mastery-badge" style="background: ${this.getMasteryColor(mastery)}">
                            ${this.getMasteryLabel(mastery)}
                        </span>
                        <span class="verse-category-badge">${verse.category}</span>
                    </div>
                    <h2>${verse.reference}</h2>
                    <blockquote>${verse.text}</blockquote>
                    ${progress ? `
                        <div class="verse-progress-info">
                            <p>Last reviewed: ${new Date(progress.lastReview).toLocaleDateString()}</p>
                            <p>Next review: ${new Date(progress.nextReview).toLocaleDateString()}</p>
                        </div>
                    ` : ''}
                    <button class="btn btn-primary" onclick="Flashcards.practiceVerse(${verse.id}); this.closest('.modal').remove();">
                        Practice This Verse
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    },

    /**
     * Practice a specific verse
     */
    practiceVerse(verseId) {
        const verse = this.verses.find(v => v.id === verseId);
        if (!verse) return;

        this.session = {
            deck: [verse],
            currentIndex: 0,
            correct: 0,
            incorrect: 0
        };

        this.state.isFlipped = false;
        const container = document.getElementById('flashcards-content');
        if (container) {
            container.innerHTML = this.renderStudyView();
        }
    }
};

// Export for global access
window.Flashcards = Flashcards;
