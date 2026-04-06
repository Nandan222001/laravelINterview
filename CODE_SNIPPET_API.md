# Code Snippet Management API Documentation

## Overview

This API provides comprehensive code snippet management with syntax highlighting support using highlight.js. It includes language detection, pre-rendered HTML highlighting, client-side rendering instructions, and full CRUD operations for code snippets.

## Base URL
```
/api
```

All endpoints require authentication via Laravel Sanctum.

---

## Code Snippet Endpoints

### 1. List Code Snippets
**GET** `/code-snippets`

Retrieve a list of code snippets with optional filtering.

#### Query Parameters:
- `question_id` (integer, optional) - Filter by question
- `language` (string, optional) - Filter by programming language
- `type` (string, optional) - Filter by type (example, solution, test, template, starter)
- `search` (string, optional) - Search in title, description, and code
- `render_html` (boolean, optional, default: true) - Include pre-rendered HTML

#### Response:
```json
{
  "success": true,
  "message": "Code snippets retrieved successfully",
  "data": [
    {
      "id": 1,
      "question_id": 5,
      "title": "JavaScript Array Example",
      "description": "Basic array operations",
      "code": "const arr = [1, 2, 3];\nconsole.log(arr);",
      "language": "javascript",
      "type": "example",
      "order": 0,
      "is_executable": true,
      "expected_output": "[1, 2, 3]",
      "highlighted_code": "<span class=\"hljs-keyword\">const</span>...",
      "detected_language": "javascript",
      "relevance": 15,
      "second_best_language": null,
      "rendering_instructions": {
        "type": "pre_rendered",
        "stylesheet": "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css",
        "usage": "Wrap highlighted_code in <pre><code class=\"hljs\"> tags"
      }
    }
  ],
  "meta": {
    "total": 1,
    "timestamp": "2024-01-15T10:30:00Z",
    "syntax_highlighting": {
      "library": "highlight.js",
      "version": "11.9.0"
    }
  }
}
```

---

### 2. Get Single Code Snippet
**GET** `/code-snippets/{id}`

Retrieve a specific code snippet by ID.

#### Query Parameters:
- `render_html` (boolean, optional, default: true) - Include pre-rendered HTML

#### Response:
```json
{
  "success": true,
  "message": "Code snippet retrieved successfully",
  "data": {
    "id": 1,
    "question_id": 5,
    "title": "JavaScript Array Example",
    "code": "const arr = [1, 2, 3];",
    "language": "javascript",
    "highlighted_code": "<span class=\"hljs-keyword\">const</span>...",
    "rendering_instructions": { }
  }
}
```

---

### 3. Create Code Snippet
**POST** `/code-snippets`

Create a new code snippet with automatic language detection.

#### Request Body:
```json
{
  "question_id": 5,
  "title": "Python List Comprehension",
  "description": "Example of list comprehension",
  "code": "squares = [x**2 for x in range(10)]",
  "language": "python",
  "type": "example",
  "order": 0,
  "is_executable": true,
  "expected_output": "[0, 1, 4, 9, 16, 25, 36, 49, 64, 81]",
  "metadata": {}
}
```

#### Required Fields:
- `question_id` (integer)
- `code` (string)

#### Optional Fields:
- `title` (string, max: 255)
- `description` (string)
- `language` (string, auto-detected if not provided)
- `type` (enum: example, solution, test, template, starter)
- `order` (integer, auto-assigned if not provided)
- `is_executable` (boolean)
- `expected_output` (string)
- `metadata` (object)

---

### 4. Update Code Snippet
**PUT/PATCH** `/code-snippets/{id}`

Update an existing code snippet.

#### Request Body:
```json
{
  "title": "Updated Title",
  "code": "updated code",
  "language": "javascript"
}
```

---

### 5. Delete Code Snippet
**DELETE** `/code-snippets/{id}`

Delete a code snippet.

---

### 6. Detect Language
**POST** `/code-snippets/detect-language`

Automatically detect the programming language of code.

#### Request Body:
```json
{
  "code": "function hello() { console.log('Hello'); }"
}
```

#### Response:
```json
{
  "success": true,
  "message": "Language detected successfully",
  "data": {
    "language": "javascript"
  }
}
```

---

### 7. Highlight Code
**POST** `/code-snippets/highlight`

Get syntax highlighted HTML for code.

#### Request Body:
```json
{
  "code": "def hello():\n    print('Hello')",
  "language": "python"
}
```

#### Response:
```json
{
  "success": true,
  "message": "Code highlighted successfully",
  "data": {
    "highlighted": "<span class=\"hljs-keyword\">def</span>...",
    "language": "python",
    "relevance": 15,
    "second_best": null
  }
}
```

---

### 8. Get Supported Languages
**GET** `/code-snippets/supported-languages`

Get list of all supported programming languages.

#### Response:
```json
{
  "success": true,
  "message": "Supported languages retrieved successfully",
  "data": {
    "languages": [
      "javascript",
      "python",
      "java",
      "cpp",
      "php",
      "ruby",
      "go",
      "rust",
      // ... many more
    ]
  }
}
```

---

### 9. Reorder Code Snippets
**POST** `/code-snippets/reorder`

Reorder code snippets for a question.

#### Request Body:
```json
{
  "question_id": 5,
  "snippet_orders": [3, 1, 2]
}
```

The array index represents the new order, and the value is the snippet ID.

---

### 10. Bulk Create Code Snippets
**POST** `/code-snippets/bulk-create`

Create multiple code snippets at once.

#### Request Body:
```json
{
  "question_id": 5,
  "snippets": [
    {
      "title": "Example 1",
      "code": "console.log('Hello');",
      "language": "javascript",
      "type": "example"
    },
    {
      "title": "Example 2",
      "code": "print('World')",
      "language": "python",
      "type": "example"
    }
  ]
}
```

---

### 11. Bulk Delete Code Snippets
**POST** `/code-snippets/bulk-delete`

Delete multiple code snippets at once.

#### Request Body:
```json
{
  "snippet_ids": [1, 2, 3]
}
```

---

### 12. Duplicate Code Snippet
**POST** `/code-snippets/{id}/duplicate`

Duplicate a code snippet, optionally to a different question.

#### Request Body:
```json
{
  "question_id": 10
}
```

---

### 13. Get Executable Snippets
**GET** `/code-snippets/executable`

Get all executable code snippets.

---

## Syntax Highlighting Endpoints

### 1. Get Themes
**GET** `/syntax-highlighting/themes`

Get available syntax highlighting themes.

#### Response:
```json
{
  "success": true,
  "message": "Themes retrieved successfully",
  "data": {
    "themes": {
      "default": "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css",
      "github": "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css",
      "monokai": "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/monokai.min.css",
      "vs2015": "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/vs2015.min.css",
      "atom-one-dark": "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css",
      "dracula": "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/dracula.min.css"
    }
  }
}
```

---

### 2. Get Client-Side Instructions
**GET** `/syntax-highlighting/client-instructions`

Get instructions for implementing client-side syntax highlighting.

#### Query Parameters:
- `language` (string, optional) - Specific language
- `library` (string, optional) - Library choice (highlight.js or prism.js)

#### Response for highlight.js:
```json
{
  "success": true,
  "message": "Client-side instructions retrieved successfully",
  "data": {
    "library": "highlight.js",
    "version": "11.9.0",
    "cdn": {
      "script": "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js",
      "stylesheet": "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css"
    },
    "installation": {
      "html": [
        "<link rel=\"stylesheet\" href=\"...\">",
        "<script src=\"...\"></script>"
      ],
      "init": "<script>hljs.highlightAll();</script>"
    },
    "usage": {
      "html": "<pre><code class=\"language-javascript\">Your code here</code></pre>",
      "javascript": {
        "auto": "hljs.highlightAll();",
        "manual": "hljs.highlightElement(codeElement);"
      }
    }
  }
}
```

#### Response for prism.js:
```json
{
  "success": true,
  "message": "Client-side instructions retrieved successfully",
  "data": {
    "library": "prism.js",
    "version": "1.29.0",
    "cdn": {
      "script": "https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js",
      "stylesheet": "https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css"
    },
    "installation": {
      "html": ["..."],
      "language_specific": "<script src=\".../prism-javascript.min.js\"></script>"
    },
    "usage": {
      "html": "<pre><code class=\"language-javascript\">Your code here</code></pre>",
      "note": "Prism.js automatically highlights code on page load"
    }
  }
}
```

---

### 3. Get Rendering Options
**GET** `/syntax-highlighting/rendering-options`

Get comprehensive rendering options for both pre-rendered and client-side approaches.

#### Response:
```json
{
  "success": true,
  "message": "Rendering options retrieved successfully",
  "data": {
    "pre_rendered": {
      "type": "pre_rendered",
      "library": "highlight.js (server-side)",
      "stylesheet": "...",
      "usage": "Wrap highlighted_code in <pre><code class=\"hljs\"> tags",
      "note": "No JavaScript required - HTML is pre-rendered on the server"
    },
    "client_side": {
      "type": "client_side",
      "options": {
        "highlight.js": { },
        "prism.js": { }
      }
    }
  }
}
```

---

### 4. Get Language Aliases
**GET** `/syntax-highlighting/language-aliases`

Get aliases for a specific programming language.

#### Query Parameters:
- `language` (string, required) - Programming language

#### Response:
```json
{
  "success": true,
  "message": "Language aliases retrieved successfully",
  "data": {
    "language": "javascript",
    "aliases": ["js", "jsx", "mjs"]
  }
}
```

---

## Code Snippet Types

The API supports the following snippet types:

- **example** - Example code to illustrate concepts
- **solution** - Solution code for a problem
- **test** - Test cases or unit tests
- **template** - Code templates or boilerplate
- **starter** - Starter code for users to begin with

---

## Rendering Strategies

### 1. Pre-Rendered HTML (Server-Side)
Code is highlighted on the server using highlight.php and returned as HTML.

**Advantages:**
- No JavaScript required
- Faster initial render
- Better for SEO
- Works with JavaScript disabled

**Usage:**
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
<pre><code class="hljs">{highlighted_code}</code></pre>
```

### 2. Client-Side Rendering
Code is highlighted in the browser using highlight.js or Prism.js.

**Advantages:**
- Smaller API responses
- Dynamic theme switching
- Plugin support
- Line numbers and other features

**Usage (highlight.js):**
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>hljs.highlightAll();</script>

<pre><code class="language-javascript">
const hello = () => console.log('Hello');
</code></pre>
```

**Usage (Prism.js):**
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>

<pre><code class="language-javascript">
const hello = () => console.log('Hello');
</code></pre>
```

---

## Language Detection

The API automatically detects programming languages using highlight.php's language detection algorithm. It returns:

- **language** - The detected language
- **relevance** - Confidence score (higher is better)
- **second_best** - Alternative language suggestion

---

## Error Responses

All endpoints return standard error responses:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Validation error"]
  }
}
```

### Common HTTP Status Codes:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## Example Workflow

### Creating a Question with Code Snippets

1. Create the question:
```bash
POST /api/questions
{
  "title": "Array Operations",
  "question_text": "How do you filter arrays?",
  "difficulty_level_id": 1
}
```

2. Add code snippets:
```bash
POST /api/code-snippets/bulk-create
{
  "question_id": 5,
  "snippets": [
    {
      "title": "JavaScript Example",
      "code": "const filtered = arr.filter(x => x > 5);",
      "type": "example"
    },
    {
      "title": "Python Example",
      "code": "filtered = [x for x in arr if x > 5]",
      "type": "example"
    }
  ]
}
```

3. Retrieve with highlighting:
```bash
GET /api/code-snippets?question_id=5&render_html=true
```

---

## Best Practices

1. **Language Detection**: Let the API auto-detect languages when possible for accuracy
2. **Rendering Choice**: Use pre-rendered HTML for static content, client-side for interactive applications
3. **Themes**: Provide theme selection to users for better accessibility
4. **Caching**: Cache highlighted code on the client side to reduce server load
5. **Order**: Use the `order` field to control snippet display sequence
6. **Types**: Use appropriate types to categorize snippets for better organization

---

## Rate Limiting

All API endpoints are subject to Laravel Sanctum's default rate limiting. Consider implementing appropriate rate limits for your application.
