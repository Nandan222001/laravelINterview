# JavaScript Assets Directory

## Core Modules

### Search & Data
- **`search-engine.js`** - Advanced search engine with Fuse.js integration
- **`data-loader.js`** - Loads and parses question data from markdown files
- **`search.js`** - Legacy search functionality

### UI Components
- **`ui-controller.js`** - UI state management and interactions
- **`filter-controller.js`** - Filter state management
- **`bookmark-system.js`** - Bookmark functionality
- **`progress-tracker.js`** - User progress tracking
- **`enhancements.js`** - UI enhancements and utilities

### Content Rendering
- **`markdown-renderer.js`** - Markdown to HTML conversion
- **`app.js`** - Main application initialization

### Question Data
- **`database-questions.js`** - Database-related questions
- **`realtime-questions.js`** - Realtime/WebSocket questions
- **`php-laravel-questions.js`** - PHP/Laravel questions (legacy)

## Documentation

### Search Engine
- **`README-search-engine.md`** - Complete search engine documentation
- **`SEARCH_ENGINE_QUICKSTART.md`** - Quick start guide
- **`IMPLEMENTATION_SUMMARY.md`** - Implementation details and statistics

### Other Docs
- **`README-markdown-renderer.md`** - Markdown renderer documentation

## Demos & Tests
- **`search-engine-demo.html`** - Interactive search engine demo
- **`search-engine-tests.js`** - Automated test suite

## Quick Links

### For Users
- [Search Engine Quick Start](SEARCH_ENGINE_QUICKSTART.md)
- [Search Demo](search-engine-demo.html)

### For Developers
- [Search Engine API](README-search-engine.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Run Tests](search-engine-tests.js) - Add `?test=true` to URL

## Usage

### Initialize Search Engine
```javascript
await SearchEngine.init({
    searchInput: document.getElementById('searchInput'),
    searchButton: document.getElementById('searchBtn'),
    suggestionsDropdown: document.getElementById('searchSuggestions'),
    resultsContainer: document.getElementById('searchResultsList'),
    activeFiltersContainer: document.getElementById('activeFilters'),
    noResultsMessage: document.getElementById('noResults')
});
```

### Load Question Data
```javascript
const { questions, searchIndex } = await DataLoader.loadData();
```

### Perform Search
```javascript
SearchEngine.performSearch('Laravel middleware');
```

## Dependencies

### External Libraries
- **Fuse.js** (6.6.2+) - Fuzzy search (loaded via CDN)

### Internal Dependencies
- Data files in `/interview-bank/` directory
- Markdown question files

## Browser Support
- Modern browsers with ES6+ support
- localStorage required for caching and history
- No IE11 support

## File Sizes (Approximate)
- `search-engine.js`: ~40 KB
- `data-loader.js`: ~28 KB
- `markdown-renderer.js`: ~15 KB
- Total (minified): ~120 KB

## Development

### Adding New Features
1. Create new module file
2. Follow existing patterns
3. Add documentation
4. Update this README
5. Add tests if applicable

### Testing
```javascript
// Run all tests
SearchEngineTests.runAllTests();

// Run specific test
SearchEngineTests.runTest('fuzzy');
```

## Performance Notes
- Data is cached in localStorage for 24 hours
- Debounced search input (300ms)
- Lazy loading of results
- Efficient filtering with Set data structures

## Security
- All user input is escaped
- No eval() or dynamic code execution
- localStorage quota handling
- XSS prevention measures

## License
Part of the Laravel Interview Question Bank project.
