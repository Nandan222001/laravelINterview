# AGENTS.md

## Laravel 11 Application Setup

This repository contains a fully-featured Laravel 11 application with authentication scaffolding using Laravel Breeze (API), MySQL database configuration, and a clean architecture with repositories, services, and standardized API responses.

## Setup Commands

### Prerequisites
- PHP 8.2 or higher
- Composer 2.x
- MySQL 8.0 or higher
- Git

### Initial Setup
```bash
# Install dependencies (requires Composer)
composer install

# Copy environment file (already exists as .env)
# If needed: cp .env.example .env

# Generate application key (if not set)
php artisan key:generate

# Configure database in .env file
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed

# Set up storage permissions
chmod -R 775 storage bootstrap/cache
```

## Build Commands
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clear all caches (development)
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## Lint Commands
```bash
# Laravel Pint (code style fixer)
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test
```

## Test Commands
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# PHPUnit directly
./vendor/bin/phpunit
```

## Dev Server
```bash
# Start development server
php artisan serve

# Start on specific host/port
php artisan serve --host=0.0.0.0 --port=8080

# Start queue worker (for background jobs)
php artisan queue:work

# Start queue worker with watching
php artisan queue:listen
```

## Tech Stack

### Backend Framework
- **Laravel 11.x** - PHP framework
- **PHP 8.2+** - Programming language

### Authentication
- **Laravel Sanctum 4.x** - API authentication
- **Laravel Breeze 2.x** - Authentication scaffolding

### Database
- **MySQL 8.0+** - Primary database
- **Eloquent ORM** - Database abstraction

### Additional Packages
- **Laravel Tinker** - REPL for Laravel
- **Faker** - Test data generation
- **PHPUnit** - Testing framework
- **Laravel Pint** - Code style fixer
- **Mockery** - Mocking framework

## Architecture & Structure

### Application Structure
```
app/
├── Contracts/              # Interfaces and contracts
├── Http/
│   ├── Controllers/
│   │   ├── Api/           # API controllers
│   │   └── Auth/          # Authentication controllers
│   ├── Middleware/        # Custom middleware
│   ├── Requests/          # Form request validation
│   └── Resources/         # API resources (JSON transformation)
├── Models/                # Eloquent models
├── Providers/             # Service providers
├── Repositories/          # Repository pattern implementation
├── Services/              # Business logic layer
└── Traits/                # Reusable traits (ApiResponse, etc.)

database/
├── factories/             # Model factories for testing
├── migrations/            # Database migrations
└── seeders/              # Database seeders

routes/
├── api.php               # API routes (Sanctum protected)
├── auth.php              # Authentication routes
├── console.php           # Console commands
└── web.php               # Web routes

tests/
├── Feature/              # Feature tests
│   └── Auth/            # Authentication tests
└── Unit/                # Unit tests
```

### Design Patterns

1. **Repository Pattern** - Data access abstraction
   - `BaseRepository` - Abstract repository with common methods
   - `UserRepository` - User-specific data operations

2. **Service Layer Pattern** - Business logic encapsulation
   - `BaseService` - Abstract service with common methods
   - `UserService` - User-specific business logic

3. **Resource Pattern** - API response transformation
   - `BaseResource` - Base resource with metadata
   - `UserResource` - User data transformation
   - `BaseCollection` - Collection responses with pagination

4. **Trait Pattern** - Reusable functionality
   - `ApiResponse` - Standardized API responses

### Key Features

1. **Authentication System**
   - User registration with email verification
   - Login/logout with Sanctum tokens
   - Password reset functionality
   - Rate limiting on authentication endpoints

2. **API Response Standardization**
   - Success responses with data
   - Error responses with details
   - Validation error handling
   - HTTP status code consistency

3. **Database Architecture**
   - Users table with email verification
   - Password reset tokens
   - Sessions for stateful authentication
   - Personal access tokens (Sanctum)
   - Cache tables
   - Job queues tables

4. **Testing Infrastructure**
   - Feature tests for authentication flows
   - Unit tests ready structure
   - PHPUnit configuration
   - Database testing with SQLite

## Code Style & Conventions

### Naming Conventions
- **Controllers**: `UserController`, singular noun + Controller
- **Services**: `UserService`, singular noun + Service
- **Repositories**: `UserRepository`, singular noun + Repository
- **Models**: `User`, singular noun
- **Resources**: `UserResource`, singular noun + Resource
- **Requests**: `LoginRequest`, action + Request
- **Migrations**: `create_users_table`, descriptive snake_case

### Coding Standards
- Follow PSR-12 coding standard
- Use type hints for parameters and return types
- Use PHP 8.2 features (enums, readonly properties, etc.)
- Keep controllers thin, services fat
- Use dependency injection
- Write meaningful comments for complex logic

### API Response Format
All API responses follow a standardized format:

**Success:**
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

**Error:**
```json
{
    "success": false,
    "message": "Error message",
    "errors": { ... }
}
```

### Database Conventions
- Use migrations for schema changes
- Use seeders for test data
- Use factories for model generation
- Index foreign keys and frequently queried columns
- Use soft deletes where appropriate

## Environment Configuration

### Required Environment Variables
```env
APP_NAME="Laravel API"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
```

## API Endpoints

### Authentication Endpoints
- `POST /register` - Register new user
- `POST /login` - Login user (returns token)
- `POST /logout` - Logout user
- `POST /forgot-password` - Request password reset
- `POST /reset-password` - Reset password
- `GET /verify-email/{id}/{hash}` - Verify email
- `POST /email/verification-notification` - Resend verification

### Protected API Endpoints (require authentication)
- `GET /api/user` - Get authenticated user
- `GET /api/users` - List users (paginated)
- `GET /api/users/{id}` - Get user by ID
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

## Documentation Files

- **README.md** - Project overview and features
- **laravel-app-README.md** - Detailed Laravel application documentation
- **INSTALLATION.md** - Step-by-step installation guide
- **API-DOCUMENTATION.md** - Complete API endpoint documentation
- **ARCHITECTURE.md** - Detailed architecture and design patterns
- **AGENTS.md** - This file (agent/automation instructions)

## Development Workflow

1. **Feature Development**
   - Create migration for database changes
   - Create/update model
   - Create repository for data access
   - Create service for business logic
   - Create controller for HTTP handling
   - Create resource for response transformation
   - Add routes
   - Write tests

2. **Testing**
   - Write feature tests for endpoints
   - Write unit tests for services
   - Run tests: `php artisan test`
   - Check coverage

3. **Code Quality**
   - Run Pint: `./vendor/bin/pint`
   - Review code for PSR-12 compliance
   - Ensure proper type hints

4. **Deployment**
   - Run optimizations
   - Set proper environment variables
   - Run migrations on production
   - Set up queue workers
   - Set up task scheduler

## Common Tasks

### Create New API Resource
```bash
# Create model with migration and factory
php artisan make:model Product -mf

# Create repository
# (manually create in app/Repositories/)

# Create service
# (manually create in app/Services/)

# Create controller
php artisan make:controller Api/ProductController --api

# Create resource
php artisan make:resource ProductResource
php artisan make:resource ProductCollection

# Create request
php artisan make:request StoreProductRequest
php artisan make:request UpdateProductRequest
```

### Database Operations
```bash
# Create migration
php artisan make:migration create_products_table

# Run migrations
php artisan migrate

# Rollback migration
php artisan migrate:rollback

# Fresh migration (drop all tables)
php artisan migrate:fresh

# Fresh with seed
php artisan migrate:fresh --seed

# Create seeder
php artisan make:seeder ProductSeeder
```

### Queue Operations
```bash
# Create job
php artisan make:job ProcessPayment

# Run queue worker
php artisan queue:work

# List failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}
```

## Troubleshooting

### Common Issues

1. **"No application encryption key has been specified"**
   ```bash
   php artisan key:generate
   ```

2. **Database connection errors**
   - Check MySQL is running
   - Verify credentials in .env
   - Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

3. **Permission errors**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

4. **Class not found**
   ```bash
   composer dump-autoload
   ```

5. **Cache issues**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

## Additional Resources

- Laravel Documentation: https://laravel.com/docs/11.x
- Laravel Sanctum: https://laravel.com/docs/11.x/sanctum
- PHP 8.2 Features: https://www.php.net/releases/8.2/en.php
- PSR-12 Standard: https://www.php-fig.org/psr/psr-12/
