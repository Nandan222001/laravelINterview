# User Progress Tracking - Quick Reference

## API Endpoints

### Progress Tracking
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/progress/attempted` | Mark question as attempted |
| POST | `/api/progress/completed` | Mark question as completed |

### Bookmark Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/progress/bookmark/{questionId}` | Bookmark a question |
| DELETE | `/api/progress/bookmark/{questionId}` | Remove bookmark |
| POST | `/api/progress/toggle-bookmark/{questionId}` | Toggle bookmark status |

### User Data & Statistics
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user/stats` | Get comprehensive statistics |
| GET | `/api/user/bookmarked` | List bookmarked questions |
| GET | `/api/user/completed` | List completed questions |
| GET | `/api/user/attempted` | List attempted questions |

### Recommendations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user/recommendations` | Get personalized recommendations |
| GET | `/api/user/next-questions` | Get next questions to attempt |
| GET | `/api/user/progression-recommendations` | Get difficulty-based recommendations |

## Request Examples

### Mark as Attempted
```json
POST /api/progress/attempted
{
  "question_id": 1,
  "user_answer": ["answer"],
  "time_spent": 120
}
```

### Mark as Completed
```json
POST /api/progress/completed
{
  "question_id": 1,
  "is_correct": true,
  "user_answer": ["answer"],
  "time_spent": 180
}
```

## Response Structure

### Progress Response
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
    "attempts_count": 1,
    "time_spent": 180,
    "completed_at": "2024-01-01T10:00:00Z"
  }
}
```

### Statistics Response
```json
{
  "success": true,
  "data": {
    "overall": {
      "total_attempted": 50,
      "total_completed": 45,
      "completion_rate": 90.0,
      "accuracy_rate": 84.44
    },
    "by_category": [...],
    "by_difficulty": [...]
  }
}
```

## Status Values

| Status | Description |
|--------|-------------|
| `not_attempted` | Question not yet attempted |
| `attempted` | Question attempted but not completed |
| `completed` | Question completed with answer |
| `skipped` | Question skipped by user |

## Models & Relationships

### UserQuestionAttempt
```php
// Create or update attempt
$attempt = UserQuestionAttempt::firstOrCreate([
    'user_id' => $userId,
    'question_id' => $questionId
]);

// Mark as attempted
$attempt->markAsAttempted($userAnswer, $timeSpent);

// Mark as completed
$attempt->markAsCompleted($isCorrect, $userAnswer, $timeSpent);

// Toggle bookmark
$attempt->toggleBookmark();
```

### User Relationships
```php
// Get all attempts
$user->questionAttempts;

// Get attempted questions
$user->attemptedQuestions;

// Get bookmarked questions
$user->bookmarkedQuestions;

// Get completed questions
$user->completedQuestions;
```

## Repository Methods

### UserProgressRepository
```php
// Mark attempts
$progressRepo->markAsAttempted($userId, $questionId, $answer, $time);
$progressRepo->markAsCompleted($userId, $questionId, $isCorrect, $answer, $time);

// Bookmarks
$progressRepo->toggleBookmark($userId, $questionId);
$progressRepo->bookmark($userId, $questionId);
$progressRepo->unbookmark($userId, $questionId);

// Statistics
$progressRepo->getUserStatistics($userId);
$progressRepo->getStatisticsByCategory($userId);
$progressRepo->getStatisticsByDifficulty($userId);

// Get questions
$progressRepo->getUserBookmarkedQuestions($userId, $perPage);
$progressRepo->getUserCompletedQuestions($userId, $perPage);
$progressRepo->getUserAttemptedQuestions($userId, $perPage);
```

### RecommendationRepository
```php
// Get recommendations
$recommendationRepo->getRecommendedQuestions($userId, $limit);
$recommendationRepo->getNextQuestions($userId, $limit);
$recommendationRepo->getProgressionBasedRecommendations($userId, $limit);
```

## Service Layer

### UserProgressService
```php
// Inject service
public function __construct(UserProgressService $progressService)

// Use methods
$progressService->markQuestionAsAttempted($userId, $questionId, $answer, $time);
$progressService->markQuestionAsCompleted($userId, $questionId, $isCorrect, $answer, $time);
$progressService->getUserStatistics($userId);
$progressService->getRecommendedQuestions($userId, $limit);
```

## Database Schema

### user_question_attempts Table
```sql
- id (primary key)
- user_id (foreign key)
- question_id (foreign key)
- status (enum)
- is_bookmarked (boolean)
- is_correct (boolean, nullable)
- attempts_count (integer)
- time_spent (integer, nullable)
- user_answer (json, nullable)
- first_attempted_at (timestamp, nullable)
- last_attempted_at (timestamp, nullable)
- completed_at (timestamp, nullable)
- created_at, updated_at

UNIQUE KEY (user_id, question_id)
INDEX (user_id, status)
INDEX (user_id, is_bookmarked)
INDEX (completed_at)
```

## Testing

### Factory Usage
```php
use App\Models\UserQuestionAttempt;

// Create with default state
UserQuestionAttempt::factory()->create();

// Create with specific state
UserQuestionAttempt::factory()->attempted()->create();
UserQuestionAttempt::factory()->completed()->create();
UserQuestionAttempt::factory()->correct()->create();
UserQuestionAttempt::factory()->bookmarked()->create();
```

### Seeder
```bash
php artisan db:seed --class=UserProgressSeeder
```

## Common Queries

### Get user completion rate
```php
$stats = $progressService->getUserStatistics($userId);
$completionRate = $stats['overall']['completion_rate'];
```

### Get weak categories
```php
$stats = $progressService->getUserStatistics($userId);
$weakCategories = collect($stats['by_category'])
    ->sortBy('accuracy_rate')
    ->take(3);
```

### Check if question is bookmarked
```php
$attempt = UserQuestionAttempt::where('user_id', $userId)
    ->where('question_id', $questionId)
    ->first();
    
$isBookmarked = $attempt?->is_bookmarked ?? false;
```

### Get user progress percentage
```php
$totalQuestions = Question::published()->count();
$stats = $progressService->getUserStatistics($userId);
$progressPercentage = ($stats['overall']['total_completed'] / $totalQuestions) * 100;
```

## Recommendation Algorithms

### General Recommendations
- Excludes attempted questions
- Focuses on weak categories (low accuracy)
- Difficulty: current ±1 level
- Random selection for variety

### Next Questions
- Based on recent category activity
- Progressive difficulty
- Maintains learning flow

### Progression-Based
- Accuracy ≥ 80% → Difficulty +1
- Accuracy ≥ 60% → Same difficulty
- Accuracy < 60% → Difficulty -1

## Authentication

All endpoints require Sanctum authentication:
```
Authorization: Bearer {token}
```

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request |
| 401 | Unauthorized |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

## Files Overview

| File | Purpose |
|------|---------|
| `UserQuestionAttempt.php` | Model for progress tracking |
| `UserProgressRepository.php` | Data access for progress |
| `RecommendationRepository.php` | Recommendation logic |
| `UserProgressService.php` | Business logic layer |
| `UserProgressController.php` | API endpoints |
| `*Request.php` | Validation rules |
| `*Resource.php` | Response formatting |
| Migration | Database schema |
| Factory | Test data generation |
| Seeder | Sample data |

## Quick Setup

```bash
# Run migration
php artisan migrate

# Seed sample data (optional)
php artisan db:seed --class=UserProgressSeeder

# Test endpoint
curl -X GET http://localhost:8000/api/user/stats \
  -H "Authorization: Bearer YOUR_TOKEN"
```
