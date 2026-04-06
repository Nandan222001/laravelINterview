# User Progress Tracking API Documentation

## Overview

This API provides endpoints for tracking user progress through questions, managing bookmarks, retrieving statistics, and getting personalized question recommendations based on performance and difficulty progression.

## Base URL

All endpoints require authentication via Laravel Sanctum and are prefixed with `/api`.

## Authentication

All endpoints require a valid Sanctum token in the `Authorization` header:

```
Authorization: Bearer {your-token}
```

---

## Progress Tracking Endpoints

### Mark Question as Attempted

Mark a question as attempted by the user.

**Endpoint:** `POST /api/progress/attempted`

**Request Body:**
```json
{
  "question_id": 1,
  "user_answer": ["answer1", "answer2"],
  "time_spent": 120
}
```

**Parameters:**
- `question_id` (required, integer) - The ID of the question
- `user_answer` (optional, array) - The user's answer(s)
- `time_spent` (optional, integer) - Time spent in seconds

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Question marked as attempted",
  "data": {
    "id": 1,
    "user_id": 1,
    "question_id": 1,
    "status": "attempted",
    "is_bookmarked": false,
    "is_correct": null,
    "attempts_count": 1,
    "time_spent": 120,
    "user_answer": ["answer1", "answer2"],
    "first_attempted_at": "2024-01-01T10:00:00Z",
    "last_attempted_at": "2024-01-01T10:00:00Z",
    "completed_at": null,
    "created_at": "2024-01-01T10:00:00Z",
    "updated_at": "2024-01-01T10:00:00Z"
  }
}
```

---

### Mark Question as Completed

Mark a question as completed with correctness status.

**Endpoint:** `POST /api/progress/completed`

**Request Body:**
```json
{
  "question_id": 1,
  "is_correct": true,
  "user_answer": ["correct_answer"],
  "time_spent": 180
}
```

**Parameters:**
- `question_id` (required, integer) - The ID of the question
- `is_correct` (required, boolean) - Whether the answer was correct
- `user_answer` (optional, array) - The user's answer(s)
- `time_spent` (optional, integer) - Time spent in seconds

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Question marked as completed",
  "data": {
    "id": 1,
    "user_id": 1,
    "question_id": 1,
    "status": "completed",
    "is_bookmarked": false,
    "is_correct": true,
    "attempts_count": 2,
    "time_spent": 180,
    "user_answer": ["correct_answer"],
    "first_attempted_at": "2024-01-01T10:00:00Z",
    "last_attempted_at": "2024-01-01T10:02:00Z",
    "completed_at": "2024-01-01T10:02:00Z",
    "created_at": "2024-01-01T10:00:00Z",
    "updated_at": "2024-01-01T10:02:00Z"
  }
}
```

---

## Bookmark Management Endpoints

### Bookmark Question

Add a question to bookmarks.

**Endpoint:** `POST /api/progress/bookmark/{questionId}`

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Question bookmarked",
  "data": {
    "id": 1,
    "user_id": 1,
    "question_id": 1,
    "status": "not_attempted",
    "is_bookmarked": true,
    "is_correct": null,
    "attempts_count": 0,
    "time_spent": null,
    "user_answer": null,
    "first_attempted_at": null,
    "last_attempted_at": null,
    "completed_at": null,
    "created_at": "2024-01-01T10:00:00Z",
    "updated_at": "2024-01-01T10:00:00Z"
  }
}
```

---

### Unbookmark Question

Remove a question from bookmarks.

**Endpoint:** `DELETE /api/progress/bookmark/{questionId}`

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Question unbookmarked",
  "data": {
    "id": 1,
    "user_id": 1,
    "question_id": 1,
    "status": "not_attempted",
    "is_bookmarked": false,
    "is_correct": null,
    "attempts_count": 0,
    "time_spent": null,
    "user_answer": null,
    "first_attempted_at": null,
    "last_attempted_at": null,
    "completed_at": null,
    "created_at": "2024-01-01T10:00:00Z",
    "updated_at": "2024-01-01T10:00:00Z"
  }
}
```

---

### Toggle Bookmark

Toggle bookmark status of a question.

**Endpoint:** `POST /api/progress/toggle-bookmark/{questionId}`

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Question bookmarked",
  "data": {
    "id": 1,
    "user_id": 1,
    "question_id": 1,
    "status": "not_attempted",
    "is_bookmarked": true,
    "is_correct": null,
    "attempts_count": 0,
    "time_spent": null,
    "user_answer": null,
    "first_attempted_at": null,
    "last_attempted_at": null,
    "completed_at": null,
    "created_at": "2024-01-01T10:00:00Z",
    "updated_at": "2024-01-01T10:00:00Z"
  }
}
```

---

## Statistics Endpoints

### Get User Statistics

Get comprehensive statistics about user progress.

**Endpoint:** `GET /api/user/stats`

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "User statistics retrieved successfully",
  "data": {
    "overall": {
      "total_attempted": 50,
      "total_completed": 45,
      "total_bookmarked": 10,
      "total_correct": 38,
      "total_incorrect": 7,
      "avg_time_spent": 145.5,
      "total_attempts": 65,
      "completion_rate": 90.0,
      "accuracy_rate": 84.44
    },
    "by_category": [
      {
        "category_id": 1,
        "category_name": "JavaScript",
        "category_slug": "javascript",
        "total_attempted": 20,
        "total_completed": 18,
        "total_correct": 15,
        "total_incorrect": 3,
        "avg_time_spent": 150.0,
        "completion_rate": 90.0,
        "accuracy_rate": 83.33
      },
      {
        "category_id": 2,
        "category_name": "PHP",
        "category_slug": "php",
        "total_attempted": 15,
        "total_completed": 14,
        "total_correct": 12,
        "total_incorrect": 2,
        "avg_time_spent": 140.0,
        "completion_rate": 93.33,
        "accuracy_rate": 85.71
      }
    ],
    "by_difficulty": [
      {
        "difficulty_id": 1,
        "difficulty_name": "Easy",
        "difficulty_slug": "easy",
        "difficulty_level": 1,
        "total_attempted": 25,
        "total_completed": 24,
        "total_correct": 22,
        "total_incorrect": 2,
        "avg_time_spent": 100.0,
        "completion_rate": 96.0,
        "accuracy_rate": 91.67
      },
      {
        "difficulty_id": 2,
        "difficulty_name": "Medium",
        "difficulty_slug": "medium",
        "difficulty_level": 2,
        "total_attempted": 15,
        "total_completed": 13,
        "total_correct": 10,
        "total_incorrect": 3,
        "avg_time_spent": 180.0,
        "completion_rate": 86.67,
        "accuracy_rate": 76.92
      }
    ]
  }
}
```

---

### Get Bookmarked Questions

Get paginated list of bookmarked questions.

**Endpoint:** `GET /api/user/bookmarked`

**Query Parameters:**
- `per_page` (optional, integer, default: 15) - Number of items per page

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Bookmarked questions retrieved successfully",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "question_id": 5,
      "status": "attempted",
      "is_bookmarked": true,
      "is_correct": null,
      "attempts_count": 1,
      "time_spent": 120,
      "user_answer": ["answer"],
      "first_attempted_at": "2024-01-01T10:00:00Z",
      "last_attempted_at": "2024-01-01T10:00:00Z",
      "completed_at": null,
      "created_at": "2024-01-01T10:00:00Z",
      "updated_at": "2024-01-01T10:00:00Z",
      "question": {
        "id": 5,
        "title": "What is closure in JavaScript?",
        "difficulty_level": {
          "id": 2,
          "name": "Medium"
        }
      }
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 10,
      "last_page": 1
    },
    "timestamp": "2024-01-01T10:00:00Z"
  }
}
```

---

### Get Completed Questions

Get paginated list of completed questions.

**Endpoint:** `GET /api/user/completed`

**Query Parameters:**
- `per_page` (optional, integer, default: 15) - Number of items per page

**Response:** Similar structure to bookmarked questions endpoint

---

### Get Attempted Questions

Get paginated list of attempted questions (includes both attempted and completed).

**Endpoint:** `GET /api/user/attempted`

**Query Parameters:**
- `per_page` (optional, integer, default: 15) - Number of items per page

**Response:** Similar structure to bookmarked questions endpoint

---

## Recommendation Endpoints

### Get Recommended Questions

Get personalized question recommendations based on user performance and weak categories.

**Endpoint:** `GET /api/user/recommendations`

**Query Parameters:**
- `limit` (optional, integer, default: 10) - Maximum number of recommendations

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Recommended questions retrieved successfully",
  "data": [
    {
      "id": 15,
      "title": "Explain event delegation in JavaScript",
      "question_text": "What is event delegation and when should you use it?",
      "difficulty_level": {
        "id": 2,
        "name": "Medium",
        "level": 2
      },
      "topics": [
        {
          "id": 3,
          "name": "Event Handling",
          "category": {
            "id": 1,
            "name": "JavaScript"
          }
        }
      ],
      "points": 20,
      "time_limit": 300
    }
  ],
  "meta": {
    "total": 10,
    "timestamp": "2024-01-01T10:00:00Z"
  }
}
```

**Algorithm:**
- Excludes already attempted questions
- Focuses on categories where user has lower accuracy
- Suggests questions within current difficulty level ±1
- Prioritizes variety across weak areas

---

### Get Next Questions

Get next questions to attempt based on recent activity.

**Endpoint:** `GET /api/user/next-questions`

**Query Parameters:**
- `limit` (optional, integer, default: 5) - Maximum number of questions

**Response:** Similar structure to recommendations endpoint

**Algorithm:**
- Excludes already attempted questions
- Focuses on recently practiced categories
- Suggests questions at or slightly above current difficulty level
- Maintains continuity in learning path

---

### Get Progression-Based Recommendations

Get recommendations based on difficulty progression according to performance.

**Endpoint:** `GET /api/user/progression-recommendations`

**Query Parameters:**
- `limit` (optional, integer, default: 10) - Maximum number of recommendations

**Response:** Similar structure to recommendations endpoint

**Algorithm:**
- If accuracy ≥ 80%: Suggests harder questions (difficulty +1)
- If accuracy ≥ 60%: Suggests same difficulty level
- If accuracy < 60%: Suggests easier questions (difficulty -1)
- Excludes already attempted questions
- Helps users progress at an appropriate pace

---

## Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error
- `500 Internal Server Error` - Server error

---

## Error Response Format

All error responses follow this format:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": ["Error detail"]
  }
}
```

---

## Progress Status Values

The `status` field in user question attempts can have the following values:

- `not_attempted` - Question has not been attempted (may be bookmarked)
- `attempted` - Question has been attempted but not completed
- `completed` - Question has been completed with a final answer
- `skipped` - Question was skipped by the user

---

## Notes

1. All timestamps are in ISO 8601 format
2. Time spent is measured in seconds
3. User answer format depends on question type (single choice, multiple choice, text, etc.)
4. Statistics are calculated in real-time based on actual attempts
5. Recommendations are personalized per user and update dynamically
6. Bookmarking a question doesn't change its attempt status
7. A question can be bookmarked at any status (not_attempted, attempted, completed)
