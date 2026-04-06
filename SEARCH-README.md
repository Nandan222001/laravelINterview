# Advanced Search Functionality

## Overview

This implementation provides a comprehensive search system for Laravel questions with support for multi-field queries, fuzzy matching, advanced filtering, sorting options, and automatic highlighting of search terms.

## Features

### 1. Multi-Field Search
- **Question Text**: Search in question titles and descriptions
- **Code Snippets**: Search within code examples, titles, and descriptions
- **Tags**: Search in tag names, slugs, and descriptions

### 2. Search Modes
- **Exact Search (Default)**: Uses MySQL FULLTEXT indexes for fast, accurate results
- **Fuzzy Search**: Uses LIKE queries for broader matching, useful for typos and partial matches

### 3. Advanced Filtering
- **Difficulty Level**: Filter by one or multiple difficulty levels
- **Categories**: Filter questions by category
- **Topics**: Filter questions by topic
- **Tags**: Filter by tag IDs or slugs
- **Programming Language**: Filter code snippets by language
- **Publication Status**: Filter by published/unpublished
- **Verification Status**: Filter by verified/unverified
- **Code Presence**: Filter questions with/without code snippets
- **Point Range**: Filter by min/max points
- **Creator**: Filter by author

### 4. Sorting Options
- **Relevance**: Score-based sorting (default when searching)
- **Newest/Oldest**: Sort by creation date
- **Views**: Sort by popularity
- **Attempts**: Sort by attempt count
- **Success Rate**: Sort by success percentage
- **Difficulty**: Sort by difficulty level
- **Title**: Alphabetical sorting
- **Points**: Sort by point value

### 5. Search Highlighting
Automatically highlights search terms in results:
- Question titles
- Question text
- Code snippets
- Code descriptions

### 6. Search Suggestions
Real-time autocomplete suggestions for:
- Question titles
- Tag names

### 7. Search Statistics
Get result statistics without fetching full results:
- Total result count
- Difficulty distribution
- Category distribution
- Tag distribution

## Architecture

### Components

1. **SearchController** (`app/Http/Controllers/Api/SearchController.php`)
   - Handles HTTP requests
   - Validates input
   - Returns formatted responses

2. **SearchService** (`app/Services/SearchService.php`)
   - Business logic layer
   - Coordinates search operations
   - Applies highlighting
   - Normalizes criteria

3. **SearchRepository** (`app/Repositories/SearchRepository.php`)
   - Data access layer
   - Builds complex queries
   - Applies filters and sorting
   - Generates statistics

4. **SearchRequest** (`app/Http/Requests/SearchRequest.php`)
   - Input validation
   - Parameter normalization
   - Criteria extraction

5. **Resources** (`app/Http/Resources/`)
   - `SearchResultResource`: Formats individual results
   - `SearchCodeSnippetResource`: Formats code snippets
   - `SearchResultCollection`: Formats paginated results

## Database Indexes

### FULLTEXT Indexes
The following FULLTEXT indexes are used for optimal search performance:

1. **questions table**: `['title', 'question_text']`
2. **code_snippets table**: `['title', 'description', 'code']`
3. **tags table**: `['name', 'slug', 'description']`
4. **categories table**: `['name', 'description']`
5. **topics table**: `['name', 'description', 'learning_objectives']`

### Regular Indexes
- `difficulty_level_id` on questions
- `question_id` on code_snippets
- `language` on code_snippets
- Various foreign key indexes

## API Endpoints

### Main Search
```
GET|POST /api/search
```

### Suggestions
```
GET /api/search/suggestions
```

### Statistics
```
GET /api/search/statistics
```

### Available Filters
```
GET /api/search/filters
```

### Extract Excerpts
```
POST /api/search/excerpt
```

See [SEARCH-API-DOCUMENTATION.md](SEARCH-API-DOCUMENTATION.md) for detailed API documentation.

## Usage Examples

### Simple Search

```php
// Search for questions containing "array"
GET /api/search?query=array
```

### Multi-Field Search with Filters

```php
POST /api/search
{
  "query": "sorting algorithms",
  "search_fields": ["question", "code"],
  "difficulty": [2, 3],
  "topics": [5, 10],
  "tags": ["algorithms", "sorting"],
  "language": ["php", "javascript"],
  "sort_by": "relevance",
  "per_page": 20
}
```

### Fuzzy Search

```php
// Enable fuzzy matching for typos
GET /api/search?query=aray&fuzzy=true&search_fields=question
```

### Search with Code Filter

```php
// Find questions with PHP code examples
GET /api/search?query=function&has_code_snippets=true&language=php
```

### Get Suggestions

```php
// Get autocomplete suggestions
GET /api/search/suggestions?term=arr&limit=10
```

## Code Examples

### Using the Search Service

```php
use App\Services\SearchService;

class ExampleController extends Controller
{
    public function __construct(
        private SearchService $searchService
    ) {}

    public function search(Request $request)
    {
        $criteria = [
            'query' => 'array sorting',
            'search_fields' => ['question', 'code'],
            'difficulty' => [1, 2],
            'fuzzy' => false,
            'sort_by' => 'relevance',
            'per_page' => 15,
        ];

        // Get results with highlighting
        $results = $this->searchService->searchWithHighlights($criteria);
        
        return response()->json($results);
    }
}
```

### Using the Search Repository

```php
use App\Repositories\SearchRepository;

class ExampleService
{
    public function __construct(
        private SearchRepository $searchRepository
    ) {}

    public function performSearch(array $criteria)
    {
        // Get paginated results
        $results = $this->searchRepository->advancedSearch($criteria);
        
        // Get suggestions
        $suggestions = $this->searchRepository->getSearchSuggestions('arr', 10);
        
        // Get statistics
        $stats = $this->searchRepository->getSearchStatistics($criteria);
        
        return compact('results', 'suggestions', 'stats');
    }
}
```

## Performance Considerations

### FULLTEXT Search
- **Pros**: Very fast for exact matches, supports natural language mode
- **Cons**: Minimum word length requirement (default: 3-4 characters)
- **Use case**: Default search mode

### LIKE Search (Fuzzy)
- **Pros**: Works with short terms, finds partial matches
- **Cons**: Slower on large datasets
- **Use case**: Fallback or when fuzzy matching is explicitly requested

### Optimization Tips

1. **Use pagination**: Always paginate results with reasonable `per_page` values
2. **Limit search fields**: Only search fields you need
3. **Cache suggestions**: Cache common search suggestions
4. **Index optimization**: Ensure all FULLTEXT indexes are created
5. **Query optimization**: Use eager loading for relationships

## Customization

### Adding New Search Fields

To add new searchable fields:

1. **Add FULLTEXT index** in migration:
```php
$table->fullText(['new_field'], 'table_fulltext_index');
```

2. **Update SearchRepository**:
```php
protected function applyExactSearch(Builder $query, string $term, array $criteria): void
{
    if (in_array('new_field', $searchFields)) {
        $query->orWhere('new_field', 'like', "%{$term}%");
    }
}
```

3. **Update SearchRequest** validation:
```php
'search_fields.*' => 'string|in:question,code,tags,new_field',
```

### Adding New Filters

To add new filter options:

1. **Update SearchRequest**:
```php
'new_filter' => 'nullable|string',
```

2. **Update SearchRepository**:
```php
if (isset($criteria['new_filter'])) {
    $query->where('new_column', $criteria['new_filter']);
}
```

### Adding New Sort Options

To add new sorting options:

1. **Update SearchRepository**:
```php
match ($sortBy) {
    // ... existing options
    'new_sort' => $query->orderBy('new_column', $sortOrder),
    default => $query->latest(),
};
```

2. **Update SearchRequest** validation:
```php
'sort_by' => 'nullable|string|in:relevance,newest,...,new_sort',
```

## Testing

### Unit Tests

```php
use Tests\TestCase;
use App\Services\SearchService;

class SearchTest extends TestCase
{
    public function test_basic_search()
    {
        $service = app(SearchService::class);
        $results = $service->search(['query' => 'test']);
        
        $this->assertNotEmpty($results);
    }
    
    public function test_fuzzy_search()
    {
        $service = app(SearchService::class);
        $results = $service->search([
            'query' => 'aray',
            'fuzzy' => true,
        ]);
        
        $this->assertNotEmpty($results);
    }
}
```

### Feature Tests

```php
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    public function test_search_endpoint()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson('/api/search?query=test');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta',
                'links',
            ]);
    }
}
```

## Troubleshooting

### FULLTEXT Searches Not Working

1. **Check minimum word length**:
```sql
SHOW VARIABLES LIKE 'ft_min_word_len';
```

2. **Rebuild indexes**:
```sql
OPTIMIZE TABLE questions;
OPTIMIZE TABLE code_snippets;
OPTIMIZE TABLE tags;
```

3. **Check stop words**: Common words like "a", "the", "is" are ignored

### Slow Search Performance

1. **Enable query log** to identify slow queries
2. **Check indexes** with `EXPLAIN` queries
3. **Reduce per_page** value
4. **Limit search fields**
5. **Use caching** for common searches

### Empty Results

1. **Check word length**: FULLTEXT requires 3-4 character minimum
2. **Try fuzzy mode**: Enable `fuzzy=true`
3. **Check filters**: Ensure filters aren't too restrictive
4. **Verify data**: Ensure questions exist with search terms

## Future Enhancements

Potential improvements for the search system:

1. **Search Analytics**: Track popular searches, clicks, and conversions
2. **Machine Learning**: Use ML for better relevance scoring
3. **Elasticsearch**: Integrate Elasticsearch for advanced features
4. **Synonyms**: Support synonym matching (e.g., "array" = "list")
5. **Spell Check**: Auto-correct misspelled search terms
6. **Search History**: Store user search history
7. **Saved Searches**: Allow users to save search queries
8. **Search Filters UI**: Pre-built UI components for filters
9. **Export Results**: Allow exporting search results
10. **Batch Operations**: Bulk actions on search results

## Contributing

When contributing to the search functionality:

1. **Maintain backward compatibility**: Don't break existing APIs
2. **Add tests**: Include unit and feature tests
3. **Update documentation**: Keep docs in sync with code
4. **Consider performance**: Profile new features
5. **Follow conventions**: Match existing code style

## License

This search functionality is part of the Laravel application and follows the same license.
