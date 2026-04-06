# Search Functionality - Quick Start Guide

## Installation

The search functionality is already implemented. To activate it:

### 1. Run Migrations

```bash
php artisan migrate
```

This will create the necessary FULLTEXT indexes on:
- `code_snippets` table: `['title', 'description', 'code']`
- `tags` table: `['name', 'slug', 'description']`

### 2. Verify Setup

Check that the search routes are available:

```bash
php artisan route:list --path=search
```

You should see:
```
GET|HEAD   api/search ..................... SearchController@search
POST       api/search ..................... SearchController@search
GET|HEAD   api/search/filters ............. SearchController@filters
GET|HEAD   api/search/statistics .......... SearchController@statistics
GET|HEAD   api/search/suggestions ......... SearchController@suggestions
POST       api/search/excerpt ............. SearchController@excerpt
```

## Basic Usage

### 1. Simple Search

Search for questions containing "array":

```bash
curl -X GET "http://localhost:8000/api/search?query=array" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. Search with Filters

Search with multiple filters:

```bash
curl -X POST "http://localhost:8000/api/search" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "sorting",
    "difficulty": [1, 2],
    "tags": ["algorithms"],
    "sort_by": "relevance"
  }'
```

### 3. Fuzzy Search

Enable fuzzy matching for typos:

```bash
curl -X GET "http://localhost:8000/api/search?query=aray&fuzzy=true" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Get Suggestions

Get autocomplete suggestions:

```bash
curl -X GET "http://localhost:8000/api/search/suggestions?term=arr" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Common Use Cases

### Search in Specific Fields

Search only in code snippets:

```bash
GET /api/search?query=function&search_fields=code
```

### Filter by Difficulty and Language

Find easy PHP questions:

```bash
GET /api/search?query=array&difficulty=1&language=php
```

### Sort by Popularity

Find most viewed questions about arrays:

```bash
GET /api/search?query=array&sort_by=views&sort_order=desc
```

### Filter by Code Presence

Find questions with code examples:

```bash
GET /api/search?query=algorithm&has_code_snippets=true
```

## Response Format

### Successful Response

```json
{
  "success": true,
  "message": "Search completed successfully",
  "data": [
    {
      "id": 1,
      "title": "How to sort an array?",
      "title_highlighted": "How to <mark>sort</mark> an <mark>array</mark>?",
      "question_text": "...",
      "difficulty_level": { ... },
      "topics": [ ... ],
      "tags": [ ... ],
      "code_snippets": [ ... ]
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 15
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

## Parameters Reference

### Essential Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `query` | string | Search term | `array sort` |
| `search_fields` | array | Fields to search | `["question", "code"]` |
| `fuzzy` | boolean | Enable fuzzy matching | `true` |
| `per_page` | integer | Results per page (1-100) | `25` |
| `page` | integer | Page number | `2` |

### Filter Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `difficulty` | array | Difficulty level IDs | `[1, 2]` |
| `categories` | array | Category IDs | `[5]` |
| `topics` | array | Topic IDs | `[10, 15]` |
| `tags` | array | Tag slugs or IDs | `["php", "arrays"]` |
| `language` | array | Programming languages | `["php", "javascript"]` |
| `has_code_snippets` | boolean | Has code examples | `true` |
| `published` | boolean | Published status | `true` |
| `verified` | boolean | Verified status | `true` |

### Sort Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `sort_by` | string | Sort field | `relevance` |
| `sort_order` | string | Sort direction | `desc` |

### Available Sort Options

- `relevance` - Best match first (default)
- `newest` - Newest first
- `oldest` - Oldest first
- `views` - Most viewed
- `attempts` - Most attempted
- `success_rate` - Highest success rate
- `difficulty` - By difficulty level
- `title` - Alphabetical
- `points` - By point value

## Tips & Tricks

### 1. Combine Multiple Filters

```bash
POST /api/search
{
  "query": "algorithm",
  "difficulty": [2, 3],
  "topics": [5],
  "language": ["php"],
  "has_code_snippets": true,
  "sort_by": "relevance"
}
```

### 2. Use GET for Simple Queries

For simple searches, use GET with comma-separated values:

```bash
GET /api/search?query=array&difficulty=1,2&tags=php,arrays
```

### 3. Use POST for Complex Queries

For complex searches with many filters, use POST with JSON:

```bash
POST /api/search
{
  "query": "sorting algorithms",
  "search_fields": ["question", "code"],
  "difficulty": [2, 3],
  "topics": [5, 10, 15],
  "tags": ["algorithms", "sorting", "complexity"]
}
```

### 4. Disable Highlighting for Performance

If you don't need highlighting:

```bash
GET /api/search?query=array&highlight=false
```

### 5. Get Statistics Before Full Search

Check result counts before fetching data:

```bash
GET /api/search/statistics?query=array&difficulty=1,2
```

## Troubleshooting

### No Results Found

**Issue**: Search returns empty results

**Solutions**:
1. Try fuzzy search: `fuzzy=true`
2. Check word length (FULLTEXT requires 3+ characters)
3. Remove restrictive filters
4. Check if data exists in database

### Slow Search Performance

**Issue**: Search takes too long

**Solutions**:
1. Use exact search (disable fuzzy)
2. Reduce `per_page` value
3. Limit search fields
4. Add more specific filters
5. Check database indexes

### FULLTEXT Not Working

**Issue**: Search doesn't use FULLTEXT indexes

**Solutions**:
1. Verify migrations ran: `php artisan migrate:status`
2. Check MySQL version (need 8.0+)
3. Rebuild indexes: `OPTIMIZE TABLE questions;`
4. Check minimum word length: `SHOW VARIABLES LIKE 'ft_min_word_len';`

## Advanced Usage

### Custom Relevance Scoring

The search automatically scores results based on:
- Exact title matches (highest)
- Title starts with term
- Question text contains term
- Code snippets contain term
- Tags match

### Search in Development

For testing without authentication:

```php
// Temporarily disable auth middleware
Route::prefix('search')->group(function () {
    Route::post('/', [SearchController::class, 'search']);
    // ... other routes
})->withoutMiddleware(['auth:sanctum']);
```

### Debug Mode

To see the generated SQL queries:

```php
DB::enableQueryLog();
// ... perform search
dd(DB::getQueryLog());
```

## Next Steps

1. **Read Full Documentation**: See [SEARCH-API-DOCUMENTATION.md](SEARCH-API-DOCUMENTATION.md)
2. **Understand Architecture**: See [SEARCH-README.md](SEARCH-README.md)
3. **Review Implementation**: See [SEARCH-IMPLEMENTATION-SUMMARY.md](SEARCH-IMPLEMENTATION-SUMMARY.md)
4. **Test the API**: Use the examples above
5. **Customize**: Modify to fit your needs

## Support

For issues or questions:
1. Check the documentation files
2. Review the code comments
3. Test with provided examples
4. Create an issue in the repository

## Quick Reference Card

```bash
# Basic search
GET /api/search?query=TERM

# With filters
POST /api/search {"query": "TERM", "difficulty": [1,2]}

# Suggestions
GET /api/search/suggestions?term=TERM

# Statistics
GET /api/search/statistics?query=TERM

# Fuzzy search
GET /api/search?query=TERM&fuzzy=true

# Code-only search
GET /api/search?query=TERM&search_fields=code

# Sort by views
GET /api/search?query=TERM&sort_by=views
```

That's it! You're ready to use the advanced search functionality. 🚀
