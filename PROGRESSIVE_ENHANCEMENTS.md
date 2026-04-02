# Progressive Enhancements Documentation

This document describes the progressive enhancement features implemented in `assets/js/enhancements.js`.

## Features Overview

The progressive enhancements module provides the following features:

1. **Dark Mode** - System preference detection and manual toggle
2. **Print Styles** - Clean question/answer printing
3. **PDF Export** - Export selected questions to PDF using jsPDF
4. **Offline Support** - Service worker for caching static assets and question data
5. **Share Functionality** - Generate shareable links with question ID in URL hash

## 1. Dark Mode

### Features
- Automatic system preference detection
- Manual toggle with persistent storage (localStorage)
- Smooth theme transitions
- Support for `prefers-color-scheme` media query
- Custom event dispatching for theme changes

### Usage

The dark mode is automatically initialized when the page loads. A theme toggle button is automatically added to the navigation.

```javascript
// Toggle dark mode programmatically
ProgressiveEnhancements.DarkMode.toggle();

// Get current theme
const theme = ProgressiveEnhancements.DarkMode.getTheme(); // 'light' or 'dark'

// Apply specific theme
ProgressiveEnhancements.DarkMode.applyTheme('dark');
```

### Listen to theme changes

```javascript
document.addEventListener('theme:changed', (e) => {
    console.log('Theme changed to:', e.detail.theme);
});
```

### CSS Variables

The dark mode uses CSS custom properties defined in `assets/css/styles.css`:

```css
:root {
    /* Light mode colors */
}

[data-theme="dark"] {
    /* Dark mode colors */
}
```

## 2. Print Styles

### Features
- Hide non-essential UI elements (navigation, buttons, sidebars)
- Optimize typography for print (serif fonts, proper sizing)
- Prevent page breaks within questions and code blocks
- Add page headers and footers with page numbers
- Include URLs for external links
- Expand all accordions before printing
- Add print footer with timestamp and URL

### Usage

Print styles are automatically applied when printing. Use the browser's print function (Ctrl+P / Cmd+P) or:

```javascript
// Trigger print dialog
ProgressiveEnhancements.PrintStyles.handlePrint();

// Print only selected questions
const questionIds = ['1', '2', '3'];
ProgressiveEnhancements.PrintStyles.printSelectedQuestions(questionIds);
```

### Print Stylesheet

A dedicated print stylesheet is available at `assets/css/print.css` with optimized styles for:
- A4 paper size
- Proper margins and page breaks
- Clean typography
- Code block formatting
- Table layouts

## 3. PDF Export

### Features
- Export selected questions to PDF
- Uses jsPDF library (loaded dynamically)
- Includes question titles, tags, and answers
- Handles code blocks appropriately
- Proper pagination
- Success notification after export

### Usage

Select questions using checkboxes (if available) and click the export button, or:

```javascript
// Export selected questions to PDF
ProgressiveEnhancements.PDFExport.exportToPDF();
```

### Selection Behavior
- If questions have checkboxes with class `question-select-checkbox`, only checked questions are exported
- If no questions are selected, all visible questions are exported
- Hidden questions (filtered out) are not included

### PDF Format
- Title: "Laravel Interview Questions"
- Generated date and time
- Question number and title
- Tags (if present)
- Answer content
- Separator lines between questions
- Automatic pagination

## 4. Offline Support

### Features
- Service Worker registration and management
- Cache static assets (HTML, CSS, JavaScript, images)
- Cache question data (JSON files)
- Network-first with cache fallback for data requests
- Cache-first for static assets
- Offline indicator
- Background sync when connection is restored
- Automatic cache cleanup of stale entries
- Update notification when new version is available

### Usage

Offline support is automatically initialized when the page loads. The service worker is registered at `/service-worker.js`.

```javascript
// Cache question data manually
const questionData = { /* your data */ };
await ProgressiveEnhancements.OfflineSupport.cacheQuestionData(questionData);

// Get cached question data
const cachedData = await ProgressiveEnhancements.OfflineSupport.getCachedQuestionData();
```

### Service Worker

The service worker (`service-worker.js`) implements:

**Cache Strategy:**
- **Static Assets**: Cache-first (HTML, CSS, JS, images)
- **Data Requests**: Network-first with cache fallback (API endpoints, JSON)
- **Runtime Requests**: Network-first with cache fallback

**Cache Names:**
- `laravel-interview-bank-static-v1` - Static assets
- `laravel-interview-bank-data-v1` - Question data
- `laravel-interview-bank-runtime-v1` - Runtime requests

**Cache Expiration:**
- Static assets: 30 days
- Data: 1 day
- Runtime: 1 hour

**Offline Indicator:**
A fixed position indicator appears at the bottom of the screen when the connection is lost.

### Testing Offline Mode

1. Open DevTools (F12)
2. Go to Application > Service Workers
3. Check "Offline" or use Network tab throttling
4. Refresh the page
5. Verify that cached content loads

## 5. Share Functionality

### Features
- Generate shareable links with question ID in URL hash
- Use native Web Share API on mobile devices
- Fallback to clipboard copy on desktop
- Automatic scroll and highlight for shared questions
- Deep linking support
- Share notification with copied URL

### Usage

Add share buttons with the class `share-button` and `data-question-id` attribute:

```html
<button class="share-button" data-question-id="123">
    Share
</button>
```

Or use programmatically:

```javascript
// Share a specific question
ProgressiveEnhancements.ShareFunctionality.shareQuestion('123');

// Share current page
ProgressiveEnhancements.ShareFunctionality.shareCurrentPage();

// Generate question URL
const url = ProgressiveEnhancements.ShareFunctionality.generateQuestionUrl('123');
// Returns: "https://example.com/page.html#question-123"
```

### URL Format

Shared questions use the following URL format:
```
https://example.com/index.html#question-{questionId}
```

### Deep Linking

When a user visits a shared link:
1. The page scrolls to the question
2. The parent accordion expands (if needed)
3. The question is highlighted with a pulse animation
4. The highlight fades after 2 seconds

### Question Highlighting

Questions are highlighted using the `shared-question-highlight` class and CSS animation:

```css
@keyframes highlightPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    50% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0.3); }
}
```

## HTML Structure Requirements

### Question Items

For full functionality, question items should have the following structure:

```html
<div class="question-item" data-question-id="unique-id">
    <div class="question-header">
        <div class="question-title-text">
            <label>
                <input type="checkbox" class="question-select-checkbox">
                Question title here
            </label>
        </div>
        <div class="question-actions">
            <button class="question-action-btn share-button" 
                    data-question-id="unique-id" 
                    title="Share this question">
                📤
            </button>
        </div>
    </div>
    <div class="question-tags">
        <span class="question-tag">tag1</span>
        <span class="question-tag">tag2</span>
    </div>
    <div class="question-answer">
        <!-- Answer content -->
    </div>
</div>
```

### Export Button

Add an export to PDF button:

```html
<button class="export-pdf-button">
    📄 Export to PDF
</button>
```

### Print Button

Add a print button:

```html
<button class="print-button">
    🖨️ Print
</button>
```

## Browser Compatibility

### Dark Mode
- ✅ Modern browsers (Chrome 76+, Firefox 67+, Safari 12.1+, Edge 79+)
- ✅ `prefers-color-scheme` media query support

### Service Worker
- ✅ Chrome 40+
- ✅ Firefox 44+
- ✅ Safari 11.1+
- ✅ Edge 17+
- ❌ Internet Explorer (not supported)

### Web Share API
- ✅ Mobile browsers (iOS Safari 12.2+, Chrome Android 61+)
- ⚠️ Desktop browsers (limited support, fallback to clipboard)

### jsPDF
- ✅ All modern browsers
- Loaded dynamically from CDN: `https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js`

## Installation

1. Include the enhancements script in your HTML:

```html
<link rel="stylesheet" href="assets/css/styles.css">
<link rel="stylesheet" href="assets/css/print.css">
<script src="assets/js/app.js"></script>
<script src="assets/js/enhancements.js"></script>
```

2. Ensure the service worker file is at the root:
```
/service-worker.js
```

3. The module auto-initializes on DOM ready

## Configuration

All modules are initialized with default configuration. You can customize by calling init with options:

```javascript
// Example: Initialize with custom options
document.addEventListener('DOMContentLoaded', () => {
    ProgressiveEnhancements.DarkMode.init({
        // Custom dark mode options
    });
    
    ProgressiveEnhancements.PrintStyles.init({
        // Custom print options
    });
    
    // etc...
});
```

## Events

The enhancements module dispatches custom events:

```javascript
// Theme changed
document.addEventListener('theme:changed', (e) => {
    console.log('Theme:', e.detail.theme);
});

// Accordion opened (from app.js)
document.addEventListener('accordion:opened', (e) => {
    console.log('Accordion opened:', e.detail.index);
});

// Accordion closed (from app.js)
document.addEventListener('accordion:closed', (e) => {
    console.log('Accordion closed:', e.detail.index);
});
```

## Best Practices

### Dark Mode
- Use CSS custom properties for colors
- Test both themes thoroughly
- Respect user preferences
- Provide clear toggle UI

### Print Styles
- Test print output in different browsers
- Use `page-break-inside: avoid` for cohesive content
- Keep file size reasonable for printing
- Consider color vs. black & white printing

### PDF Export
- Limit the number of questions per export
- Test with various content types (code, images, tables)
- Handle large documents appropriately
- Provide progress feedback for long exports

### Offline Support
- Keep cache size reasonable
- Implement proper cache expiration
- Test offline scenarios thoroughly
- Provide clear offline indicators

### Share Functionality
- Use descriptive question titles
- Test deep links thoroughly
- Ensure URLs are valid and accessible
- Handle missing questions gracefully

## Troubleshooting

### Dark Mode not working
- Check if `data-theme` attribute is being set on `<html>`
- Verify CSS custom properties are defined
- Check browser console for errors
- Ensure localStorage is available

### Service Worker not registering
- Check if served over HTTPS (required) or localhost
- Verify `/service-worker.js` is accessible
- Check browser console for registration errors
- Ensure service worker scope is correct

### PDF Export failing
- Check browser console for jsPDF loading errors
- Verify CDN is accessible
- Check for JavaScript errors in content extraction
- Ensure questions have proper structure

### Share not working
- Check if question IDs are unique
- Verify `data-question-id` attributes are set
- Test clipboard API permissions
- Check browser compatibility

## Performance Considerations

- Service worker caching reduces network requests
- Dark mode uses CSS-only transitions (no JavaScript for theme application)
- PDF export loads jsPDF only when needed (dynamic import)
- Share functionality uses native APIs when available
- Print styles are in separate stylesheet for better caching

## Security Considerations

- Service worker only caches same-origin resources
- Share functionality validates URLs
- No sensitive data cached by default
- CSP headers should allow service worker
- HTTPS required for service worker in production

## Future Enhancements

Potential additions:
- Push notifications for new questions
- Background sync for offline edits
- IndexedDB for larger datasets
- PWA manifest for installable app
- Advanced PDF customization options
- Social media share integration
- Analytics for shared questions

## License

Part of the Laravel Interview Question Bank project.

## Support

For issues or questions, please refer to the main project documentation or create an issue in the repository.
