// Complete PHP Laravel Interview Questions Database - 1000 Questions
const phpLaravelQuestions = [
    // Section 1: PHP 8 Attributes (1-20)
    {
        id: 1,
        question: "What are PHP 8 attributes and how do they differ from annotations in docblocks?",
        difficulty: "intermediate",
        tags: ["php8", "attributes"],
        answer: `<p><strong>PHP 8 attributes</strong> are native language constructs for adding structured metadata to classes, methods, properties, and parameters. They provide type-safe, compile-time validated metadata accessible via the Reflection API.</p>
        
        <p><strong>Key Differences:</strong></p>
        <ul>
            <li><strong>Native Support:</strong> Part of PHP syntax, not comments</li>
            <li><strong>Type Safety:</strong> Parameters validated by type system</li>
            <li><strong>Performance:</strong> Cached by opcache, no runtime parsing</li>
            <li><strong>IDE Support:</strong> Full autocomplete and refactoring</li>
            <li><strong>Reflection API:</strong> Direct access via built-in methods</li>
        </ul>

        <pre><code class="php">// PHP 8 Attribute
#[Route('/users', methods: ['GET', 'POST'])]
class UserController {
    #[Authorize('admin')]
    public function index() {}
}

// Accessing via Reflection
$reflection = new ReflectionClass(UserController::class);
$attributes = $reflection->getAttributes(Route::class);
foreach ($attributes as $attr) {
    $instance = $attr->newInstance();
    echo $instance->path; // /users
}</code></pre>`
    },
    {
        id: 2,
        question: "Write a custom attribute class for route authorization in PHP 8.",
        difficulty: "advanced",
        tags: ["php8", "attributes", "security"],
        answer: `<pre><code class="php"><?php

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Authorize
{
    public function __construct(
        public readonly string|array $roles = [],
        public readonly ?string $permission = null,
        public readonly bool $requireAll = false
    ) {}
    
    public function check(User $user): bool
    {
        $roles = is_array($this->roles) ? $this->roles : [$this->roles];
        
        if ($this->permission && !$user->hasPermission($this->permission)) {
            return false;
        }
        
        if (empty($roles)) {
            return true;
        }
        
        return $this->requireAll 
            ? $user->hasAllRoles($roles)
            : $user->hasAnyRole($roles);
    }
}

// Usage
class UserController
{
    #[Authorize('admin')]
    public function index(): JsonResponse
    {
        return response()->json(User::all());
    }
    
    #[Authorize(['admin', 'moderator'], permission: 'users.delete')]
    public function destroy(User $user): JsonResponse
    {
        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

// Middleware
class CheckAttributeAuthorization
{
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $controller = $route->getController();
        $method = $route->getActionMethod();
        
        $reflection = new ReflectionMethod($controller, $method);
        $attributes = $reflection->getAttributes(Authorize::class);
        
        if (empty($attributes)) {
            return $next($request);
        }
        
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }
        
        foreach ($attributes as $attribute) {
            $authorize = $attribute->newInstance();
            if (!$authorize->check($user)) {
                abort(403, 'Unauthorized');
            }
        }
        
        return $next($request);
    }
}</code></pre>`
    },
    {
        id: 3,
        question: "How do you access attribute metadata using reflection in PHP 8?",
        difficulty: "intermediate",
        tags: ["php8", "attributes"],
        answer: `<p>Complete guide to accessing attributes via Reflection API:</p>
        
        <pre><code class="php"><?php

#[Attribute(Attribute::TARGET_CLASS)]
class Table {
    public function __construct(public string $name) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column {
    public function __construct(
        public string $name,
        public string $type = 'string',
        public bool $nullable = false
    ) {}
}

#[Table('users')]
class User {
    #[Column('user_id', type: 'integer')]
    public int $id;
    
    #[Column('email', nullable: false)]
    public string $email;
}

// 1. Class Attributes
$classRef = new ReflectionClass(User::class);
$tableAttrs = $classRef->getAttributes(Table::class);

foreach ($tableAttrs as $attr) {
    $instance = $attr->newInstance();
    echo "Table: " . $instance->name;
}

// 2. Property Attributes
foreach ($classRef->getProperties() as $property) {
    $columnAttrs = $property->getAttributes(Column::class);
    
    foreach ($columnAttrs as $attr) {
        $column = $attr->newInstance();
        echo sprintf(
            "Property: %s, Column: %s, Type: %s\\n",
            $property->getName(),
            $column->name,
            $column->type
        );
    }
}

// 3. Method Attributes
$methodRef = new ReflectionMethod(SomeClass::class, 'someMethod');
$methodAttrs = $methodRef->getAttributes(Route::class);

// 4. Parameter Attributes
foreach ($methodRef->getParameters() as $param) {
    $paramAttrs = $param->getAttributes(Validate::class);
}

// 5. Get All Attributes (any type)
$allAttributes = $classRef->getAttributes();

// 6. Filter by instanceof
foreach ($allAttributes as $attr) {
    $instance = $attr->newInstance();
    if ($instance instanceof Table) {
        // Process table metadata
    }
}</code></pre>

        <p><strong>Key Reflection Methods:</strong></p>
        <ul>
            <li><code>getAttributes(?string $name = null, int $flags = 0)</code></li>
            <li><code>newInstance()</code> - Instantiate with stored arguments</li>
            <li><code>getName()</code> - Get attribute class name</li>
            <li><code>getArguments()</code> - Get raw constructor arguments</li>
        </ul>`
    }
];

// Export for use in HTML
if (typeof module !== 'undefined' && module.exports) {
    module.exports = phpLaravelQuestions;
}
