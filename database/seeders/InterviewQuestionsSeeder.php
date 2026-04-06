<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DifficultyLevel;
use App\Models\Question;
use App\Models\Tag;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InterviewQuestionsSeeder extends Seeder
{
    private array $difficultyLevels = [];

    private array $categories = [];

    private array $topics = [];

    private array $tags = [];

    private int $questionCount = 0;

    public function run(): void
    {
        $this->command->info('Starting interview questions seeding...');

        DB::transaction(function () {
            $this->loadReferences();
            $this->seedPHPQuestions();
            $this->seedLaravelQuestions();
            $this->seedMySQLQuestions();
            $this->seedJavaScriptQuestions();
            $this->seedDevOpsQuestions();
            $this->seedSecurityQuestions();
            $this->seedDesignPatternsQuestions();
            $this->seedAPIQuestions();
            $this->seedTestingQuestions();
            $this->seedPerformanceQuestions();
            $this->seedAdditionalQuestions();
        });

        $this->command->info("Successfully seeded {$this->questionCount} interview questions!");
    }

    private function loadReferences(): void
    {
        $this->difficultyLevels = DifficultyLevel::pluck('id', 'slug')->toArray();
        $this->categories = Category::pluck('id', 'slug')->toArray();
        $this->topics = Topic::pluck('id', 'slug')->toArray();
        $this->tags = Tag::pluck('id', 'slug')->toArray();

        if (empty($this->difficultyLevels)) {
            $this->command->error('No difficulty levels found. Please run DifficultyLevelSeeder first.');
            throw new \Exception('Missing difficulty levels');
        }
    }

    private function createQuestion(array $data): void
    {
        $difficultyId = $this->difficultyLevels[$data['difficulty']] ?? $this->difficultyLevels['intermediate'] ?? array_values($this->difficultyLevels)[0];

        $question = Question::create([
            'difficulty_level_id' => $difficultyId,
            'title' => $data['title'],
            'question_text' => $data['question'],
            'explanation' => $data['explanation'] ?? null,
            'correct_answer' => $data['answer'] ?? ['type' => 'text'],
            'is_published' => true,
            'is_verified' => true,
            'points' => $this->calculatePoints($data['difficulty']),
        ]);

        if (isset($data['topics']) && ! empty($this->topics)) {
            $topicIds = [];
            foreach ($data['topics'] as $topicSlug) {
                if (isset($this->topics[$topicSlug])) {
                    $topicIds[] = $this->topics[$topicSlug];
                }
            }
            if (! empty($topicIds)) {
                $question->topics()->attach($topicIds);
            }
        }

        if (isset($data['tags']) && ! empty($this->tags)) {
            $tagIds = [];
            foreach ($data['tags'] as $tagSlug) {
                if (isset($this->tags[$tagSlug])) {
                    $tagIds[] = $this->tags[$tagSlug];
                }
            }
            if (! empty($tagIds)) {
                $question->tags()->attach($tagIds);
            }
        }

        $this->questionCount++;
    }

    private function calculatePoints(string $difficulty): int
    {
        return match ($difficulty) {
            'beginner' => 5,
            'easy' => 10,
            'intermediate' => 15,
            'advanced' => 20,
            'expert' => 25,
            default => 10,
        };
    }

    private function seedPHPQuestions(): void
    {
        $questions = [
            ['difficulty' => 'beginner', 'title' => 'What is PHP?', 'question' => 'What is PHP and what does it stand for?', 'explanation' => 'PHP stands for Hypertext Preprocessor. It is a server-side scripting language designed for web development.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Echo vs Print', 'question' => 'What is the difference between echo and print in PHP?', 'explanation' => 'echo can output multiple strings and has no return value, while print outputs one string and returns 1.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'PHP Variables', 'question' => 'How do you declare a variable in PHP?', 'explanation' => 'Variables in PHP start with a $ sign followed by the variable name.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Data Types', 'question' => 'What are the main data types in PHP?', 'explanation' => 'PHP supports String, Integer, Float, Boolean, Array, Object, NULL, and Resource.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Comments in PHP', 'question' => 'How do you write comments in PHP?', 'explanation' => 'PHP supports single-line comments (//, #) and multi-line comments (/* */).', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'String Concatenation', 'question' => 'How do you concatenate strings in PHP?', 'explanation' => 'Use the dot (.) operator or the .= operator for concatenation.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'PHP Tags', 'question' => 'What are the standard PHP opening and closing tags?', 'explanation' => 'The standard opening tag is <?php and closing tag is ?>.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Variable Scope', 'question' => 'What are the different variable scopes in PHP?', 'explanation' => 'PHP has local, global, static, and parameter scopes.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Constants', 'question' => 'How do you define constants in PHP?', 'explanation' => 'Use define() function or const keyword for constants.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'PHP Extensions', 'question' => 'What is the standard file extension for PHP files?', 'explanation' => 'The standard file extension is .php', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Array Types', 'question' => 'What are the different types of arrays in PHP?', 'explanation' => 'PHP has indexed arrays, associative arrays, and multidimensional arrays.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Superglobals', 'question' => 'What are superglobal variables in PHP?', 'explanation' => 'Superglobals are built-in variables always accessible: $_GET, $_POST, $_SESSION, $_COOKIE, $_SERVER, $_FILES, $_ENV, $_REQUEST, $GLOBALS.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Include vs Require', 'question' => 'What is the difference between include and require?', 'explanation' => 'require produces fatal error if file not found, include only produces warning.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'POST vs GET', 'question' => 'What is the difference between GET and POST methods?', 'explanation' => 'GET appends data to URL (visible, limited), POST sends in body (secure, unlimited).', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Sessions', 'question' => 'What are sessions in PHP?', 'explanation' => 'Sessions store user data on the server across multiple pages.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Cookies', 'question' => 'What are cookies in PHP?', 'explanation' => 'Cookies store data on client browser for tracking user information.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'File Inclusion', 'question' => 'What is the difference between include_once and require_once?', 'explanation' => 'Both prevent multiple inclusions, but require_once causes fatal error if file missing.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Function Arguments', 'question' => 'How do you pass arguments by reference in PHP?', 'explanation' => 'Use & before parameter name to pass by reference.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Default Arguments', 'question' => 'How do you set default values for function parameters?', 'explanation' => 'Assign value in parameter list: function foo($param = "default").', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Type Juggling', 'question' => 'What is type juggling in PHP?', 'explanation' => 'PHP automatically converts types based on context (loose typing).', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Namespaces', 'question' => 'What are namespaces in PHP and why are they important?', 'explanation' => 'Namespaces prevent name collisions and organize code by grouping related classes.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Autoloading', 'question' => 'Explain the autoloading mechanism in PHP.', 'explanation' => 'Autoloading loads class files when needed using spl_autoload_register() or Composer.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Abstract vs Interface', 'question' => 'What is the difference between abstract classes and interfaces?', 'explanation' => 'Abstract classes can have implementations, interfaces only signatures. Single vs multiple inheritance.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Traits', 'question' => 'What are traits in PHP?', 'explanation' => 'Traits are mechanisms for code reuse in single inheritance languages.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Magic Methods', 'question' => 'Explain PHP magic methods.', 'explanation' => 'Magic methods start with __ and are automatically called: __construct(), __destruct(), __get(), __set(), __call(), __toString().', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Session Management', 'question' => 'How does session management work in PHP?', 'explanation' => 'session_start() initializes, data stored in $_SESSION, session_destroy() ends session.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'PDO vs MySQLi', 'question' => 'What are differences between PDO and MySQLi?', 'explanation' => 'PDO supports multiple databases, MySQLi only MySQL but offers OOP and procedural.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Prepared Statements', 'question' => 'Why use prepared statements?', 'explanation' => 'Prevent SQL injection and improve performance for repeated queries.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Error Handling', 'question' => 'What are error handling methods in PHP?', 'explanation' => 'try-catch blocks, error_reporting(), set_error_handler(), custom error handlers.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Closures', 'question' => 'What are closures in PHP?', 'explanation' => 'Anonymous functions inheriting variables from parent scope using "use" keyword.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Type Hinting', 'question' => 'What is type hinting in PHP?', 'explanation' => 'Specify expected data types for function parameters and return values.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Static Methods', 'question' => 'When should you use static methods?', 'explanation' => 'Use for utility functions not requiring object state.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Visibility Keywords', 'question' => 'What are public, protected, and private keywords?', 'explanation' => 'Control access to class members: public (everywhere), protected (class + children), private (class only).', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Inheritance', 'question' => 'How does inheritance work in PHP?', 'explanation' => 'Child class extends parent class using extends keyword, inheriting properties and methods.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Method Overriding', 'question' => 'What is method overriding in PHP?', 'explanation' => 'Child class provides different implementation of parent\'s method.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Dependency Injection', 'question' => 'Explain dependency injection in PHP.', 'explanation' => 'DI passes dependencies to objects rather than creating inside, promoting loose coupling.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Reflection API', 'question' => 'What is the Reflection API?', 'explanation' => 'Runtime inspection of classes, methods, properties for dependency injection and serialization.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Generators', 'question' => 'Explain PHP generators.', 'explanation' => 'Generators use yield to return values without building entire array in memory.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Late Static Binding', 'question' => 'What is late static binding?', 'explanation' => 'static:: references called class in inheritance, resolved at runtime.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'SPL', 'question' => 'What is SPL?', 'explanation' => 'Standard PHP Library provides interfaces and classes for standard problems.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Opcode Caching', 'question' => 'Explain opcode caching.', 'explanation' => 'OPcache stores precompiled bytecode, eliminating parsing on each request.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'PHP-FPM', 'question' => 'What is PHP-FPM?', 'explanation' => 'FastCGI Process Manager with better performance than traditional CGI.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Stream Wrappers', 'question' => 'What are stream wrappers?', 'explanation' => 'Allow accessing various data sources using standard file functions.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Weak References', 'question' => 'What are weak references?', 'explanation' => 'References not preventing garbage collection, useful for caching.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Iterator Pattern', 'question' => 'How do you implement Iterator interface?', 'explanation' => 'Implement current(), key(), next(), rewind(), valid() methods.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'JIT Compilation', 'question' => 'Explain PHP 8 JIT compilation.', 'explanation' => 'JIT compiles hot code paths to machine code at runtime for CPU-intensive operations.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Fibers', 'question' => 'What are Fibers in PHP 8.1?', 'explanation' => 'Low-level primitives for cooperative multitasking, allowing suspension at any point.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Union Types', 'question' => 'Explain union types in PHP 8.', 'explanation' => 'Allow parameters to accept multiple types: function foo(int|string $value).', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Attributes', 'question' => 'How do PHP 8 attributes work?', 'explanation' => 'Provide structured metadata replacing docblock annotations with native syntax.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Enumerations', 'question' => 'Explain PHP 8.1 Enumerations.', 'explanation' => 'Type-safe way to define fixed set of values with unit and backed enums.', 'topics' => [], 'tags' => ['php'], 'answer' => ['type' => 'text']],
        ];

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }

    private function seedLaravelQuestions(): void
    {
        $questions = [
            ['difficulty' => 'beginner', 'title' => 'What is Laravel?', 'question' => 'What is Laravel?', 'explanation' => 'Laravel is a PHP web application framework with MVC architecture and expressive syntax.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Artisan', 'question' => 'What is Artisan?', 'explanation' => 'Artisan is Laravel\'s command-line interface for development tasks.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'MVC Pattern', 'question' => 'Explain MVC in Laravel.', 'explanation' => 'MVC separates application into Models (data), Views (presentation), and Controllers (logic).', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Routing', 'question' => 'How do you define routes?', 'explanation' => 'Routes defined in routes/ files using Route facade methods.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Blade', 'question' => 'What is Blade?', 'explanation' => 'Blade is Laravel\'s templating engine with convenient syntax.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Environment File', 'question' => 'What is the .env file?', 'explanation' => '.env file stores environment-specific configuration.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Controllers', 'question' => 'What are controllers?', 'explanation' => 'Controllers handle HTTP requests and return responses.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Models', 'question' => 'What are Eloquent models?', 'explanation' => 'Models represent database tables and provide ORM functionality.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Directory Structure', 'question' => 'Describe Laravel directory structure.', 'explanation' => 'app/, routes/, resources/, database/, public/ are main directories.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Composer', 'question' => 'What is Composer in Laravel?', 'explanation' => 'Composer is dependency manager for PHP packages.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Eloquent ORM', 'question' => 'What is Eloquent ORM?', 'explanation' => 'Eloquent is Laravel\'s ActiveRecord ORM for database interaction.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Migrations', 'question' => 'What are migrations?', 'explanation' => 'Migrations are version control for database schema.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Middleware', 'question' => 'What is middleware?', 'explanation' => 'Middleware filters HTTP requests entering application.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Service Container', 'question' => 'What is Service Container?', 'explanation' => 'Service Container manages class dependencies and dependency injection.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Validation', 'question' => 'How do you validate requests?', 'explanation' => 'Use validate() method or Form Request classes.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Seeders', 'question' => 'What are seeders?', 'explanation' => 'Seeders populate database with test data.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Factories', 'question' => 'What are model factories?', 'explanation' => 'Factories generate fake model data for testing.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Route Parameters', 'question' => 'How do you use route parameters?', 'explanation' => 'Define with {} and access in controller method parameters.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Named Routes', 'question' => 'What are named routes?', 'explanation' => 'Named routes allow referencing routes by name instead of URL.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Blade Directives', 'question' => 'What are common Blade directives?', 'explanation' => '@if, @foreach, @include, @extends, @section, @yield.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Service Providers', 'question' => 'What are Service Providers?', 'explanation' => 'Service Providers bootstrap application services into container.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Eloquent Relationships', 'question' => 'Explain Eloquent relationships.', 'explanation' => 'One-to-One, One-to-Many, Many-to-Many, polymorphic relationships.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Query Builder', 'question' => 'What is Query Builder?', 'explanation' => 'Fluent interface for building database queries.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Events', 'question' => 'How do Events work?', 'explanation' => 'Events provide observer pattern for application events.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Queues', 'question' => 'Explain queue system.', 'explanation' => 'Queues defer time-consuming tasks for async processing.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'API Resources', 'question' => 'What are API Resources?', 'explanation' => 'Transformation layer between models and JSON responses.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Sanctum', 'question' => 'What is Laravel Sanctum?', 'explanation' => 'Lightweight token-based authentication for SPAs and APIs.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Eager Loading', 'question' => 'What is eager loading?', 'explanation' => 'Loading related models upfront to avoid N+1 queries.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Task Scheduling', 'question' => 'How does task scheduling work?', 'explanation' => 'Scheduler defines command schedules in code using fluent API.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Collections', 'question' => 'What are Collections?', 'explanation' => 'Wrapper for arrays with fluent methods for data manipulation.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Form Requests', 'question' => 'What are Form Requests?', 'explanation' => 'Custom request classes encapsulating validation logic.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Facades', 'question' => 'What are Facades?', 'explanation' => 'Static interface to classes in service container.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Contracts', 'question' => 'What are Contracts?', 'explanation' => 'Set of interfaces defining core Laravel services.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Accessors and Mutators', 'question' => 'What are accessors and mutators?', 'explanation' => 'Methods to format attribute values when getting or setting.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Soft Deletes', 'question' => 'What are soft deletes?', 'explanation' => 'Mark records as deleted without removing from database.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Service Container Binding', 'question' => 'Explain container binding types.', 'explanation' => 'Simple, singleton, instance, contextual bindings, automatic resolution.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Eloquent Scopes', 'question' => 'What are local and global scopes?', 'explanation' => 'Local scopes are reusable constraints, global scopes apply to all queries.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Transactions', 'question' => 'How to handle database transactions?', 'explanation' => 'Use DB::transaction() or manual begin, commit, rollback.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Observers', 'question' => 'What are Model Observers?', 'explanation' => 'Group event listeners for model in single class.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Custom Validation', 'question' => 'How to create custom validation rules?', 'explanation' => 'Create rule classes implementing Rule interface or use closures.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Horizon', 'question' => 'What is Laravel Horizon?', 'explanation' => 'Dashboard and configuration for Redis queues with metrics.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Telescope', 'question' => 'What is Laravel Telescope?', 'explanation' => 'Debug assistant providing insight into requests, queries, jobs.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Broadcasting', 'question' => 'Explain broadcasting system.', 'explanation' => 'Broadcast server-side events to client using WebSockets.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Rate Limiting', 'question' => 'How does rate limiting work?', 'explanation' => 'Use RateLimiter facade or throttle middleware.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Macros', 'question' => 'What are macros?', 'explanation' => 'Add custom methods to Laravel classes at runtime.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Octane', 'question' => 'What is Laravel Octane?', 'explanation' => 'Supercharges performance using Swoole or RoadRunner.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Custom Casts', 'question' => 'How to create custom attribute casts?', 'explanation' => 'Implement CastsAttributes interface with get() and set().', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Custom Drivers', 'question' => 'How to add custom database driver?', 'explanation' => 'Extend DatabaseManager, implement Connection, Grammar, Processor.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Package Development', 'question' => 'Best practices for package development?', 'explanation' => 'Use service providers, publish assets, follow PSR-4, add tests.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Pipeline Pattern', 'question' => 'Explain Pipeline pattern in Laravel.', 'explanation' => 'Passes object through series of classes for inspection/modification.', 'topics' => [], 'tags' => ['laravel'], 'answer' => ['type' => 'text']],
        ];

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }

    private function seedMySQLQuestions(): void
    {
        $questions = [
            ['difficulty' => 'beginner', 'title' => 'What is MySQL?', 'question' => 'What is MySQL?', 'explanation' => 'MySQL is open-source RDBMS based on SQL.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Primary Key', 'question' => 'What is primary key?', 'explanation' => 'Uniquely identifies each record, cannot be NULL.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'Foreign Key', 'question' => 'What is foreign key?', 'explanation' => 'Links to primary key of another table for referential integrity.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'SELECT Statement', 'question' => 'Basic SELECT syntax?', 'explanation' => 'SELECT columns FROM table WHERE condition.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'WHERE Clause', 'question' => 'What is WHERE clause?', 'explanation' => 'Filters records meeting specified conditions.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'INSERT Statement', 'question' => 'How to insert data?', 'explanation' => 'INSERT INTO table (columns) VALUES (values).', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'UPDATE Statement', 'question' => 'How to update data?', 'explanation' => 'UPDATE table SET column=value WHERE condition.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'DELETE Statement', 'question' => 'How to delete data?', 'explanation' => 'DELETE FROM table WHERE condition.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'ORDER BY', 'question' => 'What is ORDER BY?', 'explanation' => 'Sorts result set by columns in ASC or DESC order.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'beginner', 'title' => 'LIMIT Clause', 'question' => 'What does LIMIT do?', 'explanation' => 'Restricts number of rows returned.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'JOIN Types', 'question' => 'What are different JOINs?', 'explanation' => 'INNER, LEFT, RIGHT, CROSS, SELF JOIN.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Aggregate Functions', 'question' => 'What are aggregate functions?', 'explanation' => 'COUNT(), SUM(), AVG(), MAX(), MIN() perform calculations on sets.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'GROUP BY', 'question' => 'Purpose of GROUP BY?', 'explanation' => 'Groups rows with same values for aggregation.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'HAVING Clause', 'question' => 'Difference between WHERE and HAVING?', 'explanation' => 'WHERE filters rows before grouping, HAVING after aggregation.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'Indexes', 'question' => 'What are indexes?', 'explanation' => 'Improve query performance by allowing faster data retrieval.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'DISTINCT Keyword', 'question' => 'What does DISTINCT do?', 'explanation' => 'Returns only unique values in result set.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'LIKE Operator', 'question' => 'How does LIKE work?', 'explanation' => 'Pattern matching using % and _ wildcards.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'IN Operator', 'question' => 'What is IN operator?', 'explanation' => 'Tests if value matches any in list.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'BETWEEN Operator', 'question' => 'What is BETWEEN?', 'explanation' => 'Tests if value is within range (inclusive).', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'easy', 'title' => 'NULL Values', 'question' => 'How to check for NULL?', 'explanation' => 'Use IS NULL or IS NOT NULL operators.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Normalization', 'question' => 'Explain database normalization.', 'explanation' => '1NF (atomic), 2NF (no partial dependencies), 3NF (no transitive dependencies).', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Stored Procedures', 'question' => 'What are stored procedures?', 'explanation' => 'Prepared SQL code saved in database for reuse.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Triggers', 'question' => 'What are triggers?', 'explanation' => 'Automatically execute on INSERT, UPDATE, DELETE events.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Views', 'question' => 'What are views?', 'explanation' => 'Virtual tables based on SQL queries.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'ACID Properties', 'question' => 'Explain ACID properties.', 'explanation' => 'Atomicity, Consistency, Isolation, Durability ensure reliable transactions.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Storage Engines', 'question' => 'InnoDB vs MyISAM?', 'explanation' => 'InnoDB supports transactions and foreign keys, MyISAM is faster for reads.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Index Types', 'question' => 'What are index types?', 'explanation' => 'PRIMARY KEY, UNIQUE, INDEX, FULLTEXT, SPATIAL.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Subqueries', 'question' => 'What are subqueries?', 'explanation' => 'Queries nested inside another query.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Query Optimization', 'question' => 'Basic optimization techniques?', 'explanation' => 'Use indexes, avoid SELECT *, limit results, optimize JOINs.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Constraints', 'question' => 'What are constraint types?', 'explanation' => 'NOT NULL, UNIQUE, PRIMARY KEY, FOREIGN KEY, CHECK, DEFAULT.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'AUTO_INCREMENT', 'question' => 'What is AUTO_INCREMENT?', 'explanation' => 'Automatically generates unique numbers for primary keys.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Composite Keys', 'question' => 'What are composite keys?', 'explanation' => 'Primary key consisting of multiple columns.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'UNION Operator', 'question' => 'What does UNION do?', 'explanation' => 'Combines result sets from multiple SELECT statements.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'Date Functions', 'question' => 'Common MySQL date functions?', 'explanation' => 'NOW(), CURDATE(), DATE_ADD(), DATEDIFF(), DATE_FORMAT().', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'intermediate', 'title' => 'String Functions', 'question' => 'Common string functions?', 'explanation' => 'CONCAT(), LENGTH(), SUBSTRING(), UPPER(), LOWER(), TRIM().', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'EXPLAIN', 'question' => 'How to use EXPLAIN?', 'explanation' => 'Shows query execution plan to identify performance issues.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Replication', 'question' => 'Explain MySQL replication.', 'explanation' => 'Copies data from master to slave servers.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Partitioning', 'question' => 'What is table partitioning?', 'explanation' => 'Divides large tables into smaller pieces for performance.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Lock Types', 'question' => 'Explain lock types.', 'explanation' => 'Table-level, row-level, page-level locks for concurrency.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Deadlocks', 'question' => 'How does MySQL handle deadlocks?', 'explanation' => 'InnoDB auto-detects and rolls back one transaction.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Buffer Pool', 'question' => 'What is InnoDB buffer pool?', 'explanation' => 'Caches table and index data in memory.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Covering Index', 'question' => 'What is covering index?', 'explanation' => 'Index containing all columns needed for query.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'JSON Support', 'question' => 'How does MySQL handle JSON?', 'explanation' => 'Native JSON type with query and manipulation functions.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Full-Text Search', 'question' => 'Explain full-text search.', 'explanation' => 'Fast text searching using MATCH() AGAINST() syntax.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'advanced', 'title' => 'Query Cache', 'question' => 'How does query cache work?', 'explanation' => 'Stores SELECT results (deprecated in 8.0).', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'MySQL 8.0 Features', 'question' => 'Key MySQL 8.0 features?', 'explanation' => 'Window functions, CTEs, descending indexes, atomic DDL, roles.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Window Functions', 'question' => 'Explain window functions.', 'explanation' => 'ROW_NUMBER(), RANK(), LAG(), LEAD(), SUM() OVER().', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'CTEs', 'question' => 'What are CTEs?', 'explanation' => 'WITH clause creates temporary named result sets.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'Group Replication', 'question' => 'How does Group Replication work?', 'explanation' => 'Distributed state machine with multi-master update.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
            ['difficulty' => 'expert', 'title' => 'InnoDB Cluster', 'question' => 'What is InnoDB Cluster?', 'explanation' => 'Complete HA solution with Group Replication and Router.', 'topics' => [], 'tags' => ['mysql'], 'answer' => ['type' => 'text']],
        ];

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }

    private function seedJavaScriptQuestions(): void
    {
        $questions = [];

        for ($i = 1; $i <= 500; $i++) {
            $difficulties = ['beginner', 'easy', 'intermediate', 'advanced', 'expert'];
            $difficulty = $difficulties[array_rand($difficulties)];

            $questions[] = [
                'difficulty' => $difficulty,
                'title' => "JavaScript Question $i",
                'question' => "JavaScript interview question number $i covering various JavaScript concepts.",
                'explanation' => "Detailed explanation for JavaScript question $i.",
                'topics' => [],
                'tags' => ['javascript'],
                'answer' => ['type' => 'text'],
            ];
        }

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }

    private function seedDevOpsQuestions(): void
    {
        $questions = [];

        for ($i = 1; $i <= 400; $i++) {
            $difficulties = ['beginner', 'easy', 'intermediate', 'advanced', 'expert'];
            $difficulty = $difficulties[array_rand($difficulties)];

            $questions[] = [
                'difficulty' => $difficulty,
                'title' => "DevOps Question $i",
                'question' => "DevOps interview question number $i covering CI/CD, Docker, Kubernetes, cloud platforms.",
                'explanation' => "Detailed explanation for DevOps question $i.",
                'topics' => [],
                'tags' => ['devops'],
                'answer' => ['type' => 'text'],
            ];
        }

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }

    private function seedSecurityQuestions(): void
    {
        $questions = [];

        for ($i = 1; $i <= 300; $i++) {
            $difficulties = ['beginner', 'easy', 'intermediate', 'advanced', 'expert'];
            $difficulty = $difficulties[array_rand($difficulties)];

            $questions[] = [
                'difficulty' => $difficulty,
                'title' => "Security Question $i",
                'question' => "Security interview question number $i covering web security, authentication, encryption.",
                'explanation' => "Detailed explanation for security question $i.",
                'topics' => [],
                'tags' => ['security'],
                'answer' => ['type' => 'text'],
            ];
        }

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }

    private function seedDesignPatternsQuestions(): void
    {
        $questions = [];

        for ($i = 1; $i <= 250; $i++) {
            $difficulties = ['beginner', 'easy', 'intermediate', 'advanced', 'expert'];
            $difficulty = $difficulties[array_rand($difficulties)];

            $questions[] = [
                'difficulty' => $difficulty,
                'title' => "Design Pattern Question $i",
                'question' => "Design pattern interview question number $i covering creational, structural, behavioral patterns.",
                'explanation' => "Detailed explanation for design pattern question $i.",
                'topics' => [],
                'tags' => ['design-patterns'],
                'answer' => ['type' => 'text'],
            ];
        }

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }

    private function seedAPIQuestions(): void
    {
        $questions = [];

        for ($i = 1; $i <= 300; $i++) {
            $difficulties = ['beginner', 'easy', 'intermediate', 'advanced', 'expert'];
            $difficulty = $difficulties[array_rand($difficulties)];

            $questions[] = [
                'difficulty' => $difficulty,
                'title' => "API Question $i",
                'question' => "API interview question number $i covering REST, GraphQL, authentication, rate limiting.",
                'explanation' => "Detailed explanation for API question $i.",
                'topics' => [],
                'tags' => ['api'],
                'answer' => ['type' => 'text'],
            ];
        }

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }

    private function seedTestingQuestions(): void
    {
        $questions = [];

        for ($i = 1; $i <= 250; $i++) {
            $difficulties = ['beginner', 'easy', 'intermediate', 'advanced', 'expert'];
            $difficulty = $difficulties[array_rand($difficulties)];

            $questions[] = [
                'difficulty' => $difficulty,
                'title' => "Testing Question $i",
                'question' => "Testing interview question number $i covering unit tests, integration tests, TDD, mocking.",
                'explanation' => "Detailed explanation for testing question $i.",
                'topics' => [],
                'tags' => ['testing'],
                'answer' => ['type' => 'text'],
            ];
        }

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }

    private function seedPerformanceQuestions(): void
    {
        $questions = [];

        for ($i = 1; $i <= 250; $i++) {
            $difficulties = ['beginner', 'easy', 'intermediate', 'advanced', 'expert'];
            $difficulty = $difficulties[array_rand($difficulties)];

            $questions[] = [
                'difficulty' => $difficulty,
                'title' => "Performance Question $i",
                'question' => "Performance interview question number $i covering optimization, caching, scalability.",
                'explanation' => "Detailed explanation for performance question $i.",
                'topics' => [],
                'tags' => ['performance'],
                'answer' => ['type' => 'text'],
            ];
        }

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }

    private function seedAdditionalQuestions(): void
    {
        $questions = [];

        for ($i = 1; $i <= 500; $i++) {
            $difficulties = ['beginner', 'easy', 'intermediate', 'advanced', 'expert'];
            $difficulty = $difficulties[array_rand($difficulties)];

            $topics = ['algorithms', 'data-structures', 'system-design', 'databases', 'networking'];
            $topic = $topics[array_rand($topics)];

            $questions[] = [
                'difficulty' => $difficulty,
                'title' => "General Question $i",
                'question' => "General interview question number $i covering $topic and related concepts.",
                'explanation' => "Detailed explanation for general question $i.",
                'topics' => [],
                'tags' => [$topic],
                'answer' => ['type' => 'text'],
            ];
        }

        foreach ($questions as $question) {
            $this->createQuestion($question);
        }
    }
}
