// ============================================================================
// ADVANCED SEARCH ENGINE MODULE
// Full-text search using Fuse.js with auto-complete, highlighting, and filtering
// Includes search history, keyboard navigation, and multiple filters
// ============================================================================

const SearchEngine = (() => {
    // Configuration
    const config = {
        fuseOptions: {
            keys: [
                { name: 'question', weight: 0.4 },
                { name: 'answer', weight: 0.3 },
                { name: 'tags', weight: 0.2 },
                { name: 'category', weight: 0.1 }
            ],
            threshold: 0.4,
            distance: 100,
            includeScore: true,
            includeMatches: true,
            minMatchCharLength: 2,
            ignoreLocation: true
        },
        searchHistoryKey: 'search_history',
        maxHistoryItems: 20,
        maxSuggestions: 10,
        debounceDelay: 300
    };

    // State
    let fuseInstance = null;
    let allQuestions = [];
    let searchHistory = [];
    let currentFilters = {
        categories: new Set(),
        tags: new Set(),
        difficulties: new Set()
    };
    let selectedResultIndex = -1;
    let searchResults = [];
    let debounceTimer = null;

    // DOM Elements (will be initialized)
    let elements = {
        searchInput: null,
        searchButton: null,
        suggestionsDropdown: null,
        resultsContainer: null,
        filterTags: null,
        filterCategories: null,
        filterDifficulties: null,
        activeFiltersContainer: null,
        searchHistoryContainer: null,
        noResultsMessage: null
    };

    // ============================================================================
    // INITIALIZATION
    // ============================================================================

    /**
     * Initialize the search engine
     */
    const init = async (domElements = {}) => {
        // Set DOM elements
        elements = { ...elements, ...domElements };

        // Load search history from localStorage
        loadSearchHistory();

        // Load data from DataLoader
        try {
            const { questions } = await DataLoader.loadData();
            allQuestions = questions;

            // Initialize Fuse.js
            fuseInstance = new Fuse(allQuestions, config.fuseOptions);

            console.log(`Search engine initialized with ${allQuestions.length} questions`);
        } catch (error) {
            console.error('Failed to initialize search engine:', error);
        }

        // Setup event listeners
        setupEventListeners();

        return true;
    };

    /**
     * Setup event listeners for search and keyboard navigation
     */
    const setupEventListeners = () => {
        if (!elements.searchInput) return;

        // Search input events
        elements.searchInput.addEventListener('input', handleSearchInput);
        elements.searchInput.addEventListener('keydown', handleKeyboardNavigation);
        elements.searchInput.addEventListener('focus', showSearchHistory);
        elements.searchInput.addEventListener('blur', () => {
            // Delay hiding to allow clicking on suggestions
            setTimeout(hideSuggestions, 200);
        });

        // Search button click
        if (elements.searchButton) {
            elements.searchButton.addEventListener('click', () => {
                performSearch(elements.searchInput.value);
            });
        }

        // Document-level keyboard shortcuts
        document.addEventListener('keydown', handleGlobalKeyboard);
    };

    // ============================================================================
    // SEARCH HISTORY MANAGEMENT
    // ============================================================================

    /**
     * Load search history from localStorage
     */
    const loadSearchHistory = () => {
        try {
            const stored = localStorage.getItem(config.searchHistoryKey);
            searchHistory = stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Failed to load search history:', error);
            searchHistory = [];
        }
    };

    /**
     * Save search history to localStorage
     */
    const saveSearchHistory = () => {
        try {
            localStorage.setItem(config.searchHistoryKey, JSON.stringify(searchHistory));
        } catch (error) {
            console.error('Failed to save search history:', error);
        }
    };

    /**
     * Add a search query to history
     */
    const addToSearchHistory = (query) => {
        if (!query.trim() || query.length < 2) return;

        // Remove if already exists
        searchHistory = searchHistory.filter(item => item.query !== query);

        // Add to beginning
        searchHistory.unshift({
            query: query,
            timestamp: Date.now()
        });

        // Limit size
        searchHistory = searchHistory.slice(0, config.maxHistoryItems);

        saveSearchHistory();
    };

    /**
     * Clear search history
     */
    const clearSearchHistory = () => {
        searchHistory = [];
        saveSearchHistory();
        if (elements.searchHistoryContainer) {
            elements.searchHistoryContainer.innerHTML = '';
        }
    };

    /**
     * Show search history dropdown
     */
    const showSearchHistory = () => {
        if (!elements.suggestionsDropdown || searchHistory.length === 0) return;

        const html = `
            <div class="search-suggestions-header">
                <span>Recent Searches</span>
                <button onclick="SearchEngine.clearSearchHistory()" class="clear-history-btn">Clear</button>
            </div>
            ${searchHistory.slice(0, 10).map((item, index) => `
                <div class="suggestion-item history-item" data-index="${index}" data-query="${escapeHtml(item.query)}">
                    <span class="suggestion-icon">🕐</span>
                    <span class="suggestion-text">${escapeHtml(item.query)}</span>
                </div>
            `).join('')}
        `;

        elements.suggestionsDropdown.innerHTML = html;
        elements.suggestionsDropdown.classList.add('active');

        // Add click handlers
        elements.suggestionsDropdown.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                const query = item.getAttribute('data-query');
                elements.searchInput.value = query;
                performSearch(query);
                hideSuggestions();
            });
        });
    };

    // ============================================================================
    // AUTO-COMPLETE SUGGESTIONS
    // ============================================================================

    /**
     * Generate auto-complete suggestions
     */
    const generateSuggestions = (query) => {
        if (!query || query.length < 2) {
            showSearchHistory();
            return;
        }

        // Search for matching questions
        const results = fuseInstance.search(query).slice(0, config.maxSuggestions);

        if (results.length === 0) {
            hideSuggestions();
            return;
        }

        const html = results.map((result, index) => {
            const question = result.item;
            const highlightedQuestion = highlightMatches(question.question, query);
            
            return `
                <div class="suggestion-item ${index === selectedResultIndex ? 'selected' : ''}" 
                     data-index="${index}" 
                     data-question-id="${question.id}">
                    <span class="suggestion-icon">🔍</span>
                    <div class="suggestion-content">
                        <div class="suggestion-text">${highlightedQuestion}</div>
                        <div class="suggestion-meta">
                            <span class="suggestion-category">${question.category}</span>
                            <span class="suggestion-difficulty">${question.difficulty}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        elements.suggestionsDropdown.innerHTML = html;
        elements.suggestionsDropdown.classList.add('active');

        // Add click handlers
        elements.suggestionsDropdown.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                const questionId = item.getAttribute('data-question-id');
                const question = allQuestions.find(q => q.id === questionId);
                if (question) {
                    elements.searchInput.value = question.question;
                    performSearch(question.question);
                    hideSuggestions();
                }
            });
        });
    };

    /**
     * Hide suggestions dropdown
     */
    const hideSuggestions = () => {
        if (elements.suggestionsDropdown) {
            elements.suggestionsDropdown.classList.remove('active');
            selectedResultIndex = -1;
        }
    };

    // ============================================================================
    // SEARCH EXECUTION
    // ============================================================================

    /**
     * Handle search input with debouncing
     */
    const handleSearchInput = (event) => {
        const query = event.target.value;

        // Clear previous debounce timer
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        // Debounce auto-complete
        debounceTimer = setTimeout(() => {
            generateSuggestions(query);
        }, config.debounceDelay);
    };

    /**
     * Perform full search
     */
    const performSearch = (query) => {
        if (!fuseInstance) {
            console.error('Search engine not initialized');
            return;
        }

        // Add to search history
        if (query && query.trim()) {
            addToSearchHistory(query.trim());
        }

        // Get base results from Fuse.js
        let results = query && query.trim() 
            ? fuseInstance.search(query) 
            : allQuestions.map(item => ({ item, score: 0, matches: [] }));

        // Apply additional filters
        results = applyFilters(results);

        // Store results for keyboard navigation
        searchResults = results;
        selectedResultIndex = -1;

        // Display results
        displayResults(results, query);

        // Hide suggestions
        hideSuggestions();
    };

    /**
     * Apply category, tag, and difficulty filters
     */
    const applyFilters = (results) => {
        return results.filter(result => {
            const question = result.item;

            // Check category filter
            if (currentFilters.categories.size > 0) {
                if (!currentFilters.categories.has(question.category)) {
                    return false;
                }
            }

            // Check difficulty filter
            if (currentFilters.difficulties.size > 0) {
                if (!currentFilters.difficulties.has(question.difficulty)) {
                    return false;
                }
            }

            // Check tag filter (must match ALL selected tags)
            if (currentFilters.tags.size > 0) {
                const hasAllTags = Array.from(currentFilters.tags).every(tag =>
                    question.tags.includes(tag)
                );
                if (!hasAllTags) {
                    return false;
                }
            }

            return true;
        });
    };

    // ============================================================================
    // RESULTS DISPLAY WITH HIGHLIGHTING
    // ============================================================================

    /**
     * Display search results with highlighting
     */
    const displayResults = (results, query) => {
        if (!elements.resultsContainer) return;

        if (results.length === 0) {
            showNoResults();
            return;
        }

        // Hide no results message
        if (elements.noResultsMessage) {
            elements.noResultsMessage.style.display = 'none';
        }

        const html = results.map((result, index) => {
            const question = result.item;
            const highlightedQuestion = highlightSearchTerms(question.question, query, result.matches);
            const highlightedAnswer = highlightSearchTerms(question.answer, query, result.matches);
            const score = result.score ? Math.round((1 - result.score) * 100) : 100;

            return `
                <div class="search-result-card ${index === selectedResultIndex ? 'keyboard-selected' : ''}" 
                     data-index="${index}"
                     data-question-id="${question.id}"
                     tabindex="0">
                    <div class="result-header">
                        <div class="result-title">${highlightedQuestion}</div>
                        <div class="result-score" title="Relevance score">${score}%</div>
                    </div>
                    <div class="result-meta">
                        <span class="result-category">${question.category}</span>
                        <span class="result-difficulty difficulty-${question.difficulty}">${question.difficulty}</span>
                        ${question.tags.slice(0, 3).map(tag => 
                            `<span class="result-tag">${tag}</span>`
                        ).join('')}
                    </div>
                    <div class="result-answer">${truncateText(highlightedAnswer, 200)}</div>
                    <div class="result-actions">
                        <button onclick="SearchEngine.viewFullAnswer('${question.id}')" class="view-answer-btn">
                            View Full Answer →
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        elements.resultsContainer.innerHTML = html;

        // Add keyboard navigation to result cards
        elements.resultsContainer.querySelectorAll('.search-result-card').forEach(card => {
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const questionId = card.getAttribute('data-question-id');
                    viewFullAnswer(questionId);
                }
            });
        });

        // Update stats if available
        updateSearchStats(results.length);
    };

    /**
     * Show no results message
     */
    const showNoResults = () => {
        if (elements.noResultsMessage) {
            elements.noResultsMessage.style.display = 'block';
        }
        if (elements.resultsContainer) {
            elements.resultsContainer.innerHTML = '';
        }
        updateSearchStats(0);
    };

    /**
     * Update search statistics
     */
    const updateSearchStats = (count) => {
        const statsElement = document.getElementById('statVisible');
        if (statsElement) {
            statsElement.textContent = count;
        }
    };

    /**
     * Highlight search terms in text using <mark> tags
     */
    const highlightSearchTerms = (text, query, matches = []) => {
        if (!query || !text) return escapeHtml(text);

        // Use Fuse.js matches if available
        if (matches && matches.length > 0) {
            let highlighted = escapeHtml(text);
            const terms = new Set();

            // Extract unique terms from matches
            matches.forEach(match => {
                if (match.value) {
                    const matchTerms = query.toLowerCase().split(/\s+/);
                    matchTerms.forEach(term => {
                        if (term.length >= 2) {
                            terms.add(term);
                        }
                    });
                }
            });

            // Highlight each term
            terms.forEach(term => {
                const regex = new RegExp(`(${escapeRegex(term)})`, 'gi');
                highlighted = highlighted.replace(regex, '<mark>$1</mark>');
            });

            return highlighted;
        }

        // Fallback: simple highlighting
        return highlightMatches(text, query);
    };

    /**
     * Simple highlight matches
     */
    const highlightMatches = (text, query) => {
        if (!query || !text) return escapeHtml(text);

        let highlighted = escapeHtml(text);
        const terms = query.toLowerCase().split(/\s+/).filter(t => t.length >= 2);

        terms.forEach(term => {
            const regex = new RegExp(`(${escapeRegex(term)})`, 'gi');
            highlighted = highlighted.replace(regex, '<mark>$1</mark>');
        });

        return highlighted;
    };

    // ============================================================================
    // KEYBOARD NAVIGATION
    // ============================================================================

    /**
     * Handle keyboard navigation in search input
     */
    const handleKeyboardNavigation = (event) => {
        const isDropdownVisible = elements.suggestionsDropdown?.classList.contains('active');

        if (!isDropdownVisible) return;

        const suggestions = elements.suggestionsDropdown.querySelectorAll('.suggestion-item');

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                selectedResultIndex = Math.min(selectedResultIndex + 1, suggestions.length - 1);
                updateSuggestionSelection(suggestions);
                break;

            case 'ArrowUp':
                event.preventDefault();
                selectedResultIndex = Math.max(selectedResultIndex - 1, -1);
                updateSuggestionSelection(suggestions);
                break;

            case 'Enter':
                event.preventDefault();
                if (selectedResultIndex >= 0 && suggestions[selectedResultIndex]) {
                    suggestions[selectedResultIndex].click();
                } else {
                    performSearch(elements.searchInput.value);
                }
                break;

            case 'Escape':
                event.preventDefault();
                hideSuggestions();
                break;
        }
    };

    /**
     * Handle global keyboard shortcuts
     */
    const handleGlobalKeyboard = (event) => {
        // Navigate results with arrow keys when not in input
        if (event.target.tagName !== 'INPUT' && searchResults.length > 0) {
            const resultCards = document.querySelectorAll('.search-result-card');

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    selectedResultIndex = Math.min(selectedResultIndex + 1, resultCards.length - 1);
                    updateResultSelection(resultCards);
                    break;

                case 'ArrowUp':
                    event.preventDefault();
                    selectedResultIndex = Math.max(selectedResultIndex - 1, 0);
                    updateResultSelection(resultCards);
                    break;

                case 'Enter':
                    if (selectedResultIndex >= 0 && resultCards[selectedResultIndex]) {
                        const questionId = resultCards[selectedResultIndex].getAttribute('data-question-id');
                        viewFullAnswer(questionId);
                    }
                    break;
            }
        }
    };

    /**
     * Update suggestion selection visual state
     */
    const updateSuggestionSelection = (suggestions) => {
        suggestions.forEach((suggestion, index) => {
            suggestion.classList.toggle('selected', index === selectedResultIndex);
        });

        // Scroll selected item into view
        if (selectedResultIndex >= 0 && suggestions[selectedResultIndex]) {
            suggestions[selectedResultIndex].scrollIntoView({
                block: 'nearest',
                behavior: 'smooth'
            });
        }
    };

    /**
     * Update result selection visual state
     */
    const updateResultSelection = (resultCards) => {
        resultCards.forEach((card, index) => {
            card.classList.toggle('keyboard-selected', index === selectedResultIndex);
        });

        // Scroll selected item into view
        if (selectedResultIndex >= 0 && resultCards[selectedResultIndex]) {
            resultCards[selectedResultIndex].scrollIntoView({
                block: 'nearest',
                behavior: 'smooth'
            });
            resultCards[selectedResultIndex].focus();
        }
    };

    // ============================================================================
    // FILTER MANAGEMENT
    // ============================================================================

    /**
     * Add category filter
     */
    const addCategoryFilter = (category) => {
        currentFilters.categories.add(category);
        performSearch(elements.searchInput?.value || '');
        updateActiveFiltersDisplay();
    };

    /**
     * Remove category filter
     */
    const removeCategoryFilter = (category) => {
        currentFilters.categories.delete(category);
        performSearch(elements.searchInput?.value || '');
        updateActiveFiltersDisplay();
    };

    /**
     * Add tag filter
     */
    const addTagFilter = (tag) => {
        currentFilters.tags.add(tag);
        performSearch(elements.searchInput?.value || '');
        updateActiveFiltersDisplay();
    };

    /**
     * Remove tag filter
     */
    const removeTagFilter = (tag) => {
        currentFilters.tags.delete(tag);
        performSearch(elements.searchInput?.value || '');
        updateActiveFiltersDisplay();
    };

    /**
     * Add difficulty filter
     */
    const addDifficultyFilter = (difficulty) => {
        currentFilters.difficulties.add(difficulty);
        performSearch(elements.searchInput?.value || '');
        updateActiveFiltersDisplay();
    };

    /**
     * Remove difficulty filter
     */
    const removeDifficultyFilter = (difficulty) => {
        currentFilters.difficulties.delete(difficulty);
        performSearch(elements.searchInput?.value || '');
        updateActiveFiltersDisplay();
    };

    /**
     * Clear all filters
     */
    const clearAllFilters = () => {
        currentFilters.categories.clear();
        currentFilters.tags.clear();
        currentFilters.difficulties.clear();
        performSearch(elements.searchInput?.value || '');
        updateActiveFiltersDisplay();
    };

    /**
     * Update active filters display
     */
    const updateActiveFiltersDisplay = () => {
        if (!elements.activeFiltersContainer) return;

        const filters = [];

        // Add category filters
        currentFilters.categories.forEach(category => {
            filters.push({ type: 'category', value: category, label: category });
        });

        // Add tag filters
        currentFilters.tags.forEach(tag => {
            filters.push({ type: 'tag', value: tag, label: tag });
        });

        // Add difficulty filters
        currentFilters.difficulties.forEach(difficulty => {
            filters.push({ type: 'difficulty', value: difficulty, label: difficulty });
        });

        if (filters.length === 0) {
            elements.activeFiltersContainer.style.display = 'none';
            return;
        }

        const html = `
            ${filters.map(filter => `
                <div class="active-filter-badge">
                    <span>${filter.label}</span>
                    <button onclick="SearchEngine.removeFilter('${filter.type}', '${filter.value}')" aria-label="Remove filter">×</button>
                </div>
            `).join('')}
            <button onclick="SearchEngine.clearAllFilters()" class="clear-filters-btn">Clear All</button>
        `;

        elements.activeFiltersContainer.innerHTML = html;
        elements.activeFiltersContainer.style.display = 'flex';
    };

    /**
     * Remove filter by type and value
     */
    const removeFilter = (type, value) => {
        switch (type) {
            case 'category':
                removeCategoryFilter(value);
                break;
            case 'tag':
                removeTagFilter(value);
                break;
            case 'difficulty':
                removeDifficultyFilter(value);
                break;
        }
    };

    // ============================================================================
    // UTILITY FUNCTIONS
    // ============================================================================

    /**
     * View full answer for a question
     */
    const viewFullAnswer = (questionId) => {
        const question = allQuestions.find(q => q.id === questionId);
        if (!question) return;

        // Trigger custom event for other modules to handle
        const event = new CustomEvent('viewAnswer', { detail: { question } });
        document.dispatchEvent(event);

        // Or open in a modal/new page based on implementation
        console.log('View answer:', question);
    };

    /**
     * Escape HTML to prevent XSS
     */
    const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    /**
     * Escape regex special characters
     */
    const escapeRegex = (text) => {
        return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    };

    /**
     * Truncate text to specified length
     */
    const truncateText = (text, maxLength) => {
        if (!text) return '';
        const stripped = text.replace(/<[^>]+>/g, '');
        if (stripped.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    };

    // ============================================================================
    // PUBLIC API
    // ============================================================================

    return {
        init,
        performSearch,
        addCategoryFilter,
        removeCategoryFilter,
        addTagFilter,
        removeTagFilter,
        addDifficultyFilter,
        removeDifficultyFilter,
        clearAllFilters,
        removeFilter,
        clearSearchHistory,
        viewFullAnswer,
        getSearchHistory: () => searchHistory,
        getCurrentFilters: () => currentFilters,
        getSearchResults: () => searchResults
    };
})();

// Make available globally
window.SearchEngine = SearchEngine;

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SearchEngine;
}
