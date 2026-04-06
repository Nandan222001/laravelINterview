# Code Snippet Management - Quick Start Guide

Get started with code snippet management in 5 minutes.

## Installation

```bash
# Install the syntax highlighting library
composer require scrivo/highlight.php
```

## Basic Usage

### 1. Create a Code Snippet

```bash
POST /api/code-snippets
```

```json
{
  "question_id": 5,
  "title": "Hello World Example",
  "code": "console.log('Hello, World!');",
  "type": "example"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Code snippet created successfully",
  "data": {
    "id": 1,
    "language": "javascript",
    "highlighted_code": "<span class=\"hljs-built_in\">console</span>...",
    "rendering_instructions": {
      "type": "pre_rendered",
      "stylesheet": "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css"
    }
  }
}
```

### 2. Fetch Code Snippets

```bash
GET /api/code-snippets?question_id=5&render_html=true
```

### 3. Display in HTML

```html
<!-- Include stylesheet -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">

<!-- Display highlighted code -->
<pre><code class="hljs">
  <!-- Insert highlighted_code here -->
</code></pre>
```

## Client-Side Rendering

### Option 1: highlight.js

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
</head>
<body>
    <pre><code class="language-javascript">
const greeting = "Hello, World!";
console.log(greeting);
    </code></pre>
    
    <script>hljs.highlightAll();</script>
</body>
</html>
```

### Option 2: Prism.js

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-okaidia.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
</head>
<body>
    <pre><code class="language-javascript">
const greeting = "Hello, World!";
console.log(greeting);
    </code></pre>
</body>
</html>
```

## Common Operations

### Detect Language

```bash
POST /api/code-snippets/detect-language
```

```json
{
  "code": "def hello():\n    print('Hello')"
}
```

### Highlight Code

```bash
POST /api/code-snippets/highlight
```

```json
{
  "code": "function greet() { console.log('Hi'); }",
  "language": "javascript"
}
```

### Get Supported Languages

```bash
GET /api/code-snippets/supported-languages
```

### Bulk Create

```bash
POST /api/code-snippets/bulk-create
```

```json
{
  "question_id": 5,
  "snippets": [
    {
      "title": "Example 1",
      "code": "console.log('Hello');",
      "type": "example"
    },
    {
      "title": "Example 2",
      "code": "print('World')",
      "type": "example"
    }
  ]
}
```

## JavaScript Integration

```javascript
// Fetch and display snippets
async function displaySnippets(questionId) {
    const response = await fetch(
        `/api/code-snippets?question_id=${questionId}&render_html=true`,
        {
            headers: {
                'Authorization': 'Bearer YOUR_TOKEN'
            }
        }
    );
    
    const data = await response.json();
    
    data.data.forEach(snippet => {
        const div = document.createElement('div');
        div.innerHTML = `
            <h3>${snippet.title}</h3>
            <pre><code class="hljs">${snippet.highlighted_code}</code></pre>
        `;
        document.body.appendChild(div);
    });
}

// Create snippet
async function createSnippet(questionId, code, title) {
    const response = await fetch('/api/code-snippets', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer YOUR_TOKEN',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            question_id: questionId,
            title: title,
            code: code,
            type: 'example'
        })
    });
    
    return await response.json();
}
```

## React Example

```jsx
import React, { useState, useEffect } from 'react';

function CodeSnippets({ questionId }) {
    const [snippets, setSnippets] = useState([]);

    useEffect(() => {
        fetch(`/api/code-snippets?question_id=${questionId}&render_html=true`, {
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(data => setSnippets(data.data));
    }, [questionId]);

    return (
        <div>
            <link 
                rel="stylesheet" 
                href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css" 
            />
            {snippets.map(snippet => (
                <div key={snippet.id}>
                    <h3>{snippet.title}</h3>
                    <pre>
                        <code 
                            className="hljs"
                            dangerouslySetInnerHTML={{ __html: snippet.highlighted_code }}
                        />
                    </pre>
                </div>
            ))}
        </div>
    );
}
```

## Available Themes

- `default` - Default theme
- `github` - GitHub light theme
- `github-dark` - GitHub dark theme
- `monokai` - Monokai theme
- `vs2015` - Visual Studio 2015
- `atom-one-dark` - Atom One Dark
- `atom-one-light` - Atom One Light
- `dracula` - Dracula theme
- `nord` - Nord theme
- `tokyo-night-dark` - Tokyo Night Dark

Change theme:
```javascript
function changeTheme(theme) {
    document.querySelector('link[href*="highlight.js"]').href = 
        `https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/${theme}.min.css`;
}
```

## Snippet Types

- `example` - Example code
- `solution` - Solution code
- `test` - Test cases
- `template` - Code template
- `starter` - Starter code

## Tips

1. **Auto Language Detection**: Omit `language` field to auto-detect
2. **Pre-rendered HTML**: Use `render_html=true` for better performance
3. **Caching**: Cache highlighted code on the client side
4. **Themes**: Offer theme selection for better UX
5. **Order**: Use `order` field to control snippet sequence

## Demo

Visit `/code-snippet-example.html` for a live demo.

## Documentation

- Full API Docs: `CODE_SNIPPET_API.md`
- Detailed Guide: `CODE_SNIPPET_README.md`
- Integration Examples: `INTEGRATION_EXAMPLE.md`

## Support

For issues or questions, refer to the complete documentation files.
