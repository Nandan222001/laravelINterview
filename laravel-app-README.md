# Laravel 11 API Application

A fully-featured Laravel 11 application with authentication scaffolding using Laravel Breeze (API), MySQL database configuration, and a clean architecture with repositories, services, and standardized API responses.

## Features

- ✅ Laravel 11 Framework
- ✅ Laravel Sanctum Authentication (API)
- ✅ Laravel Breeze Authentication Scaffolding
- ✅ MySQL Database Configuration
- ✅ Repository Pattern Implementation
- ✅ Service Layer Architecture
- ✅ Standardized API Response Formatting
- ✅ Resource Classes for API Responses
- ✅ Database Migrations & Seeders
- ✅ PHPUnit Tests
- ✅ Queue Support
- ✅ Cache Configuration
- ✅ Session Management

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── UserController.php          # Example API controller
│   │   ├── Auth/                            # Authentication controllers
│   │   │   ├── AuthenticatedSessionController.php
│   │   │   ├── RegisteredUserController.php
│   │   │   ├── PasswordResetLinkController.php
│   │   │   ├── NewPasswordController.php
│   │   │   ├── VerifyEmailController.php
│   │   │   └── EmailVerificationNotificationController.php
│   │   └── Controller.php
│   ├── Middleware/
│   │   └── EnsureEmailIsVerified.php
│   ├── Requests/
│   │   └── Auth/
│   │       └── LoginRequest.php
│   └── Resources/
│       ├── BaseResource.php                 # Base resource class
│       ├── BaseCollection.php               # Base collection class
│       ├── UserResource.php                 # User resource
│       └── UserCollection.php               # User collection
├── Models/
│   └── User.php                             # User model with Sanctum
├── Repositories/
│   ├── BaseRepository.php                   # Base repository pattern
│   └── UserRepository.php                   # User repository
├── Services/
│   ├── BaseService.php                      # Base service layer
│   └── UserService.php                      # User service
├── Traits/
│   └── ApiResponse.php                      # Standardized API responses
└── Providers/
    └── AppServiceProvider.php

database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000001_create_cache_table.php
│   ├── 0001_01_01_000002_create_jobs_table.php
│   └── 2024_01_01_000003_create_personal_access_tokens_table.php
├── factories/
│   └── UserFactory.php
└── seeders/
    └── DatabaseSeeder.php
```

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Node.js & NPM (optional, for frontend assets)

### Setup Steps

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   ```

3. **Configure Database**
   
   Update `.env` file with your MySQL credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

4. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed Database (Optional)**
   ```bash
   php artisan db:seed
   ```

## Authentication

This application uses Laravel Sanctum for API authentication.

### Register a New User

```bash
POST /register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

### Login

```bash
POST /login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password"
}

Response:
{
    "message": "Login successful",
    "user": { ... },
    "token": "1|xxx..."
}
```

### Logout

```bash
POST /logout
Authorization: Bearer {token}
```

## API Response Format

All API responses follow a standardized format using the `ApiResponse` trait:

### Success Response
```json
{
    "success": true,
    "message": "Success message",
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

## Using the ApiResponse Trait

```php
use App\Traits\ApiResponse;

class YourController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $data = ['key' => 'value'];
        return $this->successResponse($data, 'Data retrieved');
    }

    public function store()
    {
        // ...
        return $this->createdResponse($model, 'Created successfully');
    }

    public function error()
    {
        return $this->errorResponse('Something went wrong', 400);
    }
}
```

### Available Response Methods

- `successResponse($data, $message, $statusCode = 200)`
- `errorResponse($message, $statusCode, $errors = null)`
- `createdResponse($data, $message = 'Created successfully')`
- `noContentResponse()`
- `notFoundResponse($message = 'Resource not found')`
- `validationErrorResponse($errors, $message = 'Validation failed')`
- `unauthorizedResponse($message = 'Unauthorized')`
- `forbiddenResponse($message = 'Forbidden')`
- `serverErrorResponse($message = 'Internal server error')`

## Repository Pattern

The application implements the repository pattern for data access:

```php
// UserRepository extends BaseRepository
public function findByEmail(string $email): ?User
{
    return $this->model->where('email', $email)->first();
}
```

### Base Repository Methods

- `all($columns = ['*'])`
- `paginate($perPage = 15, $columns = ['*'])`
- `find($id, $columns = ['*'])`
- `findOrFail($id, $columns = ['*'])`
- `findBy($field, $value, $columns = ['*'])`
- `findWhere($where, $columns = ['*'])`
- `create($attributes)`
- `update($id, $attributes)`
- `delete($id)`
- `updateOrCreate($attributes, $values = [])`
- `firstOrCreate($attributes, $values = [])`

## Service Layer

Services encapsulate business logic:

```php
// UserService extends BaseService
public function register(array $data): User
{
    return $this->repository->createWithHash($data);
}
```

### Using Services

```php
public function __construct(
    private UserService $userService
) {}

public function index()
{
    $users = $this->userService->getPaginated(15);
    return $this->successResponse(new UserCollection($users));
}
```

## Resource Classes

Transform models into consistent JSON responses:

```php
class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

## API Routes

### Authentication Routes
- `POST /register` - Register new user
- `POST /login` - Login user
- `POST /logout` - Logout user
- `POST /forgot-password` - Request password reset
- `POST /reset-password` - Reset password
- `GET /verify-email/{id}/{hash}` - Verify email
- `POST /email/verification-notification` - Resend verification email

### Protected API Routes (require authentication)
- `GET /api/user` - Get authenticated user
- `GET /api/users` - List all users (paginated)
- `GET /api/users/{id}` - Get specific user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

## Testing

Run the test suite:

```bash
php artisan test
```

Or with PHPUnit:

```bash
vendor/bin/phpunit
```

### Example Tests

The application includes example authentication tests:
- User registration
- User login
- User logout
- Invalid credentials handling

## Queue Configuration

The application is configured to use database queues:

```bash
# Run queue worker
php artisan queue:work

# Process jobs on specific queue
php artisan queue:work --queue=default,emails
```

## Cache Configuration

Default cache driver is set to `database`. Available drivers:
- `array` - In-memory cache (testing)
- `database` - Database cache
- `file` - File-based cache
- `memcached` - Memcached
- `redis` - Redis cache

## Session Configuration

Sessions are stored in the database by default for API stateful authentication.

## Development

### Run Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### Create New Repository

```php
namespace App\Repositories;

use App\Models\YourModel;

class YourRepository extends BaseRepository
{
    public function __construct(YourModel $model)
    {
        parent::__construct($model);
    }
    
    // Add custom repository methods
}
```

### Create New Service

```php
namespace App\Services;

use App\Repositories\YourRepository;

class YourService extends BaseService
{
    public function __construct(YourRepository $repository)
    {
        parent::__construct($repository);
    }
    
    // Add custom business logic
}
```

### Create New Resource

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;

class YourResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Add your fields
        ];
    }
}
```

## Security Features

- ✅ CSRF Protection
- ✅ SQL Injection Prevention (Eloquent ORM)
- ✅ XSS Protection (Blade templating)
- ✅ Rate Limiting on Authentication
- ✅ Password Hashing (bcrypt)
- ✅ API Token Authentication (Sanctum)
- ✅ Email Verification
- ✅ Password Reset

## Performance Optimization

- Query optimization with Eloquent
- Database indexing on migrations
- Cache support for frequently accessed data
- Queue support for long-running tasks
- Pagination for large datasets

## License

This is an open-source application built with Laravel 11.

## Contributing

Contributions are welcome! Please follow PSR-12 coding standards.

## Support

For issues and questions, please refer to the [Laravel Documentation](https://laravel.com/docs/11.x).
