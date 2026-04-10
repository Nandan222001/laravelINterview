/**
 * Theme Manager - Customizable UI Themes
 * 
 * Features:
 * - Dark/Light mode toggle with smooth transitions
 * - Code syntax theme switcher (multiple Prism.js themes)
 * - Font size controls (small/medium/large/extra-large)
 * - Reading mode (removes distractions, increases line spacing, centers content)
 * - Keyboard shortcut (Ctrl+Shift+D) for quick theme toggle
 * - LocalStorage persistence
 */

class ThemeManager {
    constructor() {
        this.preferences = {
            theme: 'light', // 'light' or 'dark'
            syntaxTheme: 'default', // 'default', 'tomorrow-night', 'okaidia', 'solarized-light', 'dracula'
            fontSize: 'medium', // 'small', 'medium', 'large', 'extra-large'
            readingMode: false
        };

        // Available syntax themes with their CDN URLs
        this.syntaxThemes = {
            'default': null, // Uses built-in styles
            'tomorrow-night': 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css',
            'okaidia': 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-okaidia.min.css',
            'solarized-light': 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-solarizedlight.min.css',
            'dracula': 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-dracula.min.css',
            'twilight': 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-twilight.min.css',
            'atom-dark': 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-atom-dark.min.css'
        };

        // Font size mappings
        this.fontSizes = {
            'small': '14px',
            'medium': '16px',
            'large': '18px',
            'extra-large': '20px'
        };

        this.init();
    }

    /**
     * Initialize the theme manager
     */
    init() {
        this.loadPreferences();
        this.applyTheme();
        this.applySyntaxTheme();
        this.applyFontSize();
        this.applyReadingMode();
        this.setupEventListeners();
        this.addTransitionStyles();
        this.createThemeControls();
    }

    /**
     * Load preferences from localStorage
     */
    loadPreferences() {
        const stored = localStorage.getItem('themePreferences');
        if (stored) {
            try {
                const parsed = JSON.parse(stored);
                this.preferences = { ...this.preferences, ...parsed };
            } catch (e) {
                console.error('Error loading theme preferences:', e);
            }
        }
    }

    /**
     * Save preferences to localStorage
     */
    savePreferences() {
        try {
            localStorage.setItem('themePreferences', JSON.stringify(this.preferences));
        } catch (e) {
            console.error('Error saving theme preferences:', e);
        }
    }

    /**
     * Add smooth CSS transitions for theme changes
     */
    addTransitionStyles() {
        const styleId = 'theme-transition-styles';
        
        // Don't add if already exists
        if (document.getElementById(styleId)) return;

        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            /* Smooth theme transitions */
            * {
                transition: background-color 0.3s ease, 
                           color 0.3s ease, 
                           border-color 0.3s ease,
                           box-shadow 0.3s ease !important;
            }

            /* Preserve specific transitions */
            a, button, .btn, 
            .accordion-header, 
            .accordion-toggle-icon,
            .theme-toggle,
            .question-action-btn,
            .code-copy-btn {
                transition: all 0.3s ease !important;
            }

            /* Reading mode styles */
            body.reading-mode {
                max-width: 900px;
                margin: 0 auto;
                padding: 2rem;
            }

            body.reading-mode .app-sidebar,
            body.reading-mode .app-header,
            body.reading-mode .app-footer,
            body.reading-mode .question-actions,
            body.reading-mode .stats-bar,
            body.reading-mode .search-container {
                display: none !important;
            }

            body.reading-mode .app-main {
                display: block;
                max-width: 100%;
            }

            body.reading-mode .app-content {
                max-width: 800px;
                margin: 0 auto;
                padding: 3rem 2rem;
                line-height: 1.9;
            }

            body.reading-mode p,
            body.reading-mode li {
                line-height: 2;
                margin-bottom: 1.5rem;
            }

            body.reading-mode .accordion-item {
                margin-bottom: 2rem;
            }

            body.reading-mode .question-item {
                padding: 2rem;
                margin-bottom: 2rem;
            }

            /* Font size classes */
            html.font-small {
                font-size: 14px;
            }

            html.font-medium {
                font-size: 16px;
            }

            html.font-large {
                font-size: 18px;
            }

            html.font-extra-large {
                font-size: 20px;
            }

            /* Theme controls panel */
            .theme-controls-panel {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                background: var(--bg-primary);
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 1.5rem;
                box-shadow: var(--shadow-xl);
                z-index: var(--z-popover);
                min-width: 300px;
                max-width: 350px;
                transform: translateY(0);
                opacity: 1;
                transition: opacity 0.3s ease, transform 0.3s ease;
            }

            .theme-controls-panel.hidden {
                opacity: 0;
                transform: translateY(20px);
                pointer-events: none;
            }

            .theme-controls-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 1.5rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid var(--border-color);
            }

            .theme-controls-title {
                font-size: 1.125rem;
                font-weight: 600;
                color: var(--text-primary);
                margin: 0;
            }

            .theme-controls-close {
                background: transparent;
                border: none;
                color: var(--text-secondary);
                cursor: pointer;
                padding: 0.25rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: background-color 0.2s ease;
            }

            .theme-controls-close:hover {
                background-color: var(--bg-hover);
                color: var(--text-primary);
            }

            .theme-control-group {
                margin-bottom: 1.25rem;
            }

            .theme-control-group:last-child {
                margin-bottom: 0;
            }

            .theme-control-label {
                display: block;
                font-size: 0.875rem;
                font-weight: 600;
                color: var(--text-secondary);
                margin-bottom: 0.5rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .theme-button-group {
                display: flex;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .theme-btn {
                flex: 1;
                min-width: 80px;
                padding: 0.5rem 1rem;
                border: 1px solid var(--border-color);
                background: var(--bg-secondary);
                color: var(--text-primary);
                border-radius: 6px;
                font-size: 0.875rem;
                cursor: pointer;
                transition: all 0.2s ease;
                font-weight: 500;
            }

            .theme-btn:hover {
                background: var(--bg-hover);
                border-color: var(--color-primary);
            }

            .theme-btn.active {
                background: var(--color-primary);
                color: white;
                border-color: var(--color-primary);
            }

            .theme-btn-icon {
                margin-right: 0.25rem;
            }

            .theme-select {
                width: 100%;
                padding: 0.5rem;
                border: 1px solid var(--border-color);
                background: var(--bg-secondary);
                color: var(--text-primary);
                border-radius: 6px;
                font-size: 0.875rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .theme-select:hover {
                border-color: var(--color-primary);
            }

            .theme-select:focus {
                outline: none;
                border-color: var(--color-primary);
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }

            .reading-mode-toggle {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem;
                background: var(--bg-secondary);
                border-radius: 6px;
                cursor: pointer;
                transition: background-color 0.2s ease;
            }

            .reading-mode-toggle:hover {
                background: var(--bg-hover);
            }

            .reading-mode-toggle input[type="checkbox"] {
                width: 18px;
                height: 18px;
                cursor: pointer;
                accent-color: var(--color-primary);
            }

            .reading-mode-label {
                flex: 1;
                font-size: 0.875rem;
                color: var(--text-primary);
                cursor: pointer;
            }

            .theme-toggle-fab {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: var(--color-primary);
                color: white;
                border: none;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                transition: all 0.3s ease;
                z-index: var(--z-fixed);
            }

            .theme-toggle-fab:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
            }

            .theme-toggle-fab.hidden {
                display: none;
            }

            .theme-keyboard-hint {
                font-size: 0.75rem;
                color: var(--text-tertiary);
                text-align: center;
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid var(--border-color);
            }

            .theme-keyboard-hint kbd {
                background: var(--bg-tertiary);
                padding: 0.125rem 0.375rem;
                border-radius: 3px;
                font-family: monospace;
                font-size: 0.75rem;
                border: 1px solid var(--border-color);
            }

            @media (max-width: 768px) {
                .theme-controls-panel {
                    right: 1rem;
                    left: 1rem;
                    bottom: 1rem;
                    max-width: none;
                }

                .theme-toggle-fab {
                    bottom: 1rem;
                    right: 1rem;
                }

                body.reading-mode .app-content {
                    padding: 2rem 1rem;
                }
            }
        `;
        
        document.head.appendChild(style);
    }

    /**
     * Apply the current theme
     */
    applyTheme() {
        const html = document.documentElement;
        
        if (this.preferences.theme === 'dark') {
            html.setAttribute('data-theme', 'dark');
        } else {
            html.removeAttribute('data-theme');
        }

        // Update theme toggle button if it exists
        this.updateThemeToggleButton();
    }

    /**
     * Toggle between light and dark themes
     */
    toggleTheme() {
        this.preferences.theme = this.preferences.theme === 'light' ? 'dark' : 'light';
        this.applyTheme();
        this.savePreferences();
        this.dispatchThemeChangeEvent();
    }

    /**
     * Apply the syntax highlighting theme
     */
    applySyntaxTheme() {
        const themeId = 'prism-theme-custom';
        let linkElement = document.getElementById(themeId);

        // Remove existing theme link if present
        if (linkElement) {
            linkElement.remove();
        }

        // Apply new theme if not default
        const themeUrl = this.syntaxThemes[this.preferences.syntaxTheme];
        if (themeUrl) {
            linkElement = document.createElement('link');
            linkElement.id = themeId;
            linkElement.rel = 'stylesheet';
            linkElement.href = themeUrl;
            document.head.appendChild(linkElement);
        }
    }

    /**
     * Set syntax theme
     */
    setSyntaxTheme(themeName) {
        if (this.syntaxThemes.hasOwnProperty(themeName)) {
            this.preferences.syntaxTheme = themeName;
            this.applySyntaxTheme();
            this.savePreferences();
            
            // Re-highlight code blocks if Prism is available
            if (window.Prism && window.Prism.highlightAll) {
                setTimeout(() => window.Prism.highlightAll(), 100);
            }
        }
    }

    /**
     * Apply font size
     */
    applyFontSize() {
        const html = document.documentElement;
        
        // Remove all font size classes
        html.classList.remove('font-small', 'font-medium', 'font-large', 'font-extra-large');
        
        // Add current font size class
        html.classList.add(`font-${this.preferences.fontSize}`);
    }

    /**
     * Set font size
     */
    setFontSize(size) {
        if (this.fontSizes.hasOwnProperty(size)) {
            this.preferences.fontSize = size;
            this.applyFontSize();
            this.savePreferences();
        }
    }

    /**
     * Apply reading mode
     */
    applyReadingMode() {
        const body = document.body;
        
        if (this.preferences.readingMode) {
            body.classList.add('reading-mode');
        } else {
            body.classList.remove('reading-mode');
        }
    }

    /**
     * Toggle reading mode
     */
    toggleReadingMode() {
        this.preferences.readingMode = !this.preferences.readingMode;
        this.applyReadingMode();
        this.savePreferences();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Keyboard shortcut: Ctrl+Shift+D for theme toggle
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggleTheme();
                this.showNotification('Theme toggled!');
            }
        });

        // Listen for system theme changes
        if (window.matchMedia) {
            const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
            darkModeQuery.addEventListener('change', (e) => {
                // Only auto-switch if user hasn't set a preference
                const hasUserPreference = localStorage.getItem('themePreferences');
                if (!hasUserPreference) {
                    this.preferences.theme = e.matches ? 'dark' : 'light';
                    this.applyTheme();
                }
            });
        }
    }

    /**
     * Create theme controls UI
     */
    createThemeControls() {
        // Create floating action button
        const fab = document.createElement('button');
        fab.className = 'theme-toggle-fab';
        fab.innerHTML = '🎨';
        fab.setAttribute('aria-label', 'Open theme settings');
        fab.title = 'Theme Settings (Ctrl+Shift+D to toggle dark mode)';
        
        // Create theme controls panel
        const panel = document.createElement('div');
        panel.className = 'theme-controls-panel hidden';
        panel.innerHTML = `
            <div class="theme-controls-header">
                <h3 class="theme-controls-title">Theme Settings</h3>
                <button class="theme-controls-close" aria-label="Close theme settings">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                    </svg>
                </button>
            </div>

            <div class="theme-control-group">
                <label class="theme-control-label">Theme Mode</label>
                <div class="theme-button-group">
                    <button class="theme-btn theme-mode-btn" data-theme="light">
                        <span class="theme-btn-icon">☀️</span> Light
                    </button>
                    <button class="theme-btn theme-mode-btn" data-theme="dark">
                        <span class="theme-btn-icon">🌙</span> Dark
                    </button>
                </div>
            </div>

            <div class="theme-control-group">
                <label class="theme-control-label">Code Theme</label>
                <select class="theme-select syntax-theme-select">
                    <option value="default">Default</option>
                    <option value="tomorrow-night">Tomorrow Night</option>
                    <option value="okaidia">Okaidia</option>
                    <option value="solarized-light">Solarized Light</option>
                    <option value="dracula">Dracula</option>
                    <option value="twilight">Twilight</option>
                    <option value="atom-dark">Atom Dark</option>
                </select>
            </div>

            <div class="theme-control-group">
                <label class="theme-control-label">Font Size</label>
                <div class="theme-button-group">
                    <button class="theme-btn font-size-btn" data-size="small">S</button>
                    <button class="theme-btn font-size-btn" data-size="medium">M</button>
                    <button class="theme-btn font-size-btn" data-size="large">L</button>
                    <button class="theme-btn font-size-btn" data-size="extra-large">XL</button>
                </div>
            </div>

            <div class="theme-control-group">
                <label class="theme-control-label">Reading Mode</label>
                <div class="reading-mode-toggle">
                    <input type="checkbox" id="reading-mode-checkbox" class="reading-mode-checkbox">
                    <label for="reading-mode-checkbox" class="reading-mode-label">
                        Focus on content, hide distractions
                    </label>
                </div>
            </div>

            <div class="theme-keyboard-hint">
                Press <kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>D</kbd> to toggle theme
            </div>
        `;

        document.body.appendChild(fab);
        document.body.appendChild(panel);

        // Setup FAB click handler
        fab.addEventListener('click', () => {
            panel.classList.toggle('hidden');
            fab.classList.toggle('hidden');
        });

        // Setup close button handler
        const closeBtn = panel.querySelector('.theme-controls-close');
        closeBtn.addEventListener('click', () => {
            panel.classList.add('hidden');
            fab.classList.remove('hidden');
        });

        // Theme mode buttons
        const themeBtns = panel.querySelectorAll('.theme-mode-btn');
        themeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const theme = btn.dataset.theme;
                this.preferences.theme = theme;
                this.applyTheme();
                this.savePreferences();
                this.updateThemeButtons();
            });
        });

        // Syntax theme select
        const syntaxSelect = panel.querySelector('.syntax-theme-select');
        syntaxSelect.value = this.preferences.syntaxTheme;
        syntaxSelect.addEventListener('change', (e) => {
            this.setSyntaxTheme(e.target.value);
        });

        // Font size buttons
        const fontBtns = panel.querySelectorAll('.font-size-btn');
        fontBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const size = btn.dataset.size;
                this.setFontSize(size);
                this.updateFontSizeButtons();
            });
        });

        // Reading mode checkbox
        const readingCheckbox = panel.querySelector('.reading-mode-checkbox');
        readingCheckbox.checked = this.preferences.readingMode;
        readingCheckbox.addEventListener('change', () => {
            this.toggleReadingMode();
        });

        // Close panel when clicking outside
        document.addEventListener('click', (e) => {
            if (!panel.contains(e.target) && !fab.contains(e.target)) {
                if (!panel.classList.contains('hidden')) {
                    panel.classList.add('hidden');
                    fab.classList.remove('hidden');
                }
            }
        });

        // Update button states
        this.updateThemeButtons();
        this.updateFontSizeButtons();
    }

    /**
     * Update theme toggle button appearance
     */
    updateThemeToggleButton() {
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            const icon = this.preferences.theme === 'dark' ? '☀️' : '🌙';
            themeToggle.innerHTML = icon;
            themeToggle.setAttribute('aria-label', 
                `Switch to ${this.preferences.theme === 'dark' ? 'light' : 'dark'} mode`);
        }
    }

    /**
     * Update theme button states in control panel
     */
    updateThemeButtons() {
        const themeBtns = document.querySelectorAll('.theme-mode-btn');
        themeBtns.forEach(btn => {
            if (btn.dataset.theme === this.preferences.theme) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    /**
     * Update font size button states in control panel
     */
    updateFontSizeButtons() {
        const fontBtns = document.querySelectorAll('.font-size-btn');
        fontBtns.forEach(btn => {
            if (btn.dataset.size === this.preferences.fontSize) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    /**
     * Show a temporary notification
     */
    showNotification(message, duration = 2000) {
        // Remove existing notification if any
        const existing = document.querySelector('.theme-notification');
        if (existing) {
            existing.remove();
        }

        const notification = document.createElement('div');
        notification.className = 'theme-notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--color-primary);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            font-weight: 500;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }

    /**
     * Dispatch custom event when theme changes
     */
    dispatchThemeChangeEvent() {
        const event = new CustomEvent('themechange', {
            detail: {
                theme: this.preferences.theme,
                syntaxTheme: this.preferences.syntaxTheme,
                fontSize: this.preferences.fontSize,
                readingMode: this.preferences.readingMode
            }
        });
        document.dispatchEvent(event);
    }

    /**
     * Get current theme preferences
     */
    getPreferences() {
        return { ...this.preferences };
    }

    /**
     * Set theme preferences programmatically
     */
    setPreferences(prefs) {
        this.preferences = { ...this.preferences, ...prefs };
        
        if (prefs.theme) this.applyTheme();
        if (prefs.syntaxTheme) this.applySyntaxTheme();
        if (prefs.fontSize) this.applyFontSize();
        if (prefs.hasOwnProperty('readingMode')) this.applyReadingMode();
        
        this.savePreferences();
        this.updateThemeButtons();
        this.updateFontSizeButtons();
    }

    /**
     * Reset to default preferences
     */
    reset() {
        this.preferences = {
            theme: 'light',
            syntaxTheme: 'default',
            fontSize: 'medium',
            readingMode: false
        };
        
        this.applyTheme();
        this.applySyntaxTheme();
        this.applyFontSize();
        this.applyReadingMode();
        this.savePreferences();
        this.updateThemeButtons();
        this.updateFontSizeButtons();
        
        const readingCheckbox = document.querySelector('.reading-mode-checkbox');
        if (readingCheckbox) {
            readingCheckbox.checked = false;
        }
        
        const syntaxSelect = document.querySelector('.syntax-theme-select');
        if (syntaxSelect) {
            syntaxSelect.value = 'default';
        }
    }
}

// Initialize theme manager when DOM is ready
if (typeof window !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.themeManager = new ThemeManager();
        });
    } else {
        window.themeManager = new ThemeManager();
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
