# Laravel 11 Application Architecture

## Overview

This application follows a clean, layered architecture pattern with clear separation of concerns. The architecture consists of the following layers:

1. **Presentation Layer** (Controllers, Resources, Requests)
2. **Service Layer** (Business Logic)
3. **Repository Layer** (Data Access)
4. **Model Layer** (Domain Models)

## Architecture Diagram

```
┌─────────────────────────────────────────────────────┐
│                  API Endpoints                       │
│                  (routes/api.php)                    │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│              Controllers Layer                       │
│     (App\Http\Controllers\Api\*)                    │
│  - Handle HTTP requests                             │
│  - Validate input (Request classes)                 │
│  - Return responses (Resource classes)              │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│              Service Layer                           │
│         (App\Services\*)                            │
│  - Business logic                                   │
│  - Orchestrate operations                           │
│  - Transaction management                           │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│           Repository Layer                           │
│        (App\Repositories\*)                         │
│  - Data access abstraction                          │
│  - Database queries                                 │
│  - CRUD operations                                  │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│              Model Layer                             │
│          (App\Models\*)                             │
│  - Domain entities                                  │
│  - Relationships                                    │
│  - Attributes casting                               │
└─────────────────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│              Database                                │
│            (MySQL)                                   │
└─────────────────────────────────────────────────────┘
```

## Layer Responsibilities

### 1. Controllers Layer

**Location:** `app/Http/Controllers/`

**Responsibilities:**
- Receive HTTP requests
- Validate input using Form Requests
- Call appropriate service methods
- Transform data using Resources
- Return HTTP responses

**Example:**
```php
class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private UserService $userService
    ) {}

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getById($id);
        
        if (!$user) {
            return $this->notFoundResponse('User not found');
        }

        return $this->successResponse(
            new UserResource($user),
            'User retrieved successfully'
        );
    }
}
```

### 2. Service Layer

**Location:** `app/Services/`

**Responsibilities:**
- Business logic implementation
- Data transformation
- Transaction management
- Orchestrating multiple repositories
- Complex operations

**Example:**
```php
class UserService extends BaseService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    public function register(array $data): User
    {
        // Business logic
        return $this->repository->createWithHash($data);
    }
}
```

### 3. Repository Layer

**Location:** `app/Repositories/`

**Responsibilities:**
- Data access abstraction
- Database queries
- CRUD operations
- Query optimization

**Example:**
```php
class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }
}
```

### 4. Model Layer

**Location:** `app/Models/`

**Responsibilities:**
- Domain entity representation
- Relationships definition
- Attributes casting
- Accessors and mutators

**Example:**
```php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

## Design Patterns

### Repository Pattern

The repository pattern provides an abstraction layer between the data access logic and business logic.

**Benefits:**
- Centralized data access logic
- Easier to test (can mock repositories)
- Swappable data sources
- Reusable queries

**Implementation:**
```php
interface RepositoryInterface
{
    public function find(int|string $id): ?Model;
    public function create(array $attributes): Model;
    public function update(int|string $id, array $attributes): bool;
    public function delete(int|string $id): bool;
}
```

### Service Pattern

The service layer encapsulates business logic and orchestrates data flow.

**Benefits:**
- Single Responsibility Principle
- Business logic separation
- Reusable across controllers
- Easier testing

### Resource Pattern

API Resources transform models into JSON responses.

**Benefits:**
- Consistent API responses
- Easy data transformation
- Control over exposed data
- Versioning support

## Request Flow

```
HTTP Request
    ↓
Route Middleware (auth, throttle, etc.)
    ↓
Controller
    ↓
Form Request Validation
    ↓
Service Layer
    ↓
Repository Layer
    ↓
Model/Database
    ↓
Repository Layer (return data)
    ↓
Service Layer (business logic)
    ↓
Controller
    ↓
Resource Transformation
    ↓
ApiResponse Trait
    ↓
HTTP Response
```

## Authentication Flow

```
POST /login
    ↓
LoginRequest validation
    ↓
AuthenticatedSessionController
    ↓
Authenticate credentials
    ↓
Generate Sanctum token
    ↓
Return token + user data
    ↓
Client stores token
    ↓
Subsequent requests include:
Authorization: Bearer {token}
    ↓
Sanctum middleware validates token
    ↓
Request proceeds to controller
```

## API Response Format

All API responses follow a standardized format using the `ApiResponse` trait:

### Success Response
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": { ... }
}
```

### Resource Response
```json
{
    "data": { ... },
    "meta": {
        "timestamp": "2024-01-01T00:00:00Z"
    }
}
```

## Database Schema

### Users Table
- `id` (bigint, primary key)
- `name` (string)
- `email` (string, unique)
- `email_verified_at` (timestamp, nullable)
- `password` (string, hashed)
- `remember_token` (string, nullable)
- `created_at` (timestamp)
- `updated_at` (timestamp)

### Password Reset Tokens Table
- `email` (string, primary key)
- `token` (string)
- `created_at` (timestamp)

### Sessions Table
- `id` (string, primary key)
- `user_id` (bigint, nullable, indexed)
- `ip_address` (string)
- `user_agent` (text)
- `payload` (longtext)
- `last_activity` (integer, indexed)

### Personal Access Tokens Table
- `id` (bigint, primary key)
- `tokenable_type` (string)
- `tokenable_id` (bigint)
- `name` (string)
- `token` (string, unique, hashed)
- `abilities` (text, nullable)
- `last_used_at` (timestamp, nullable)
- `expires_at` (timestamp, nullable)
- `created_at` (timestamp)
- `updated_at` (timestamp)

## Directory Structure

```
app/
├── Contracts/              # Interfaces
│   └── RepositoryInterface.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/           # API Controllers
│   │   ├── Auth/          # Authentication Controllers
│   │   └── Controller.php # Base Controller
│   ├── Middleware/        # Custom Middleware
│   ├── Requests/          # Form Requests
│   └── Resources/         # API Resources
├── Models/                # Eloquent Models
├── Providers/             # Service Providers
├── Repositories/          # Repository Pattern
├── Services/              # Business Logic
└── Traits/                # Reusable Traits

bootstrap/
├── app.php               # Application Bootstrap
└── providers.php         # Service Providers List

config/                   # Configuration Files
├── app.php
├── auth.php
├── cache.php
├── database.php
├── queue.php
├── sanctum.php
└── session.php

database/
├── factories/            # Model Factories
├── migrations/           # Database Migrations
└── seeders/             # Database Seeders

public/
└── index.php            # Entry Point

routes/
├── api.php              # API Routes
├── auth.php             # Authentication Routes
├── console.php          # Console Commands
└── web.php              # Web Routes

storage/
├── app/                 # Application Files
├── framework/           # Framework Files
└── logs/                # Application Logs

tests/
├── Feature/             # Feature Tests
└── Unit/                # Unit Tests
```

## Security Considerations

1. **Authentication:** Laravel Sanctum for API token authentication
2. **Authorization:** Gates and Policies (to be implemented per feature)
3. **Validation:** Form Requests for input validation
4. **SQL Injection:** Eloquent ORM with parameter binding
5. **XSS Protection:** Automatic HTML escaping
6. **CSRF Protection:** Included for stateful requests
7. **Rate Limiting:** Throttle middleware on authentication endpoints
8. **Password Hashing:** Bcrypt with configurable rounds

## Performance Optimization

1. **Database:**
   - Proper indexing on migrations
   - Eager loading to prevent N+1 queries
   - Query result caching

2. **Caching:**
   - Config caching: `php artisan config:cache`
   - Route caching: `php artisan route:cache`
   - View caching: `php artisan view:cache`

3. **Queue:**
   - Async processing for long-running tasks
   - Email sending via queues
   - Webhook processing via queues

## Testing Strategy

1. **Unit Tests:** Test individual service and repository methods
2. **Feature Tests:** Test complete API endpoints
3. **Integration Tests:** Test database interactions
4. **Authentication Tests:** Test all auth flows

## Scalability Considerations

1. **Horizontal Scaling:**
   - Stateless API design
   - Session storage in database/Redis
   - Queue workers can be scaled independently

2. **Database Scaling:**
   - Read replicas support
   - Connection pooling
   - Query optimization

3. **Cache Scaling:**
   - Redis cluster support
   - Cache tags for organized invalidation

## Future Enhancements

1. **API Versioning:** `/api/v1`, `/api/v2`
2. **GraphQL Support:** Alternative to REST
3. **Event Sourcing:** For audit trails
4. **CQRS Pattern:** Separate read/write operations
5. **Microservices:** Domain-driven design
6. **Documentation:** OpenAPI/Swagger integration
7. **Monitoring:** Application performance monitoring
8. **CI/CD:** Automated testing and deployment

## Best Practices

1. **Code Organization:**
   - Follow PSR-12 coding standards
   - Use type hints and return types
   - Keep controllers thin, services fat

2. **Naming Conventions:**
   - Controllers: `UserController`
   - Services: `UserService`
   - Repositories: `UserRepository`
   - Resources: `UserResource`
   - Requests: `StoreUserRequest`, `UpdateUserRequest`

3. **Error Handling:**
   - Use appropriate HTTP status codes
   - Provide meaningful error messages
   - Log errors for debugging

4. **Documentation:**
   - Comment complex business logic
   - Use PHPDoc for methods
   - Maintain API documentation

## Conclusion

This architecture provides a solid foundation for building scalable, maintainable Laravel applications. The separation of concerns makes the codebase easier to understand, test, and extend.
