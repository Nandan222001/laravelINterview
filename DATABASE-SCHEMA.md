# Database Schema Documentation

## Overview
This document describes the database schema for the question management system with support for categories, topics, questions, code snippets, tags, and difficulty levels.

## Tables

### 1. difficulty_levels
Stores difficulty levels for questions.

**Columns:**
- `id` - Primary key
- `name` - Difficulty level name (unique)
- `slug` - URL-friendly slug (unique, indexed)
- `description` - Optional description
- `level` - Numeric level (unique, indexed)
- `color` - Color code for UI display
- `metadata` - JSON column for additional data
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `slug` - For URL lookups
- `level` - For ordering by difficulty

**Relationships:**
- Has many Questions

---

### 2. categories
Hierarchical category system with parent-child relationships.

**Columns:**
- `id` - Primary key
- `name` - Category name
- `slug` - URL-friendly slug (unique, indexed)
- `description` - Optional description
- `parent_id` - Self-referencing foreign key (nullable, indexed)
- `order` - Display order
- `is_active` - Active status (indexed)
- `metadata` - JSON column for additional data
- `created_at`, `updated_at` - Timestamps
- `deleted_at` - Soft delete timestamp

**Indexes:**
- `slug` - For URL lookups
- `parent_id` - For parent category queries
- `is_active` - For filtering active categories
- `parent_id, order` - Composite index for ordered children

**Relationships:**
- Self-referencing (parent/children)
- Has many Topics
- Polymorphic many-to-many with Tags

---

### 3. topics
Topics within categories.

**Columns:**
- `id` - Primary key
- `category_id` - Foreign key to categories (indexed)
- `name` - Topic name
- `slug` - URL-friendly slug (unique, indexed)
- `description` - Optional description
- `learning_objectives` - Learning objectives text
- `order` - Display order
- `is_active` - Active status (indexed)
- `metadata` - JSON column for additional data
- `created_at`, `updated_at` - Timestamps
- `deleted_at` - Soft delete timestamp

**Indexes:**
- `slug` - For URL lookups
- `category_id` - For category queries
- `is_active` - For filtering active topics
- `category_id, order` - Composite index for ordered topics

**Relationships:**
- Belongs to Category
- Many-to-many with Questions (via question_topic pivot)
- Polymorphic many-to-many with Tags

---

### 4. questions
Main questions table with comprehensive metadata.

**Columns:**
- `id` - Primary key
- `difficulty_level_id` - Foreign key to difficulty_levels (indexed)
- `created_by` - Foreign key to users (nullable, indexed)
- `title` - Question title
- `question_text` - Full question text
- `explanation` - Answer explanation
- `options` - JSON column for answer options
- `correct_answer` - JSON column for correct answer(s)
- `hints` - JSON array of hints
- `points` - Points awarded
- `time_limit` - Time limit in seconds
- `is_published` - Publication status (indexed)
- `is_verified` - Verification status (indexed)
- `view_count` - Number of views
- `attempt_count` - Number of attempts
- `success_count` - Number of successful attempts
- `metadata` - JSON column for additional data
- `created_at`, `updated_at` - Timestamps
- `deleted_at` - Soft delete timestamp

**Indexes:**
- `difficulty_level_id` - For difficulty filtering
- `created_by` - For user queries
- `is_published` - For published questions
- `is_verified` - For verified questions
- `is_published, is_verified` - Composite for filtering
- Full-text index on `title, question_text` - For search

**Relationships:**
- Belongs to DifficultyLevel
- Belongs to User (creator)
- Many-to-many with Topics (via question_topic pivot)
- Has many CodeSnippets
- Polymorphic many-to-many with Tags

---

### 5. code_snippets
Code examples associated with questions.

**Columns:**
- `id` - Primary key
- `question_id` - Foreign key to questions (indexed)
- `title` - Snippet title
- `description` - Optional description
- `code` - The actual code
- `language` - Programming language (indexed)
- `type` - Snippet type: example, solution, test, template (indexed)
- `order` - Display order
- `is_executable` - Whether code can be executed
- `expected_output` - Expected output for executable code
- `metadata` - JSON column for additional data
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `question_id` - For question queries
- `language` - For filtering by language
- `type` - For filtering by type
- `question_id, order` - Composite for ordered snippets

**Relationships:**
- Belongs to Question
- Polymorphic many-to-many with Tags

---

### 6. tags
Reusable tags for multiple entity types.

**Columns:**
- `id` - Primary key
- `name` - Tag name (indexed)
- `slug` - URL-friendly slug (unique, indexed)
- `description` - Optional description
- `color` - Color code for UI display
- `usage_count` - Number of times used (indexed)
- `metadata` - JSON column for additional data
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `slug` - For URL lookups
- `name` - For tag name searches
- `usage_count` - For popular tags

**Relationships:**
- Polymorphic many-to-many with Questions, Categories, Topics, CodeSnippets

---

### 7. taggables (Polymorphic Pivot)
Polymorphic many-to-many relationship table for tags.

**Columns:**
- `id` - Primary key
- `tag_id` - Foreign key to tags (indexed)
- `taggable_id` - ID of the tagged entity (indexed)
- `taggable_type` - Type of the tagged entity (indexed)
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `tag_id` - For tag queries
- `taggable_id, taggable_type` - Composite for entity lookups
- Unique constraint on `tag_id, taggable_id, taggable_type` - Prevent duplicates

**Supported Taggable Types:**
- App\Models\Question
- App\Models\Category
- App\Models\Topic
- App\Models\CodeSnippet

---

### 8. question_topic (Pivot)
Many-to-many relationship between questions and topics.

**Columns:**
- `id` - Primary key
- `question_id` - Foreign key to questions (indexed)
- `topic_id` - Foreign key to topics (indexed)
- `order` - Display order for questions within a topic
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `question_id` - For question queries
- `topic_id` - For topic queries
- `topic_id, order` - Composite for ordered questions
- Unique constraint on `question_id, topic_id` - Prevent duplicates

---

## Relationships Summary

### One-to-Many
- DifficultyLevel → Questions
- Category → Categories (self-referencing, parent-child)
- Category → Topics
- Question → CodeSnippets
- User → Questions (created_by)
- Topic → Questions (via pivot)

### Many-to-Many
- Question ↔ Topic (via question_topic pivot)

### Polymorphic Many-to-Many
- Tag ↔ Question (via taggables)
- Tag ↔ Category (via taggables)
- Tag ↔ Topic (via taggables)
- Tag ↔ CodeSnippet (via taggables)

---

## JSON Column Usage

All tables include a `metadata` JSON column for flexible data storage:

### categories.metadata
```json
{
  "icon": "folder|book|code|database",
  "color": "string"
}
```

### topics.metadata
```json
{
  "estimated_time": 120,
  "prerequisites": ["topic1", "topic2"]
}
```

### questions.metadata
```json
{
  "source": "user_generated|imported|ai_generated",
  "version": "1.0"
}
```

### code_snippets.metadata
```json
{
  "version": "1.0",
  "framework": "Laravel|React|etc"
}
```

### tags.metadata
```json
{
  "category": "language|framework|concept|tool",
  "featured": true
}
```

### difficulty_levels.metadata
```json
{
  "icon": "star|trophy|medal",
  "badge_color": "string"
}
```

---

## Key Features

1. **Hierarchical Categories**: Self-referencing parent-child relationships
2. **Flexible Tagging**: Polymorphic relationships allow tagging any entity
3. **Many-to-Many Topics**: Questions can belong to multiple topics
4. **Soft Deletes**: Categories and Topics use soft deletes
5. **Full-Text Search**: Questions table has full-text index on title and question_text
6. **JSON Metadata**: All tables support flexible metadata storage
7. **Comprehensive Indexing**: Strategic indexes on foreign keys, status flags, and searchable columns
8. **Statistics Tracking**: Questions track views, attempts, and success counts
9. **Ordered Relationships**: Support for custom ordering in hierarchical and many-to-many relationships

---

## Migration Order

Migrations are numbered to ensure proper execution order:

1. `2024_01_01_000004_create_difficulty_levels_table.php`
2. `2024_01_01_000005_create_categories_table.php`
3. `2024_01_01_000006_create_topics_table.php`
4. `2024_01_01_000007_create_questions_table.php`
5. `2024_01_01_000008_create_code_snippets_table.php`
6. `2024_01_01_000009_create_tags_table.php`
7. `2024_01_01_000010_create_taggables_table.php`
8. `2024_01_01_000011_create_question_topic_table.php`

---

## Sample Queries

### Get all questions for a topic with difficulty level
```php
$topic->questions()
    ->with('difficultyLevel')
    ->where('is_published', true)
    ->get();
```

### Get all tags for a question
```php
$question->tags;
```

### Get all questions tagged with specific tag
```php
$tag->questions()
    ->where('is_published', true)
    ->where('is_verified', true)
    ->get();
```

### Get category hierarchy
```php
Category::with('children.children')->whereNull('parent_id')->get();
```

### Search questions by title or text
```php
Question::whereFullText(['title', 'question_text'], 'search term')->get();
```
