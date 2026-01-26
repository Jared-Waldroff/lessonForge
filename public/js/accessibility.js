/**
 * LessonForge Accessibility Features
 * 
 * Enhanced accessibility and user preferences
 */

const Accessibility = {
    STORAGE_KEY: 'lessonforge_accessibility',

    // Default settings
    settings: {
        highContrast: false,
        largeText: false,
        reducedMotion: false,
        focusOutlines: true
    },

    /**
     * Initialize accessibility features
     */
    init() {
        this.loadSettings();
        this.applySettings();
        this.setupKeyboardNav();
        this.renderToolbar();
        console.log('♿ Accessibility features initialized');
    },

    /**
     * Load settings from localStorage
     */
    loadSettings() {
        const saved = localStorage.getItem(this.STORAGE_KEY);
        if (saved) {
            try {
                this.settings = { ...this.settings, ...JSON.parse(saved) };
            } catch (e) {
                console.warn('Could not load accessibility settings');
            }
        }

        // Check for system preferences
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.settings.reducedMotion = true;
        }
    },

    /**
     * Save settings to localStorage
     */
    saveSettings() {
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.settings));
    },

    /**
     * Apply current settings to DOM
     */
    applySettings() {
        const root = document.documentElement;

        // High contrast mode
        root.classList.toggle('high-contrast', this.settings.highContrast);

        // Large text mode
        root.classList.toggle('large-text', this.settings.largeText);

        // Reduced motion
        root.classList.toggle('reduced-motion', this.settings.reducedMotion);

        // Focus outlines
        root.classList.toggle('show-focus', this.settings.focusOutlines);
    },

    /**
     * Toggle a specific setting
     */
    toggle(setting) {
        if (this.settings.hasOwnProperty(setting)) {
            this.settings[setting] = !this.settings[setting];
            this.saveSettings();
            this.applySettings();
            this.updateToolbar();

            if (typeof app !== 'undefined') {
                app.showToast(`${this.getSettingLabel(setting)} ${this.settings[setting] ? 'enabled' : 'disabled'}`, 'success');
            }
        }
    },

    /**
     * Get human-readable label for setting
     */
    getSettingLabel(setting) {
        const labels = {
            highContrast: 'High Contrast',
            largeText: 'Large Text',
            reducedMotion: 'Reduced Motion',
            focusOutlines: 'Focus Outlines'
        };
        return labels[setting] || setting;
    },

    /**
     * Setup keyboard navigation
     */
    setupKeyboardNav() {
        document.addEventListener('keydown', (e) => {
            // Skip to main content (Alt + M)
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                const main = document.querySelector('.main-content');
                if (main) {
                    main.focus();
                    main.scrollIntoView();
                }
            }

            // Toggle accessibility toolbar (Alt + A)
            if (e.altKey && e.key === 'a') {
                e.preventDefault();
                this.toggleToolbar();
            }

            // Escape key to close modals
            if (e.key === 'Escape') {
                const modal = document.querySelector('.modal.active');
                if (modal) {
                    modal.classList.remove('active');
                }
            }
        });

        // Tab trap for modals
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;

            const modal = document.querySelector('.modal.active');
            if (!modal) return;

            const focusable = modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );

            if (focusable.length === 0) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        });
    },

    /**
     * Render accessibility toolbar
     */
    renderToolbar() {
        const toolbar = document.createElement('div');
        toolbar.id = 'accessibility-toolbar';
        toolbar.className = 'a11y-toolbar';
        toolbar.innerHTML = `
            <button class="a11y-toggle" onclick="Accessibility.toggleToolbar()" 
                    title="Accessibility Settings (Alt+A)" aria-label="Accessibility Settings">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <circle cx="12" cy="8" r="2"/>
                    <path d="M12 14v4"/>
                    <path d="M9 12h6"/>
                </svg>
            </button>
            <div class="a11y-panel" id="a11y-panel">
                <h4>Accessibility</h4>
                <div class="a11y-options">
                    <label class="a11y-option">
                        <input type="checkbox" ${this.settings.highContrast ? 'checked' : ''} 
                               onchange="Accessibility.toggle('highContrast')">
                        <span>High Contrast</span>
                    </label>
                    <label class="a11y-option">
                        <input type="checkbox" ${this.settings.largeText ? 'checked' : ''} 
                               onchange="Accessibility.toggle('largeText')">
                        <span>Larger Text</span>
                    </label>
                    <label class="a11y-option">
                        <input type="checkbox" ${this.settings.reducedMotion ? 'checked' : ''} 
                               onchange="Accessibility.toggle('reducedMotion')">
                        <span>Reduce Motion</span>
                    </label>
                    <label class="a11y-option">
                        <input type="checkbox" ${this.settings.focusOutlines ? 'checked' : ''} 
                               onchange="Accessibility.toggle('focusOutlines')">
                        <span>Focus Outlines</span>
                    </label>
                </div>
                <div class="a11y-shortcuts">
                    <h5>Keyboard Shortcuts</h5>
                    <ul>
                        <li><kbd>Alt</kbd> + <kbd>M</kbd> Skip to content</li>
                        <li><kbd>Alt</kbd> + <kbd>A</kbd> This menu</li>
                        <li><kbd>Esc</kbd> Close dialogs</li>
                    </ul>
                </div>
            </div>
        `;
        document.body.appendChild(toolbar);
    },

    /**
     * Toggle toolbar visibility
     */
    toggleToolbar() {
        const panel = document.getElementById('a11y-panel');
        if (panel) {
            panel.classList.toggle('open');
        }
    },

    /**
     * Update toolbar checkboxes to match current settings
     */
    updateToolbar() {
        const panel = document.getElementById('a11y-panel');
        if (!panel) return;

        Object.keys(this.settings).forEach(setting => {
            const checkbox = panel.querySelector(`input[onchange*="${setting}"]`);
            if (checkbox) {
                checkbox.checked = this.settings[setting];
            }
        });
    }
};

// Export for global access
window.Accessibility = Accessibility;
