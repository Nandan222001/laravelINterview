# User Progress Tracking System

## 📋 Overview

A comprehensive progress tracking system for the Laravel Interview Questions application that enables users to track their learning journey, bookmark questions, view detailed statistics, and receive intelligent question recommendations based on their performance.

## ✨ Features

### 1. Progress Tracking
- ✅ Mark questions as attempted
- ✅ Mark questions as completed with correctness validation
- ✅ Track multiple attempts per question
- ✅ Record time spent on each question
- ✅ Store user answers for review

### 2. Bookmark Management
- ✅ Bookmark questions for later review
- ✅ Toggle bookmark status easily
- ✅ View all bookmarked questions
- ✅ Bookmark any question regardless of attempt status

### 3. Statistics & Analytics
- ✅ Overall progress statistics (completion rate, accuracy rate)
- ✅ Category-wise performance breakdown
- ✅ Difficulty-wise performance analysis
- ✅ Average time spent metrics
- ✅ Total attempts tracking

### 4. Intelligent Recommendations
- ✅ Personalized recommendations based on weak areas
- ✅ Next questions based on recent activity
- ✅ Adaptive difficulty progression
- ✅ Excludes already attempted questions

## 🚀 Quick Start

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Seed Sample Data (Optional)
```bash
php artisan db:seed --class=UserProgressSeeder
```

### 3. Test API Endpoint
```bash
curl -X GET http://localhost:8000/api/user/stats \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📡 API Endpoints

### Progress Tracking
- `POST /api/progress/attempted` - Mark question as attempted
- `POST /api/progress/completed` - Mark question as completed

### Bookmark Management
- `POST /api/progress/bookmark/{id}` - Bookmark question
- `DELETE /api/progress/bookmark/{id}` - Unbookmark question
- `POST /api/progress/toggle-bookmark/{id}` - Toggle bookmark

### Statistics & Data
- `GET /api/user/stats` - Get comprehensive statistics
- `GET /api/user/bookmarked` - List bookmarked questions
- `GET /api/user/completed` - List completed questions
- `GET /api/user/attempted` - List attempted questions

### Recommendations
- `GET /api/user/recommendations` - Get personalized recommendations
- `GET /api/user/next-questions` - Get next questions to attempt
- `GET /api/user/progression-recommendations` - Get adaptive recommendations

## 📖 Documentation

Comprehensive documentation is available:

- **[API Documentation](USER-PROGRESS-API.md)** - Complete API reference with examples
- **[Implementation Guide](USER-PROGRESS-IMPLEMENTATION.md)** - Technical implementation details
- **[Quick Reference](USER-PROGRESS-QUICK-REFERENCE.md)** - Quick lookup guide
- **[Endpoints Summary](USER-PROGRESS-ENDPOINTS.md)** - All endpoints at a glance

## 💾 Database Schema

### user_question_attempts Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users |
| question_id | bigint | Foreign key to questions |
| status | enum | not_attempted, attempted, completed, skipped |
| is_bookmarked | boolean | Bookmark flag |
| is_correct | boolean | Correctness (nullable) |
| attempts_count | integer | Number of attempts |
| time_spent | integer | Time in seconds (nullable) |
| user_answer | json | User's answer (nullable) |
| first_attempted_at | timestamp | First attempt time (nullable) |
| last_attempted_at | timestamp | Last attempt time (nullable) |
| completed_at | timestamp | Completion time (nullable) |
| created_at | timestamp | Record creation |
| updated_at | timestamp | Last update |

**Indexes:**
- Unique: `(user_id, question_id)`
- Index: `(user_id, status)`
- Index: `(user_id, is_bookmarked)`
- Index: `completed_at`

## 🏗️ Architecture

### Components

```
┌─────────────────────────────────────────────────┐
│                  Controller                      │
│         (UserProgressController)                 │
└──────────────────┬──────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────┐
│                   Service                        │
│          (UserProgressService)                   │
└──────────────────┬──────────────────────────────┘
                   │
         ┌─────────┴──────────┐
         ▼                    ▼
┌──────────────────┐  ┌───────────────────────┐
│   Repository     │  │    Repository         │
│  (UserProgress)  │  │  (Recommendation)     │
└────────┬─────────┘  └──────────┬────────────┘
         │                       │
         └───────────┬───────────┘
                     ▼
         ┌────────────────────┐
         │      Models        │
         │  UserQuestion      │
         │     Attempt        │
         └────────────────────┘
```

### Design Patterns Used

1. **Repository Pattern** - Data access abstraction
2. **Service Layer** - Business logic encapsulation
3. **Resource Pattern** - API response transformation
4. **Factory Pattern** - Test data generation

## 🧪 Testing

### Using Factory

```php
use App\Models\UserQuestionAttempt;

// Create attempted question
UserQuestionAttempt::factory()->attempted()->create();

// Create completed question
UserQuestionAttempt::factory()->completed()->create();

// Create correct answer
UserQuestionAttempt::factory()->correct()->create();

// Create bookmarked question
UserQuestionAttempt::factory()->bookmarked()->create();
```

### Using Seeder

```bash
php artisan db:seed --class=UserProgressSeeder
```

## 📊 Statistics Example

```json
{
  "overall": {
    "total_attempted": 50,
    "total_completed": 45,
    "total_bookmarked": 10,
    "total_correct": 38,
    "total_incorrect": 7,
    "avg_time_spent": 145.5,
    "completion_rate": 90.0,
    "accuracy_rate": 84.44
  },
  "by_category": [
    {
      "category_name": "JavaScript",
      "total_completed": 18,
      "accuracy_rate": 83.33
    }
  ],
  "by_difficulty": [
    {
      "difficulty_name": "Easy",
      "accuracy_rate": 91.67
    }
  ]
}
```

## 🎯 Recommendation Algorithms

### 1. Personalized Recommendations
**Endpoint:** `GET /api/user/recommendations`

**Logic:**
- Identifies weak categories (low accuracy)
- Suggests questions at appropriate difficulty (current ±1)
- Excludes already attempted questions
- Randomized for variety

### 2. Next Questions
**Endpoint:** `GET /api/user/next-questions`

**Logic:**
- Focuses on recently practiced categories
- Progressive difficulty increase
- Maintains learning continuity

### 3. Progression-Based
**Endpoint:** `GET /api/user/progression-recommendations`

**Logic:**
- **High Performance (≥80% accuracy):** Difficulty +1
- **Good Performance (≥60% accuracy):** Same difficulty
- **Needs Practice (<60% accuracy):** Difficulty -1
- Adaptive learning path

## 🔐 Authentication

All endpoints require Sanctum authentication:

```http
Authorization: Bearer {your-token}
```

## 💡 Usage Examples

### Mark Question as Completed

```javascript
// JavaScript/Axios
axios.post('/api/progress/completed', {
  question_id: 1,
  is_correct: true,
  user_answer: ['correct_answer'],
  time_spent: 180
}, {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

```bash
# cURL
curl -X POST http://localhost:8000/api/progress/completed \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_id": 1,
    "is_correct": true,
    "time_spent": 180
  }'
```

### Get User Statistics

```javascript
// JavaScript/Axios
const response = await axios.get('/api/user/stats', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const stats = response.data.data;
console.log(`Completion: ${stats.overall.completion_rate}%`);
console.log(`Accuracy: ${stats.overall.accuracy_rate}%`);
```

### Bookmark Question

```javascript
// JavaScript/Axios
axios.post(`/api/progress/bookmark/${questionId}`, {}, {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

## 📦 Files Structure

```
app/
├── Models/
│   └── UserQuestionAttempt.php
├── Repositories/
│   ├── UserProgressRepository.php
│   └── RecommendationRepository.php
├── Services/
│   └── UserProgressService.php
├── Http/
│   ├── Controllers/Api/
│   │   └── UserProgressController.php
│   ├── Requests/
│   │   ├── MarkQuestionAttemptedRequest.php
│   │   └── MarkQuestionCompletedRequest.php
│   └── Resources/
│       ├── UserQuestionAttemptResource.php
│       └── UserQuestionAttemptCollection.php
database/
├── migrations/
│   └── 2026_04_06_133122_create_user_question_attempts_table.php
├── factories/
│   └── UserQuestionAttemptFactory.php
└── seeders/
    └── UserProgressSeeder.php
routes/
└── api.php (updated)
```

## 🔧 Configuration

No additional configuration required. The system uses:
- Existing Sanctum authentication
- Standard Laravel database connection
- Default pagination settings

## ⚡ Performance

### Optimization Features
- Strategic database indexes
- Efficient SQL queries with joins and aggregations
- Pagination on all list endpoints
- Eager loading of relationships
- Query result caching ready

### Database Indexes
- Unique constraint prevents duplicate attempts
- Status index speeds up filtering
- Bookmark index accelerates bookmark queries
- Completion date index optimizes sorting

## 🚦 Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

## 🛠️ Troubleshooting

### Migration Issues
```bash
# Refresh migrations
php artisan migrate:fresh

# Run specific migration
php artisan migrate --path=/database/migrations/2026_04_06_133122_create_user_question_attempts_table.php
```

### No Recommendations
- Ensure questions exist in database
- Ensure difficulty levels are set
- User needs some completed questions for better recommendations

### Statistics Not Showing
- Ensure user has attempted some questions
- Check authentication token is valid
- Verify user_id matches authenticated user

## 🔄 Future Enhancements

Potential areas for expansion:
- Leaderboards and rankings
- Achievements and badges
- Learning streaks
- Daily goals
- Social features (compare with friends)
- Spaced repetition algorithm
- Time-based challenges
- Category mastery levels

## 📝 License

This feature is part of the Laravel Interview Questions application and follows the same license.

## 🤝 Contributing

Follow the existing code style and patterns:
- PSR-12 coding standard
- Repository pattern for data access
- Service layer for business logic
- Resource pattern for API responses
- Comprehensive validation
- Proper documentation

## 📞 Support

For issues or questions:
1. Check the documentation files
2. Review implementation guide
3. Examine API examples
4. Review code comments

## ✅ Checklist for Integration

- [ ] Run migration
- [ ] Update your User model (already done)
- [ ] Update your Question model (already done)
- [ ] Test all endpoints with Postman/Insomnia
- [ ] Integrate with frontend
- [ ] Handle loading states
- [ ] Display statistics in UI
- [ ] Implement bookmark UI
- [ ] Show recommendations
- [ ] Handle errors gracefully

---

**Built with Laravel 11 | Uses Sanctum Authentication | Production Ready**
