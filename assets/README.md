# Assets Directory

This directory contains JavaScript modules and CSS styles for the Laravel Interview Question Bank application.

## Structure

```
assets/
├── js/
│   └── app.js              # Main application JavaScript with all modules
├── css/
│   └── syntax-highlighting.css  # Styles for code syntax highlighting
└── README.md               # This file
```

## JavaScript Modules (assets/js/app.js)

The `app.js` file contains four main modules and an application controller:

### 1. MarkdownParser Module

Converts markdown text to HTML with syntax highlighting for code blocks.

**Features:**
- Full markdown parsing (headers, lists, blockquotes, links, images)
- Code block syntax highlighting for multiple languages (JavaScript, PHP, Python, Java, SQL)
- Inline code formatting
- Bold, italic, and other inline styles
- Automatic HTML escaping for security

**API:**
```javascript
MarkdownParser.parse(markdownText)        // Convert markdown to HTML
MarkdownParser.parseInlineElements(text)  // Parse inline markdown only
MarkdownParser.highlightCode(code, lang)  // Highlight code with syntax
```

**Example:**
```javascript
const html = MarkdownParser.parse('# Hello World\nThis is **bold** text');
```

### 2. AccordionController Module

Manages accordion expand/collapse with smooth transitions, keyboard navigation, and deep linking.

**Features:**
- Smooth expand/collapse animations
- Keyboard navigation (Arrow keys, Home, End, Enter, Space)
- Deep linking to specific questions via URL hash
- ARIA attributes for accessibility
- Single or multi-expand modes
- Custom events (accordion:opened, accordion:closed)

**API:**
```javascript
AccordionController.init(options)   // Initialize with config
AccordionController.toggle(index)   // Toggle accordion by index
AccordionController.open(index)     // Open specific accordion
AccordionController.close(index)    // Close specific accordion
AccordionController.closeAll()      // Close all accordions
AccordionController.openAll()       // Open all accordions
AccordionController.getState()      // Get current state of all accordions
AccordionController.destroy()       // Clean up event listeners
```

**Configuration Options:**
```javascript
{
    animationDuration: 300,     // Animation duration in ms
    multiExpand: false,         // Allow multiple accordions open
    keyboardNavigation: true,   // Enable keyboard controls
    deepLinking: true,          // Enable URL hash deep linking
    scrollOffset: 100           // Scroll offset for deep links
}
```

**Keyboard Shortcuts:**
- `Enter` or `Space`: Toggle accordion
- `Arrow Down`: Focus next accordion
- `Arrow Up`: Focus previous accordion
- `Home`: Focus first accordion
- `End`: Focus last accordion

### 3. SearchEngine Module

Implements fuzzy search across questions and answers with highlighting.

**Features:**
- Fuzzy matching using Levenshtein distance algorithm
- Multi-field search (title, content, tags, topic, difficulty)
- Weighted scoring for different fields
- Configurable similarity threshold
- Search result highlighting
- Case-insensitive search option

**API:**
```javascript
SearchEngine.init(options)           // Initialize with config
SearchEngine.search(query)           // Perform search, returns results
SearchEngine.highlight(text, query)  // Highlight matches in text
SearchEngine.updateIndex()           // Rebuild search index
SearchEngine.getIndex()              // Get current search index
```

**Configuration Options:**
```javascript
{
    threshold: 0.4,             // Minimum similarity score (0-1)
    caseSensitive: false,       // Case-sensitive search
    searchFields: [...],        // Fields to search in
    minQueryLength: 2,          // Minimum query length
    maxResults: 50              // Maximum results to return
}
```

**Search Algorithm:**
1. Exact match: score = 1.0
2. Contains query: score = 0.9 - position penalty
3. Fuzzy match: Levenshtein distance similarity
4. Word match: Checks if all query words exist in text
5. Field weighting: Title (1.5x), Tags (1.3x), Topic (1.2x), Difficulty (1.0x), Content (0.8x)

### 4. FilterManager Module

Multi-select filtering system for topics, difficulty levels, and tags.

**Features:**
- Multi-select checkboxes for filtering
- AND/OR combine modes
- Smooth show/hide animations
- Empty state messaging
- Filter statistics
- Custom filter events

**API:**
```javascript
FilterManager.init(options)           // Initialize with config
FilterManager.applyFilters()          // Apply current filter selection
FilterManager.clearFilters()          // Clear all active filters
FilterManager.setFilter(type, values) // Programmatically set filters
FilterManager.getActiveFilters()      // Get current filter state
FilterManager.getFilterStats()        // Get item counts per filter
FilterManager.toggleCombineMode()     // Toggle AND/OR mode
```

**Configuration Options:**
```javascript
{
    animationDuration: 300,     // Animation duration in ms
    showEmptyMessage: true,     // Show message when no results
    emptyMessage: '...',        // Custom empty message
    combineMode: 'AND'          // 'AND' or 'OR' filter combination
}
```

**Events:**
- `filters:applied`: Fired when filters are applied
  - Detail: `{ filters, visibleCount, totalCount }`

### 5. App Controller

Main application controller that initializes all modules and manages the UI.

**Features:**
- Automatic module initialization on DOM ready
- Integrated search UI with debouncing
- Mobile menu toggle
- Markdown rendering for dynamic content
- Global API access via `window.InterviewBankApp`

**API:**
```javascript
App.init()                      // Initialize application
App.MarkdownParser              // Access MarkdownParser module
App.AccordionController         // Access AccordionController module
App.SearchEngine                // Access SearchEngine module
App.FilterManager               // Access FilterManager module
```

## CSS Styles (assets/css/syntax-highlighting.css)

Provides comprehensive styling for:

- Code block syntax highlighting (keywords, strings, numbers, comments)
- Inline code formatting
- Search result highlighting (mark tags)
- Markdown elements (blockquotes, headings, lists, tables)
- Responsive design for mobile devices

**Supported Languages:**
- JavaScript/JS
- PHP
- Python
- Java
- SQL
- Bash/Shell

## Usage

### Include in HTML

Add to your HTML file:

```html
<!-- CSS -->
<link rel="stylesheet" href="assets/css/syntax-highlighting.css">

<!-- JavaScript -->
<script src="assets/js/app.js"></script>
```

### Accessing Modules

All modules are available globally:

```javascript
// Via App controller
window.InterviewBankApp.SearchEngine.search('laravel');

// Direct module access (after initialization)
AccordionController.open(0);
SearchEngine.search('payment gateway');
FilterManager.setFilter('topics', ['php-laravel']);
```

### HTML Structure Requirements

The JavaScript modules expect specific HTML structure:

#### Accordion Items
```html
<div class="accordion-item" data-topic="php-laravel" data-difficulty="advanced">
    <div class="accordion-header">
        <h3>Question Title</h3>
        <span class="accordion-icon">▼</span>
    </div>
    <div class="accordion-content">
        <div class="accordion-body">
            <!-- Content here -->
        </div>
    </div>
</div>
```

#### Filter Checkboxes
```html
<input type="checkbox" name="topic" value="php-laravel">
<input type="checkbox" name="difficulty" value="advanced">
<input type="checkbox" name="tag" value="security">
```

#### Search Bar
```html
<div class="search-bar">
    <input type="search" placeholder="Search...">
    <button type="button">Search</button>
</div>
```

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- ES6+ support required
- No external dependencies

## Performance

- Lazy loading: Modules only initialize on DOM ready
- Debounced search: 300ms delay to reduce processing
- Efficient DOM operations: Minimal reflows and repaints
- Indexed search: Pre-built search index for fast queries

## Accessibility

- ARIA attributes for accordions
- Keyboard navigation support
- Focus management
- Screen reader friendly
- Semantic HTML structure

## Customization

All modules accept configuration options during initialization:

```javascript
App.AccordionController.init({
    animationDuration: 500,
    multiExpand: true
});

App.SearchEngine.init({
    threshold: 0.5,
    maxResults: 100
});

App.FilterManager.init({
    combineMode: 'OR',
    showEmptyMessage: false
});
```

## Development

To extend or modify the modules:

1. Edit `assets/js/app.js`
2. Each module is self-contained in an IIFE
3. Add new methods to the returned object
4. Update this README with changes

## License

Part of the Laravel Interview Question Bank project.
