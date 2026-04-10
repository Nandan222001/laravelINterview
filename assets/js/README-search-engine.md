# Advanced Search Engine

## Overview

The Advanced Search Engine module provides a comprehensive client-side search solution for the Laravel Interview Question Bank, featuring:

- **Full-text search** using Fuse.js for fuzzy matching
- **Auto-complete suggestions** with dropdown
- **Search term highlighting** using `<mark>` tags
- **Multiple simultaneous filters** (categories, tags, difficulty)
- **Search history** stored in localStorage
- **Keyboard navigation** with arrow keys and Enter to select

## Features

### 1. Fuzzy Full-Text Search

Uses Fuse.js to search across:
- Question text (40% weight)
- Answer text (30% weight)
- Tags (20% weight)
- Category (10% weight)

Supports typos and partial matches with configurable threshold.

### 2. Auto-Complete Suggestions

- Shows top 10 matching questions as you type
- Displays question preview, category, and difficulty
- Navigate with arrow keys (↑/↓)
- Select with Enter key
- Shows recent searches when input is empty

### 3. Search Term Highlighting

- Highlights matching terms in results using `<mark>` tags
- Works in both question and answer text
- Visual distinction with yellow background

### 4. Multiple Filter Support

Combine filters simultaneously:
- **Categories**: CMS, Web Tech, Database, PHP & Backend, Frontend, DevOps
- **Tags**: Filter by specific topics (can select multiple)
- **Difficulty**: Beginner, Intermediate, Advanced, Expert

All filters work together (AND logic for most filters).

### 5. Search History

- Stores last 20 searches in localStorage
- Shows recent searches in suggestions dropdown
- Click to re-run previous searches
- Clear history option available

### 6. Keyboard Navigation

- **`/`**: Focus search input
- **`Esc`**: Clear search and close suggestions
- **`↑`/`↓`**: Navigate suggestions/results
- **`Enter`**: Select suggestion or view result
- **`?`**: Show keyboard shortcuts modal

## Usage

### Initialization

```javascript
// Initialize the search engine
await SearchEngine.init({
    searchInput: document.getElementById('searchInput'),
    searchButton: document.getElementById('searchBtn'),
    suggestionsDropdown: document.getElementById('searchSuggestions'),
    resultsContainer: document.getElementById('searchResultsList'),
    activeFiltersContainer: document.getElementById('activeFilters'),
    noResultsMessage: document.getElementById('noResults')
});
```

### Performing Searches

```javascript
// Programmatic search
SearchEngine.performSearch('Laravel middleware');

// Add filters
SearchEngine.addCategoryFilter('PHP & Backend');
SearchEngine.addTagFilter('eloquent');
SearchEngine.addDifficultyFilter('advanced');

// Remove filters
SearchEngine.removeCategoryFilter('PHP & Backend');
SearchEngine.clearAllFilters();
```

### Getting Search Data

```javascript
// Get current search results
const results = SearchEngine.getSearchResults();

// Get search history
const history = SearchEngine.getSearchHistory();

// Get active filters
const filters = SearchEngine.getCurrentFilters();
```

## Configuration

Modify the configuration in `search-engine.js`:

```javascript
const config = {
    fuseOptions: {
        keys: [
            { name: 'question', weight: 0.4 },
            { name: 'answer', weight: 0.3 },
            { name: 'tags', weight: 0.2 },
            { name: 'category', weight: 0.1 }
        ],
        threshold: 0.4,           // 0.0 = exact match, 1.0 = match anything
        distance: 100,            // How far to search for matches
        minMatchCharLength: 2     // Minimum characters to trigger search
    },
    searchHistoryKey: 'search_history',
    maxHistoryItems: 20,
    maxSuggestions: 10,
    debounceDelay: 300           // ms delay for auto-complete
};
```

## Data Format

The search engine expects questions in this format:

```javascript
{
    id: 'unique-question-id',
    question: 'What is Laravel middleware?',
    answer: 'Middleware provides a convenient mechanism...',
    category: 'PHP & Backend',
    difficulty: 'intermediate',
    tags: ['laravel', 'middleware', 'http'],
    source: 'php-laravel-api-security'
}
```

## Events

The search engine emits custom events:

```javascript
// Listen for answer view requests
document.addEventListener('viewAnswer', (event) => {
    const question = event.detail.question;
    // Handle viewing the full answer
});
```

## CSS Classes

Required CSS classes for proper styling:

- `.search-suggestions` - Dropdown container
- `.suggestion-item` - Individual suggestion
- `.search-result-card` - Result card
- `.keyboard-selected` - Selected result
- `mark` - Highlighted text

## Browser Support

- Modern browsers with ES6+ support
- localStorage required for search history
- Fuse.js 6.6.2+ required

## Dependencies

- **Fuse.js**: Fuzzy search library
- **DataLoader**: Module to load question data from markdown files

## Performance

- **Debouncing**: 300ms delay for auto-complete to reduce unnecessary searches
- **Caching**: DataLoader caches parsed questions in localStorage
- **Lazy loading**: Results rendered on-demand
- **Virtual scrolling**: Recommended for 1000+ results (not implemented in v1)

## Accessibility

- ARIA labels on search inputs
- Keyboard navigation support
- Focus management
- Screen reader friendly

## Known Limitations

1. Search is client-side only (all data must be loaded first)
2. Performance may degrade with 10,000+ questions
3. No server-side indexing or persistence
4. Search history limited to current browser

## Future Enhancements

- [ ] Server-side search API integration
- [ ] Advanced query syntax (AND, OR, NOT operators)
- [ ] Save search queries
- [ ] Export search results
- [ ] Search analytics
- [ ] Voice search support
- [ ] Multi-language support
