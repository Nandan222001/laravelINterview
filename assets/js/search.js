// ============================================================================
// SEARCH MODULE - Comprehensive Search System with Fuse.js
// Implements fuzzy search, real-time results, highlighting, history, filters, 
// and keyboard navigation
// ============================================================================

const SearchModule = (() => {
    // Configuration
    const config = {
        fuseOptions: {
            includeScore: true,
            includeMatches: true,
            threshold: 0.4,
            distance: 100,
            minMatchCharLength: 2,
            keys: [
                { name: 'question', weight: 2.0 },
                { name: 'answer', weight: 1.5 },
                { name: 'title', weight: 1.8 },
                { name: 'tags', weight: 1.3 },
                { name: 'category', weight: 1.0 },
                { name: 'topic', weight: 1.2 }
            ]
        },
        maxResults: 50,
        maxHistoryItems: 10,
        debounceDelay: 300,
        minQueryLength: 2,
        storageKey: 'laravel_interview_search_history',
        highlightClass: 'search-highlight'
    };

    // State
    let fuseInstance = null;
    let searchData = [];
    let searchHistory = [];
    let currentResults = [];
    let selectedIndex = -1;
    let debounceTimer = null;
    let isDropdownOpen = false;

    // DOM Elements (cached)
    let elements = {
        searchInput: null,
        searchButton: null,
        searchDropdown: null,
        searchResults: null,
        historyList: null,
        clearHistoryBtn: null,
        filterCheckboxes: [],
        difficultyRadios: [],
        tagChips: null
    };

    // Active filters
    let activeFilters = {
        topics: new Set(),
        difficulties: new Set(),
        tags: new Set()
    };

    // ============================================================================
    // INITIALIZATION
    // ============================================================================

    const init = (options = {}) => {
        Object.assign(config, options);
        
        // Cache DOM elements
        cacheElements();
        
        // Load search history from localStorage
        loadSearchHistory();
        
        // Initialize Fuse.js with data
        initializeFuse();
        
        // Set up event listeners
        setupEventListeners();
        
        // Set up filters UI
        setupFiltersUI();
        
        console.log('Search Module initialized successfully');
    };

    const cacheElements = () => {
        elements.searchInput = document.querySelector('.search-bar input');
        elements.searchButton = document.querySelector('.search-bar button');
        
        // Create dropdown if it doesn't exist
        if (!document.querySelector('.search-dropdown')) {
            createSearchDropdown();
        }
        
        elements.searchDropdown = document.querySelector('.search-dropdown');
        elements.searchResults = document.querySelector('.search-results-list');
        elements.historyList = document.querySelector('.search-history-list');
        elements.clearHistoryBtn = document.querySelector('.clear-history-btn');
        
        // Filter elements
        elements.filterCheckboxes = Array.from(document.querySelectorAll('input[name="topic"]'));
        elements.difficultyRadios = Array.from(document.querySelectorAll('input[name="difficulty"]'));
        elements.tagChips = document.querySelector('.tag-chips-container');
    };

    const createSearchDropdown = () => {
        const searchBar = document.querySelector('.search-bar');
        if (!searchBar) return;

        const dropdown = document.createElement('div');
        dropdown.className = 'search-dropdown';
        dropdown.innerHTML = `
            <div class="search-dropdown-header">
                <span class="search-dropdown-title">Search Results</span>
                <button class="search-dropdown-close" aria-label="Close search">&times;</button>
            </div>
            <div class="search-results-container">
                <ul class="search-results-list" role="listbox"></ul>
            </div>
            <div class="search-history-container">
                <div class="search-history-header">
                    <span>Recent Searches</span>
                    <button class="clear-history-btn">Clear</button>
                </div>
                <ul class="search-history-list"></ul>
            </div>
        `;

        // Add styles
        const styles = document.createElement('style');
        styles.textContent = `
            .search-dropdown {
                position: absolute;
                top: calc(100% + 0.5rem);
                left: 0;
                right: 0;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-height: 500px;
                overflow: hidden;
                display: none;
                z-index: 1000;
                animation: slideDown 0.2s ease;
            }

            .search-dropdown.open {
                display: block;
            }

            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .search-dropdown-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1rem;
                border-bottom: 1px solid #eee;
                background: #f9f9f9;
            }

            .search-dropdown-title {
                font-weight: 600;
                color: #333;
            }

            .search-dropdown-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: #999;
                padding: 0;
                width: 30px;
                height: 30px;
                line-height: 1;
            }

            .search-dropdown-close:hover {
                color: #333;
            }

            .search-results-container {
                max-height: 300px;
                overflow-y: auto;
            }

            .search-results-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .search-result-item {
                padding: 1rem;
                border-bottom: 1px solid #eee;
                cursor: pointer;
                transition: background-color 0.2s;
            }

            .search-result-item:hover,
            .search-result-item.selected {
                background-color: #f5f5f5;
            }

            .search-result-item.selected {
                border-left: 3px solid #e3342f;
            }

            .search-result-title {
                font-weight: 600;
                color: #333;
                margin-bottom: 0.25rem;
            }

            .search-result-snippet {
                font-size: 0.875rem;
                color: #666;
                margin-bottom: 0.5rem;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .search-result-meta {
                display: flex;
                gap: 0.5rem;
                flex-wrap: wrap;
                font-size: 0.75rem;
            }

            .search-result-badge {
                padding: 0.2rem 0.5rem;
                border-radius: 4px;
                background: #e3342f;
                color: white;
            }

            .search-result-tag {
                padding: 0.2rem 0.5rem;
                border-radius: 4px;
                background: #f0f0f0;
                color: #666;
            }

            .search-highlight {
                background-color: #ffeb3b;
                font-weight: 600;
                padding: 0 2px;
            }

            .search-history-container {
                border-top: 1px solid #eee;
                padding: 1rem;
                background: #fafafa;
            }

            .search-history-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.5rem;
                font-size: 0.875rem;
                color: #666;
            }

            .clear-history-btn {
                background: none;
                border: none;
                color: #e3342f;
                cursor: pointer;
                font-size: 0.875rem;
                padding: 0.25rem 0.5rem;
            }

            .clear-history-btn:hover {
                text-decoration: underline;
            }

            .search-history-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .search-history-item {
                padding: 0.5rem;
                cursor: pointer;
                color: #666;
                font-size: 0.875rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 4px;
            }

            .search-history-item:hover {
                background-color: #f0f0f0;
            }

            .search-history-remove {
                background: none;
                border: none;
                color: #999;
                cursor: pointer;
                font-size: 1rem;
                padding: 0 0.25rem;
            }

            .search-history-remove:hover {
                color: #e3342f;
            }

            .search-no-results {
                padding: 2rem;
                text-align: center;
                color: #999;
            }

            .tag-chips-container {
                display: flex;
                gap: 0.5rem;
                flex-wrap: wrap;
                margin-top: 0.5rem;
                padding: 0.5rem;
                min-height: 40px;
            }

            .tag-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.25rem 0.75rem;
                background: #e3342f;
                color: white;
                border-radius: 16px;
                font-size: 0.875rem;
            }

            .tag-chip-remove {
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                font-size: 1rem;
                padding: 0;
                margin-left: 0.25rem;
                line-height: 1;
            }

            .tag-chip-remove:hover {
                opacity: 0.8;
            }

            .search-bar {
                position: relative;
            }
        `;

        document.head.appendChild(styles);
        searchBar.parentElement.style.position = 'relative';
        searchBar.parentElement.appendChild(dropdown);
    };

    const initializeFuse = () => {
        // Build search data from DOM
        searchData = buildSearchData();
        
        // Initialize Fuse.js
        fuseInstance = new Fuse(searchData, config.fuseOptions);
        
        console.log(`Fuse.js initialized with ${searchData.length} items`);
    };

    const buildSearchData = () => {
        const data = [];
        const accordionItems = document.querySelectorAll('.accordion-item');

        accordionItems.forEach((item, index) => {
            const header = item.querySelector('.accordion-header h3');
            const body = item.querySelector('.accordion-body');
            const description = item.querySelector('.topic-description');
            const features = item.querySelectorAll('.topic-features li');
            const badges = item.querySelectorAll('.badge');

            const title = header ? header.textContent.trim() : '';
            const content = body ? body.textContent.trim() : '';
            const desc = description ? description.textContent.trim() : '';
            const featuresList = Array.from(features).map(f => f.textContent.trim());
            const tags = Array.from(badges).map(b => b.textContent.trim());

            // Extract topic from data attributes or content
            const topic = item.getAttribute('data-topic') || extractTopicFromTitle(title);
            const difficulty = item.getAttribute('data-difficulty') || 'intermediate';
            const hash = item.getAttribute('data-hash') || createHash(title);

            data.push({
                id: `item-${index}`,
                element: item,
                title,
                question: title,
                answer: desc || content,
                content: [title, desc, ...featuresList].join(' '),
                tags,
                topic,
                category: topic,
                difficulty,
                hash,
                index
            });
        });

        return data;
    };

    const extractTopicFromTitle = (title) => {
        if (title.includes('Laravel') || title.includes('PHP')) return 'php-laravel';
        if (title.includes('API') || title.includes('Security')) return 'api-security';
        if (title.includes('Payment')) return 'payment';
        if (title.includes('Realtime') || title.includes('WebSocket')) return 'realtime';
        if (title.includes('Database')) return 'database';
        if (title.includes('React') || title.includes('Next.js')) return 'frontend';
        if (title.includes('DevOps') || title.includes('Kubernetes')) return 'devops';
        if (title.includes('AI') || title.includes('LLM')) return 'ai';
        return 'general';
    };

    const createHash = (text) => {
        return text.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .substring(0, 50);
    };

    // ============================================================================
    // EVENT LISTENERS
    // ============================================================================

    const setupEventListeners = () => {
        if (!elements.searchInput || !elements.searchButton) return;

        // Search input - real-time search with debouncing
        elements.searchInput.addEventListener('input', handleSearchInput);

        // Search button
        elements.searchButton.addEventListener('click', handleSearchSubmit);

        // Enter key on search input
        elements.searchInput.addEventListener('keydown', handleSearchKeydown);

        // Focus events
        elements.searchInput.addEventListener('focus', handleSearchFocus);
        
        // Close dropdown button
        const closeBtn = document.querySelector('.search-dropdown-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeDropdown);
        }

        // Clear history button
        if (elements.clearHistoryBtn) {
            elements.clearHistoryBtn.addEventListener('click', clearSearchHistory);
        }

        // Click outside to close dropdown
        document.addEventListener('click', handleOutsideClick);

        // Keyboard navigation in results
        document.addEventListener('keydown', handleGlobalKeydown);
    };

    const handleSearchInput = (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            performSearch(e.target.value);
        }, config.debounceDelay);
    };

    const handleSearchSubmit = () => {
        const query = elements.searchInput.value.trim();
        if (query) {
            performSearch(query);
            addToSearchHistory(query);
        }
    };

    const handleSearchKeydown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSearchSubmit();
        } else if (e.key === 'Escape') {
            closeDropdown();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            navigateResults('down');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            navigateResults('up');
        }
    };

    const handleSearchFocus = () => {
        const query = elements.searchInput.value.trim();
        if (query.length >= config.minQueryLength) {
            openDropdown();
        } else {
            showSearchHistory();
        }
    };

    const handleOutsideClick = (e) => {
        if (!e.target.closest('.search-bar') && !e.target.closest('.search-dropdown')) {
            closeDropdown();
        }
    };

    const handleGlobalKeydown = (e) => {
        if (!isDropdownOpen) return;

        if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            selectResult(selectedIndex);
        }
    };

    // ============================================================================
    // SEARCH FUNCTIONALITY
    // ============================================================================

    const performSearch = (query) => {
        if (!query || query.length < config.minQueryLength) {
            currentResults = [];
            closeDropdown();
            clearHighlights();
            applyFilteredData(searchData);
            return;
        }

        // Perform fuzzy search with Fuse.js
        const fuseResults = fuseInstance.search(query);

        // Apply active filters
        let filteredResults = fuseResults.map(result => result.item);
        filteredResults = applyFilters(filteredResults);

        currentResults = filteredResults.slice(0, config.maxResults);

        // Display results
        displayResults(currentResults, query);
        openDropdown();

        // Apply highlighting
        highlightMatches(fuseResults.slice(0, config.maxResults), query);

        // Filter visible items in the page
        applyFilteredData(currentResults);
    };

    const applyFilters = (results) => {
        let filtered = results;

        // Filter by topics
        if (activeFilters.topics.size > 0) {
            filtered = filtered.filter(item => 
                activeFilters.topics.has(item.topic) || 
                activeFilters.topics.has(item.category)
            );
        }

        // Filter by difficulties
        if (activeFilters.difficulties.size > 0) {
            filtered = filtered.filter(item => 
                activeFilters.difficulties.has(item.difficulty)
            );
        }

        // Filter by tags
        if (activeFilters.tags.size > 0) {
            filtered = filtered.filter(item =>
                item.tags.some(tag => activeFilters.tags.has(tag))
            );
        }

        return filtered;
    };

    const displayResults = (results, query) => {
        if (!elements.searchResults) return;

        elements.searchResults.innerHTML = '';
        selectedIndex = -1;

        if (results.length === 0) {
            elements.searchResults.innerHTML = `
                <div class="search-no-results">
                    No results found for "${escapeHtml(query)}"
                </div>
            `;
            return;
        }

        results.forEach((item, index) => {
            const li = document.createElement('li');
            li.className = 'search-result-item';
            li.setAttribute('role', 'option');
            li.setAttribute('data-index', index);

            const snippet = createSnippet(item.answer || item.content, query, 100);

            li.innerHTML = `
                <div class="search-result-title">${highlightText(item.title, query)}</div>
                <div class="search-result-snippet">${snippet}</div>
                <div class="search-result-meta">
                    <span class="search-result-badge">${item.difficulty}</span>
                    ${item.tags.slice(0, 3).map(tag => 
                        `<span class="search-result-tag">${escapeHtml(tag)}</span>`
                    ).join('')}
                </div>
            `;

            li.addEventListener('click', () => selectResult(index));
            li.addEventListener('mouseenter', () => highlightResult(index));

            elements.searchResults.appendChild(li);
        });
    };

    const createSnippet = (text, query, maxLength) => {
        if (!text) return '';

        const lowerText = text.toLowerCase();
        const lowerQuery = query.toLowerCase();
        const index = lowerText.indexOf(lowerQuery);

        if (index === -1) {
            return escapeHtml(text.substring(0, maxLength)) + (text.length > maxLength ? '...' : '');
        }

        const start = Math.max(0, index - 30);
        const end = Math.min(text.length, index + query.length + 70);

        let snippet = text.substring(start, end);
        if (start > 0) snippet = '...' + snippet;
        if (end < text.length) snippet = snippet + '...';

        return highlightText(snippet, query);
    };

    const highlightText = (text, query) => {
        if (!query) return escapeHtml(text);

        const escaped = escapeHtml(text);
        const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
        return escaped.replace(regex, `<span class="${config.highlightClass}">$1</span>`);
    };

    const highlightMatches = (fuseResults, query) => {
        clearHighlights();

        fuseResults.forEach(result => {
            if (!result.matches || !result.item.element) return;

            const header = result.item.element.querySelector('.accordion-header h3');
            if (header) {
                const originalText = header.textContent;
                header.innerHTML = highlightText(originalText, query);
            }
        });
    };

    const clearHighlights = () => {
        document.querySelectorAll(`.${config.highlightClass}`).forEach(mark => {
            const parent = mark.parentNode;
            if (parent) {
                parent.replaceChild(document.createTextNode(mark.textContent), mark);
                parent.normalize();
            }
        });
    };

    const applyFilteredData = (filteredItems) => {
        const allItems = document.querySelectorAll('.accordion-item');
        const visibleElements = new Set(filteredItems.map(item => item.element));

        allItems.forEach(item => {
            if (filteredItems.length === 0 || visibleElements.has(item)) {
                item.style.display = '';
                item.style.opacity = '1';
            } else {
                item.style.opacity = '0.3';
            }
        });
    };

    // ============================================================================
    // KEYBOARD NAVIGATION
    // ============================================================================

    const navigateResults = (direction) => {
        if (!currentResults.length) return;

        const resultItems = elements.searchResults.querySelectorAll('.search-result-item');
        if (!resultItems.length) return;

        // Remove previous selection
        if (selectedIndex >= 0 && resultItems[selectedIndex]) {
            resultItems[selectedIndex].classList.remove('selected');
        }

        // Update index
        if (direction === 'down') {
            selectedIndex = (selectedIndex + 1) % resultItems.length;
        } else if (direction === 'up') {
            selectedIndex = selectedIndex <= 0 ? resultItems.length - 1 : selectedIndex - 1;
        }

        // Apply new selection
        if (resultItems[selectedIndex]) {
            resultItems[selectedIndex].classList.add('selected');
            resultItems[selectedIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    };

    const highlightResult = (index) => {
        const resultItems = elements.searchResults.querySelectorAll('.search-result-item');
        resultItems.forEach((item, i) => {
            item.classList.toggle('selected', i === index);
        });
        selectedIndex = index;
    };

    const selectResult = (index) => {
        if (index < 0 || index >= currentResults.length) return;

        const result = currentResults[index];
        if (result.element) {
            // Scroll to the element
            const offset = 100;
            const elementPosition = result.element.offsetTop - offset;
            window.scrollTo({ top: elementPosition, behavior: 'smooth' });

            // Open accordion if it has one
            setTimeout(() => {
                const accordionHeader = result.element.querySelector('.accordion-header');
                if (accordionHeader) {
                    accordionHeader.click();
                }
            }, 500);

            closeDropdown();
        }
    };

    // ============================================================================
    // SEARCH HISTORY
    // ============================================================================

    const loadSearchHistory = () => {
        try {
            const stored = localStorage.getItem(config.storageKey);
            if (stored) {
                searchHistory = JSON.parse(stored);
            }
        } catch (error) {
            console.error('Failed to load search history:', error);
            searchHistory = [];
        }
    };

    const saveSearchHistory = () => {
        try {
            localStorage.setItem(config.storageKey, JSON.stringify(searchHistory));
        } catch (error) {
            console.error('Failed to save search history:', error);
        }
    };

    const addToSearchHistory = (query) => {
        if (!query || query.length < config.minQueryLength) return;

        // Remove duplicates
        searchHistory = searchHistory.filter(item => item !== query);

        // Add to beginning
        searchHistory.unshift(query);

        // Limit size
        if (searchHistory.length > config.maxHistoryItems) {
            searchHistory = searchHistory.slice(0, config.maxHistoryItems);
        }

        saveSearchHistory();
    };

    const removeFromSearchHistory = (query) => {
        searchHistory = searchHistory.filter(item => item !== query);
        saveSearchHistory();
        showSearchHistory();
    };

    const clearSearchHistory = () => {
        searchHistory = [];
        saveSearchHistory();
        showSearchHistory();
    };

    const showSearchHistory = () => {
        if (!elements.historyList) return;

        elements.historyList.innerHTML = '';

        if (searchHistory.length === 0) {
            elements.historyList.innerHTML = `
                <li style="padding: 1rem; text-align: center; color: #999;">
                    No recent searches
                </li>
            `;
            return;
        }

        searchHistory.forEach(query => {
            const li = document.createElement('li');
            li.className = 'search-history-item';
            li.innerHTML = `
                <span class="search-history-text">${escapeHtml(query)}</span>
                <button class="search-history-remove" aria-label="Remove from history">&times;</button>
            `;

            li.querySelector('.search-history-text').addEventListener('click', () => {
                elements.searchInput.value = query;
                performSearch(query);
            });

            li.querySelector('.search-history-remove').addEventListener('click', (e) => {
                e.stopPropagation();
                removeFromSearchHistory(query);
            });

            elements.historyList.appendChild(li);
        });

        openDropdown();
    };

    // ============================================================================
    // FILTERS UI
    // ============================================================================

    const setupFiltersUI = () => {
        // Topic checkboxes
        elements.filterCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    activeFilters.topics.add(e.target.value);
                } else {
                    activeFilters.topics.delete(e.target.value);
                }
                updateTagChips();
                refreshSearch();
            });
        });

        // Difficulty checkboxes (converted to multi-select)
        elements.difficultyRadios.forEach(radio => {
            radio.type = 'checkbox'; // Convert radios to checkboxes
            radio.addEventListener('change', (e) => {
                if (e.target.checked) {
                    activeFilters.difficulties.add(e.target.value);
                } else {
                    activeFilters.difficulties.delete(e.target.value);
                }
                updateTagChips();
                refreshSearch();
            });
        });

        // Create tag chips container if it doesn't exist
        if (!elements.tagChips) {
            const searchBar = document.querySelector('.search-bar');
            if (searchBar) {
                const container = document.createElement('div');
                container.className = 'tag-chips-container';
                searchBar.parentNode.insertBefore(container, searchBar.nextSibling);
                elements.tagChips = container;
            }
        }
    };

    const updateTagChips = () => {
        if (!elements.tagChips) return;

        elements.tagChips.innerHTML = '';

        // Add topic chips
        activeFilters.topics.forEach(topic => {
            addTagChip(topic, 'topic');
        });

        // Add difficulty chips
        activeFilters.difficulties.forEach(difficulty => {
            addTagChip(difficulty, 'difficulty');
        });

        // Add tag chips
        activeFilters.tags.forEach(tag => {
            addTagChip(tag, 'tag');
        });
    };

    const addTagChip = (value, type) => {
        const chip = document.createElement('div');
        chip.className = 'tag-chip';
        chip.innerHTML = `
            <span>${escapeHtml(value)}</span>
            <button class="tag-chip-remove" aria-label="Remove filter">&times;</button>
        `;

        chip.querySelector('.tag-chip-remove').addEventListener('click', () => {
            removeFilter(value, type);
        });

        elements.tagChips.appendChild(chip);
    };

    const removeFilter = (value, type) => {
        if (type === 'topic') {
            activeFilters.topics.delete(value);
            const checkbox = elements.filterCheckboxes.find(cb => cb.value === value);
            if (checkbox) checkbox.checked = false;
        } else if (type === 'difficulty') {
            activeFilters.difficulties.delete(value);
            const radio = elements.difficultyRadios.find(r => r.value === value);
            if (radio) radio.checked = false;
        } else if (type === 'tag') {
            activeFilters.tags.delete(value);
        }

        updateTagChips();
        refreshSearch();
    };

    const refreshSearch = () => {
        const query = elements.searchInput.value.trim();
        if (query.length >= config.minQueryLength) {
            performSearch(query);
        } else {
            // Just apply filters to existing data
            const filteredData = applyFilters(searchData);
            applyFilteredData(filteredData);
        }
    };

    // ============================================================================
    // DROPDOWN MANAGEMENT
    // ============================================================================

    const openDropdown = () => {
        if (elements.searchDropdown) {
            elements.searchDropdown.classList.add('open');
            isDropdownOpen = true;
        }
    };

    const closeDropdown = () => {
        if (elements.searchDropdown) {
            elements.searchDropdown.classList.remove('open');
            isDropdownOpen = false;
            selectedIndex = -1;
        }
    };

    // ============================================================================
    // UTILITY FUNCTIONS
    // ============================================================================

    const escapeHtml = (text) => {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    };

    const escapeRegex = (str) => {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    };

    // ============================================================================
    // PUBLIC API
    // ============================================================================

    return {
        init,
        search: performSearch,
        clearHistory: clearSearchHistory,
        getHistory: () => [...searchHistory],
        getActiveFilters: () => ({
            topics: Array.from(activeFilters.topics),
            difficulties: Array.from(activeFilters.difficulties),
            tags: Array.from(activeFilters.tags)
        }),
        clearFilters: () => {
            activeFilters.topics.clear();
            activeFilters.difficulties.clear();
            activeFilters.tags.clear();
            elements.filterCheckboxes.forEach(cb => cb.checked = false);
            elements.difficultyRadios.forEach(r => r.checked = false);
            updateTagChips();
            refreshSearch();
        },
        refresh: refreshSearch,
        rebuild: () => {
            initializeFuse();
            refreshSearch();
        }
    };
})();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Wait for Fuse.js to load
        if (typeof Fuse !== 'undefined') {
            SearchModule.init();
        } else {
            console.error('Fuse.js not loaded. Please include Fuse.js before search.js');
        }
    });
} else {
    if (typeof Fuse !== 'undefined') {
        SearchModule.init();
    } else {
        console.error('Fuse.js not loaded. Please include Fuse.js before search.js');
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SearchModule;
}

// Make available globally
window.SearchModule = SearchModule;
