# Eloquent Models Documentation

This document provides comprehensive documentation for all Eloquent models, their relationships, query scopes, and hierarchical management features.

## Table of Contents

1. [Models Overview](#models-overview)
2. [Relationships](#relationships)
3. [Query Scopes](#query-scopes)
4. [Hierarchical Topic Management](#hierarchical-topic-management)
5. [Traits](#traits)
6. [Usage Examples](#usage-examples)

---

## Models Overview

### Category Model
Represents content categories with support for parent-child hierarchy.

**Key Features:**
- Parent-child relationships
- Soft deletes
- Taggable
- Metadata support
- Query scopes for filtering

**Fillable Attributes:**
- `name`, `slug`, `description`
- `parent_id`, `order`
- `is_active`, `metadata`

### Topic Model
Represents learning topics with advanced hierarchical management using both Nested Set and Closure Table patterns.

**Key Features:**
- Nested Set Pattern (for efficient tree queries)
- Closure Table Pattern (for flexible ancestor/descendant queries)
- Parent-child relationships
- Belongs to category
- Soft deletes
- Taggable
- Metadata support

**Fillable Attributes:**
- `category_id`, `parent_id`, `name`, `slug`
- `description`, `learning_objectives`
- `order`, `is_active`, `metadata`
- `_lft`, `_rgt`, `depth` (Nested Set columns)

### Question Model
Represents quiz/test questions with rich metadata and relationships.

**Key Features:**
- Belongs to difficulty level
- Belongs to creator (User)
- Many-to-many with topics
- Has many code snippets
- Taggable
- Soft deletes
- Statistics tracking (views, attempts, success rate)
- Metadata support

**Fillable Attributes:**
- `difficulty_level_id`, `created_by`
- `title`, `question_text`, `explanation`
- `options` (JSON), `correct_answer` (JSON), `hints` (JSON)
- `points`, `time_limit`
- `is_published`, `is_verified`
- `view_count`, `attempt_count`, `success_count`
- `metadata` (JSON)

### DifficultyLevel Model
Represents difficulty levels for questions.

**Key Features:**
- Has many questions
- Level-based ordering
- Metadata support

**Fillable Attributes:**
- `name`, `slug`, `description`
- `level` (integer for sorting)
- `color`, `metadata`

### Tag Model
Polymorphic tagging system for all taggable entities.

**Key Features:**
- Polymorphic relationships (questions, categories, topics, code snippets)
- Usage tracking
- Metadata support

**Fillable Attributes:**
- `name`, `slug`, `description`
- `color`, `usage_count`, `metadata`

### CodeSnippet Model
Code examples associated with questions.

**Key Features:**
- Belongs to question
- Language and type filtering
- Executable flag
- Taggable
- Metadata support

**Fillable Attributes:**
- `question_id`, `title`, `description`
- `code`, `language`, `type`
- `order`, `is_executable`, `expected_output`
- `metadata`

---

## Relationships

### Category Relationships
```php
$category->parent;          // BelongsTo: Parent category
$category->children;        // HasMany: Child categories
$category->topics;          // HasMany: Topics in this category
$category->tags;            // MorphToMany: Tags
```

### Topic Relationships
```php
$topic->category;           // BelongsTo: Parent category
$topic->parent;             // BelongsTo: Parent topic
$topic->children;           // HasMany: Child topics
$topic->questions;          // BelongsToMany: Associated questions
$topic->tags;               // MorphToMany: Tags
$topic->ancestors;          // BelongsToMany: All ancestor topics (closure table)
$topic->descendants;        // BelongsToMany: All descendant topics (closure table)
```

### Question Relationships
```php
$question->difficultyLevel; // BelongsTo: Difficulty level
$question->creator;         // BelongsTo: User who created
$question->topics;          // BelongsToMany: Associated topics
$question->codeSnippets;    // HasMany: Code examples
$question->tags;            // MorphToMany: Tags
```

### DifficultyLevel Relationships
```php
$difficulty->questions;     // HasMany: Questions at this level
```

### Tag Relationships
```php
$tag->questions;            // MorphedByMany: Tagged questions
$tag->categories;           // MorphedByMany: Tagged categories
$tag->topics;               // MorphedByMany: Tagged topics
$tag->codeSnippets;         // MorphedByMany: Tagged code snippets
```

### CodeSnippet Relationships
```php
$snippet->question;         // BelongsTo: Parent question
$snippet->tags;             // MorphToMany: Tags
```

---

## Query Scopes

### Category Scopes
```php
Category::active()                      // Active categories only
Category::inactive()                    // Inactive categories only
Category::rootOnly()                    // Root categories (no parent)
Category::withChildren()                // Categories with parent
Category::bySlug($slug)                 // Find by slug
Category::ordered()                     // Order by order column and name
Category::withTag($tag)                 // Has specific tag
Category::withTags($tags)               // Has all specified tags
```

### Topic Scopes
```php
Topic::active()                         // Active topics only
Topic::inactive()                       // Inactive topics only
Topic::byCategory($categoryId)          // Filter by category
Topic::bySlug($slug)                    // Find by slug
Topic::rootOnly()                       // Root topics (no parent)
Topic::withChildren()                   // Topics with parent
Topic::ordered()                        // Order by order and name
Topic::byDepth($depth)                  // Topics at specific depth
Topic::maxDepth($depth)                 // Topics up to depth
Topic::withDifficulty($difficulty)      // Has questions of difficulty
Topic::withTag($tag)                    // Has specific tag
Topic::withTags($tags)                  // Has all specified tags
Topic::withNestedChildren($depth)       // Eager load nested children
Topic::filter($filters)                 // Apply multiple filters
```

### Question Scopes
```php
Question::published()                   // Published questions
Question::unpublished()                 // Unpublished questions
Question::verified()                    // Verified questions
Question::unverified()                  // Unverified questions
Question::byDifficulty($difficulty)     // Filter by difficulty
Question::byDifficultyLevel($level)     // Filter by difficulty level number
Question::minDifficulty($level)         // Minimum difficulty level
Question::maxDifficulty($level)         // Maximum difficulty level
Question::byCategory($category)         // Filter by category
Question::byTopic($topic)               // Filter by topic
Question::withTag($tag)                 // Has specific tag
Question::withTags($tags)               // Has all specified tags
Question::withAnyTag($tags)             // Has any of specified tags
Question::byCreator($userId)            // Filter by creator
Question::popular($minViews)            // Popular questions
Question::mostViewed($limit)            // Most viewed questions
Question::mostAttempted($limit)         // Most attempted questions
Question::highestSuccessRate($limit)    // Highest success rate questions
Question::search($term)                 // Full-text search
Question::filter($filters)              // Apply multiple filters
```

### DifficultyLevel Scopes
```php
DifficultyLevel::bySlug($slug)          // Find by slug
DifficultyLevel::byLevel($level)        // Find by level number
DifficultyLevel::minLevel($level)       // Minimum level
DifficultyLevel::maxLevel($level)       // Maximum level
DifficultyLevel::betweenLevels($min, $max) // Between levels
DifficultyLevel::ordered()              // Order by level
DifficultyLevel::withQuestionsCount()   // With question count
```

### Tag Scopes
```php
Tag::bySlug($slug)                      // Find by slug
Tag::popular($minUsage)                 // Popular tags
Tag::mostUsed($limit)                   // Most used tags
Tag::ordered()                          // Order by name
Tag::withUsageCounts()                  // With usage counts
Tag::search($term)                      // Search tags
```

### CodeSnippet Scopes
```php
CodeSnippet::byLanguage($language)      // Filter by language
CodeSnippet::byType($type)              // Filter by type
CodeSnippet::executable()               // Executable snippets only
CodeSnippet::nonExecutable()            // Non-executable snippets
CodeSnippet::ordered()                  // Order by order and created_at
CodeSnippet::forQuestion($questionId)   // For specific question
CodeSnippet::withTag($tag)              // Has specific tag
CodeSnippet::search($term)              // Search snippets
```

---

## Hierarchical Topic Management

### Nested Set Pattern
The Topic model uses the Nested Set pattern for efficient tree queries. Each topic has `_lft`, `_rgt`, and `depth` columns.

**Benefits:**
- Fast retrieval of entire subtrees
- Efficient ancestor/descendant queries
- Quick sibling access

**Usage:**
```php
// Get all descendants (including self)
$descendants = $topic->getDescendantsAndSelf();

// Get only descendants (excluding self)
$descendants = $topic->getDescendantsOnly();

// Get all ancestors (including self)
$ancestors = $topic->getAncestorsAndSelf();

// Get only ancestors (excluding self)
$ancestors = $topic->getAncestorsOnly();

// Check if topic is root
$isRoot = $topic->isRoot();

// Check if topic is leaf (no children)
$isLeaf = $topic->isLeaf();

// Check if topic has children
$hasChildren = $topic->hasChildren();

// Check relationships
$isDescendant = $topic->isDescendantOf($otherTopic);
$isAncestor = $topic->isAncestorOf($otherTopic);
$isSibling = $topic->isSiblingOf($otherTopic);

// Rebuild nested set for all topics
Topic::rebuildAllNestedSets();
```

### Closure Table Pattern
The Topic model also implements a Closure Table pattern for flexible ancestor/descendant relationships.

**Benefits:**
- Easy to move nodes
- Simple path queries
- Flexible depth queries

**Usage:**
```php
// Get all ancestors (via closure table)
$ancestors = $topic->ancestors;

// Get all descendants (via closure table)
$descendants = $topic->descendants;

// Rebuild closure table for all topics
Topic::rebuildAllClosureTables();
```

### Topic Hierarchy Operations

**Moving Topics:**
```php
// Make topic a child of another topic
$topic->makeChildOf($parentTopic);

// Make topic a root topic
$topic->makeRoot();

// Using service
$topicService->moveTopic($topicId, $newParentId);
```

**Reordering Topics:**
```php
// Reorder topics
$topicService->reorderTopics([
    0 => 1,  // order => topic_id
    1 => 3,
    2 => 2,
]);
```

**Duplicating Topics:**
```php
// Duplicate topic without children
$newTopic = $topicService->duplicateTopic($topicId, false);

// Duplicate topic with all children
$newTopic = $topicService->duplicateTopic($topicId, true);
```

**Getting Topic Paths:**
```php
// Get breadcrumb array
$breadcrumb = $topicService->getTopicBreadcrumb($topicId);
// Returns: [['id' => 1, 'name' => 'Root', 'slug' => 'root'], ...]

// Get full path
$path = $topicService->getTopicPath($topicId);
```

**Building Topic Trees:**
```php
// Build tree structure
$tree = $hierarchyService->buildTree($categoryId);

// Build flat tree with indentation
$flatTree = $hierarchyService->buildFlatTree($categoryId);
```

**Console Commands:**
```bash
# Rebuild nested set structure
php artisan topics:rebuild-hierarchy --nested-set

# Rebuild closure table
php artisan topics:rebuild-hierarchy --closure

# Rebuild both
php artisan topics:rebuild-hierarchy --all
```

---

## Traits

### Taggable Trait
Provides tagging functionality to models.

**Methods:**
```php
$model->tags;                           // Get all tags
$model->attachTag($tag);                // Attach a tag
$model->detachTag($tag);                // Detach a tag
$model->syncTags($tags);                // Sync tags
$model->hasTag($tag);                   // Check if has tag
$model->getTagNames();                  // Get tag names array
$model->getTagSlugs();                  // Get tag slugs array
```

**Scopes:**
```php
Model::withTag($tag);                   // Has specific tag
Model::withTags($tags);                 // Has all tags (AND)
Model::withAnyTag($tags);               // Has any tags (OR)
```

### HasMetadata Trait
Provides JSON metadata field manipulation.

**Methods:**
```php
$model->getMetadata('key', $default);   // Get metadata value
$model->setMetadata('key', $value);     // Set metadata value
$model->hasMetadata('key');             // Check if key exists
$model->removeMetadata('key');          // Remove metadata key
$model->mergeMetadata($array);          // Merge metadata
$model->clearMetadata();                // Clear all metadata
```

### HasNestedSet Trait
Provides nested set functionality for hierarchical models.

**Methods:**
```php
$model->parent;                         // Get parent
$model->children;                       // Get children
$model->getDescendantsAndSelf();        // Get descendants + self
$model->getDescendantsOnly();           // Get descendants only
$model->getAncestorsAndSelf();          // Get ancestors + self
$model->getAncestorsOnly();             // Get ancestors only
$model->isRoot();                       // Check if root
$model->isLeaf();                       // Check if leaf
$model->hasChildren();                  // Check if has children
$model->isDescendantOf($other);         // Check relationship
$model->isAncestorOf($other);           // Check relationship
$model->getDepth();                     // Get depth (0-based)
$model->getLevel();                     // Get level (1-based)
```

**Scopes:**
```php
Model::roots();                         // Root nodes only
Model::leaves();                        // Leaf nodes only
Model::withDepth($depth);               // Specific depth
Model::withMaxDepth($maxDepth);         // Up to depth
```

### Filterable Trait
Provides dynamic filtering capabilities.

**Methods:**
```php
Model::filter($filters);                // Apply array of filters
Model::search($term);                   // Search in searchable columns
Model::sort($column, $direction);       // Sort results
Model::whereDate($column, $operator, $value);
Model::whereDateBetween($column, $start, $end);
```

---

## Usage Examples

### Example 1: Creating a Question with Topics and Tags
```php
use App\Services\QuestionService;

$questionService = app(QuestionService::class);

$question = $questionService->createQuestion([
    'title' => 'What is PHP?',
    'question_text' => 'Explain PHP programming language',
    'difficulty_level_id' => 1,
    'created_by' => auth()->id(),
    'options' => ['A', 'B', 'C', 'D'],
    'correct_answer' => ['A'],
    'points' => 10,
    'is_published' => true,
    'topics' => [1, 2, 3],  // Topic IDs
    'tags' => ['php', 'programming', 'basics'],
    'code_snippets' => [
        [
            'title' => 'Example Code',
            'code' => '<?php echo "Hello"; ?>',
            'language' => 'php',
            'order' => 1,
        ],
    ],
]);
```

### Example 2: Filtering Questions
```php
$filters = [
    'difficulty' => 'intermediate',
    'category' => 'programming',
    'tags' => ['php', 'oop'],
    'published' => true,
    'verified' => true,
    'min_difficulty_level' => 2,
    'max_difficulty_level' => 4,
];

$questions = $questionService->filterQuestions($filters);
```

### Example 3: Building Topic Hierarchy
```php
use App\Services\TopicService;

$topicService = app(TopicService::class);

// Create root topic
$rootTopic = $topicService->createTopic([
    'category_id' => 1,
    'name' => 'Web Development',
    'slug' => 'web-development',
    'is_active' => true,
]);

// Create child topic
$childTopic = $topicService->createTopic([
    'category_id' => 1,
    'parent_id' => $rootTopic->id,
    'name' => 'Frontend',
    'slug' => 'frontend',
    'is_active' => true,
]);

// Get tree
$tree = $topicService->getTopicTree(1);
```

### Example 4: Working with Tags
```php
use App\Models\Question;
use App\Models\Tag;

$question = Question::find(1);

// Attach tags
$question->attachTag('php');
$question->attachTag('oop');

// Check if has tag
if ($question->hasTag('php')) {
    // Do something
}

// Get all tag names
$tagNames = $question->getTagNames();

// Sync tags (remove old, add new)
$question->syncTags(['php', 'laravel', 'eloquent']);
```

### Example 5: Topic Hierarchy Operations
```php
$topic = Topic::find(5);

// Get all ancestors
$ancestors = $topic->getAncestorsOnly();

// Get all descendants
$descendants = $topic->getDescendantsOnly();

// Get siblings
$siblings = $topic->getSiblings();

// Move to new parent
$topic->makeChildOf(Topic::find(3));

// Check depth
$depth = $topic->getDepth();
$level = $topic->getLevel();
```

### Example 6: Question Statistics
```php
$question = Question::find(1);

// Record a view
$question->incrementViews();

// Record an attempt
$question->recordAttempt($success = true);

// Get success rate
$successRate = $question->getSuccessRate();

// Check difficulty based on success rate
if ($question->isEasy()) {
    // Success rate >= 70%
}

if ($question->isHard()) {
    // Success rate <= 30%
}
```

### Example 7: Using Repositories
```php
use App\Repositories\QuestionRepository;

$repository = app(QuestionRepository::class);

// Get most viewed questions
$popular = $repository->getMostViewed(10);

// Get random questions with filters
$random = $repository->getRandomQuestions(5, [
    'difficulty' => 'easy',
    'category' => 'programming',
]);

// Search questions
$results = $repository->searchQuestions('array functions', true);
```

### Example 8: Metadata Usage
```php
$question = Question::find(1);

// Set metadata
$question->setMetadata('author.name', 'John Doe');
$question->setMetadata('source.url', 'https://example.com');

// Get metadata
$authorName = $question->getMetadata('author.name');

// Check if metadata exists
if ($question->hasMetadata('source.url')) {
    // Do something
}

// Merge metadata
$question->mergeMetadata([
    'difficulty_factors' => ['time', 'complexity'],
    'last_reviewed' => now(),
]);
```

---

## Best Practices

1. **Always use repositories** for data access to keep controllers thin
2. **Use services** for complex business logic
3. **Leverage query scopes** for reusable query logic
4. **Rebuild hierarchies** after bulk operations on topics
5. **Use transactions** when creating/updating related entities
6. **Eager load relationships** to avoid N+1 queries
7. **Use soft deletes** to maintain data integrity
8. **Tag consistently** using slugs for better filtering
9. **Maintain metadata** for extensible data storage
10. **Monitor statistics** (views, attempts) for insights

---

## Performance Tips

1. **Index frequently queried columns** (already set in migrations)
2. **Use eager loading** for relationships
3. **Cache expensive queries** (topic trees, tag clouds)
4. **Paginate large result sets**
5. **Use nested set queries** for tree operations
6. **Rebuild hierarchies** asynchronously for large datasets
7. **Use full-text search** for question searches
8. **Consider read replicas** for high-traffic applications

---

This documentation provides a comprehensive guide to using the Eloquent models in the application. For more specific use cases, refer to the individual model, repository, and service classes.
