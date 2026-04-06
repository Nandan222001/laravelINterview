# Search API Documentation

## Overview

The Search API provides advanced search functionality with support for multi-field queries, fuzzy matching, filtering by difficulty/category/topic, sorting options, and response with highlighted search terms.

## Base URL

All search endpoints are prefixed with `/api/search` and require authentication via Sanctum.

## Authentication

All endpoints require authentication. Include the Sanctum token in the request headers:

```
Authorization: Bearer {token}
```

## Endpoints

### 1. Advanced Search

Perform an advanced search with multiple filters and options.

**Endpoint:** `GET|POST /api/search`

**Method:** GET or POST (POST recommended for complex queries)

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `query` | string | No | Search term (max 500 characters) |
| `search_fields` | array/string | No | Fields to search in: `question`, `code`, `tags` (comma-separated for GET) |
| `fuzzy` | boolean | No | Enable fuzzy matching (default: false) |
| `difficulty` | array/integer | No | Difficulty level IDs (comma-separated for GET) |
| `categories` | array/integer | No | Category IDs (comma-separated for GET) |
| `topics` | array/integer | No | Topic IDs (comma-separated for GET) |
| `tags` | array/string | No | Tag IDs or slugs (comma-separated for GET) |
| `language` | array/string | No | Programming language filter (comma-separated for GET) |
| `published` | boolean | No | Filter by published status |
| `verified` | boolean | No | Filter by verified status |
| `creator_id` | integer | No | Filter by creator user ID |
| `min_points` | integer | No | Minimum points value |
| `max_points` | integer | No | Maximum points value |
| `has_code_snippets` | boolean | No | Filter questions with code snippets |
| `sort_by` | string | No | Sort field: `relevance`, `newest`, `oldest`, `views`, `attempts`, `success_rate`, `difficulty`, `title`, `points` |
| `sort_order` | string | No | Sort direction: `asc`, `desc` (default: desc) |
| `per_page` | integer | No | Results per page (1-100, default: 15) |
| `page` | integer | No | Page number (default: 1) |
| `highlight` | boolean | No | Enable search term highlighting (default: true) |

#### Example Request (GET)

```http
GET /api/search?query=array+sort&search_fields=question,code&difficulty=1,2&fuzzy=true&sort_by=relevance&per_page=20
Authorization: Bearer {token}
```

#### Example Request (POST)

```http
POST /api/search
Authorization: Bearer {token}
Content-Type: application/json

{
  "query": "array sort",
  "search_fields": ["question", "code"],
  "difficulty": [1, 2],
  "topics": [5, 10],
  "tags": ["arrays", "sorting"],
  "language": ["php", "javascript"],
  "fuzzy": true,
  "sort_by": "relevance",
  "per_page": 20,
  "highlight": true
}
```

#### Example Response

```json
{
  "success": true,
  "message": "Search completed successfully",
  "data": [
    {
      "id": 1,
      "title": "How to sort an array in PHP?",
      "title_highlighted": "How to <mark>sort</mark> an <mark>array</mark> in PHP?",
      "question_text": "What is the best way to sort an array...",
      "question_text_highlighted": "What is the best way to <mark>sort</mark> an <mark>array</mark>...",
      "explanation": "PHP provides multiple functions for sorting...",
      "points": 10,
      "time_limit": 300,
      "is_published": true,
      "is_verified": true,
      "view_count": 150,
      "attempt_count": 75,
      "success_count": 60,
      "success_rate": 80.0,
      "relevance_score": 15,
      "difficulty_level": {
        "id": 1,
        "name": "Easy",
        "slug": "easy",
        "level": 1,
        "color": "#22c55e"
      },
      "topics": [
        {
          "id": 5,
          "name": "Arrays",
          "slug": "arrays"
        }
      ],
      "tags": [
        {
          "id": 10,
          "name": "Arrays",
          "slug": "arrays"
        },
        {
          "id": 20,
          "name": "Sorting",
          "slug": "sorting"
        }
      ],
      "creator": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "code_snippets": [
        {
          "id": 1,
          "title": "Basic array sorting",
          "description": "Using sort() function",
          "code": "sort($array);",
          "code_highlighted": "<mark>sort</mark>($<mark>array</mark>);",
          "language": "php",
          "type": "example",
          "order": 1,
          "is_executable": true
        }
      ],
      "has_code_snippets": true,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 95,
    "timestamp": "2024-01-15T10:30:00Z"
  },
  "links": {
    "first": "http://localhost/api/search?page=1",
    "last": "http://localhost/api/search?page=5",
    "prev": null,
    "next": "http://localhost/api/search?page=2"
  }
}
```

### 2. Search Suggestions

Get autocomplete suggestions based on a search term.

**Endpoint:** `GET /api/search/suggestions`

**Method:** GET

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `term` | string | Yes | Search term (min 2, max 100 characters) |
| `limit` | integer | No | Maximum suggestions to return (1-50, default: 10) |

#### Example Request

```http
GET /api/search/suggestions?term=arra&limit=10
Authorization: Bearer {token}
```

#### Example Response

```json
{
  "success": true,
  "message": "Suggestions retrieved successfully",
  "data": {
    "questions": [
      {
        "type": "question",
        "id": 1,
        "text": "How to sort an array in PHP?"
      },
      {
        "type": "question",
        "id": 2,
        "text": "Array manipulation best practices"
      }
    ],
    "tags": [
      {
        "type": "tag",
        "id": 10,
        "text": "Arrays",
        "slug": "arrays"
      },
      {
        "type": "tag",
        "id": 15,
        "text": "Array Methods",
        "slug": "array-methods"
      }
    ]
  }
}
```

### 3. Search Statistics

Get statistics about search results without retrieving the full results.

**Endpoint:** `GET /api/search/statistics`

**Method:** GET

#### Query Parameters

Accepts the same parameters as the main search endpoint.

#### Example Request

```http
GET /api/search/statistics?query=array&difficulty=1,2
Authorization: Bearer {token}
```

#### Example Response

```json
{
  "success": true,
  "message": "Search statistics retrieved successfully",
  "data": {
    "total_results": 95,
    "difficulty_distribution": [
      {
        "id": 1,
        "name": "Easy",
        "count": 45
      },
      {
        "id": 2,
        "name": "Medium",
        "count": 50
      }
    ],
    "category_distribution": [],
    "tag_distribution": []
  }
}
```

### 4. Available Filters

Get a list of available filter options for the search interface.

**Endpoint:** `GET /api/search/filters`

**Method:** GET

#### Example Request

```http
GET /api/search/filters
Authorization: Bearer {token}
```

#### Example Response

```json
{
  "success": true,
  "message": "Available filters retrieved successfully",
  "data": {
    "search_fields": [
      {
        "value": "question",
        "label": "Question Text"
      },
      {
        "value": "code",
        "label": "Code Snippets"
      },
      {
        "value": "tags",
        "label": "Tags"
      }
    ],
    "sort_options": [
      {
        "value": "relevance",
        "label": "Relevance"
      },
      {
        "value": "newest",
        "label": "Newest First"
      },
      {
        "value": "oldest",
        "label": "Oldest First"
      },
      {
        "value": "views",
        "label": "Most Viewed"
      },
      {
        "value": "attempts",
        "label": "Most Attempted"
      },
      {
        "value": "success_rate",
        "label": "Success Rate"
      },
      {
        "value": "difficulty",
        "label": "Difficulty Level"
      },
      {
        "value": "title",
        "label": "Title (A-Z)"
      },
      {
        "value": "points",
        "label": "Points"
      }
    ],
    "sort_directions": [
      {
        "value": "asc",
        "label": "Ascending"
      },
      {
        "value": "desc",
        "label": "Descending"
      }
    ]
  }
}
```

### 5. Extract Highlighted Excerpts

Extract highlighted excerpts from text based on a search term.

**Endpoint:** `POST /api/search/excerpt`

**Method:** POST

#### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `text` | string | Yes | Text to extract excerpts from |
| `term` | string | Yes | Search term to highlight |
| `context_length` | integer | No | Context length around matches (50-500, default: 150) |

#### Example Request

```http
POST /api/search/excerpt
Authorization: Bearer {token}
Content-Type: application/json

{
  "text": "Arrays are fundamental data structures in programming...",
  "term": "array",
  "context_length": 150
}
```

#### Example Response

```json
{
  "success": true,
  "message": "Excerpts extracted successfully",
  "data": {
    "text": "Arrays are fundamental data structures in programming...",
    "highlighted": "<mark>Array</mark>s are fundamental data structures in programming...",
    "excerpts": [
      "<mark>Array</mark>s are fundamental data structures...",
      "...working with <mark>array</mark>s in PHP..."
    ]
  }
}
```

## Search Features

### Multi-Field Search

Search across multiple fields simultaneously:
- **Question Text**: Title and question text
- **Code Snippets**: Code, title, and description
- **Tags**: Tag names and slugs

### Fuzzy Matching

Enable fuzzy matching for more flexible searches:
- **Exact mode (default)**: Uses FULLTEXT indexes for performance
- **Fuzzy mode**: Uses LIKE queries for broader matches

### Filtering Options

Filter results by:
- **Difficulty**: Filter by one or more difficulty levels
- **Categories**: Filter by category IDs
- **Topics**: Filter by topic IDs
- **Tags**: Filter by tag IDs or slugs
- **Language**: Filter by programming language
- **Status**: Filter by published/verified status
- **Points**: Filter by min/max points
- **Code Snippets**: Filter questions with code examples

### Sorting Options

Sort results by:
- **Relevance**: Based on search term matches (default for searches)
- **Newest/Oldest**: By creation date
- **Views**: By view count
- **Attempts**: By attempt count
- **Success Rate**: By success percentage
- **Difficulty**: By difficulty level
- **Title**: Alphabetically
- **Points**: By point value

### Highlighting

Search terms are highlighted in results:
- Wrapped in `<mark>` tags
- Applied to title, question text, and code snippets
- Can be disabled with `highlight=false`

## Error Responses

### Validation Error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "query": ["Search query cannot exceed 500 characters."],
    "per_page": ["Cannot request more than 100 results per page."]
  }
}
```

### Authentication Error

```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

## Performance Tips

1. **Use exact search** (default) for better performance with FULLTEXT indexes
2. **Limit search fields** to only what you need
3. **Use pagination** with reasonable `per_page` values
4. **Cache results** for common searches
5. **Use suggestions** for autocomplete instead of full searches

## Best Practices

1. **Implement debouncing** for real-time search
2. **Show search statistics** before full results
3. **Use suggestions** for better UX
4. **Combine filters** for more precise results
5. **Handle highlighting** safely in your frontend
6. **Provide clear feedback** on empty results

## Examples

### Simple Search

```bash
curl -X GET "http://localhost/api/search?query=array" \
  -H "Authorization: Bearer {token}"
```

### Advanced Search with Filters

```bash
curl -X POST "http://localhost/api/search" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "sorting algorithms",
    "search_fields": ["question", "code"],
    "difficulty": [2, 3],
    "topics": [5, 10],
    "language": ["php", "javascript"],
    "fuzzy": false,
    "sort_by": "relevance",
    "per_page": 25
  }'
```

### Search with Code Filter

```bash
curl -X GET "http://localhost/api/search?query=function&has_code_snippets=true&language=php" \
  -H "Authorization: Bearer {token}"
```

### Autocomplete Suggestions

```bash
curl -X GET "http://localhost/api/search/suggestions?term=arra&limit=5" \
  -H "Authorization: Bearer {token}"
```
