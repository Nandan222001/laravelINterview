// ============================================================================
// BOOKMARK SYSTEM MODULE
// Client-side bookmarking with localStorage persistence
// Features: add/remove bookmarks, search, personal notes, tag organization
// ============================================================================

const BookmarkSystem = (() => {
    // ========================================================================
    // CONFIGURATION
    // ========================================================================
    
    const config = {
        storageKey: 'interview_bookmarks',
        tagsKey: 'bookmark_tags',
        animationDuration: 300,
        maxNotesLength: 5000,
        autoSave: true,
        autoSaveDelay: 500
    };
    
    // ========================================================================
    // STATE MANAGEMENT
    // ========================================================================
    
    let state = {
        bookmarks: new Map(),
        tags: [],
        isOpen: false,
        searchQuery: '',
        filterTag: '',
        autoSaveTimer: null
    };
    
    // ========================================================================
    // CORE BOOKMARK FUNCTIONS
    // ========================================================================
    
    /**
     * Add a bookmark for a question
     * @param {string} questionId - Unique identifier for the question
     * @param {string} notes - Optional personal notes
     * @returns {Object} The created bookmark object
     */
    function addBookmark(questionId, notes = '') {
        if (!questionId) {
            console.error('Question ID is required to add a bookmark');
            return null;
        }
        
        const questionElement = document.querySelector(`[data-question-id="${questionId}"]`) || 
                               document.querySelector(`article[data-id="${questionId}"]`) ||
                               document.getElementById(questionId);
        
        if (!questionElement) {
            console.warn(`Question element not found for ID: ${questionId}`);
        }
        
        const questionText = questionElement ? 
            (questionElement.querySelector('.question')?.textContent || 
             questionElement.querySelector('.question-title-text')?.textContent ||
             questionElement.querySelector('h2, h3')?.textContent ||
             'Unknown Question') : 
            `Question ${questionId}`;
        
        const bookmark = {
            id: questionId,
            questionText: questionText.trim(),
            notes: notes.substring(0, config.maxNotesLength),
            tags: [],
            dateAdded: new Date().toISOString(),
            lastModified: new Date().toISOString(),
            difficulty: questionElement?.dataset?.difficulty || '',
            category: questionElement?.dataset?.category || ''
        };
        
        state.bookmarks.set(questionId, bookmark);
        saveBookmarks();
        updateBookmarkIcon(questionId, true);
        
        // Dispatch event
        document.dispatchEvent(new CustomEvent('bookmark:added', {
            detail: { bookmark }
        }));
        
        return bookmark;
    }
    
    /**
     * Remove a bookmark
     * @param {string} questionId - ID of the question to unbookmark
     * @returns {boolean} Success status
     */
    function removeBookmark(questionId) {
        if (!questionId) {
            console.error('Question ID is required to remove a bookmark');
            return false;
        }
        
        const bookmark = state.bookmarks.get(questionId);
        const success = state.bookmarks.delete(questionId);
        
        if (success) {
            saveBookmarks();
            updateBookmarkIcon(questionId, false);
            
            // Dispatch event
            document.dispatchEvent(new CustomEvent('bookmark:removed', {
                detail: { questionId, bookmark }
            }));
        }
        
        return success;
    }
    
    /**
     * Get all bookmarks
     * @returns {Array} Array of bookmark objects
     */
    function getBookmarks() {
        return Array.from(state.bookmarks.values());
    }
    
    /**
     * Search bookmarks by query
     * @param {string} query - Search query
     * @returns {Array} Filtered bookmarks
     */
    function searchBookmarks(query) {
        if (!query || query.trim() === '') {
            return getBookmarks();
        }
        
        const lowerQuery = query.toLowerCase().trim();
        
        return getBookmarks().filter(bookmark => {
            const searchableText = [
                bookmark.questionText,
                bookmark.notes,
                bookmark.difficulty,
                bookmark.category,
                ...(bookmark.tags || [])
            ].join(' ').toLowerCase();
            
            return searchableText.includes(lowerQuery);
        });
    }
    
    /**
     * Update bookmark notes
     * @param {string} questionId - Question ID
     * @param {string} notes - New notes content
     */
    function updateBookmarkNotes(questionId, notes) {
        const bookmark = state.bookmarks.get(questionId);
        if (bookmark) {
            bookmark.notes = notes.substring(0, config.maxNotesLength);
            bookmark.lastModified = new Date().toISOString();
            
            if (config.autoSave) {
                clearTimeout(state.autoSaveTimer);
                state.autoSaveTimer = setTimeout(() => {
                    saveBookmarks();
                }, config.autoSaveDelay);
            } else {
                saveBookmarks();
            }
            
            // Dispatch event
            document.dispatchEvent(new CustomEvent('bookmark:updated', {
                detail: { bookmark }
            }));
        }
    }
    
    /**
     * Add tags to a bookmark
     * @param {string} questionId - Question ID
     * @param {Array|string} tags - Tag(s) to add
     */
    function addBookmarkTags(questionId, tags) {
        const bookmark = state.bookmarks.get(questionId);
        if (!bookmark) return;
        
        const tagsArray = Array.isArray(tags) ? tags : [tags];
        bookmark.tags = bookmark.tags || [];
        
        tagsArray.forEach(tag => {
            const normalizedTag = tag.trim().toLowerCase();
            if (normalizedTag && !bookmark.tags.includes(normalizedTag)) {
                bookmark.tags.push(normalizedTag);
                
                // Add to global tags list
                if (!state.tags.includes(normalizedTag)) {
                    state.tags.push(normalizedTag);
                    saveTags();
                }
            }
        });
        
        bookmark.lastModified = new Date().toISOString();
        saveBookmarks();
        
        // Dispatch event
        document.dispatchEvent(new CustomEvent('bookmark:tagsUpdated', {
            detail: { bookmark }
        }));
    }
    
    /**
     * Remove tags from a bookmark
     * @param {string} questionId - Question ID
     * @param {Array|string} tags - Tag(s) to remove
     */
    function removeBookmarkTags(questionId, tags) {
        const bookmark = state.bookmarks.get(questionId);
        if (!bookmark) return;
        
        const tagsArray = Array.isArray(tags) ? tags : [tags];
        bookmark.tags = bookmark.tags || [];
        
        tagsArray.forEach(tag => {
            const normalizedTag = tag.trim().toLowerCase();
            bookmark.tags = bookmark.tags.filter(t => t !== normalizedTag);
        });
        
        bookmark.lastModified = new Date().toISOString();
        saveBookmarks();
        
        // Dispatch event
        document.dispatchEvent(new CustomEvent('bookmark:tagsUpdated', {
            detail: { bookmark }
        }));
    }
    
    /**
     * Filter bookmarks by tag
     * @param {string} tag - Tag to filter by
     * @returns {Array} Filtered bookmarks
     */
    function filterBookmarksByTag(tag) {
        if (!tag) return getBookmarks();
        
        const normalizedTag = tag.toLowerCase();
        return getBookmarks().filter(bookmark => 
            bookmark.tags && bookmark.tags.includes(normalizedTag)
        );
    }
    
    /**
     * Check if a question is bookmarked
     * @param {string} questionId - Question ID
     * @returns {boolean}
     */
    function isBookmarked(questionId) {
        return state.bookmarks.has(questionId);
    }
    
    // ========================================================================
    // STORAGE FUNCTIONS
    // ========================================================================
    
    /**
     * Save bookmarks to localStorage
     */
    function saveBookmarks() {
        try {
            const bookmarksArray = Array.from(state.bookmarks.entries());
            localStorage.setItem(config.storageKey, JSON.stringify(bookmarksArray));
            
            // Dispatch event
            document.dispatchEvent(new CustomEvent('bookmarks:saved', {
                detail: { count: bookmarksArray.length }
            }));
        } catch (error) {
            console.error('Failed to save bookmarks:', error);
            showNotification('Failed to save bookmarks', 'error');
        }
    }
    
    /**
     * Load bookmarks from localStorage
     */
    function loadBookmarks() {
        try {
            const saved = localStorage.getItem(config.storageKey);
            if (saved) {
                const bookmarksArray = JSON.parse(saved);
                state.bookmarks = new Map(bookmarksArray);
                
                // Dispatch event
                document.dispatchEvent(new CustomEvent('bookmarks:loaded', {
                    detail: { count: state.bookmarks.size }
                }));
            }
        } catch (error) {
            console.error('Failed to load bookmarks:', error);
            state.bookmarks = new Map();
        }
    }
    
    /**
     * Save tags to localStorage
     */
    function saveTags() {
        try {
            localStorage.setItem(config.tagsKey, JSON.stringify(state.tags));
        } catch (error) {
            console.error('Failed to save tags:', error);
        }
    }
    
    /**
     * Load tags from localStorage
     */
    function loadTags() {
        try {
            const saved = localStorage.getItem(config.tagsKey);
            if (saved) {
                state.tags = JSON.parse(saved);
            }
        } catch (error) {
            console.error('Failed to load tags:', error);
            state.tags = [];
        }
    }
    
    /**
     * Export bookmarks as JSON
     * @returns {string} JSON string of bookmarks
     */
    function exportBookmarks() {
        const data = {
            bookmarks: Array.from(state.bookmarks.values()),
            tags: state.tags,
            exportDate: new Date().toISOString(),
            version: '1.0'
        };
        return JSON.stringify(data, null, 2);
    }
    
    /**
     * Import bookmarks from JSON
     * @param {string} jsonString - JSON data to import
     * @returns {Object} Import result
     */
    function importBookmarks(jsonString) {
        try {
            const data = JSON.parse(jsonString);
            
            if (!data.bookmarks || !Array.isArray(data.bookmarks)) {
                throw new Error('Invalid bookmark data format');
            }
            
            let imported = 0;
            let skipped = 0;
            
            data.bookmarks.forEach(bookmark => {
                if (bookmark.id && bookmark.questionText) {
                    state.bookmarks.set(bookmark.id, bookmark);
                    imported++;
                } else {
                    skipped++;
                }
            });
            
            if (data.tags && Array.isArray(data.tags)) {
                data.tags.forEach(tag => {
                    if (!state.tags.includes(tag)) {
                        state.tags.push(tag);
                    }
                });
            }
            
            saveBookmarks();
            saveTags();
            refreshBookmarkIcons();
            
            return { imported, skipped, total: data.bookmarks.length };
        } catch (error) {
            console.error('Failed to import bookmarks:', error);
            throw error;
        }
    }
    
    /**
     * Clear all bookmarks
     * @param {boolean} confirm - Require confirmation
     */
    function clearAllBookmarks(confirm = true) {
        if (confirm && !window.confirm('Are you sure you want to clear all bookmarks? This cannot be undone.')) {
            return;
        }
        
        state.bookmarks.clear();
        saveBookmarks();
        refreshBookmarkIcons();
        
        if (state.isOpen) {
            renderBookmarksList();
        }
        
        showNotification('All bookmarks cleared', 'success');
    }
    
    // ========================================================================
    // UI FUNCTIONS
    // ========================================================================
    
    /**
     * Initialize the bookmark system
     */
    function init() {
        loadBookmarks();
        loadTags();
        
        // Create floating button
        createFloatingButton();
        
        // Create bookmarks panel
        createBookmarksPanel();
        
        // Add bookmark icons to questions
        addBookmarkIconsToQuestions();
        
        // Set up event listeners
        setupEventListeners();
        
        console.log(`Bookmark system initialized with ${state.bookmarks.size} bookmarks`);
        
        // Dispatch ready event
        document.dispatchEvent(new CustomEvent('bookmarks:ready'));
    }
    
    /**
     * Create floating bookmark button
     */
    function createFloatingButton() {
        // Remove existing button if any
        const existing = document.getElementById('bookmark-floating-btn');
        if (existing) existing.remove();
        
        const button = document.createElement('button');
        button.id = 'bookmark-floating-btn';
        button.className = 'bookmark-floating-btn';
        button.setAttribute('aria-label', 'Open bookmarks panel');
        button.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
            </svg>
            <span class="bookmark-count">${state.bookmarks.size}</span>
        `;
        
        button.addEventListener('click', toggleBookmarksPanel);
        
        document.body.appendChild(button);
        
        // Add styles
        addFloatingButtonStyles();
    }
    
    /**
     * Add styles for floating button
     */
    function addFloatingButtonStyles() {
        if (document.getElementById('bookmark-floating-btn-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'bookmark-floating-btn-styles';
        style.textContent = `
            .bookmark-floating-btn {
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                z-index: 1000;
                position: relative;
            }
            
            .bookmark-floating-btn:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 30px rgba(102, 126, 234, 0.6);
            }
            
            .bookmark-floating-btn:active {
                transform: scale(0.95);
            }
            
            .bookmark-floating-btn svg {
                width: 28px;
                height: 28px;
            }
            
            .bookmark-count {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #e53e3e;
                color: white;
                border-radius: 50%;
                min-width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
                border: 2px solid white;
            }
            
            .bookmark-count:empty {
                display: none;
            }
            
            @media (max-width: 768px) {
                .bookmark-floating-btn {
                    bottom: 20px;
                    right: 20px;
                    width: 56px;
                    height: 56px;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    /**
     * Create bookmarks panel
     */
    function createBookmarksPanel() {
        // Remove existing panel if any
        const existing = document.getElementById('bookmarks-panel');
        if (existing) existing.remove();
        
        const panel = document.createElement('div');
        panel.id = 'bookmarks-panel';
        panel.className = 'bookmarks-panel';
        panel.innerHTML = `
            <div class="bookmarks-panel-overlay"></div>
            <div class="bookmarks-panel-content">
                <div class="bookmarks-panel-header">
                    <h2>My Bookmarks</h2>
                    <button class="bookmarks-panel-close" aria-label="Close bookmarks panel">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                
                <div class="bookmarks-panel-toolbar">
                    <div class="bookmarks-search-box">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="bookmarks-search" placeholder="Search bookmarks..." />
                    </div>
                    
                    <div class="bookmarks-filters">
                        <select id="bookmarks-tag-filter">
                            <option value="">All Tags</option>
                        </select>
                        <select id="bookmarks-sort">
                            <option value="recent">Recently Added</option>
                            <option value="oldest">Oldest First</option>
                            <option value="alpha">Alphabetical</option>
                        </select>
                    </div>
                    
                    <div class="bookmarks-actions">
                        <button id="bookmark-export-btn" class="btn-icon" title="Export bookmarks">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </button>
                        <button id="bookmark-import-btn" class="btn-icon" title="Import bookmarks">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </button>
                        <button id="bookmark-clear-btn" class="btn-icon" title="Clear all bookmarks">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="bookmarks-stats">
                    <span class="bookmarks-count-display">
                        <strong id="bookmarks-shown-count">0</strong> of <strong id="bookmarks-total-count">0</strong> bookmarks
                    </span>
                </div>
                
                <div class="bookmarks-list-container" id="bookmarks-list-container">
                    <!-- Bookmarks will be rendered here -->
                </div>
            </div>
        `;
        
        document.body.appendChild(panel);
        
        // Add styles
        addBookmarksPanelStyles();
        
        // Setup panel event listeners
        setupPanelEventListeners(panel);
    }
    
    /**
     * Add styles for bookmarks panel
     */
    function addBookmarksPanelStyles() {
        if (document.getElementById('bookmarks-panel-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'bookmarks-panel-styles';
        style.textContent = `
            .bookmarks-panel {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 9999;
                display: none;
            }
            
            .bookmarks-panel.active {
                display: block;
            }
            
            .bookmarks-panel-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
                animation: fadeIn 0.3s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .bookmarks-panel-content {
                position: absolute;
                top: 0;
                right: 0;
                bottom: 0;
                width: 90%;
                max-width: 600px;
                background: white;
                box-shadow: -4px 0 20px rgba(0, 0, 0, 0.3);
                display: flex;
                flex-direction: column;
                animation: slideIn 0.3s ease;
            }
            
            @keyframes slideIn {
                from { transform: translateX(100%); }
                to { transform: translateX(0); }
            }
            
            .bookmarks-panel-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 20px;
                border-bottom: 2px solid #e9ecef;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .bookmarks-panel-header h2 {
                margin: 0;
                font-size: 1.5rem;
                font-weight: 700;
            }
            
            .bookmarks-panel-close {
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s ease;
            }
            
            .bookmarks-panel-close:hover {
                background: rgba(255, 255, 255, 0.3);
            }
            
            .bookmarks-panel-toolbar {
                padding: 15px 20px;
                border-bottom: 1px solid #e9ecef;
                display: flex;
                flex-direction: column;
                gap: 12px;
                background: #f8f9fa;
            }
            
            .bookmarks-search-box {
                position: relative;
                flex: 1;
            }
            
            .bookmarks-search-box .search-icon {
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #6c757d;
                pointer-events: none;
            }
            
            .bookmarks-search-box input {
                width: 100%;
                padding: 10px 12px 10px 40px;
                border: 2px solid #dee2e6;
                border-radius: 8px;
                font-size: 0.95rem;
                transition: border-color 0.2s ease;
            }
            
            .bookmarks-search-box input:focus {
                outline: none;
                border-color: #667eea;
            }
            
            .bookmarks-filters {
                display: flex;
                gap: 10px;
            }
            
            .bookmarks-filters select {
                flex: 1;
                padding: 8px 12px;
                border: 2px solid #dee2e6;
                border-radius: 8px;
                font-size: 0.9rem;
                background: white;
                cursor: pointer;
                transition: border-color 0.2s ease;
            }
            
            .bookmarks-filters select:focus {
                outline: none;
                border-color: #667eea;
            }
            
            .bookmarks-actions {
                display: flex;
                gap: 8px;
                justify-content: flex-end;
            }
            
            .btn-icon {
                padding: 8px;
                background: white;
                border: 2px solid #dee2e6;
                border-radius: 8px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                color: #495057;
            }
            
            .btn-icon:hover {
                background: #667eea;
                border-color: #667eea;
                color: white;
                transform: translateY(-2px);
            }
            
            .bookmarks-stats {
                padding: 12px 20px;
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
                font-size: 0.9rem;
                color: #6c757d;
            }
            
            .bookmarks-list-container {
                flex: 1;
                overflow-y: auto;
                padding: 15px;
            }
            
            .bookmark-item {
                background: white;
                border: 2px solid #e9ecef;
                border-radius: 12px;
                padding: 15px;
                margin-bottom: 12px;
                transition: all 0.2s ease;
            }
            
            .bookmark-item:hover {
                border-color: #667eea;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
                transform: translateX(-4px);
            }
            
            .bookmark-item-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                margin-bottom: 10px;
            }
            
            .bookmark-item-title {
                flex: 1;
                font-size: 1rem;
                font-weight: 600;
                color: #2d3748;
                line-height: 1.4;
                margin-right: 10px;
            }
            
            .bookmark-item-actions {
                display: flex;
                gap: 6px;
            }
            
            .bookmark-action-btn {
                padding: 6px;
                background: transparent;
                border: 1px solid #dee2e6;
                border-radius: 6px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                color: #6c757d;
            }
            
            .bookmark-action-btn:hover {
                background: #667eea;
                border-color: #667eea;
                color: white;
            }
            
            .bookmark-action-btn.delete:hover {
                background: #e53e3e;
                border-color: #e53e3e;
            }
            
            .bookmark-action-btn svg {
                width: 16px;
                height: 16px;
            }
            
            .bookmark-item-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-bottom: 10px;
                font-size: 0.8rem;
            }
            
            .bookmark-meta-badge {
                padding: 3px 8px;
                border-radius: 12px;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.7rem;
            }
            
            .bookmark-meta-badge.difficulty-basic {
                background: #d1fae5;
                color: #065f46;
            }
            
            .bookmark-meta-badge.difficulty-intermediate {
                background: #fed7aa;
                color: #92400e;
            }
            
            .bookmark-meta-badge.difficulty-advanced {
                background: #fecaca;
                color: #991b1b;
            }
            
            .bookmark-meta-badge.difficulty-expert {
                background: #ddd6fe;
                color: #5b21b6;
            }
            
            .bookmark-meta-badge.date {
                background: #e0e7ff;
                color: #3730a3;
            }
            
            .bookmark-notes {
                margin-bottom: 10px;
            }
            
            .bookmark-notes-label {
                font-size: 0.85rem;
                font-weight: 600;
                color: #4a5568;
                margin-bottom: 6px;
                display: block;
            }
            
            .bookmark-notes-textarea {
                width: 100%;
                min-height: 60px;
                padding: 10px;
                border: 2px solid #e9ecef;
                border-radius: 8px;
                font-size: 0.9rem;
                font-family: inherit;
                resize: vertical;
                transition: border-color 0.2s ease;
            }
            
            .bookmark-notes-textarea:focus {
                outline: none;
                border-color: #667eea;
            }
            
            .bookmark-notes-display {
                padding: 10px;
                background: #f8f9fa;
                border-radius: 8px;
                font-size: 0.9rem;
                color: #4a5568;
                line-height: 1.5;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            
            .bookmark-notes-empty {
                color: #9ca3af;
                font-style: italic;
            }
            
            .bookmark-tags-section {
                margin-bottom: 10px;
            }
            
            .bookmark-tags-input-wrapper {
                display: flex;
                gap: 6px;
                margin-bottom: 8px;
            }
            
            .bookmark-tags-input {
                flex: 1;
                padding: 6px 10px;
                border: 2px solid #e9ecef;
                border-radius: 6px;
                font-size: 0.85rem;
            }
            
            .bookmark-tags-input:focus {
                outline: none;
                border-color: #667eea;
            }
            
            .bookmark-tags-add-btn {
                padding: 6px 12px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 0.85rem;
                cursor: pointer;
                transition: background 0.2s ease;
            }
            
            .bookmark-tags-add-btn:hover {
                background: #5568d3;
            }
            
            .bookmark-tags-list {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            
            .bookmark-tag {
                padding: 4px 10px;
                background: #e0e7ff;
                color: #4c51bf;
                border-radius: 12px;
                font-size: 0.8rem;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            
            .bookmark-tag-remove {
                background: none;
                border: none;
                color: #4c51bf;
                cursor: pointer;
                padding: 0;
                display: flex;
                align-items: center;
                font-size: 1.1rem;
                line-height: 1;
                opacity: 0.7;
                transition: opacity 0.2s ease;
            }
            
            .bookmark-tag-remove:hover {
                opacity: 1;
            }
            
            .bookmarks-empty {
                text-align: center;
                padding: 60px 20px;
                color: #6c757d;
            }
            
            .bookmarks-empty svg {
                width: 80px;
                height: 80px;
                margin-bottom: 20px;
                opacity: 0.3;
            }
            
            .bookmarks-empty h3 {
                font-size: 1.3rem;
                margin-bottom: 10px;
                color: #495057;
            }
            
            .bookmarks-empty p {
                font-size: 0.95rem;
            }
            
            .bookmark-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 32px;
                height: 32px;
                padding: 6px;
                background: transparent;
                border: 2px solid #dee2e6;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.3s ease;
                color: #6c757d;
                position: relative;
            }
            
            .bookmark-icon:hover {
                background: rgba(102, 126, 234, 0.1);
                border-color: #667eea;
                color: #667eea;
                transform: scale(1.1);
            }
            
            .bookmark-icon.bookmarked {
                background: #667eea;
                border-color: #667eea;
                color: white;
            }
            
            .bookmark-icon.bookmarked:hover {
                background: #5568d3;
                border-color: #5568d3;
            }
            
            .bookmark-icon.animating {
                animation: bookmarkPulse 0.5s ease;
            }
            
            @keyframes bookmarkPulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.3); }
            }
            
            .bookmark-icon svg {
                width: 18px;
                height: 18px;
            }
            
            .bookmark-icon.bookmarked svg {
                fill: currentColor;
            }
            
            @media (max-width: 768px) {
                .bookmarks-panel-content {
                    width: 100%;
                    max-width: 100%;
                }
                
                .bookmarks-panel-toolbar {
                    padding: 12px 15px;
                }
                
                .bookmarks-filters {
                    flex-direction: column;
                }
                
                .bookmark-item-header {
                    flex-direction: column;
                    gap: 10px;
                }
                
                .bookmark-item-title {
                    margin-right: 0;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    /**
     * Setup event listeners for the panel
     */
    function setupPanelEventListeners(panel) {
        // Close button
        panel.querySelector('.bookmarks-panel-close').addEventListener('click', closeBookmarksPanel);
        
        // Overlay click
        panel.querySelector('.bookmarks-panel-overlay').addEventListener('click', closeBookmarksPanel);
        
        // Search input
        const searchInput = panel.querySelector('#bookmarks-search');
        searchInput.addEventListener('input', (e) => {
            state.searchQuery = e.target.value;
            renderBookmarksList();
        });
        
        // Tag filter
        const tagFilter = panel.querySelector('#bookmarks-tag-filter');
        tagFilter.addEventListener('change', (e) => {
            state.filterTag = e.target.value;
            renderBookmarksList();
        });
        
        // Sort select
        const sortSelect = panel.querySelector('#bookmarks-sort');
        sortSelect.addEventListener('change', renderBookmarksList);
        
        // Export button
        panel.querySelector('#bookmark-export-btn').addEventListener('click', handleExport);
        
        // Import button
        panel.querySelector('#bookmark-import-btn').addEventListener('click', handleImport);
        
        // Clear button
        panel.querySelector('#bookmark-clear-btn').addEventListener('click', () => clearAllBookmarks(true));
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && state.isOpen) {
                closeBookmarksPanel();
            }
        });
    }
    
    /**
     * Toggle bookmarks panel
     */
    function toggleBookmarksPanel() {
        if (state.isOpen) {
            closeBookmarksPanel();
        } else {
            openBookmarksPanel();
        }
    }
    
    /**
     * Open bookmarks panel
     */
    function openBookmarksPanel() {
        const panel = document.getElementById('bookmarks-panel');
        if (!panel) return;
        
        state.isOpen = true;
        panel.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Update tags dropdown
        updateTagsDropdown();
        
        // Render bookmarks list
        renderBookmarksList();
        
        // Focus search input
        setTimeout(() => {
            panel.querySelector('#bookmarks-search').focus();
        }, 300);
        
        // Dispatch event
        document.dispatchEvent(new CustomEvent('bookmarks:panelOpened'));
    }
    
    /**
     * Close bookmarks panel
     */
    function closeBookmarksPanel() {
        const panel = document.getElementById('bookmarks-panel');
        if (!panel) return;
        
        state.isOpen = false;
        panel.classList.remove('active');
        document.body.style.overflow = '';
        
        // Dispatch event
        document.dispatchEvent(new CustomEvent('bookmarks:panelClosed'));
    }
    
    /**
     * Update tags dropdown
     */
    function updateTagsDropdown() {
        const select = document.getElementById('bookmarks-tag-filter');
        if (!select) return;
        
        // Collect all unique tags from bookmarks
        const allTags = new Set();
        getBookmarks().forEach(bookmark => {
            if (bookmark.tags) {
                bookmark.tags.forEach(tag => allTags.add(tag));
            }
        });
        
        const sortedTags = Array.from(allTags).sort();
        
        select.innerHTML = '<option value="">All Tags</option>' + 
            sortedTags.map(tag => `<option value="${tag}">${tag}</option>`).join('');
    }
    
    /**
     * Render bookmarks list
     */
    function renderBookmarksList() {
        const container = document.getElementById('bookmarks-list-container');
        if (!container) return;
        
        let bookmarks = getBookmarks();
        
        // Apply search filter
        if (state.searchQuery) {
            bookmarks = searchBookmarks(state.searchQuery);
        }
        
        // Apply tag filter
        if (state.filterTag) {
            bookmarks = bookmarks.filter(b => b.tags && b.tags.includes(state.filterTag));
        }
        
        // Apply sorting
        const sortMethod = document.getElementById('bookmarks-sort')?.value || 'recent';
        bookmarks = sortBookmarks(bookmarks, sortMethod);
        
        // Update counts
        document.getElementById('bookmarks-shown-count').textContent = bookmarks.length;
        document.getElementById('bookmarks-total-count').textContent = state.bookmarks.size;
        
        // Render
        if (bookmarks.length === 0) {
            container.innerHTML = `
                <div class="bookmarks-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <h3>${state.searchQuery || state.filterTag ? 'No bookmarks found' : 'No bookmarks yet'}</h3>
                    <p>${state.searchQuery || state.filterTag ? 'Try adjusting your filters' : 'Start bookmarking questions to see them here'}</p>
                </div>
            `;
        } else {
            container.innerHTML = bookmarks.map(bookmark => createBookmarkItemHTML(bookmark)).join('');
            
            // Setup event listeners for each bookmark item
            bookmarks.forEach(bookmark => {
                setupBookmarkItemListeners(bookmark.id);
            });
        }
    }
    
    /**
     * Sort bookmarks
     */
    function sortBookmarks(bookmarks, method) {
        const sorted = [...bookmarks];
        
        switch (method) {
            case 'recent':
                sorted.sort((a, b) => new Date(b.dateAdded) - new Date(a.dateAdded));
                break;
            case 'oldest':
                sorted.sort((a, b) => new Date(a.dateAdded) - new Date(b.dateAdded));
                break;
            case 'alpha':
                sorted.sort((a, b) => a.questionText.localeCompare(b.questionText));
                break;
        }
        
        return sorted;
    }
    
    /**
     * Create HTML for a bookmark item
     */
    function createBookmarkItemHTML(bookmark) {
        const dateAdded = new Date(bookmark.dateAdded).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        
        return `
            <div class="bookmark-item" data-bookmark-id="${bookmark.id}">
                <div class="bookmark-item-header">
                    <div class="bookmark-item-title">${escapeHTML(bookmark.questionText)}</div>
                    <div class="bookmark-item-actions">
                        <button class="bookmark-action-btn goto-btn" title="Go to question" data-question-id="${bookmark.id}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                        <button class="bookmark-action-btn delete" title="Remove bookmark" data-question-id="${bookmark.id}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="bookmark-item-meta">
                    ${bookmark.difficulty ? `<span class="bookmark-meta-badge difficulty-${bookmark.difficulty}">${bookmark.difficulty}</span>` : ''}
                    <span class="bookmark-meta-badge date">${dateAdded}</span>
                </div>
                
                <div class="bookmark-notes">
                    <label class="bookmark-notes-label">Personal Notes:</label>
                    <textarea 
                        class="bookmark-notes-textarea" 
                        placeholder="Add your notes here..."
                        data-question-id="${bookmark.id}"
                        maxlength="${config.maxNotesLength}"
                    >${escapeHTML(bookmark.notes || '')}</textarea>
                </div>
                
                <div class="bookmark-tags-section">
                    <div class="bookmark-tags-input-wrapper">
                        <input 
                            type="text" 
                            class="bookmark-tags-input" 
                            placeholder="Add tag..."
                            data-question-id="${bookmark.id}"
                        />
                        <button class="bookmark-tags-add-btn" data-question-id="${bookmark.id}">Add</button>
                    </div>
                    <div class="bookmark-tags-list" data-question-id="${bookmark.id}">
                        ${(bookmark.tags || []).map(tag => `
                            <span class="bookmark-tag">
                                ${escapeHTML(tag)}
                                <button class="bookmark-tag-remove" data-question-id="${bookmark.id}" data-tag="${escapeHTML(tag)}">&times;</button>
                            </span>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Setup event listeners for a bookmark item
     */
    function setupBookmarkItemListeners(questionId) {
        const item = document.querySelector(`.bookmark-item[data-bookmark-id="${questionId}"]`);
        if (!item) return;
        
        // Notes textarea
        const notesTextarea = item.querySelector('.bookmark-notes-textarea');
        if (notesTextarea) {
            notesTextarea.addEventListener('input', (e) => {
                updateBookmarkNotes(questionId, e.target.value);
            });
        }
        
        // Tags input
        const tagsInput = item.querySelector('.bookmark-tags-input');
        const tagsAddBtn = item.querySelector('.bookmark-tags-add-btn');
        
        const addTag = () => {
            const tag = tagsInput.value.trim();
            if (tag) {
                addBookmarkTags(questionId, tag);
                tagsInput.value = '';
                renderBookmarksList();
            }
        };
        
        if (tagsAddBtn) {
            tagsAddBtn.addEventListener('click', addTag);
        }
        
        if (tagsInput) {
            tagsInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addTag();
                }
            });
        }
        
        // Tag remove buttons
        item.querySelectorAll('.bookmark-tag-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tag = e.target.dataset.tag;
                removeBookmarkTags(questionId, tag);
                renderBookmarksList();
            });
        });
        
        // Delete button
        const deleteBtn = item.querySelector('.bookmark-action-btn.delete');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                removeBookmark(questionId);
                renderBookmarksList();
                updateBookmarkCount();
            });
        }
        
        // Goto button
        const gotoBtn = item.querySelector('.goto-btn');
        if (gotoBtn) {
            gotoBtn.addEventListener('click', () => {
                scrollToQuestion(questionId);
                closeBookmarksPanel();
            });
        }
    }
    
    /**
     * Add bookmark icons to all question cards
     */
    function addBookmarkIconsToQuestions() {
        // Find all question elements
        const questions = document.querySelectorAll('article, .question-item, .accordion-item');
        
        questions.forEach((question, index) => {
            // Get or generate question ID
            let questionId = question.dataset.questionId || 
                           question.dataset.id || 
                           question.id;
            
            if (!questionId) {
                questionId = `question-${index}`;
                question.dataset.questionId = questionId;
            }
            
            // Check if bookmark icon already exists
            if (question.querySelector('.bookmark-icon')) return;
            
            // Find the appropriate container for the icon
            let container = question.querySelector('.question-header .badges') ||
                          question.querySelector('.question-header') ||
                          question.querySelector('.question-actions') ||
                          question.querySelector('.accordion-header .accordion-meta');
            
            if (!container) {
                // Create a container if none exists
                const header = question.querySelector('.question-header, .accordion-header');
                if (header) {
                    container = document.createElement('div');
                    container.className = 'question-actions';
                    header.appendChild(container);
                }
            }
            
            if (container) {
                const icon = createBookmarkIcon(questionId);
                container.appendChild(icon);
            }
        });
    }
    
    /**
     * Create bookmark icon element
     */
    function createBookmarkIcon(questionId) {
        const isActive = isBookmarked(questionId);
        
        const icon = document.createElement('button');
        icon.className = `bookmark-icon ${isActive ? 'bookmarked' : ''}`;
        icon.setAttribute('data-question-id', questionId);
        icon.setAttribute('aria-label', isActive ? 'Remove bookmark' : 'Add bookmark');
        icon.innerHTML = `
            <svg viewBox="0 0 24 24" fill="${isActive ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
            </svg>
        `;
        
        icon.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleBookmark(questionId);
        });
        
        return icon;
    }
    
    /**
     * Toggle bookmark for a question
     */
    function toggleBookmark(questionId) {
        const icon = document.querySelector(`.bookmark-icon[data-question-id="${questionId}"]`);
        
        if (isBookmarked(questionId)) {
            removeBookmark(questionId);
            if (icon) {
                icon.classList.remove('bookmarked');
                icon.classList.add('animating');
                icon.querySelector('svg').setAttribute('fill', 'none');
                icon.setAttribute('aria-label', 'Add bookmark');
                setTimeout(() => icon.classList.remove('animating'), 500);
            }
            showNotification('Bookmark removed', 'info');
        } else {
            addBookmark(questionId);
            if (icon) {
                icon.classList.add('bookmarked', 'animating');
                icon.querySelector('svg').setAttribute('fill', 'currentColor');
                icon.setAttribute('aria-label', 'Remove bookmark');
                setTimeout(() => icon.classList.remove('animating'), 500);
            }
            showNotification('Bookmark added', 'success');
        }
        
        updateBookmarkCount();
        
        // Update list if panel is open
        if (state.isOpen) {
            renderBookmarksList();
        }
    }
    
    /**
     * Update bookmark icon state
     */
    function updateBookmarkIcon(questionId, isActive) {
        const icon = document.querySelector(`.bookmark-icon[data-question-id="${questionId}"]`);
        if (!icon) return;
        
        if (isActive) {
            icon.classList.add('bookmarked');
            icon.querySelector('svg').setAttribute('fill', 'currentColor');
            icon.setAttribute('aria-label', 'Remove bookmark');
        } else {
            icon.classList.remove('bookmarked');
            icon.querySelector('svg').setAttribute('fill', 'none');
            icon.setAttribute('aria-label', 'Add bookmark');
        }
    }
    
    /**
     * Refresh all bookmark icons
     */
    function refreshBookmarkIcons() {
        document.querySelectorAll('.bookmark-icon').forEach(icon => {
            const questionId = icon.dataset.questionId;
            updateBookmarkIcon(questionId, isBookmarked(questionId));
        });
    }
    
    /**
     * Update bookmark count badge
     */
    function updateBookmarkCount() {
        const badge = document.querySelector('.bookmark-count');
        if (badge) {
            badge.textContent = state.bookmarks.size || '';
        }
    }
    
    /**
     * Scroll to a question
     */
    function scrollToQuestion(questionId) {
        const element = document.querySelector(`[data-question-id="${questionId}"]`) ||
                       document.querySelector(`[data-id="${questionId}"]`) ||
                       document.getElementById(questionId);
        
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Highlight the element briefly
            element.style.transition = 'background-color 0.3s ease';
            const originalBg = element.style.backgroundColor;
            element.style.backgroundColor = 'rgba(102, 126, 234, 0.1)';
            
            setTimeout(() => {
                element.style.backgroundColor = originalBg;
            }, 2000);
        }
    }
    
    /**
     * Setup global event listeners
     */
    function setupEventListeners() {
        // Listen for dynamically added questions
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && 
                        (node.matches('article, .question-item, .accordion-item') || 
                         node.querySelector('article, .question-item, .accordion-item'))) {
                        addBookmarkIconsToQuestions();
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    /**
     * Handle export
     */
    function handleExport() {
        try {
            const json = exportBookmarks();
            const blob = new Blob([json], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `bookmarks-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showNotification('Bookmarks exported successfully', 'success');
        } catch (error) {
            console.error('Export failed:', error);
            showNotification('Failed to export bookmarks', 'error');
        }
    }
    
    /**
     * Handle import
     */
    function handleImport() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        
        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const result = importBookmarks(e.target.result);
                    showNotification(`Imported ${result.imported} bookmarks`, 'success');
                    refreshBookmarkIcons();
                    updateBookmarkCount();
                    if (state.isOpen) {
                        renderBookmarksList();
                    }
                } catch (error) {
                    console.error('Import failed:', error);
                    showNotification('Failed to import bookmarks', 'error');
                }
            };
            reader.readAsText(file);
        });
        
        input.click();
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        // Create notification element if it doesn't exist
        let notification = document.getElementById('bookmark-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'bookmark-notification';
            document.body.appendChild(notification);
            
            // Add styles
            const style = document.createElement('style');
            style.textContent = `
                #bookmark-notification {
                    position: fixed;
                    bottom: 100px;
                    right: 30px;
                    padding: 12px 20px;
                    border-radius: 8px;
                    color: white;
                    font-size: 0.9rem;
                    font-weight: 500;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                    z-index: 10001;
                    opacity: 0;
                    transform: translateY(20px);
                    transition: all 0.3s ease;
                    pointer-events: none;
                }
                
                #bookmark-notification.show {
                    opacity: 1;
                    transform: translateY(0);
                }
                
                #bookmark-notification.success {
                    background: #48bb78;
                }
                
                #bookmark-notification.error {
                    background: #e53e3e;
                }
                
                #bookmark-notification.info {
                    background: #4299e1;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Set message and type
        notification.textContent = message;
        notification.className = `show ${type}`;
        
        // Hide after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
    
    /**
     * Escape HTML for security
     */
    function escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    // ========================================================================
    // PUBLIC API
    // ========================================================================
    
    return {
        init,
        addBookmark,
        removeBookmark,
        getBookmarks,
        searchBookmarks,
        updateBookmarkNotes,
        addBookmarkTags,
        removeBookmarkTags,
        filterBookmarksByTag,
        isBookmarked,
        exportBookmarks,
        importBookmarks,
        clearAllBookmarks,
        openBookmarksPanel,
        closeBookmarksPanel,
        toggleBookmarksPanel,
        
        // Utility
        getState: () => ({ ...state }),
        getConfig: () => ({ ...config })
    };
})();

// ============================================================================
// AUTO-INITIALIZATION
// ============================================================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => BookmarkSystem.init());
} else {
    BookmarkSystem.init();
}

// ============================================================================
// EXPORTS
// ============================================================================

// CommonJS
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BookmarkSystem;
}

// Global
window.BookmarkSystem = BookmarkSystem;
