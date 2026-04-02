# Markdown Renderer Module

A comprehensive JavaScript module for rendering Markdown to HTML with support for:
- **marked.js** integration for Markdown-to-HTML conversion
- **Prism.js** for syntax highlighting (PHP, JavaScript, SQL, Bash, YAML, and more)
- **Mermaid.js** for rendering architecture diagrams from code blocks
- Custom rendering for special elements (admonitions, callouts, metadata badges)

## Features

### 1. Marked.js Integration
- GitHub Flavored Markdown (GFM) support
- Smart lists and typography
- Custom heading renderer with anchor links
- Secure link and image rendering

### 2. Prism.js Syntax Highlighting
Supports the following languages:
- **PHP** - Full Laravel/PHP syntax support
- **JavaScript/TypeScript** - Modern JS/TS features
- **SQL** - Database queries
- **Bash/Shell** - Command-line scripts
- **YAML** - Configuration files
- **JSON, CSS, HTML, XML** - Web technologies
- **Python, Java, C#, Go, Rust, Ruby, Swift** - Additional languages

### 3. Mermaid.js Diagram Rendering
Renders diagrams directly from code blocks:
```markdown
\`\`\`mermaid
graph TD
    A[Client] --> B[Load Balancer]
    B --> C[Server01]
    B --> D[Server02]
\`\`\`
```

### 4. Admonitions/Callouts
Support for GitHub-style admonitions:

```markdown
> [!NOTE] This is a note
> Important information here

> [!TIP] Pro Tip
> Helpful advice

> [!WARNING] Be Careful
> Important warning

> [!DANGER] Critical
> Critical information

> [!INFO] Information
> Additional context
```

Or using code block syntax:
```markdown
\`\`\`admonition
[NOTE] Custom Title
Your content here with **markdown** support.
\`\`\`
```

### 5. Metadata Badges
Frontmatter-style metadata for difficulty, tags, and more:

```markdown
---
difficulty: Advanced
tags: [Laravel, API, Security]
category: Backend
timeEstimate: 45 minutes
---

# Your Content Here
```

Renders as badges at the top of the content:
- **Difficulty**: `Beginner`, `Intermediate`, `Advanced`, `Expert`
- **Tags**: Array of custom tags
- **Category**: Content category
- **Time Estimate**: Estimated reading/completion time

## Usage

### Basic Usage

#### 1. Include Dependencies
```html
<!-- Marked.js -->
<script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>

<!-- Prism.js (Core + Languages) -->
<link href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-sql.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-bash.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-yaml.min.js"></script>

<!-- Mermaid.js -->
<script src="https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js"></script>

<!-- Markdown Renderer -->
<link href="assets/css/styles.css" rel="stylesheet">
<script src="assets/js/markdown-renderer.js"></script>
```

#### 2. Initialize the Renderer
```javascript
// Auto-initialization on DOMContentLoaded
// The module initializes automatically

// Or manually initialize with options
MarkdownRenderer.init({
    customElements: {
        admonitions: true,
        badges: true,
        metadataBlocks: true,
        copyButtons: true,
        lineNumbers: false
    }
});
```

#### 3. Render Markdown
```javascript
// Method 1: Render to string
const markdown = '# Hello World\n\nThis is **bold** text.';
const html = MarkdownRenderer.render(markdown);
console.log(html);

// Method 2: Render into element
const markdownContent = `
# API Security Best Practices

## Authentication
- Use OAuth 2.0 or JWT
- Implement rate limiting
- Enable HTTPS only
`;

MarkdownRenderer.renderInto('#content', markdownContent);

// Method 3: Auto-render with data attribute
// <div data-markdown="# Auto Render\n\nContent here"></div>
// Automatically rendered on initialization
```

### Advanced Usage

#### Custom Configuration
```javascript
MarkdownRenderer.configure({
    marked: {
        gfm: true,
        breaks: true,
        smartypants: true
    },
    mermaid: {
        theme: 'dark',
        themeVariables: {
            primaryColor: '#3b82f6'
        }
    },
    customElements: {
        admonitions: true,
        badges: true,
        metadataBlocks: true,
        copyButtons: true,
        lineNumbers: true
    }
});
```

#### Parse Metadata
```javascript
const markdownWithMetadata = `
---
difficulty: Advanced
tags: [Laravel, PHP, Security]
category: Backend Development
timeEstimate: 1 hour
---

# Content Here
`;

const { metadata, content } = MarkdownRenderer.parseMetadata(markdownWithMetadata);
console.log(metadata);
// { difficulty: 'Advanced', tags: ['Laravel', 'PHP', 'Security'], ... }
```

#### Render Metadata Badges
```javascript
const metadata = {
    difficulty: 'Advanced',
    tags: ['Laravel', 'API', 'Security'],
    category: 'Backend',
    timeEstimate: '45 minutes'
};

const badgesHtml = MarkdownRenderer.renderMetadataBadges(metadata);
```

#### Manual Mermaid Rendering
```javascript
// After dynamic content updates
MarkdownRenderer.renderMermaidDiagrams();
```

## Examples

### Example 1: Laravel API Documentation
```javascript
const apiDoc = `
---
difficulty: Intermediate
tags: [Laravel, API, REST]
category: Backend Development
timeEstimate: 30 minutes
---

# Laravel REST API Setup

## Installation

\`\`\`bash
composer create-project laravel/laravel my-api
cd my-api
php artisan serve
\`\`\`

## Create API Routes

\`\`\`php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
\`\`\`

> [!TIP] Best Practice
> Always use middleware for authentication and authorization.

## Database Schema

\`\`\`mermaid
erDiagram
    USER ||--o{ POST : creates
    USER {
        int id
        string name
        string email
    }
    POST {
        int id
        string title
        text content
        int user_id
    }
\`\`\`
`;

MarkdownRenderer.renderInto('#api-docs', apiDoc);
```

### Example 2: Code with Syntax Highlighting
```javascript
const codeExample = `
# Payment Gateway Integration

\`\`\`php
<?php

namespace App\\Services;

use Illuminate\\Support\\Facades\\Http;

class PaymentService
{
    public function processPayment(array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('payment.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.payment.com/v1/charge', [
            'amount' => $data['amount'],
            'currency' => 'USD',
            'source' => $data['token'],
        ]);

        return $response->json();
    }
}
\`\`\`

> [!WARNING] Security Notice
> Never expose API keys in client-side code. Always use server-side processing.
`;

MarkdownRenderer.renderInto('#payment-docs', codeExample);
```

### Example 3: Architecture Diagram
```javascript
const architectureDocs = `
# Microservices Architecture

\`\`\`mermaid
graph TB
    Client[Client Application]
    Gateway[API Gateway]
    Auth[Auth Service]
    User[User Service]
    Order[Order Service]
    Payment[Payment Service]
    DB1[(User DB)]
    DB2[(Order DB)]
    Cache[Redis Cache]
    
    Client --> Gateway
    Gateway --> Auth
    Gateway --> User
    Gateway --> Order
    Gateway --> Payment
    
    User --> DB1
    Order --> DB2
    Payment --> Cache
    
    Auth -.-> Cache
\`\`\`
`;

MarkdownRenderer.renderInto('#architecture', architectureDocs);
```

## API Reference

### Methods

#### `init(options)`
Initialize the renderer with optional configuration.
```javascript
MarkdownRenderer.init({
    customElements: { /* ... */ }
});
```

#### `render(markdown, options)`
Convert markdown string to HTML.
```javascript
const html = MarkdownRenderer.render('# Title\n\nContent');
```

#### `renderInto(element, markdown, options)`
Render markdown into a specific DOM element.
```javascript
MarkdownRenderer.renderInto('#content', markdownString);
```

#### `configure(options)`
Update configuration at runtime.
```javascript
MarkdownRenderer.configure({ /* options */ });
```

#### `renderMermaidDiagrams()`
Manually trigger Mermaid diagram rendering.
```javascript
MarkdownRenderer.renderMermaidDiagrams();
```

#### `parseMetadata(markdown)`
Extract frontmatter metadata from markdown.
```javascript
const { metadata, content } = MarkdownRenderer.parseMetadata(markdownString);
```

#### `renderMetadataBadges(metadata)`
Generate HTML for metadata badges.
```javascript
const badges = MarkdownRenderer.renderMetadataBadges({
    difficulty: 'Advanced',
    tags: ['PHP', 'Laravel']
});
```

#### `renderAdmonition(content, type)`
Render an admonition/callout block.
```javascript
const html = MarkdownRenderer.renderAdmonition('[NOTE] Title\nContent', 'note');
```

#### `renderCodeBlock(code, language, isEscaped)`
Render a code block with syntax highlighting.
```javascript
const html = MarkdownRenderer.renderCodeBlock('<?php echo "Hello";', 'php', false);
```

#### `escapeHtml(text)`
Escape HTML special characters.
```javascript
const safe = MarkdownRenderer.escapeHtml('<script>alert("xss")</script>');
```

#### `generateId(text)`
Generate a URL-safe ID from text.
```javascript
const id = MarkdownRenderer.generateId('Hello World!'); // "hello-world"
```

## Styling

The module includes comprehensive CSS in `assets/css/styles.css` for:
- Markdown content styling
- Code block themes
- Admonition styling
- Metadata badges
- Mermaid diagram containers
- Responsive design
- Dark mode support

### Custom Styling

Override default styles:
```css
/* Custom admonition colors */
.admonition-note {
    border-left-color: #your-color;
    background-color: rgba(your-color, 0.05);
}

/* Custom code block theme */
.code-block-wrapper {
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Custom badge styling */
.difficulty-badge.difficulty-advanced {
    background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
    color: white;
}
```

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Modern mobile browsers

## Dependencies

- **marked.js** (v11+): Core markdown parsing
- **Prism.js** (v1.29+): Syntax highlighting (optional but recommended)
- **Mermaid.js** (v10+): Diagram rendering (optional but recommended)

## License

This module is part of the Laravel Interview Question Bank project.

## Contributing

Contributions welcome! The module is designed to be extensible:
- Add new language support
- Create custom admonition types
- Extend metadata badge rendering
- Improve accessibility features
