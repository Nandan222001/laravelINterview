# User Progress Tracking System - Implementation Summary

## Overview

Complete implementation of a user progress tracking system for the Laravel interview questions application. The system tracks user attempts on questions, manages bookmarks, provides detailed statistics, and includes an intelligent recommendation engine.

## Components Implemented

### 1. Database Layer

#### Migration: `create_user_question_attempts_table`
**File:** `database/migrations/2026_04_06_133122_create_user_question_attempts_table.php`

**Table Schema:**
- `id` - Primary key
- `user_id` - Foreign key to users table
- `question_id` - Foreign key to questions table
- `status` - Enum: not_attempted, attempted, completed, skipped
- `is_bookmarked` - Boolean flag for bookmarked questions
- `is_correct` - Nullable boolean for correctness
- `attempts_count` - Number of attempts made
- `time_spent` - Time spent in seconds
- `user_answer` - JSON field for storing user's answer
- `first_attempted_at` - Timestamp of first attempt
- `last_attempted_at` - Timestamp of last attempt
- `completed_at` - Timestamp when completed
- `created_at`, `updated_at` - Standard timestamps

**Indexes:**
- Unique constraint on `(user_id, question_id)`
- Index on `(user_id, status)`
- Index on `(user_id, is_bookmarked)`
- Index on `completed_at`

### 2. Models

#### UserQuestionAttempt Model
**File:** `app/Models/UserQuestionAttempt.php`

**Features:**
- Relationships to User and Question models
- Methods for marking attempts, completion, and bookmarking
- Proper casting for all fields
- Helper methods: `markAsAttempted()`, `markAsCompleted()`, `toggleBookmark()`, `bookmark()`, `unbookmark()`

#### Updated User Model
**File:** `app/Models/User.php`

**New Relationships:**
- `questionAttempts()` - HasMany relationship
- `attemptedQuestions()` - BelongsToMany with pivot data
- `bookmarkedQuestions()` - BelongsToMany filtered by bookmark status
- `completedQuestions()` - BelongsToMany filtered by completed status

#### Updated Question Model
**File:** `app/Models/Question.php`

**New Relationships:**
- `userAttempts()` - HasMany relationship
- `attemptedByUsers()` - BelongsToMany with pivot data

### 3. Repositories

#### UserProgressRepository
**File:** `app/Repositories/UserProgressRepository.php`

**Methods:**
- `getOrCreateAttempt()` - Get or create attempt record
- `markAsAttempted()` - Mark question as attempted
- `markAsCompleted()` - Mark question as completed
- `toggleBookmark()`, `bookmark()`, `unbookmark()` - Bookmark management
- `getUserStatistics()` - Overall statistics
- `getStatisticsByCategory()` - Category-wise statistics
- `getStatisticsByDifficulty()` - Difficulty-wise statistics
- `getUserBookmarkedQuestions()` - Paginated bookmarked questions
- `getUserCompletedQuestions()` - Paginated completed questions
- `getUserAttemptedQuestions()` - Paginated attempted questions

#### RecommendationRepository
**File:** `app/Repositories/RecommendationRepository.php`

**Methods:**
- `getRecommendedQuestions()` - Personalized recommendations based on weak categories
- `getNextQuestions()` - Next questions based on recent activity
- `getProgressionBasedRecommendations()` - Recommendations based on performance
- Helper methods for analyzing user progress and preferences

**Recommendation Algorithms:**

1. **General Recommendations:**
   - Excludes already attempted questions
   - Focuses on weak categories (lower accuracy)
   - Suggests questions within current difficulty ±1
   - Randomized selection for variety

2. **Next Questions:**
   - Continues with recently practiced categories
   - Maintains learning momentum
   - Progressive difficulty

3. **Progression-Based:**
   - Accuracy ≥ 80%: Increase difficulty (+1)
   - Accuracy ≥ 60%: Same difficulty
   - Accuracy < 60%: Decrease difficulty (-1)
   - Adaptive learning path

### 4. Services

#### UserProgressService
**File:** `app/Services/UserProgressService.php`

**Features:**
- Orchestrates repository operations
- Business logic layer between controllers and repositories
- Clean API for controllers to consume

**Methods:**
- `markQuestionAsAttempted()` - Mark attempt
- `markQuestionAsCompleted()` - Mark completion
- `toggleBookmark()`, `bookmarkQuestion()`, `unbookmarkQuestion()` - Bookmark operations
- `getUserStatistics()` - Get comprehensive statistics
- `getBookmarkedQuestions()`, `getCompletedQuestions()`, `getAttemptedQuestions()` - Get filtered questions
- `getRecommendedQuestions()`, `getNextQuestions()`, `getProgressionBasedRecommendations()` - Get recommendations

### 5. HTTP Layer

#### Request Validation Classes

**MarkQuestionAttemptedRequest**
**File:** `app/Http/Requests/MarkQuestionAttemptedRequest.php`
- Validates: question_id, user_answer (optional), time_spent (optional)

**MarkQuestionCompletedRequest**
**File:** `app/Http/Requests/MarkQuestionCompletedRequest.php`
- Validates: question_id, is_correct, user_answer (optional), time_spent (optional)

#### Resource Classes

**UserQuestionAttemptResource**
**File:** `app/Http/Resources/UserQuestionAttemptResource.php`
- Transforms UserQuestionAttempt model to JSON
- Includes related question data when loaded

**UserQuestionAttemptCollection**
**File:** `app/Http/Resources/UserQuestionAttemptCollection.php`
- Transforms collections of attempts
- Includes pagination metadata

#### Controller

**UserProgressController**
**File:** `app/Http/Controllers/Api/UserProgressController.php`

**Endpoints:**
- `markAttempted()` - POST /api/progress/attempted
- `markCompleted()` - POST /api/progress/completed
- `toggleBookmark()` - POST /api/progress/toggle-bookmark/{questionId}
- `bookmark()` - POST /api/progress/bookmark/{questionId}
- `unbookmark()` - DELETE /api/progress/bookmark/{questionId}
- `statistics()` - GET /api/user/stats
- `bookmarkedQuestions()` - GET /api/user/bookmarked
- `completedQuestions()` - GET /api/user/completed
- `attemptedQuestions()` - GET /api/user/attempted
- `recommendations()` - GET /api/user/recommendations
- `nextQuestions()` - GET /api/user/next-questions
- `progressionRecommendations()` - GET /api/user/progression-recommendations

### 6. Routes

**File:** `routes/api.php`

**Progress Tracking Routes:**
```php
Route::prefix('progress')->group(function () {
    Route::post('/attempted', [UserProgressController::class, 'markAttempted']);
    Route::post('/completed', [UserProgressController::class, 'markCompleted']);
    Route::post('/bookmark/{questionId}', [UserProgressController::class, 'bookmark']);
    Route::delete('/bookmark/{questionId}', [UserProgressController::class, 'unbookmark']);
    Route::post('/toggle-bookmark/{questionId}', [UserProgressController::class, 'toggleBookmark']);
});
```

**User Statistics and Data Routes:**
```php
Route::prefix('user')->group(function () {
    Route::get('/stats', [UserProgressController::class, 'statistics']);
    Route::get('/bookmarked', [UserProgressController::class, 'bookmarkedQuestions']);
    Route::get('/completed', [UserProgressController::class, 'completedQuestions']);
    Route::get('/attempted', [UserProgressController::class, 'attemptedQuestions']);
    Route::get('/recommendations', [UserProgressController::class, 'recommendations']);
    Route::get('/next-questions', [UserProgressController::class, 'nextQuestions']);
    Route::get('/progression-recommendations', [UserProgressController::class, 'progressionRecommendations']);
});
```

### 7. Testing Support

#### Factory
**File:** `database/factories/UserQuestionAttemptFactory.php`

**Features:**
- Generates realistic test data
- State methods: `notAttempted()`, `attempted()`, `completed()`, `correct()`, `incorrect()`, `bookmarked()`
- Proper data consistency (e.g., completed questions have timestamps)

#### Seeder
**File:** `database/seeders/UserProgressSeeder.php`

**Features:**
- Seeds realistic user progress data
- Randomly assigns 5-20 attempts per user
- Weighted distribution of statuses (60% completed, 20% attempted, etc.)
- 20% chance of bookmarking

### 8. Documentation

**USER-PROGRESS-API.md**
- Complete API documentation
- Request/response examples
- Algorithm explanations
- Error handling details
- Usage guidelines

**USER-PROGRESS-IMPLEMENTATION.md** (this file)
- Implementation overview
- Component descriptions
- Architecture decisions
- Usage instructions

## Key Features

### 1. Progress Tracking
- Track question attempts with status (not_attempted, attempted, completed, skipped)
- Record correctness of answers
- Store time spent on each question
- Count multiple attempts
- Store user answers for review

### 2. Bookmark Management
- Bookmark questions for later review
- Toggle bookmark status easily
- Filter questions by bookmark status
- Independent of attempt status

### 3. Comprehensive Statistics

**Overall Statistics:**
- Total attempted questions
- Total completed questions
- Total bookmarked questions
- Correct/incorrect counts
- Average time spent
- Completion rate percentage
- Accuracy rate percentage

**Category-wise Statistics:**
- Breakdown by each category
- Identify weak areas
- Track progress per topic area

**Difficulty-wise Statistics:**
- Performance by difficulty level
- Understand comfort zones
- Guide progression

### 4. Intelligent Recommendations

**Personalized Recommendations:**
- Based on weak categories
- Appropriate difficulty level
- Excludes already attempted questions

**Next Questions:**
- Maintains learning continuity
- Based on recent activity
- Progressive difficulty

**Progression-Based:**
- Adaptive difficulty adjustment
- Performance-driven suggestions
- Prevents frustration or boredom

## Usage Instructions

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Seed Sample Data (Optional)
```bash
php artisan db:seed --class=UserProgressSeeder
```

### 3. Example API Calls

**Mark Question as Attempted:**
```bash
curl -X POST http://localhost:8000/api/progress/attempted \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_id": 1,
    "user_answer": ["answer1"],
    "time_spent": 120
  }'
```

**Mark Question as Completed:**
```bash
curl -X POST http://localhost:8000/api/progress/completed \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_id": 1,
    "is_correct": true,
    "user_answer": ["correct_answer"],
    "time_spent": 180
  }'
```

**Bookmark Question:**
```bash
curl -X POST http://localhost:8000/api/progress/bookmark/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Get User Statistics:**
```bash
curl -X GET http://localhost:8000/api/user/stats \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Get Recommendations:**
```bash
curl -X GET http://localhost:8000/api/user/recommendations?limit=10 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Architecture Decisions

### 1. Pivot Table Approach
- Used dedicated `user_question_attempts` table instead of direct many-to-many
- Allows rich metadata storage
- Flexible for future extensions
- Better query performance with indexes

### 2. Status Enum
- Clear state management
- Four states cover all use cases
- Prevents invalid state combinations

### 3. Separate Repositories
- `UserProgressRepository` for progress tracking
- `RecommendationRepository` for recommendation logic
- Clear separation of concerns
- Easier to test and maintain

### 4. Multiple Recommendation Strategies
- Different algorithms for different needs
- Users can choose what works for them
- Flexible and extensible

### 5. Comprehensive Statistics
- Overall, category, and difficulty breakdowns
- Calculated rates (completion, accuracy)
- Real-time calculations from actual data

## Database Indexes

Optimized queries with strategic indexes:
- `(user_id, question_id)` - Unique constraint, fast lookups
- `(user_id, status)` - Filter by status efficiently
- `(user_id, is_bookmarked)` - Filter bookmarks quickly
- `completed_at` - Sort by completion time

## Performance Considerations

1. **Pagination:** All list endpoints support pagination
2. **Indexes:** Strategic indexes on frequently queried columns
3. **Eager Loading:** Relationships loaded when needed
4. **Query Optimization:** Uses joins and aggregations efficiently
5. **Caching Ready:** Structure allows for future caching layer

## Extension Points

The system is designed to be extended:

1. **New Status Types:** Easily add more status values
2. **Additional Metadata:** JSON fields allow flexible data storage
3. **More Recommendation Algorithms:** Add new strategies in RecommendationRepository
4. **Analytics:** Statistics structure supports deeper analysis
5. **Gamification:** Progress data ready for badges, streaks, leaderboards

## Testing Recommendations

1. **Unit Tests:**
   - Test model methods (markAsAttempted, markAsCompleted, etc.)
   - Test repository methods with different scenarios
   - Test recommendation algorithms

2. **Feature Tests:**
   - Test all API endpoints
   - Test authentication requirements
   - Test validation rules
   - Test response structures

3. **Integration Tests:**
   - Test complete user flows
   - Test statistics accuracy
   - Test recommendation quality

## Best Practices Followed

1. **Repository Pattern:** Data access abstraction
2. **Service Layer:** Business logic separation
3. **Request Validation:** Dedicated request classes
4. **API Resources:** Consistent response formatting
5. **Type Hints:** Full type safety
6. **Documentation:** Comprehensive API docs
7. **Database Design:** Proper normalization and indexes
8. **Error Handling:** Consistent error responses
9. **Code Organization:** Clear file structure
10. **PSR-12 Compliance:** Standard PHP coding style

## Summary

This implementation provides a complete, production-ready user progress tracking system with:
- ✅ Robust progress tracking
- ✅ Flexible bookmark management  
- ✅ Comprehensive statistics
- ✅ Intelligent recommendations
- ✅ Clean architecture
- ✅ Full documentation
- ✅ Testing support
- ✅ Performance optimized
- ✅ Extension ready

All endpoints are secured with Sanctum authentication and follow Laravel best practices.
