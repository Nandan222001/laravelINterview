# Search Engine Quick Start Guide

## Installation

### 1. Include Dependencies

Add Fuse.js to your HTML file:

```html
<!-- Include Fuse.js for fuzzy search -->
<script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2"></script>

<!-- Include Data Loader -->
<script src="assets/js/data-loader.js"></script>

<!-- Include Search Engine -->
<script src="assets/js/search-engine.js"></script>
```

### 2. HTML Structure

Add the required HTML elements:

```html
<div class="search-filter-section">
    <div class="search-box">
        <div class="search-input-wrapper">
            <input type="search" id="searchInput" 
                   placeholder="Search..." 
                   autocomplete="off">
            <div class="search-suggestions" id="searchSuggestions"></div>
        </div>
        <button type="button" id="searchBtn">🔍 Search</button>
    </div>
    
    <div id="activeFilters" style="display: none;"></div>
</div>

<div class="search-results-container" id="searchResultsContainer">
    <div id="searchResultsList"></div>
</div>

<div class="no-results" id="noResults" style="display: none;">
    <h3>No Results Found</h3>
</div>
```

### 3. Initialize the Search Engine

```javascript
document.addEventListener('DOMContentLoaded', async () => {
    await SearchEngine.init({
        searchInput: document.getElementById('searchInput'),
        searchButton: document.getElementById('searchBtn'),
        suggestionsDropdown: document.getElementById('searchSuggestions'),
        resultsContainer: document.getElementById('searchResultsList'),
        activeFiltersContainer: document.getElementById('activeFilters'),
        noResultsMessage: document.getElementById('noResults')
    });
    
    console.log('Search engine ready!');
});
```

## Basic Usage

### Search

```javascript
// Perform a search
SearchEngine.performSearch('Laravel middleware');
```

### Filters

```javascript
// Add filters
SearchEngine.addCategoryFilter('PHP Laravel API Security');
SearchEngine.addTagFilter('security');
SearchEngine.addDifficultyFilter('advanced');

// Remove filters
SearchEngine.removeCategoryFilter('PHP Laravel API Security');
SearchEngine.removeTagFilter('security');

// Clear all filters
SearchEngine.clearAllFilters();
```

### Get Results

```javascript
// Get current results
const results = SearchEngine.getSearchResults();

// Get search history
const history = SearchEngine.getSearchHistory();

// Get active filters
const filters = SearchEngine.getCurrentFilters();
```

## Keyboard Shortcuts

- **`/`** - Focus search input
- **`↑`/`↓`** - Navigate suggestions/results
- **`Enter`** - Select suggestion or open result
- **`Esc`** - Clear search and close suggestions

## Features Overview

### 1. Auto-Complete
- Shows suggestions as you type
- Displays question preview, category, and difficulty
- Navigate with arrow keys
- Shows recent searches when input is empty

### 2. Fuzzy Search
- Tolerates typos
- Matches partial terms
- Configurable threshold (default: 0.4)

### 3. Highlighting
- Automatically highlights search terms in results
- Uses `<mark>` tags for styling

### 4. Search History
- Stores last 20 searches
- Persists in localStorage
- Clear history option available

### 5. Multiple Filters
- Categories (can select multiple)
- Tags (can select multiple)
- Difficulty levels (can select multiple)
- All filters work together

## Customization

### Configure Search Options

Edit `assets/js/search-engine.js`:

```javascript
const config = {
    fuseOptions: {
        threshold: 0.4,          // 0 = exact, 1 = anything
        distance: 100,           // Search distance
        minMatchCharLength: 2    // Minimum chars
    },
    maxHistoryItems: 20,         // History size
    maxSuggestions: 10,          // Dropdown size
    debounceDelay: 300           // Input delay (ms)
};
```

### Style the Results

Add CSS:

```css
/* Suggestions dropdown */
.search-suggestions {
    position: absolute;
    background: white;
    border: 2px solid #667eea;
    max-height: 400px;
    overflow-y: auto;
}

/* Highlighted terms */
mark {
    background: #fff3cd;
    color: #856404;
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
}

/* Result cards */
.search-result-card {
    background: white;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 8px;
}
```

## Events

### Listen for Answer Views

```javascript
document.addEventListener('viewAnswer', (event) => {
    const question = event.detail.question;
    // Handle viewing the answer
    console.log('View:', question.question);
});
```

## Troubleshooting

### Search Not Working?

1. Check browser console for errors
2. Ensure Fuse.js is loaded
3. Verify data-loader.js loaded questions
4. Check if DOM elements exist

### No Suggestions Appearing?

1. Check if input has `autocomplete="off"`
2. Verify suggestions dropdown element exists
3. Check CSS - dropdown might be hidden
4. Look for JavaScript errors

### History Not Persisting?

1. Check if localStorage is enabled
2. Verify no private/incognito mode
3. Check browser storage quota

## Performance Tips

1. **Limit results**: Modify `maxSuggestions` config
2. **Adjust debounce**: Increase `debounceDelay` for slower typing
3. **Reduce threshold**: Lower value = stricter matching = fewer results
4. **Cache wisely**: DataLoader caches parsed data automatically

## Example Integration

See `assets/js/search-engine-demo.html` for a complete working example.

## Support

For issues or questions:
1. Check the README: `assets/js/README-search-engine.md`
2. Review demo: `assets/js/search-engine-demo.html`
3. Check browser console for errors
4. Verify all dependencies are loaded
