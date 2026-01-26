/**
 * LessonForge - Interactive Learning Platform
 * Main Application JavaScript
 * 
 * @author Jared
 * @version 1.0.0
 */

// ============================================
// Configuration
// ============================================
const CONFIG = {
    API_BASE: '/api',
    STORAGE_KEY: 'lessonforge_user',
    TOAST_DURATION: 4000
};

// ============================================
// Application State
// ============================================
const state = {
    user: null,
    lessons: [],
    currentLesson: null,
    builderBlocks: [],
    editingLessonId: null
};

// ============================================
// API Client
// ============================================
const api = {
    /**
     * Make an API request
     */
    async request(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`${CONFIG.API_BASE}${endpoint}`, options);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Request failed');
            }

            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    // Auth endpoints
    async login(email, password) {
        return this.request('/auth/login', 'POST', { email, password });
    },

    async register(name, email, password, role) {
        return this.request('/auth/register', 'POST', { name, email, password, role });
    },

    // Lesson endpoints
    async getLessons(publishedOnly = false) {
        const query = publishedOnly ? '?published=true' : '';
        return this.request(`/lessons${query}`);
    },

    async getLesson(id) {
        return this.request(`/lessons/${id}`);
    },

    async createLesson(data) {
        return this.request('/lessons', 'POST', data);
    },

    async updateLesson(id, data) {
        return this.request(`/lessons/${id}`, 'PUT', data);
    },

    async deleteLesson(id) {
        return this.request(`/lessons/${id}`, 'DELETE');
    },

    // Block endpoints
    async addBlock(lessonId, data) {
        return this.request(`/lessons/${lessonId}/blocks`, 'POST', data);
    },

    async updateBlock(id, data) {
        return this.request(`/blocks/${id}`, 'PUT', data);
    },

    async deleteBlock(id) {
        return this.request(`/blocks/${id}`, 'DELETE');
    },

    // Progress endpoints
    async getProgress(userId) {
        return this.request(`/progress/${userId}`);
    },

    async getStats(userId) {
        return this.request(`/progress/${userId}/stats`);
    },

    async recordProgress(data) {
        return this.request('/progress', 'POST', data);
    },

    // Verse endpoints
    async getTodayVerse() {
        return this.request('/verse');
    },

    async getThemes() {
        return this.request('/verses/themes');
    }
};

// ============================================
// Time Tracking System
// ============================================
const TimeTracker = {
    timer: null,
    activeLessonId: null,
    INTERVAL: 30000, // 30 seconds

    /**
     * Start tracking time for a lesson
     */
    start(lessonId) {
        // Stop any existing timer
        this.stop();

        console.log(`⏱️ Starting time tracker for lesson ${lessonId}`);
        this.activeLessonId = lessonId;

        // Initial heartbeat immediately (optional, or wait for interval)
        // waiting for interval to avoid spamming on quick clicks

        this.timer = setInterval(() => {
            if (document.visibilityState === 'visible') {
                this.sendHeartbeat();
            }
        }, this.INTERVAL);
    },

    /**
     * Stop tracking
     */
    stop() {
        if (this.timer) {
            console.log('🛑 Stopping time tracker');
            clearInterval(this.timer);
            this.timer = null;
        }
        this.activeLessonId = null;
    },

    /**
     * Send heartbeat to server
     */
    async sendHeartbeat() {
        if (!this.activeLessonId || !state.user) return;

        try {
            await api.recordProgress({
                student_id: state.user.id,
                lesson_id: this.activeLessonId,
                time_spent_seconds: this.INTERVAL / 1000,
                // We don't specify block_id so it counts towards general lesson time
            });
            console.log(`✅ Recorded ${this.INTERVAL / 1000}s learning time`);
        } catch (error) {
            console.warn('Failed to record learning time:', error);
        }
    }
};

// ============================================
// Application Controller
// ============================================
const app = {
    /**
     * Initialize the application
     */
    async init() {
        console.log('🚀 LessonForge initializing...');

        // Check for stored user session
        this.loadUserSession();

        // Setup event listeners
        this.setupEventListeners();

        // Load initial data
        await this.loadInitialData();

        // Initialize feature modules
        if (typeof Gamification !== 'undefined') {
            Gamification.init();
        }
        if (typeof Analytics !== 'undefined') {
            Analytics.init();
        }
        if (typeof Flashcards !== 'undefined') {
            Flashcards.init();
        }
        if (typeof DragDrop !== 'undefined') {
            DragDrop.init();
        }
        if (typeof Accessibility !== 'undefined') {
            Accessibility.init();
        }

        // Hide loading screen
        setTimeout(() => {
            document.getElementById('loading-screen').classList.add('hidden');
            document.getElementById('app').style.display = 'flex';

            // Show auth modal if not logged in
            if (!state.user) {
                this.showAuthModal();
            }
        }, 1000);

        console.log('✅ LessonForge ready!');
    },

    /**
     * Load user session from storage
     */
    loadUserSession() {
        const stored = localStorage.getItem(CONFIG.STORAGE_KEY);
        if (stored) {
            try {
                state.user = JSON.parse(stored);
                this.updateUserDisplay();
            } catch (e) {
                localStorage.removeItem(CONFIG.STORAGE_KEY);
            }
        }
    },

    /**
     * Save user session to storage
     */
    saveUserSession(user) {
        state.user = user;
        localStorage.setItem(CONFIG.STORAGE_KEY, JSON.stringify(user));
        this.updateUserDisplay();
    },

    /**
     * Clear user session
     */
    logout() {
        state.user = null;
        localStorage.removeItem(CONFIG.STORAGE_KEY);
        this.showAuthModal();
        app.showToast('Logged out successfully', 'success');
    },

    /**
     * Update user display in sidebar
     */
    updateUserDisplay() {
        if (state.user) {
            document.getElementById('user-name').textContent = state.user.name;
            document.getElementById('user-role').textContent = state.user.role;
            document.getElementById('user-initials').textContent =
                state.user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        }

        // Mobile Menu Toggle
        const mobileToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');

        if (mobileToggle && sidebar) {
            console.log('📱 Mobile toggle initialized');
            mobileToggle.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent bubbling
                console.log('👆 Toggle clicked');
                sidebar.classList.toggle('active');
                console.log('Sidebar active:', sidebar.classList.contains('active'));
            });

            // Close sidebar when clicking outside (optional but good UX)
            document.addEventListener('click', (e) => {
                if (window.innerWidth < 768 &&
                    sidebar.classList.contains('active') &&
                    !sidebar.contains(e.target) &&
                    !mobileToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            });

            // Close sidebar when clicking a link on mobile
            document.querySelectorAll('.nav-item').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 768) {
                        sidebar.classList.remove('active');
                    }
                });
            });
        }
    },

    /**
     * Setup all event listeners
     */
    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const view = item.dataset.view;
                this.showView(view);
            });
        });

        // Auth tabs
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const targetTab = tab.dataset.tab;
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(`${targetTab}-form`).classList.add('active');
            });
        });

        // Login form
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleLogin();
        });

        // Register form
        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleRegister();
        });

        // Logout button
        document.getElementById('logout-btn').addEventListener('click', () => this.logout());

        // Search and filter
        document.getElementById('lesson-search')?.addEventListener('input', (e) => {
            this.filterLessons(e.target.value);
        });

        document.getElementById('lesson-filter')?.addEventListener('change', (e) => {
            this.filterLessonsBySubject(e.target.value);
        });

        // Mobile Menu Toggle
        const mobileToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');

        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('active');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', (e) => {
                if (window.innerWidth < 768 &&
                    sidebar.classList.contains('active') &&
                    !sidebar.contains(e.target) &&
                    !mobileToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            });

            // Close sidebar when clicking a link
            document.querySelectorAll('.nav-item').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 768) {
                        sidebar.classList.remove('active');
                    }
                });
            });
        }
    },

    /**
     * Load initial data from API
     */
    async loadInitialData() {
        try {
            // Load daily verse
            await this.loadDailyVerse();

            // Load lessons
            await this.loadLessons();

            // Load stats if user is logged in
            if (state.user) {
                await this.loadStats();
            }
        } catch (error) {
            console.warn('Could not load some data:', error);
            app.showToast('Could not reach the server. Please check your connection.', 'error');
        }
    },

    /**
     * Load demo data for offline/demo mode
     */
    loadDemoData() {
        // Use centralized demo data if available
        if (typeof DEMO_DATA !== 'undefined') {
            this.displayVerse(DEMO_DATA.todayVerse);

            // 1. FILTER LESSONS BASED ON ROLE
            let visibleLessons = DEMO_DATA.lessons;

            // If user is a student, only show published lessons
            if (state.user && state.user.role === 'student') {
                visibleLessons = DEMO_DATA.lessons.filter(l => l.is_published);
            }
            // Teachers see everything (including drafts)

            state.lessons = visibleLessons;
            this.renderLessons();

            // 2. LOAD USER-SPECIFIC STATS
            const userId = state.user ? state.user.id : 2; // Default to Student stats if not logged in
            const stats = DEMO_DATA.stats[userId] || DEMO_DATA.stats[2];

            if (state.user && state.user.role === 'teacher') {
                // Teacher View
                document.getElementById('stat-lessons').textContent = stats.lessons_started;
                document.getElementById('stat-lessons').parentElement.querySelector('.stat-label').textContent = 'Lessons Created';

                document.getElementById('stat-completed').textContent = stats.lessons_completed;
                document.getElementById('stat-completed').parentElement.querySelector('.stat-label').textContent = 'Published';

                document.getElementById('stat-score').textContent = '—';
                document.getElementById('stat-time').textContent = '—';
            } else {
                // Student View
                document.getElementById('stat-lessons').textContent = stats.lessons_started;
                document.getElementById('stat-completed').textContent = stats.lessons_completed;
                document.getElementById('stat-score').textContent = stats.average_score ? `${stats.average_score}%` : '—';
                document.getElementById('stat-time').textContent = stats.total_time_formatted;
            }

            // 3. LOAD PROGRESS
            // In a real app we'd map this to lessons, but for demo we just need to ensure
            // the UI looks populated if we click around
            console.log('📚 Demo mode active for:', state.user ? state.user.role : 'Guest');
            return;
        }

        // Fallback (keep existing fallback just in case)
        this.displayVerse({
            verse_text: 'The fear of the Lord is the beginning of wisdom...',
            verse_reference: 'Proverbs 9:10',
            theme: 'Wisdom'
        });
        // ... (rest of fallback ignored as DEMO_DATA is present)
    },

    /**
     * Load daily verse
     */
    async loadDailyVerse() {
        try {
            const result = await api.getTodayVerse();
            this.displayVerse(result.verse);
        } catch (error) {
            // Use fallback verse
            this.displayVerse({
                verse_text: 'Let the wise listen and add to their learning, and let the discerning get guidance.',
                verse_reference: 'Proverbs 1:5',
                theme: 'Learning'
            });
        }
    },

    /**
     * Display verse in UI
     */
    displayVerse(verse) {
        // Dashboard verse
        document.getElementById('verse-text').textContent = `"${verse.verse_text}"`;
        document.getElementById('verse-reference').textContent = `— ${verse.verse_reference}`;
        document.getElementById('verse-theme').textContent = verse.theme || 'Daily Wisdom';

        // Full verse view
        document.getElementById('verse-text-large').textContent = `"${verse.verse_text}"`;
        document.getElementById('verse-reference-large').textContent = `— ${verse.verse_reference}`;
        document.getElementById('verse-theme-large').textContent = `Theme: ${verse.theme || 'Daily Wisdom'}`;
    },

    /**
     * Load lessons
     */
    async loadLessons() {
        try {
            const result = await api.getLessons();
            state.lessons = result.lessons || [];
            this.renderLessons();
        } catch (error) {
            console.warn('Could not load lessons:', error);
        }
    },

    /**
     * Render lessons in UI
     */
    renderLessons() {
        const recentContainer = document.getElementById('recent-lessons');
        const allContainer = document.getElementById('all-lessons');

        const lessonHTML = state.lessons.map(lesson => this.createLessonCard(lesson)).join('');

        if (recentContainer) {
            recentContainer.innerHTML = lessonHTML || this.createEmptyState('No lessons yet', 'Create your first lesson to get started!');
        }

        if (allContainer) {
            allContainer.innerHTML = lessonHTML || this.createEmptyState('No lessons found', 'Try adjusting your search or filters.');
        }
    },

    /**
     * Create lesson card HTML
     */
    createLessonCard(lesson) {
        const progress = lesson.progress || 0;
        return `
            <div class="lesson-card" onclick="app.openLesson(${lesson.id})">
                <div class="lesson-card-header">
                    <div>
                        <h3 class="lesson-card-title">${this.escapeHtml(lesson.title)}</h3>
                        <span class="lesson-card-subject">${this.escapeHtml(lesson.subject || 'General')}</span>
                    </div>
                    <span class="lesson-card-badge ${lesson.is_published ? 'published' : 'draft'}">
                        ${lesson.is_published ? 'Published' : 'Draft'}
                    </span>
                </div>
                <p class="lesson-card-description">${this.escapeHtml(lesson.description || 'No description')}</p>
                <div class="lesson-card-footer">
                    <span class="lesson-card-meta">${lesson.grade_level || 'All grades'} • By ${this.escapeHtml(lesson.teacher_name || 'Unknown')}</span>
                    <div class="lesson-card-progress">
                        <div class="progress-bar">
                            <div class="progress-bar-fill" style="width: ${progress}%"></div>
                        </div>
                        <span class="progress-text">${progress}%</span>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Create empty state HTML
     */
    createEmptyState(title, message) {
        return `
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
                <p>${title}</p>
                <span>${message}</span>
            </div>
        `;
    },

    /**
     * Load user statistics
     */
    async loadStats() {
        if (!state.user) return;

        try {
            const result = await api.getStats(state.user.id);
            const stats = result.stats;

            document.getElementById('stat-lessons').textContent = stats.lessons_started || 0;
            document.getElementById('stat-completed').textContent = stats.lessons_completed || 0;
            document.getElementById('stat-score').textContent = stats.average_score ? `${stats.average_score}%` : '—';
            document.getElementById('stat-time').textContent = stats.total_time_formatted || '0m';
        } catch (error) {
            console.warn('Could not load stats:', error);
        }
    },

    /**
     * Show a specific view
     */
    showView(viewName) {
        // Update navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.toggle('active', item.dataset.view === viewName);
        });

        // Update views
        document.querySelectorAll('.view').forEach(view => {
            view.classList.remove('active');
        });
        document.getElementById(`view-${viewName}`)?.classList.add('active');

        // Load view-specific data
        switch (viewName) {
            case 'verses':
                this.loadThemes();
                break;
            case 'flashcards':
                this.renderFlashcardsView();
                break;
            case 'analytics':
                this.renderAnalyticsView();
                break;
            case 'achievements':
                this.renderAchievementsView();
                break;
        }
    },

    /**
     * Render flashcards view
     */
    renderFlashcardsView() {
        const container = document.getElementById('flashcards-content');
        if (container && typeof Flashcards !== 'undefined') {
            container.innerHTML = Flashcards.renderPage();
        }
    },

    /**
     * Render analytics view
     */
    renderAnalyticsView() {
        const container = document.getElementById('analytics-content');
        if (container && typeof Analytics !== 'undefined') {
            container.innerHTML = Analytics.renderDashboard();
            Analytics.initCharts();
        }
    },

    /**
     * Render achievements view
     */
    renderAchievementsView() {
        const container = document.getElementById('achievements-content');
        if (container && typeof Gamification !== 'undefined') {
            container.innerHTML = Gamification.renderAchievementsPage();
            Gamification.updateXPDisplay();
        }
    },

    /**
     * Load theme tags for verse view
     */
    async loadThemes() {
        const container = document.getElementById('theme-tags');
        if (!container) return;

        const themes = ['Learning', 'Wisdom', 'Diligence', 'Strength', 'Guidance', 'Excellence', 'Knowledge', 'Study', 'Education'];

        container.innerHTML = themes.map(theme =>
            `<span class="theme-tag" onclick="app.loadVerseByTheme('${theme}')">${theme}</span>`
        ).join('');
    },

    /**
     * Load verse by theme
     */
    async loadVerseByTheme(theme) {
        try {
            const result = await api.request(`/verses/theme/${encodeURIComponent(theme)}`);
            if (result.verses && result.verses.length > 0) {
                const verse = result.verses[Math.floor(Math.random() * result.verses.length)];
                this.displayVerse(verse);
            } else {
                throw new Error('No verses found');
            }
        } catch (error) {
            console.warn('Could not load verse by theme');
            app.showToast(`Could not load verse for ${theme}`, 'warning');
        }
    },

    /**
     * Filter lessons by search query
     */
    filterLessons(query) {
        const filtered = state.lessons.filter(lesson =>
            lesson.title.toLowerCase().includes(query.toLowerCase()) ||
            (lesson.description && lesson.description.toLowerCase().includes(query.toLowerCase()))
        );

        const container = document.getElementById('all-lessons');
        if (container) {
            container.innerHTML = filtered.map(lesson => this.createLessonCard(lesson)).join('') ||
                this.createEmptyState('No lessons found', 'Try a different search term.');
        }
    },

    /**
     * Filter lessons by subject
     */
    filterLessonsBySubject(subject) {
        const filtered = subject === 'all'
            ? state.lessons
            : state.lessons.filter(lesson => lesson.subject === subject);

        const container = document.getElementById('all-lessons');
        if (container) {
            container.innerHTML = filtered.map(lesson => this.createLessonCard(lesson)).join('') ||
                this.createEmptyState('No lessons found', 'Try a different filter.');
        }
    },

    /**
     * Open a lesson
     */
    async openLesson(lessonId) {
        try {
            const result = await api.getLesson(lessonId);
            state.currentLesson = result.lesson;
            this.renderLessonContent();
            document.getElementById('lesson-modal').classList.add('active');

            // Start tracking time
            TimeTracker.start(lessonId);
        } catch (error) {
            // Use demo content
            this.showDemoLesson(lessonId);
            TimeTracker.start(lessonId); // Track time even for demo lessons
        }
    },

    /**
     * Close lesson modal
     */
    closeLessonModal() {
        document.getElementById('lesson-modal').classList.remove('active');
        state.currentLesson = null;
        TimeTracker.stop(); // Stop time tracking

        // Refresh stats to show new time
        if (state.user) {
            this.loadStats();
        }
    },

    /**
     * Show demo lesson content
     */
    showDemoLesson(lessonId) {
        const lesson = state.lessons.find(l => l.id === lessonId);
        if (!lesson) return;

        const container = document.getElementById('lesson-content');

        // If lesson has blocks (from DEMO_DATA), render them properly
        if (lesson.blocks && lesson.blocks.length > 0) {
            state.currentLesson = lesson;
            this.renderLessonContent();
            document.getElementById('lesson-modal').classList.add('active');
            return;
        }

        // Fallback simple demo content
        container.innerHTML = `
            <h2>${this.escapeHtml(lesson.title)}</h2>
            <p style="color: var(--text-secondary); margin-bottom: var(--space-6);">
                ${this.escapeHtml(lesson.description || '')}
            </p>
            
            <div class="lesson-block">
                <h3>Welcome to the Lesson!</h3>
                <p>This is a demo of how lesson content would appear. In a full implementation, 
                you would see interactive content blocks including text, videos, quizzes, and scripture references.</p>
            </div>
            
            <div class="lesson-block scripture">
                <h3>📖 Scripture for Today</h3>
                <blockquote style="font-style: italic; margin: var(--space-4) 0;">
                    "The fear of the Lord is the beginning of wisdom, and knowledge of the Holy One is understanding."
                </blockquote>
                <p>— Proverbs 9:10</p>
            </div>
            
            <div class="lesson-block">
                <h3>🧠 Quick Quiz</h3>
                <p class="quiz-question">What is the main topic of this lesson?</p>
                <div class="quiz-answers">
                    <div class="quiz-answer" onclick="app.checkQuizAnswer(this, 0, true)">${this.escapeHtml(lesson.subject || 'General Topic')}</div>
                    <div class="quiz-answer" onclick="app.checkQuizAnswer(this, 0, false)">History</div>
                    <div class="quiz-answer" onclick="app.checkQuizAnswer(this, 0, false)">Art</div>
                </div>
            </div>
        `;

        document.getElementById('lesson-modal').classList.add('active');
    },

    /**
     * Render lesson content
     */
    renderLessonContent() {
        if (!state.currentLesson) return;

        const lesson = state.currentLesson;
        const container = document.getElementById('lesson-content');

        let blocksHTML = '<h2>' + this.escapeHtml(lesson.title) + '</h2>';
        blocksHTML += '<p style="color: var(--text-secondary); margin-bottom: var(--space-6);">' +
            this.escapeHtml(lesson.description || '') + '</p>';

        if (lesson.blocks && lesson.blocks.length > 0) {
            lesson.blocks.forEach((block, index) => {
                blocksHTML += this.renderBlock(block, index);
            });
        } else {
            blocksHTML += '<div class="lesson-block"><p>This lesson has no content yet.</p></div>';
        }

        container.innerHTML = blocksHTML;
    },

    /**
     * Render a single block
     */
    renderBlock(block, index) {
        const content = typeof block.content === 'string' ? JSON.parse(block.content) : block.content;

        switch (block.block_type) {
            case 'text':
                return `
                    <div class="lesson-block">
                        ${content.title ? `<h3>${this.escapeHtml(content.title)}</h3>` : ''}
                        <p>${this.escapeHtml(content.body || '')}</p>
                    </div>
                `;

            case 'quiz':
                return `
                    <div class="lesson-block">
                        <h3>🧠 Quiz Question</h3>
                        <p class="quiz-question">${this.escapeHtml(content.question || '')}</p>
                        <div class="quiz-answers">
                            ${(content.options || []).map((opt, i) => `
                                <div class="quiz-answer" data-correct="${i === content.correct}" 
                                     onclick="app.checkQuizAnswer(this, ${block.id}, ${i === content.correct})">
                                    ${this.escapeHtml(opt)}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;

            case 'video':
                return `
                    <div class="lesson-block">
                        ${content.title ? `<h3>🎬 ${this.escapeHtml(content.title)}</h3>` : ''}
                        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: var(--radius-lg);">
                            <iframe src="${this.escapeHtml(content.url || '')}" 
                                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
                                    allowfullscreen></iframe>
                        </div>
                    </div>
                `;

            case 'scripture':
                return `
                    <div class="lesson-block scripture">
                        <h3>📖 Scripture</h3>
                        <blockquote style="font-style: italic; margin: var(--space-4) 0;">
                            "${this.escapeHtml(content.text || '')}"
                        </blockquote>
                        <p>— ${this.escapeHtml(content.reference || '')}</p>
                        ${content.reflection ? `<p style="margin-top: var(--space-4); color: var(--text-secondary);"><em>${this.escapeHtml(content.reflection)}</em></p>` : ''}
                    </div>
                `;

            case 'image':
                return `
                    <div class="lesson-block">
                        <img src="${this.escapeHtml(content.url || '')}" 
                             alt="${this.escapeHtml(content.alt || '')}"
                             style="max-width: 100%; border-radius: var(--radius-lg);">
                        ${content.caption ? `<p style="text-align: center; color: var(--text-secondary); margin-top: var(--space-2); font-size: 0.875rem;">${this.escapeHtml(content.caption)}</p>` : ''}
                    </div>
                `;

            default:
                return `<div class="lesson-block"><p>Unknown block type</p></div>`;
        }
    },

    /**
     * Check quiz answer
     */
    async checkQuizAnswer(element, blockId, isCorrect) {
        // Remove previous selections
        element.parentElement.querySelectorAll('.quiz-answer').forEach(a => {
            a.classList.remove('selected', 'correct', 'incorrect');
        });

        element.classList.add('selected');
        element.classList.add(isCorrect ? 'correct' : 'incorrect');

        // Record progress
        if (state.user && state.currentLesson) {
            try {
                await api.recordProgress({
                    student_id: state.user.id,
                    lesson_id: state.currentLesson.id,
                    block_id: blockId,
                    status: 'completed',
                    score: isCorrect ? 100 : 0
                });
            } catch (error) {
                console.warn('Could not record progress');
            }
        }

        // Award XP through gamification system
        if (typeof Gamification !== 'undefined') {
            Gamification.recordQuizAnswer(isCorrect, isCorrect);
        }

        if (isCorrect) {
            this.showToast('Correct! Great job! 🎉', 'success');
        } else {
            this.showToast('Not quite. Try again!', 'warning');
        }
    },

    /**
     * Close lesson modal
     */
    closeLessonModal() {
        document.getElementById('lesson-modal').classList.remove('active');
        state.currentLesson = null;
    },

    // ============================================
    // Lesson Builder
    // ============================================

    /**
     * Add a block to the builder
     */
    addBlock(type) {
        const block = {
            id: Date.now(),
            block_type: type,
            content: this.getDefaultBlockContent(type)
        };

        state.builderBlocks.push(block);
        this.renderBuilderBlocks();
    },

    /**
     * Get default content for block type
     */
    getDefaultBlockContent(type) {
        switch (type) {
            case 'text':
                return { title: '', body: '' };
            case 'quiz':
                return { question: '', options: ['', '', '', ''], correct: 0 };
            case 'video':
                return { title: '', url: '' };
            case 'scripture':
                return { reference: '', text: '', reflection: '' };
            case 'image':
                return { url: '', alt: '', caption: '' };
            default:
                return {};
        }
    },

    /**
     * Render builder blocks
     */
    renderBuilderBlocks() {
        const container = document.getElementById('blocks-list');

        if (state.builderBlocks.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    <p>Add blocks to build your lesson</p>
                    <span>Click the buttons above to add content</span>
                </div>
            `;
            return;
        }

        container.innerHTML = state.builderBlocks.map((block, index) =>
            this.createBuilderBlockHTML(block, index)
        ).join('');
    },

    /**
     * Create builder block HTML
     */
    createBuilderBlockHTML(block, index) {
        const icons = {
            text: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
            quiz: '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            video: '<polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>',
            scripture: '<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
            image: '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'
        };

        let contentFields = '';
        switch (block.block_type) {
            case 'text':
                contentFields = `
                    <input type="text" placeholder="Section title (optional)" value="${this.escapeHtml(block.content.title || '')}"
                           onchange="app.updateBlockContent(${index}, 'title', this.value)">
                    <textarea placeholder="Enter your content..." rows="3"
                              onchange="app.updateBlockContent(${index}, 'body', this.value)">${this.escapeHtml(block.content.body || '')}</textarea>
                `;
                break;
            case 'quiz':
                contentFields = `
                    <input type="text" placeholder="Quiz question" value="${this.escapeHtml(block.content.question || '')}"
                           onchange="app.updateBlockContent(${index}, 'question', this.value)">
                    <div class="quiz-options">
                        ${block.content.options.map((opt, i) => `
                            <div class="quiz-option">
                                <input type="radio" name="correct-${block.id}" ${i === block.content.correct ? 'checked' : ''}
                                       onchange="app.updateBlockContent(${index}, 'correct', ${i})">
                                <input type="text" placeholder="Option ${i + 1}" value="${this.escapeHtml(opt)}"
                                       onchange="app.updateQuizOption(${index}, ${i}, this.value)">
                            </div>
                        `).join('')}
                    </div>
                `;
                break;
            case 'video':
                contentFields = `
                    <input type="text" placeholder="Video title" value="${this.escapeHtml(block.content.title || '')}"
                           onchange="app.updateBlockContent(${index}, 'title', this.value)">
                    <input type="text" placeholder="YouTube embed URL" value="${this.escapeHtml(block.content.url || '')}"
                           onchange="app.updateBlockContent(${index}, 'url', this.value)">
                `;
                break;
            case 'scripture':
                contentFields = `
                    <input type="text" placeholder="Reference (e.g., John 3:16)" value="${this.escapeHtml(block.content.reference || '')}"
                           onchange="app.updateBlockContent(${index}, 'reference', this.value)">
                    <textarea placeholder="Scripture text..." rows="2"
                              onchange="app.updateBlockContent(${index}, 'text', this.value)">${this.escapeHtml(block.content.text || '')}</textarea>
                    <input type="text" placeholder="Reflection or application (optional)" value="${this.escapeHtml(block.content.reflection || '')}"
                           onchange="app.updateBlockContent(${index}, 'reflection', this.value)">
                `;
                break;
            case 'image':
                contentFields = `
                    <input type="text" placeholder="Image URL" value="${this.escapeHtml(block.content.url || '')}"
                           onchange="app.updateBlockContent(${index}, 'url', this.value)">
                    <input type="text" placeholder="Alt text" value="${this.escapeHtml(block.content.alt || '')}"
                           onchange="app.updateBlockContent(${index}, 'alt', this.value)">
                    <input type="text" placeholder="Caption (optional)" value="${this.escapeHtml(block.content.caption || '')}"
                           onchange="app.updateBlockContent(${index}, 'caption', this.value)">
                `;
                break;
        }

        return `
            <div class="block-item" data-index="${index}">
                <div class="block-handle" title="Drag to reorder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"/>
                        <line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                </div>
                <div class="block-type-icon ${block.block_type}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        ${icons[block.block_type]}
                    </svg>
                </div>
                <div class="block-content">
                    ${contentFields}
                </div>
                <div class="block-actions-right">
                    <button class="block-action-btn delete" onclick="app.removeBlock(${index})" title="Delete">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Update block content field
     */
    updateBlockContent(index, field, value) {
        if (state.builderBlocks[index]) {
            state.builderBlocks[index].content[field] = value;
        }
    },

    /**
     * Update quiz option
     */
    updateQuizOption(blockIndex, optionIndex, value) {
        if (state.builderBlocks[blockIndex]) {
            state.builderBlocks[blockIndex].content.options[optionIndex] = value;
        }
    },

    /**
     * Remove a block
     */
    removeBlock(index) {
        state.builderBlocks.splice(index, 1);
        this.renderBuilderBlocks();
    },

    /**
     * Clear the builder
     */
    clearBuilder() {
        state.builderBlocks = [];
        state.editingLessonId = null;
        document.getElementById('lesson-title').value = '';
        document.getElementById('lesson-description').value = '';
        document.getElementById('lesson-subject').value = '';
        document.getElementById('lesson-grade').value = '';
        this.renderBuilderBlocks();
        this.showToast('Builder cleared', 'success');
    },

    /**
     * Save lesson
     */
    async saveLesson(publish = false) {
        const title = document.getElementById('lesson-title').value.trim();

        if (!title) {
            this.showToast('Please enter a lesson title', 'error');
            return;
        }

        const lessonData = {
            teacher_id: state.user?.id || 1,
            title,
            description: document.getElementById('lesson-description').value.trim(),
            subject: document.getElementById('lesson-subject').value,
            grade_level: document.getElementById('lesson-grade').value,
            is_published: publish
        };

        try {
            let result;
            if (state.editingLessonId) {
                result = await api.updateLesson(state.editingLessonId, lessonData);
            } else {
                result = await api.createLesson(lessonData);
            }

            const lessonId = result.lesson.id;

            // Save blocks
            for (const block of state.builderBlocks) {
                await api.addBlock(lessonId, {
                    block_type: block.block_type,
                    content: block.content
                });
            }

            this.showToast(publish ? 'Lesson published!' : 'Lesson saved as draft', 'success');
            this.clearBuilder();
            await this.loadLessons();
            this.showView('lessons');
        } catch (error) {
            console.warn('Could not save to API, saving locally');
            // Demo mode - just show success
            this.showToast(publish ? 'Lesson published! (Demo mode)' : 'Lesson saved! (Demo mode)', 'success');
            this.clearBuilder();
        }
    },

    // ============================================
    // Authentication
    // ============================================

    /**
     * Show auth modal
     */
    showAuthModal() {
        document.getElementById('auth-modal').classList.add('active');
    },

    /**
     * Hide auth modal
     */
    hideAuthModal() {
        document.getElementById('auth-modal').classList.remove('active');
    },

    /**
     * Handle login
     */
    async handleLogin() {
        const email = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;

        if (!email || !password) {
            this.showToast('Please fill in all fields', 'error');
            return;
        }

        try {
            const result = await api.login(email, password);
            this.saveUserSession(result.user);
            this.hideAuthModal();
            this.showToast(`Welcome back, ${result.user.name}!`, 'success');
            await this.loadStats();
        } catch (error) {
            // Demo login
            if (email === 'teacher@hcos.demo' || email === 'student@hcos.demo') {
                const demoUser = {
                    id: email.includes('teacher') ? 1 : 2,
                    email,
                    name: email.includes('teacher') ? 'Sarah Johnson' : 'Michael Chen',
                    role: email.includes('teacher') ? 'teacher' : 'student'
                };
                this.saveUserSession(demoUser);
                this.hideAuthModal();
                this.showToast(`Welcome, ${demoUser.name}! (Demo mode)`, 'success');
            } else {
                this.showToast('Invalid credentials. Try the demo account!', 'error');
            }
        }
    },

    /**
     * Handle registration
     */
    async handleRegister() {
        const name = document.getElementById('register-name').value.trim();
        const email = document.getElementById('register-email').value.trim();
        const password = document.getElementById('register-password').value;
        const role = document.getElementById('register-role').value;

        if (!name || !email || !password) {
            this.showToast('Please fill in all fields', 'error');
            return;
        }

        if (password.length < 8) {
            this.showToast('Password must be at least 8 characters', 'error');
            return;
        }

        try {
            const result = await api.register(name, email, password, role);
            this.saveUserSession(result.user);
            this.hideAuthModal();
            this.showToast(`Welcome to HCOS LessonForge, ${result.user.name}!`, 'success');
        } catch (error) {
            // Demo mode
            const demoUser = {
                id: Date.now(),
                email,
                name,
                role
            };
            this.saveUserSession(demoUser);
            this.hideAuthModal();
            this.showToast(`Account created! (Demo mode)`, 'success');
        }
    },

    // ============================================
    // Utilities
    // ============================================

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        const icons = {
            success: '<polyline points="20 6 9 17 4 12"/>',
            error: '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            warning: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            info: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'
        };

        toast.innerHTML = `
            <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${icons[type] || icons.info}
            </svg>
            <span class="toast-message">${this.escapeHtml(message)}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastIn 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, CONFIG.TOAST_DURATION);
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// ============================================
// Initialize on DOM Ready
// ============================================
document.addEventListener('DOMContentLoaded', () => app.init());

// Export for global access
window.app = app;
