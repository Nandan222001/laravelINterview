# Code Snippet Management System

A comprehensive code snippet management system with syntax highlighting support for Laravel applications.

## Features

### 🎨 Syntax Highlighting
- **Server-Side Pre-rendering**: Code highlighted on the server using `highlight.php`
- **Client-Side Rendering**: Support for both `highlight.js` and `Prism.js`
- **10+ Themes**: Multiple color schemes including Atom One Dark, GitHub, Monokai, VS2015, and more
- **190+ Languages**: Comprehensive language support through highlight.php

### 🔍 Language Detection
- Automatic programming language detection
- Confidence scoring (relevance)
- Alternative language suggestions
- Support for language aliases

### 📝 Code Snippet Management
- Full CRUD operations for code snippets
- Multiple snippets per question
- Snippet ordering and reordering
- Bulk creation and deletion
- Snippet duplication across questions

### 🏷️ Categorization
- **5 Snippet Types**: example, solution, test, template, starter
- Custom metadata support
- Tagging support through existing tag system
- Filtering by language, type, and question

### ⚡ Performance
- Pre-rendered HTML for faster loading
- Optional caching of highlighted code
- Efficient database queries
- Lazy loading support

## Installation

### 1. Install Dependencies

```bash
composer require scrivo/highlight.php
```

### 2. Configuration

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=config
```

Edit `config/syntax-highlighting.php` to customize settings:

```php
return [
    'default_library' => 'highlight.js',
    'pre_render' => true,
    'default_theme' => 'atom-one-dark',
    'auto_detect_language' => true,
    'cache_highlighted' => true,
];
```

### 3. Run Migrations

The `code_snippets` table should already exist. If not:

```bash
php artisan migrate
```

### 4. Seed Sample Data (Optional)

```bash
php artisan db:seed --class=CodeSnippetSeeder
```

## API Endpoints

### Core CRUD Operations

```
GET    /api/code-snippets              - List all snippets
GET    /api/code-snippets/{id}         - Get single snippet
POST   /api/code-snippets              - Create snippet
PUT    /api/code-snippets/{id}         - Update snippet
DELETE /api/code-snippets/{id}         - Delete snippet
```

### Special Operations

```
POST   /api/code-snippets/detect-language      - Detect language
POST   /api/code-snippets/highlight            - Highlight code
GET    /api/code-snippets/supported-languages  - List languages
POST   /api/code-snippets/reorder              - Reorder snippets
POST   /api/code-snippets/bulk-create          - Create multiple
POST   /api/code-snippets/bulk-delete          - Delete multiple
POST   /api/code-snippets/{id}/duplicate       - Duplicate snippet
GET    /api/code-snippets/executable           - Get executable snippets
```

### Syntax Highlighting Utilities

```
GET    /api/syntax-highlighting/themes              - Available themes
GET    /api/syntax-highlighting/client-instructions - Implementation guide
GET    /api/syntax-highlighting/rendering-options   - Rendering strategies
GET    /api/syntax-highlighting/language-aliases    - Language aliases
```

## Usage Examples

### Creating a Code Snippet

```javascript
const response = await fetch('/api/code-snippets', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer YOUR_TOKEN',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        question_id: 5,
        title: 'JavaScript Array Filter',
        code: 'const filtered = arr.filter(x => x > 5);',
        language: 'javascript', // Optional - auto-detected if omitted
        type: 'example',
        is_executable: true
    })
});

const data = await response.json();
```

### Fetching with Pre-Rendered HTML

```javascript
const response = await fetch('/api/code-snippets?question_id=5&render_html=true');
const data = await response.json();

// Use the highlighted code directly
data.data.forEach(snippet => {
    const html = `
        <link rel="stylesheet" href="${snippet.rendering_instructions.stylesheet}">
        <pre><code class="hljs">
            ${snippet.highlighted_code}
        </code></pre>
    `;
});
```

### Client-Side Rendering with highlight.js

```html
<!-- Include highlight.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

<!-- Your code -->
<pre><code class="language-javascript">
const greeting = "Hello, World!";
console.log(greeting);
</code></pre>

<script>
    hljs.highlightAll();
</script>
```

### Language Detection

```javascript
const response = await fetch('/api/code-snippets/detect-language', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer YOUR_TOKEN',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        code: 'def hello():\n    print("Hello")'
    })
});

const data = await response.json();
console.log(data.data.language); // "python"
```

### Bulk Creation

```javascript
const response = await fetch('/api/code-snippets/bulk-create', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer YOUR_TOKEN',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        question_id: 5,
        snippets: [
            {
                title: 'JavaScript Example',
                code: 'console.log("Hello");',
                type: 'example'
            },
            {
                title: 'Python Example',
                code: 'print("Hello")',
                type: 'example'
            }
        ]
    })
});
```

## Service Layer Usage

### In Laravel Controllers

```php
use App\Services\CodeSnippetService;

class QuestionController extends Controller
{
    public function __construct(
        private CodeSnippetService $codeSnippetService
    ) {}
    
    public function show($id)
    {
        $snippets = $this->codeSnippetService->getSnippetsWithHighlighting($id);
        
        return view('question.show', compact('snippets'));
    }
}
```

### Direct Service Methods

```php
// Create snippet with auto language detection
$snippet = $codeSnippetService->createSnippet([
    'question_id' => 5,
    'code' => 'const x = 10;',
    // language auto-detected
]);

// Highlight code manually
$result = $codeSnippetService->highlightCode(
    'print("Hello")', 
    'python'
);

// Detect language
$language = $codeSnippetService->detectLanguage($code);

// Get supported languages
$languages = $codeSnippetService->getSupportedLanguages();
```

## Snippet Types

### Example (`example`)
Example code to illustrate concepts or demonstrate usage.

### Solution (`solution`)
Complete solution code for a problem or exercise.

### Test (`test`)
Test cases, unit tests, or validation code.

### Template (`template`)
Code templates, boilerplate, or scaffolding.

### Starter (`starter`)
Starter code for users to complete or modify.

## Rendering Strategies

### Pre-Rendered (Server-Side)

**Advantages:**
- No JavaScript required
- Faster initial render
- Better for SEO
- Works with JS disabled

**Usage:**
```php
// In controller
$snippet = $codeSnippetService->getSnippetWithHighlighting($id, true);

// In view
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
<pre><code class="hljs">{!! $snippet->highlighted_code !!}</code></pre>
```

### Client-Side Rendering

**Advantages:**
- Smaller API responses
- Dynamic theme switching
- Plugin ecosystem
- Line numbers, copy buttons, etc.

**Usage:**
```javascript
// Fetch without pre-rendering
const response = await fetch('/api/code-snippets?render_html=false');

// Render with highlight.js
hljs.highlightAll();
```

## Themes

Available themes for highlight.js:
- default
- github / github-dark
- monokai
- vs2015
- atom-one-dark / atom-one-light
- dracula
- nord
- tokyo-night-dark
- solarized-dark / solarized-light

Change theme dynamically:

```javascript
function changeTheme(theme) {
    const link = document.querySelector('link[href*="highlight.js"]');
    link.href = `https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/${theme}.min.css`;
}
```

## Testing

### Factory Usage

```php
use App\Models\CodeSnippet;

// Create basic snippet
$snippet = CodeSnippet::factory()->create();

// Create JavaScript snippet
$snippet = CodeSnippet::factory()->javascript()->create();

// Create executable snippet
$snippet = CodeSnippet::factory()->executable()->create();

// Create solution snippet
$snippet = CodeSnippet::factory()->solution()->create();
```

### Test Examples

```php
public function test_can_create_code_snippet()
{
    $user = User::factory()->create();
    $question = Question::factory()->create();
    
    $response = $this->actingAs($user)->postJson('/api/code-snippets', [
        'question_id' => $question->id,
        'code' => 'console.log("test");',
        'type' => 'example',
    ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('code_snippets', [
        'question_id' => $question->id,
    ]);
}
```

## Performance Optimization

### Enable Caching

```php
// In config/syntax-highlighting.php
'cache_highlighted' => true,
'cache_ttl' => 3600, // 1 hour
```

### Lazy Loading

```javascript
// Load snippets on demand
const snippets = await fetch('/api/code-snippets?question_id=5');

// Only highlight visible code
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            hljs.highlightElement(entry.target);
        }
    });
});
```

## Browser Support

- All modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (with polyfills)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Language Not Detected Correctly

```javascript
// Manually specify language
{
    "code": "...",
    "language": "javascript"
}
```

### Themes Not Loading

Ensure the CDN link is accessible and correct:
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
```

### Highlighting Not Working

1. Ensure highlight.js is loaded before calling `hljs.highlightAll()`
2. Check code is wrapped in `<code>` tags with proper language class
3. Verify no JavaScript errors in console

## Demo

Visit `/code-snippet-example.html` for a live demonstration of all features.

## Documentation

- **API Documentation**: See `CODE_SNIPPET_API.md`
- **highlight.js Docs**: https://highlightjs.org/
- **Prism.js Docs**: https://prismjs.com/

## License

This code snippet management system is part of the Laravel application and follows the same license.
