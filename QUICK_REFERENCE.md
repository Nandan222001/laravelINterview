# Quick Reference Guide

## Common Operations

### Questions

```php
// Create question
$question = Question::create([
    'title' => 'Question Title',
    'question_text' => 'Question content',
    'difficulty_level_id' => 1,
    'created_by' => auth()->id(),
    'options' => ['A', 'B', 'C', 'D'],
    'correct_answer' => ['A'],
    'points' => 10,
]);

// Filter questions
Question::published()->verified()->byDifficulty('easy')->get();
Question::byCategory('programming')->withTags(['php', 'oop'])->get();
Question::search('array functions')->get();

// Statistics
$question->incrementViews();
$question->recordAttempt($success = true);
$successRate = $question->getSuccessRate();
```

### Topics

```php
// Create topic
$topic = Topic::create([
    'category_id' => 1,
    'name' => 'Topic Name',
    'slug' => 'topic-name',
]);

// Hierarchy operations
$topic->makeChildOf($parent);
$topic->makeRoot();
$descendants = $topic->getDescendantsOnly();
$ancestors = $topic->getAncestorsOnly();

// Filter topics
Topic::byCategory(1)->active()->ordered()->get();
Topic::byDepth(2)->get();
Topic::withDifficulty('intermediate')->get();
```

### Categories

```php
// Create category
$category = Category::create([
    'name' => 'Category Name',
    'slug' => 'category-name',
]);

// Get hierarchy
$roots = Category::rootOnly()->active()->get();
$children = $category->children;

// Filter
Category::active()->withTag('featured')->get();
```

### Tags

```php
// Attach/detach tags
$model->attachTag('php');
$model->detachTag('oop');
$model->syncTags(['php', 'laravel', 'eloquent']);

// Check tags
$model->hasTag('php');
$names = $model->getTagNames();
$slugs = $model->getTagSlugs();

// Query by tags
Question::withTag('php')->get();
Question::withTags(['php', 'oop'])->get();
Question::withAnyTag(['php', 'javascript'])->get();
```

### Metadata

```php
// Set metadata
$model->setMetadata('key', 'value');
$model->setMetadata('author.name', 'John');

// Get metadata
$value = $model->getMetadata('key', 'default');

// Check/remove
$model->hasMetadata('key');
$model->removeMetadata('key');
$model->mergeMetadata(['key' => 'value']);
$model->clearMetadata();
```

## Query Scopes Cheat Sheet

### Question Scopes
- `published()` / `unpublished()`
- `verified()` / `unverified()`
- `byDifficulty($id|$slug)`
- `byCategory($id|$slug)`
- `byTopic($id|$slug)`
- `withTag($tag)` / `withTags($tags)` / `withAnyTag($tags)`
- `mostViewed($limit)` / `mostAttempted($limit)`
- `search($term)`
- `filter($filters)`

### Topic Scopes
- `active()` / `inactive()`
- `byCategory($id|$slug)`
- `rootOnly()`
- `byDepth($depth)` / `maxDepth($depth)`
- `withTag($tag)` / `withTags($tags)`
- `ordered()`
- `filter($filters)`

### Category Scopes
- `active()` / `inactive()`
- `rootOnly()` / `withChildren()`
- `withTag($tag)` / `withTags($tags)`
- `ordered()`

### Tag Scopes
- `popular($minUsage)`
- `mostUsed($limit)`
- `search($term)`
- `ordered()`

## Services

```php
// QuestionService
$questionService->createQuestion($data);
$questionService->updateQuestion($id, $data);
$questionService->publishQuestion($id);
$questionService->verifyQuestion($id);
$questionService->filterQuestions($filters);
$questionService->recordQuestionView($id);
$questionService->recordQuestionAttempt($id, $success);

// TopicService
$topicService->createTopic($data);
$topicService->getTopicTree($categoryId);
$topicService->moveTopic($id, $parentId);
$topicService->reorderTopics($order);
$topicService->duplicateTopic($id, $includeChildren);

// TopicHierarchyService
$hierarchyService->buildTree($categoryId);
$hierarchyService->buildFlatTree($categoryId);
$hierarchyService->getPath($topic);
$hierarchyService->getBreadcrumb($topic);
```

## Console Commands

```bash
# Rebuild topic hierarchy
php artisan topics:rebuild-hierarchy --nested-set
php artisan topics:rebuild-hierarchy --closure
php artisan topics:rebuild-hierarchy --all
```

## Common Filters

```php
// Question filters
$filters = [
    'difficulty' => 'intermediate',
    'category' => 'programming',
    'topic' => 'php-basics',
    'tags' => ['php', 'oop'],
    'published' => true,
    'verified' => true,
    'min_difficulty_level' => 2,
    'max_difficulty_level' => 4,
    'creator' => 1,
];
$questions = $questionService->filterQuestions($filters);

// Topic filters
$filters = [
    'category' => 1,
    'tags' => ['featured'],
    'active' => true,
    'depth' => 2,
    'max_depth' => 3,
    'root_only' => false,
];
$topics = $topicService->filterTopics($filters);
```

## Relationships Loading

```php
// Eager load relationships
Question::with(['difficultyLevel', 'topics', 'tags', 'codeSnippets'])->get();
Topic::with(['category', 'parent', 'children'])->get();
Category::with(['children', 'topics', 'tags'])->get();

// Nested eager loading
Question::with('topics.category')->get();
Topic::with('children.children.children')->get();
```

## Hierarchical Queries

```php
// Get all descendants
$topic->getDescendantsAndSelf();
$topic->getDescendantsOnly();

// Get all ancestors
$topic->getAncestorsAndSelf();
$topic->getAncestorsOnly();

// Using closure table
$topic->ancestors; // BelongsToMany relationship
$topic->descendants; // BelongsToMany relationship

// Check relationships
$topic->isRoot();
$topic->isLeaf();
$topic->hasChildren();
$topic->isDescendantOf($other);
$topic->isAncestorOf($other);

// Get specific depth topics
Topic::byDepth(2)->get();
Topic::maxDepth(3)->get();
```

## JSON Fields

```php
// Question options
$question->options = ['Option A', 'Option B', 'Option C'];
$question->correct_answer = ['Option A'];
$question->hints = ['Hint 1', 'Hint 2'];

// Access as array
foreach ($question->options as $option) {
    echo $option;
}

// Metadata (all models)
$model->metadata = ['key' => 'value'];
$value = $model->metadata['key'];
```

## Best Practices

1. **Always use eager loading** to avoid N+1 queries
2. **Use scopes** instead of where() for reusable queries
3. **Use services** for business logic
4. **Use repositories** for data access
5. **Rebuild hierarchies** after bulk operations
6. **Tag with slugs** for consistency
7. **Use transactions** for multi-step operations
8. **Cache frequently accessed** tree structures
9. **Paginate large result sets**
10. **Use soft deletes** to maintain referential integrity
