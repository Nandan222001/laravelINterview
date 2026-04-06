# Category and Topic API Endpoints Documentation

This document describes the hierarchical data management endpoints for Categories and Topics, including nested JSON responses, breadcrumb generation, and parent-child relationship filtering.

## Category Endpoints

### 1. List Categories (Paginated)

**Endpoint:** `GET /api/categories`

**Description:** Retrieve a paginated list of categories with optional filtering.

**Query Parameters:**
- `per_page` (integer, optional): Number of items per page (default: 15)
- `search` (string, optional): Full-text search in category name and description
- `is_active` (boolean, optional): Filter by active status
- `parent_id` (integer|null, optional): Filter by parent category ID (use "root" for root categories)
- `has_children` (boolean, optional): Filter categories that have children
- `tag_slugs` (array, optional): Filter by tag slugs

**Response Example:**
```json
{
  "success": true,
  "message": "Categories retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Programming Languages",
      "slug": "programming-languages",
      "description": "Various programming languages",
      "parent_id": null,
      "order": 1,
      "is_active": true,
      "metadata": {},
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "pagination": {...}
  }
}
```

### 2. Get Category Tree

**Endpoint:** `GET /api/categories/tree`

**Description:** Retrieve hierarchical category tree with nested children.

**Query Parameters:**
- `max_depth` (integer, optional): Maximum depth of nesting to retrieve
- `active_only` (boolean, optional): Only return active categories (default: true)
- `parent_id` (integer, optional): Start tree from specific parent category

**Response Example:**
```json
{
  "success": true,
  "message": "Category tree retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Programming Languages",
      "slug": "programming-languages",
      "description": "Various programming languages",
      "parent_id": null,
      "order": 1,
      "is_active": true,
      "metadata": {},
      "children": [
        {
          "id": 2,
          "name": "JavaScript",
          "slug": "javascript",
          "description": "JavaScript programming",
          "parent_id": 1,
          "order": 1,
          "is_active": true,
          "metadata": {},
          "children": [
            {
              "id": 3,
              "name": "ES6+",
              "slug": "es6-plus",
              "description": "Modern JavaScript features",
              "parent_id": 2,
              "order": 1,
              "is_active": true,
              "metadata": {},
              "children": []
            }
          ]
        }
      ]
    }
  ]
}
```

### 3. Get Single Category

**Endpoint:** `GET /api/categories/{id}`

**Description:** Retrieve a single category with optional related data.

**Query Parameters:**
- `include_children` (boolean, optional): Include direct children
- `include_topics` (boolean, optional): Include topics in this category
- `include_breadcrumb` (boolean, optional): Include breadcrumb trail

**Response Example (with breadcrumb):**
```json
{
  "success": true,
  "message": "Category retrieved successfully",
  "data": {
    "category": {
      "id": 3,
      "name": "ES6+",
      "slug": "es6-plus",
      "description": "Modern JavaScript features",
      "parent_id": 2,
      "order": 1,
      "is_active": true,
      "metadata": {},
      "parent": {...},
      "children": [...],
      "topics": [...]
    },
    "breadcrumb": [
      {
        "id": 1,
        "name": "Programming Languages",
        "slug": "programming-languages",
        "parent_id": null
      },
      {
        "id": 2,
        "name": "JavaScript",
        "slug": "javascript",
        "parent_id": 1
      },
      {
        "id": 3,
        "name": "ES6+",
        "slug": "es6-plus",
        "parent_id": 2
      }
    ]
  }
}
```

### 4. Get Category Children

**Endpoint:** `GET /api/categories/{id}/children`

**Description:** Get direct children of a category.

**Query Parameters:**
- `active_only` (boolean, optional): Only return active categories (default: true)

**Response Example:**
```json
{
  "success": true,
  "message": "Child categories retrieved successfully",
  "data": [
    {
      "id": 2,
      "name": "JavaScript",
      "slug": "javascript",
      "description": "JavaScript programming",
      "parent_id": 1,
      "order": 1,
      "is_active": true,
      "metadata": {}
    }
  ]
}
```

### 5. Get Category Breadcrumb

**Endpoint:** `GET /api/categories/{id}/breadcrumb`

**Description:** Get the breadcrumb trail for a category (all ancestors).

**Response Example:**
```json
{
  "success": true,
  "message": "Breadcrumb retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Programming Languages",
      "slug": "programming-languages",
      "parent_id": null
    },
    {
      "id": 2,
      "name": "JavaScript",
      "slug": "javascript",
      "parent_id": 1
    },
    {
      "id": 3,
      "name": "ES6+",
      "slug": "es6-plus",
      "parent_id": 2
    }
  ]
}
```

## Topic Endpoints

### 1. List Topics (Paginated)

**Endpoint:** `GET /api/topics`

**Description:** Retrieve a paginated list of topics with optional filtering.

**Query Parameters:**
- `per_page` (integer, optional): Number of items per page (default: 15)
- `search` (string, optional): Full-text search in topic name, description, and learning objectives
- `category_id` (integer, optional): Filter by category
- `is_active` (boolean, optional): Filter by active status
- `parent_id` (integer|null, optional): Filter by parent topic ID
- `depth` (integer, optional): Filter by specific depth level
- `has_children` (boolean, optional): Filter topics that have children
- `has_questions` (boolean, optional): Filter topics that have questions
- `tag_slugs` (array, optional): Filter by tag slugs

**Response Example:**
```json
{
  "success": true,
  "message": "Topics retrieved successfully",
  "data": [
    {
      "id": 1,
      "category_id": 1,
      "parent_id": null,
      "name": "Functions",
      "slug": "functions",
      "description": "JavaScript functions",
      "learning_objectives": "Understand function declaration and usage",
      "order": 1,
      "is_active": true,
      "depth": 0,
      "metadata": {},
      "category": {...},
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "pagination": {...}
  }
}
```

### 2. Get Topic Tree

**Endpoint:** `GET /api/topics/tree`

**Description:** Retrieve hierarchical topic tree with nested children.

**Query Parameters:**
- `category_id` (integer, optional): Filter by category
- `max_depth` (integer, optional): Maximum depth of nesting to retrieve
- `active_only` (boolean, optional): Only return active topics (default: true)
- `parent_id` (integer, optional): Start tree from specific parent topic

**Response Example:**
```json
{
  "success": true,
  "message": "Topic tree retrieved successfully",
  "data": [
    {
      "id": 1,
      "category_id": 1,
      "parent_id": null,
      "name": "Functions",
      "slug": "functions",
      "description": "JavaScript functions",
      "learning_objectives": "Understand function declaration and usage",
      "order": 1,
      "is_active": true,
      "depth": 0,
      "metadata": {},
      "children": [
        {
          "id": 2,
          "category_id": 1,
          "parent_id": 1,
          "name": "Arrow Functions",
          "slug": "arrow-functions",
          "description": "ES6 arrow function syntax",
          "learning_objectives": "Master arrow function syntax and use cases",
          "order": 1,
          "is_active": true,
          "depth": 1,
          "metadata": {},
          "children": []
        }
      ]
    }
  ]
}
```

### 3. Get Single Topic

**Endpoint:** `GET /api/topics/{id}`

**Description:** Retrieve a single topic with optional related data.

**Query Parameters:**
- `include_children` (boolean, optional): Include direct children
- `include_questions` (boolean, optional): Include questions for this topic
- `include_breadcrumb` (boolean, optional): Include breadcrumb trail

**Response Example (with breadcrumb):**
```json
{
  "success": true,
  "message": "Topic retrieved successfully",
  "data": {
    "topic": {
      "id": 2,
      "category_id": 1,
      "parent_id": 1,
      "name": "Arrow Functions",
      "slug": "arrow-functions",
      "description": "ES6 arrow function syntax",
      "learning_objectives": "Master arrow function syntax and use cases",
      "order": 1,
      "is_active": true,
      "depth": 1,
      "metadata": {},
      "category": {...},
      "parent": {...},
      "children": [...],
      "questions": [...]
    },
    "breadcrumb": [
      {
        "id": 1,
        "name": "Functions",
        "slug": "functions",
        "parent_id": null,
        "category_id": 1
      },
      {
        "id": 2,
        "name": "Arrow Functions",
        "slug": "arrow-functions",
        "parent_id": 1,
        "category_id": 1
      }
    ]
  }
}
```

### 4. Get Topic Questions

**Endpoint:** `GET /api/topics/{id}/questions`

**Description:** Get paginated questions for a topic with optional filters.

**Query Parameters:**
- `per_page` (integer, optional): Number of items per page (default: 15)
- `include_descendants` (boolean, optional): Include questions from descendant topics (default: false)
- `difficulty` (string|integer, optional): Filter by difficulty level slug or ID
- `published` (boolean, optional): Filter by published status
- `verified` (boolean, optional): Filter by verified status
- `tags` (array, optional): Filter by tag slugs
- `min_difficulty_level` (integer, optional): Minimum difficulty level
- `max_difficulty_level` (integer, optional): Maximum difficulty level

**Response Example:**
```json
{
  "success": true,
  "message": "Questions retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "What is an arrow function?",
      "question_text": "Explain the concept of arrow functions in JavaScript.",
      "explanation": "Arrow functions are a concise way to write functions...",
      "options": ["Option A", "Option B", "Option C"],
      "hints": ["Think about ES6 syntax"],
      "points": 10,
      "time_limit": 300,
      "is_published": true,
      "is_verified": true,
      "view_count": 150,
      "attempt_count": 100,
      "success_count": 75,
      "success_rate": 75.0,
      "metadata": {},
      "difficulty_level": {...},
      "topics": [...],
      "tags": [...],
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "pagination": {...}
  }
}
```

### 5. Get Topic Children

**Endpoint:** `GET /api/topics/{id}/children`

**Description:** Get direct children of a topic.

**Query Parameters:**
- `active_only` (boolean, optional): Only return active topics (default: true)

**Response Example:**
```json
{
  "success": true,
  "message": "Child topics retrieved successfully",
  "data": [
    {
      "id": 2,
      "category_id": 1,
      "parent_id": 1,
      "name": "Arrow Functions",
      "slug": "arrow-functions",
      "description": "ES6 arrow function syntax",
      "learning_objectives": "Master arrow function syntax and use cases",
      "order": 1,
      "is_active": true,
      "depth": 1,
      "metadata": {}
    }
  ]
}
```

### 6. Get Topic Breadcrumb

**Endpoint:** `GET /api/topics/{id}/breadcrumb`

**Description:** Get the breadcrumb trail for a topic (all ancestors).

**Response Example:**
```json
{
  "success": true,
  "message": "Breadcrumb retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Functions",
      "slug": "functions",
      "parent_id": null,
      "category_id": 1
    },
    {
      "id": 2,
      "name": "Arrow Functions",
      "slug": "arrow-functions",
      "parent_id": 1,
      "category_id": 1
    }
  ]
}
```

### 7. Get Topic Descendants

**Endpoint:** `GET /api/topics/{id}/descendants`

**Description:** Get all descendant topics (children, grandchildren, etc.).

**Query Parameters:**
- `max_depth` (integer, optional): Limit the depth of descendants to retrieve

**Response Example:**
```json
{
  "success": true,
  "message": "Descendant topics retrieved successfully",
  "data": [
    {
      "id": 2,
      "category_id": 1,
      "parent_id": 1,
      "name": "Arrow Functions",
      "slug": "arrow-functions",
      "depth": 1,
      "...": "..."
    },
    {
      "id": 3,
      "category_id": 1,
      "parent_id": 2,
      "name": "Arrow Function Syntax",
      "slug": "arrow-function-syntax",
      "depth": 2,
      "...": "..."
    }
  ]
}
```

### 8. Get Topic Ancestors

**Endpoint:** `GET /api/topics/{id}/ancestors`

**Description:** Get all ancestor topics (parent, grandparent, etc.).

**Response Example:**
```json
{
  "success": true,
  "message": "Ancestor topics retrieved successfully",
  "data": [
    {
      "id": 1,
      "category_id": 1,
      "parent_id": null,
      "name": "Functions",
      "slug": "functions",
      "depth": 0,
      "...": "..."
    }
  ]
}
```

## Common Response Formats

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {...}
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {...}
}
```

### Not Found Response
```json
{
  "success": false,
  "message": "Resource not found"
}
```

## Features

### 1. Hierarchical Data Retrieval
- Supports nested JSON responses with configurable depth
- Parent-child relationships maintained through `parent_id`
- Efficient nested set model for topics with `_lft`, `_rgt`, and `depth` fields

### 2. Breadcrumb Generation
- Automatic breadcrumb trail generation for navigation
- Shows complete path from root to current item
- Ordered from top-level to current item

### 3. Parent-Child Filtering
- Filter by parent ID to get specific branches
- Get only root items (parent_id = null)
- Get direct children or all descendants

### 4. Advanced Filtering
- Full-text search across relevant fields
- Filter by active/inactive status
- Filter by presence of children or questions
- Filter by tags
- Category-specific filtering for topics

### 5. Performance Optimizations
- Eager loading of relationships to prevent N+1 queries
- Configurable depth limits for nested data
- Pagination support for large datasets
- Indexed fields for fast queries

## Authentication

All endpoints require authentication using Laravel Sanctum. Include the API token in the Authorization header:

```
Authorization: Bearer {your-api-token}
```

## Rate Limiting

API endpoints are rate-limited according to Laravel's default throttle settings. Refer to the main API documentation for details.
