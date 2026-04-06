# User Progress Tracking - API Endpoints Summary

## All Available Endpoints

### 1. Mark Question as Attempted
**POST** `/api/progress/attempted`

Mark a question as attempted (started but not necessarily completed)

**Request Body:**
```json
{
  "question_id": 1,
  "user_answer": ["answer"],
  "time_spent": 120
}
```

---

### 2. Mark Question as Completed
**POST** `/api/progress/completed`

Mark a question as completed with correctness validation

**Request Body:**
```json
{
  "question_id": 1,
  "is_correct": true,
  "user_answer": ["answer"],
  "time_spent": 180
}
```

---

### 3. Bookmark Question
**POST** `/api/progress/bookmark/{questionId}`

Add a question to user's bookmarks

**URL Parameters:**
- `questionId` (integer) - The question ID

---

### 4. Unbookmark Question
**DELETE** `/api/progress/bookmark/{questionId}`

Remove a question from user's bookmarks

**URL Parameters:**
- `questionId` (integer) - The question ID

---

### 5. Toggle Bookmark
**POST** `/api/progress/toggle-bookmark/{questionId}`

Toggle bookmark status (bookmark if not bookmarked, unbookmark if bookmarked)

**URL Parameters:**
- `questionId` (integer) - The question ID

---

### 6. Get User Statistics
**GET** `/api/user/stats`

Get comprehensive user progress statistics

**Returns:**
- Overall statistics (attempted, completed, accuracy, etc.)
- Statistics by category
- Statistics by difficulty level

---

### 7. Get Bookmarked Questions
**GET** `/api/user/bookmarked`

Get paginated list of user's bookmarked questions

**Query Parameters:**
- `per_page` (optional, integer, default: 15)

---

### 8. Get Completed Questions
**GET** `/api/user/completed`

Get paginated list of user's completed questions

**Query Parameters:**
- `per_page` (optional, integer, default: 15)

---

### 9. Get Attempted Questions
**GET** `/api/user/attempted`

Get paginated list of user's attempted questions (includes both attempted and completed)

**Query Parameters:**
- `per_page` (optional, integer, default: 15)

---

### 10. Get Recommended Questions
**GET** `/api/user/recommendations`

Get personalized question recommendations based on weak areas

**Query Parameters:**
- `limit` (optional, integer, default: 10)

**Algorithm:**
- Analyzes weak categories (low accuracy)
- Suggests questions at appropriate difficulty
- Excludes already attempted questions

---

### 11. Get Next Questions
**GET** `/api/user/next-questions`

Get next questions to attempt based on recent activity

**Query Parameters:**
- `limit` (optional, integer, default: 5)

**Algorithm:**
- Continues with recently practiced categories
- Progressive difficulty
- Maintains learning flow

---

### 12. Get Progression-Based Recommendations
**GET** `/api/user/progression-recommendations`

Get recommendations based on adaptive difficulty progression

**Query Parameters:**
- `limit` (optional, integer, default: 10)

**Algorithm:**
- Accuracy ≥ 80%: Suggests harder questions (+1 difficulty)
- Accuracy ≥ 60%: Suggests same difficulty
- Accuracy < 60%: Suggests easier questions (-1 difficulty)

---

## Endpoint Groups

### Progress Tracking (`/api/progress/*`)
1. `POST /attempted` - Mark as attempted
2. `POST /completed` - Mark as completed
3. `POST /bookmark/{id}` - Bookmark question
4. `DELETE /bookmark/{id}` - Unbookmark question
5. `POST /toggle-bookmark/{id}` - Toggle bookmark

### User Data & Statistics (`/api/user/*`)
1. `GET /stats` - User statistics
2. `GET /bookmarked` - Bookmarked questions
3. `GET /completed` - Completed questions
4. `GET /attempted` - Attempted questions
5. `GET /recommendations` - General recommendations
6. `GET /next-questions` - Next questions
7. `GET /progression-recommendations` - Progression-based recommendations

---

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "meta": {
    "timestamp": "2024-01-01T10:00:00Z"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Error detail"]
  }
}
```

---

## Authentication

All endpoints require Bearer token authentication:

```
Authorization: Bearer {your-sanctum-token}
```

---

## Common HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

## Usage Examples

### cURL Examples

**Mark Question as Completed:**
```bash
curl -X POST http://localhost:8000/api/progress/completed \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "question_id": 1,
    "is_correct": true,
    "time_spent": 180
  }'
```

**Get User Statistics:**
```bash
curl -X GET http://localhost:8000/api/user/stats \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Bookmark Question:**
```bash
curl -X POST http://localhost:8000/api/progress/bookmark/5 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Get Recommendations:**
```bash
curl -X GET "http://localhost:8000/api/user/recommendations?limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### JavaScript/Axios Examples

**Mark Question as Completed:**
```javascript
axios.post('/api/progress/completed', {
  question_id: 1,
  is_correct: true,
  user_answer: ['answer'],
  time_spent: 180
}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
.then(response => {
  console.log(response.data);
})
.catch(error => {
  console.error(error.response.data);
});
```

**Get User Statistics:**
```javascript
axios.get('/api/user/stats', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
.then(response => {
  const stats = response.data.data;
  console.log(`Completion Rate: ${stats.overall.completion_rate}%`);
  console.log(`Accuracy Rate: ${stats.overall.accuracy_rate}%`);
})
.catch(error => {
  console.error(error.response.data);
});
```

---

## Validation Rules

### Mark as Attempted
- `question_id`: required, integer, exists in questions table
- `user_answer`: optional, array
- `time_spent`: optional, integer, minimum 0

### Mark as Completed
- `question_id`: required, integer, exists in questions table
- `is_correct`: required, boolean
- `user_answer`: optional, array
- `time_spent`: optional, integer, minimum 0

---

## Field Descriptions

### UserQuestionAttempt Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique identifier |
| `user_id` | integer | User who made the attempt |
| `question_id` | integer | Question attempted |
| `status` | enum | not_attempted, attempted, completed, skipped |
| `is_bookmarked` | boolean | Bookmark status |
| `is_correct` | boolean/null | Answer correctness (null if not completed) |
| `attempts_count` | integer | Number of attempts made |
| `time_spent` | integer/null | Time spent in seconds |
| `user_answer` | array/null | User's answer(s) |
| `first_attempted_at` | timestamp/null | First attempt timestamp |
| `last_attempted_at` | timestamp/null | Last attempt timestamp |
| `completed_at` | timestamp/null | Completion timestamp |
| `created_at` | timestamp | Record creation |
| `updated_at` | timestamp | Last update |

---

## Statistics Breakdown

### Overall Statistics
- `total_attempted`: Total questions attempted
- `total_completed`: Total questions completed
- `total_bookmarked`: Total bookmarked questions
- `total_correct`: Correct answers count
- `total_incorrect`: Incorrect answers count
- `avg_time_spent`: Average time per question (seconds)
- `total_attempts`: Sum of all attempts
- `completion_rate`: Percentage of completed questions
- `accuracy_rate`: Percentage of correct answers

### Category Statistics
Per category breakdown:
- Category ID, name, slug
- Total attempted/completed/correct/incorrect
- Average time spent
- Completion and accuracy rates

### Difficulty Statistics
Per difficulty level breakdown:
- Difficulty ID, name, slug, level
- Total attempted/completed/correct/incorrect
- Average time spent
- Completion and accuracy rates

---

## Quick Integration Checklist

- [ ] Set up authentication (Sanctum tokens)
- [ ] Run migration: `php artisan migrate`
- [ ] Test marking questions as attempted
- [ ] Test marking questions as completed
- [ ] Test bookmark functionality
- [ ] Verify statistics endpoint
- [ ] Test recommendation endpoints
- [ ] Handle error responses appropriately
- [ ] Implement loading states
- [ ] Display statistics in UI

---

## Notes

1. All timestamps are in ISO 8601 format
2. Time spent is measured in seconds
3. Bookmarking doesn't change attempt status
4. Questions can be attempted multiple times (attempts_count increments)
5. Statistics are calculated in real-time
6. Recommendations update based on latest progress
7. Pagination uses standard Laravel format
8. All endpoints require authentication
