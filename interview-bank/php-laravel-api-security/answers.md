# PHP, Laravel & Advanced API Security - 1000 Answers

## Section 1: PHP 8.x Features

### Attributes (Answers 1-20)

1. PHP 8 attributes are a language feature for adding metadata to classes, properties, methods, and parameters. They differ from docblock annotations in that they are processed at runtime via the Reflection API with full type safety and compile-time validation. Docblock annotations are just comments and require custom parsing.

2. `#[Route('/api/users', 'authorize')]` Attribute example - create a custom attribute class with `#[Attribute(Attribute::TARGET_METHOD)]` that stores permission requirements, then use reflection in middleware to check permissions before method execution.

3. Use `Reflection API` to access attribute metadata: `$refClass = new ReflectionClass($class); $attributes = $refClass->getAttributes(RouteAttribute::class); foreach ($attributes as $attr) { $attr->newInstance(); }`

4. Built-in PHP 8 attributes include `#[Deprecated]`, `#[Sensitive]` (PHP 8.2), `#[SensitiveParameter]` (PHP 8.2) for parameter redaction in stack traces, and `#[AllowDynamicProperties]` (PHP 8.2) to enable dynamic properties on classes.

5. Create a `#[Cached]` attribute using a decorator pattern: store cache TTL and key in the attribute, then wrap method calls in a decorator that checks cache before execution. Use Reflection to extract attribute instance and apply caching logic.

6. Attributes enable declarative validation by marking properties with validation rules like `#[Email]`, `#[Min(1)]`, `#[Max(100)]`. A validation service reads these attributes via Reflection and generates validation rules dynamically at runtime.

7. `Attribute::TARGET_CLASS` restricts the attribute to class declarations only, while `Attribute::TARGET_METHOD` restricts it to methods. Use `Attribute::TARGET_ALL` or combine flags with `|` for multiple targets.

8. Implement attribute-based DI by creating `#[Inject]` attribute, scanning classes with Reflection for injected properties, and resolving them through the service container before returning the instance.

9. Add `Attribute::IS_REPEATABLE` flag: `#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]`. Then use `getAttributes()` with return all instances, allowing multiple identical attributes on same target.

10. Create `#[RateLimit('10/1minute')]` attribute storing limit config. In middleware, use Reflection to read attribute from matched route/action, then check Redis counter to enforce limit before execution.

11. Attributes are processed and cached by PHP's opcache during compilation, making them faster than parsing docblock strings at runtime. Attributes have minimal overhead; parsing docblocks is slower and requires custom regex parsing.

12. Generate OpenAPI specs by scanning controllers and methods for attributes like `#[Route]`, `#[RequestBody]`, `#[Response]`. Extract metadata via Reflection and build OpenAPI JSON/YAML definitions automatically.

13. Create `#[Authorize('admin')]` attribute checking user roles before execution. Use a decorator or middleware that reads the attribute via Reflection and throws `AuthorizationException` if user lacks required role.

14. Apply attributes to enum cases using `#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]`. Example: `enum Status { #[Label('Active')] case Active; #[Label('Inactive')] case Inactive; }` - read via Reflection on enum case.

15. Create `#[LogExecutionTime]` attribute storing method name and start time before execution, then log elapsed duration and method name after completion. Use `microtime(true)` for precise timing.

16. Attributes are validated when instantiated via `getAttribute()->newInstance()`. To validate at definition time, use attribute constructors with type hints - invalid parameters cause `TypeError` at attribute definition, not instantiation.

17. Create an `#[Table('users')]` attribute specifying table name. In ORM, use Reflection to read attribute from model class and use it for `SELECT/INSERT/UPDATE/DELETE` queries instead of deriving table name from class name.

18. Limitations: attributes only support primitive types and arrays as parameters (no objects), they can't be applied to properties via attributes directly in PHP 8.0 (added in 8.1), and they require Reflection API for runtime access.

19. For event sourcing, use attributes like `#[DomainEvent]` to mark classes as events, `#[AggregateRoot]` for aggregate roots, and `#[EventHandler]` for handlers. Store attribute metadata in event metadata during serialization.

20. Create `#[Transactional]` attribute that wraps method execution in database transaction using `DB::transaction()`. Nested calls use savepoints; if inner transaction fails, outer rolls back all changes.

### Enums (Answers 21-40)

21. Backed enums have an underlying scalar value (int or string) accessible via `->value`, while pure enums have no value. Example: `enum Status: string { case Active = 'active'; case Inactive = 'inactive'; }` vs `enum Color { case Red; case Blue; }`.

22. Create HTTP status enum: `enum HttpStatus: int { case OK = 200; case Created = 201; case BadRequest = 400; public function message(): string { return match($this) { ... }; } }`

23. Enums can't use traits directly, but implement interfaces and add methods. Simulating traits: define methods in enum using static cases, compose methods via interfaces, or create enum wrapper classes that delegate to shared implementation.

24. `enum PaymentStatus { case Pending; case Processing; case Completed; case Failed; public function canTransitionTo(self $newStatus): bool { return match($this) { PaymentStatus::Pending => in_array($newStatus, [self::Processing, self::Failed]), ... }; } }`

25. Enums are compiled into single instances at runtime, using less memory than class constants (no separate object for each constant). Comparisons use identity (`===`) instead of equality checks, making them faster for switch/match operations.

26. `enum UserRole implements Permissible { case Admin; case User; public function hasPermission(string $action): bool { return match($this) { UserRole::Admin => true, UserRole::User => in_array($action, ['read', 'comment']), }; } }`

27. Backed enums serialize to their value: `json_encode($status) => 'active'`. Deserialize with `HttpStatus::from('active')` or `HttpStatus::tryFrom('invalid') => null`. For custom serialization, implement `JsonSerializable` interface.

28. `enum UserRole { case Super_Admin; case Admin; case User; public function permissions(): array { return match($this) { UserRole::Super_Admin => ['*'], UserRole::Admin => ['manage_users', 'manage_content'], ... }; } }`

29. Use match expressions with enums: `$message = match($status) { PaymentStatus::Pending => 'Awaiting payment', PaymentStatus::Completed => 'Order confirmed', default => 'Unknown' };`

30. Create error code enum with i18n: `enum ErrorCode: string { case InvalidEmail = 'INVALID_EMAIL'; public function message(string $locale = 'en'): string { return trans("errors.{$this->value}", locale: $locale); } }`

31. Iterate with `$enum::cases()`: returns array of all cases. Use `array_map(fn(Status $case) => $case->value, Status::cases())` to get all values.

32. `enum OrderStatus { case Pending; case Confirmed; case Shipped; case Delivered; case Cancelled; public function canCancel(): bool { return $this === OrderStatus::Pending || $this === OrderStatus::Confirmed; } }`

33. Enums have minimal memory footprint - one instance per case stored in opcache. Class constants require object initialization overhead per constant. Enums are also type-safe, preventing invalid values at compile-time.

34. `enum CurrencyCode: string { case USD = 'USD'; case EUR = 'EUR'; case GBP = 'GBP'; public function symbol(): string { return match($this) { CurrencyCode::USD => '$', ... }; } public function format(float $amount): string { ... } }`

35. For backward compatibility, define new enum cases without removing old ones. Use `@deprecated` in docblocks. Create factory methods that map old strings to new enum cases: `static function fromLegacy(string $old): self { return match($old) { 'active' => self::Active, ... }; }`

36. `enum MimeType: string { case JSON = 'application/json'; case XML = 'application/xml'; case PDF = 'application/pdf'; public function extension(): string { return match($this) { MimeType::JSON => 'json', ... }; } }`

37. Enums are first-class types in PHP's type system. Use in type hints: `function setStatus(PaymentStatus $status)` - type safety checked at compile-time. instanceof works: `$status instanceof PaymentStatus`. Union types supported: `PaymentStatus|int`.

38. `enum DatabaseConnectionType { case MySQL; case PostgreSQL; case SQLite; case Redis; public function driver(): string { return match($this) { DatabaseConnectionType::MySQL => 'mysql', ... }; } }`

39. Test enum methods: `$this->assertTrue(UserRole::Admin->permissions()->contains('manage_users'));` Create test provider with `enum::cases()` to parameterize tests across all cases.

40. `enum ContentType: string { case Json = 'application/json'; case Xml = 'application/xml'; case FormUrlencoded = 'application/x-www-form-urlencoded'; public function parser(): Closure { ... } }`

### JIT Compiler (Answers 41-60)

41. PHP 8's JIT (Just-In-Time) compiler converts opcodes to native machine code at runtime, speeding up CPU-intensive code. Beneficial for computational tasks (math, loops); has minimal impact on typical I/O-bound web applications. Enable with `opcache.jit=tracing` or `opcache.jit=function`.

42. Key opcache settings for JIT: `opcache.jit=tracing` (best for most apps), `opcache.jit_buffer_size=100M` (space for compiled code), `opcache.jit_max_poly_inline_size=8` (inlining threshold), `opcache.jit_hot_loop_cycles=64` (hot loop detection).

43. Profile JIT effectiveness using `opcache_get_status()` to check `jit.*` stats. Use Blackfire or Xdebug to compare execution times with/without JIT. Measure specific code paths: `microtime(true)` before/after JIT-compilation.

44. Function-level JIT compiles entire functions when called frequently. Tracing JIT creates optimized code paths for hot code patterns (loops, branches). Tracing JIT is more aggressive but has higher compilation overhead; best for code with loops.

45. Code with loops, mathematical operations, and hot paths benefits most from JIT. Web request handlers (controller actions) rarely enter hot loops. Database queries, I/O operations won't improve. Batch processing, data analysis benefit significantly.

46. JIT memory overhead comes from compiled code stored in JIT buffer. Allocate 100-200MB for typical apps. Code size increases by 2-4x when compiled. Memory usage is predictable - when buffer fills, compilation stops until next request.

47. Debugging challenges: stack traces show compiled code references (line numbers may not match source). IDE breakpoints may not work in JIT-compiled code. Disable JIT for debugging: `opcache.jit=0` or use `opcache.jit_debug=1` for additional debugging info.

48. For production Laravel: set `opcache.jit=tracing`, `opcache.jit_buffer_size=256M` (or 20% of available RAM), `opcache.enable=1`, `opcache.enable_cli=0` (CLI doesn't benefit). Monitor JIT stats with `opcache_get_status()` monitoring tools.

49. `opcache.jit_buffer_size` allocates memory for compiled code. Default 0 (JIT disabled). Example: 256M allocates 256MB for native code storage. Larger buffer = more code cached; insufficient buffer causes spilling (least-used compiled code evicted).

50. `opcache.jit` modes: `0` = disabled, `1` (1005) = function JIT, `2` (tracing) = tracing JIT, `4` (off mode) = disabled. Format: `opcache.jit=tracing` sets tracing mode. Run `php -i | grep opcache.jit` to verify mode.

51. JIT interacts with C extensions by compiling to machine code that can call C functions. FFI (Foreign Function Interface) benefits from JIT for wrapper overhead reduction. C extensions themselves aren't JIT-compiled, only PHP code calling them.

52. Security implications: JIT-compiled code may expose different attack surface. Timing attacks become more predictable (less variable overhead). Enable JIT safely in secured environments. Disable if running untrusted code. No known JIT-specific security issues currently.

53. Measure API endpoint performance: use APM tools (New Relic, Datadog) or `microtime(true)` for request start/end times. Compare metrics with JIT enabled vs disabled over large sample sizes. Most web APIs see <5% improvement; benefit varies by code complexity.

54. JIT has minimal impact on typical web applications because: HTTP requests are I/O-bound, code paths are rarely hot (no loops), most time spent in database/network operations, not CPU. JIT shines for batch processing, report generation, mathematical computations.

55. Best practices: enable in production if CPU-bound code identified, allocate sufficient JIT buffer (100-256MB), monitor JIT compilation stats, disable for debugging, consider containerized resource limits, test thoroughly in staging.

56. Blackfire profiling shows compiled code regions but can't directly profile JIT code. Use flame graphs with `perf` tool on Linux. Stack traces may show addresses instead of function names for JIT code. Use `objdump` to inspect compiled code if needed.

57. Warm-up period: JIT needs to observe code execution patterns before compilation. Initial requests are slower (profiling overhead). After ~1000-10000 requests, hot code paths are compiled. Scaling apps should warm up JIT on deployment to minimize initial latency.

58. JIT-related crashes are rare but can happen from: buffer overflow in compiler, incompatible CPU instructions, corrupted compiled code cache. Solutions: upgrade PHP, reduce `opcache.jit_buffer_size`, disable JIT temporarily, check system stability (RAM errors).

59. JIT improves CPU cache efficiency by compiling code to tight machine code sequences, reducing instruction cache misses. Branch prediction improves in compiled code (fewer indirect jumps). Memory bandwidth usage decreases for hot code paths.

60. Future PHP JIT developments: ARM support improvements, WASM compilation target, tier-2 JIT (two-level compilation), speculative JIT (aggressive optimizations), and integration with CPU-specific features (AVX-512, etc.).

### Fibers (Answers 61-80)

61. PHP 8.1 Fibers enable lightweight cooperative multitasking. Unlike threads, fibers don't require OS context switching. Create with `new Fiber($callback)`, start with `->start()`, suspend execution with `Fiber::suspend()`, and resume with `->resume()`. Ideal for async/await patterns.

62. Example fiber task scheduler: `class TaskScheduler { private array $tasks = []; public function add(Fiber $fiber) { $this->tasks[] = $fiber; } public function run() { while(!empty($this->tasks)) { foreach ($this->tasks as $k => $f) { $f->resume(); if ($f->isTerminated()) unset($this->tasks[$k]); } } } }`

63. Fibers are cheaper (smaller memory footprint ~1KB vs generators ~10KB) and explicit about suspension points. Generators are iterators; fibers are cooperative multitasking primitives. Generators `yield` values; fibers suspend/resume execution flow.

64. Create fiber pool for parallel HTTP requests: `class FiberPool { private array $fibers = []; public function add(string $url) { $this->fibers[] = new Fiber(fn() => file_get_contents($url)); } public function execute() { foreach ($this->fibers as $f) $f->start(); while(!all_terminated()) { foreach ($this->fibers as $f) if (!$f->isTerminated()) $f->resume(); } } }`

65. Fiber lifecycle: (1) Create with `new Fiber()`, (2) Start with `->start()` (runs until first suspend), (3) Suspend with `Fiber::suspend($value)` (pauses execution), (4) Resume with `->resume($value)` (continues from suspend), (5) Terminate when callback ends, (6) Check status with `->isTerminated()`.

66. Example event loop: `class EventLoop { private array $fibers = []; public function on(string $event, Fiber $f) { $this->fibers[$event][] = $f; } public function emit(string $event) { foreach ($this->fibers[$event] as $f) { $f->start(); while (!$f->isTerminated()) { Fiber::suspend($event); $f->resume(); } } } }`

67. Handle exceptions in fibers with try/catch within fiber callback, or catch thrown exceptions when resuming: `try { $f->resume(); } catch (Exception $e) { handle_error($e); }`. Exceptions in suspended fibers must be caught during resume to prevent silent failures.

68. Fiber-based queue worker: `class FiberQueueWorker { public function process(Job $job) { $fiber = new Fiber(function() use ($job) { $job->execute(); Fiber::suspend('done'); }); $fiber->start(); while (!$fiber->isTerminated()) { Fiber::suspend(); $fiber->resume(); } } }`

69. Memory implications: creating thousands of fibers uses ~1MB per 1000 fibers (1KB each). Unlike processes (50MB+) or threads (8MB+), fibers are lightweight. However, storing fiber references in arrays prevents garbage collection - clean up references when done.

70. Fiber-based rate limiter: `class FiberRateLimiter { private int $count = 0; private int $limit; public function check() { if (++$this->count > $this->limit) { Fiber::suspend('rate-limit'); $this->count--; } } }`

71. Fibers enable async/await-like patterns in PHP without language keywords. Implement using Fiber::suspend for "await" and fiber->resume for resolution. Example: `$result = await($fiber)` via custom function wrapping suspend/resume.

72. Fiber wrapper for DB connection pooling: `class ConnectionPool { public function borrow(Fiber $user) { $conn = $this->getConnection(); $user->start(); $conn->execute(...); if (!$user->isTerminated()) Fiber::suspend(); $user->resume(); $this->release($conn); } }`

73. `Fiber::suspend($value)` pauses current fiber and returns value to caller. Caller must explicitly `->resume($value)` to continue. `yield` is implicit return in generators; `Fiber::suspend` is explicit control flow. Fibers don't yield values automatically.

74. Fiber-based web scraper: `class Scraper { public function scrape(array $urls) { $fibers = array_map(fn($url) => new Fiber(fn() => $this->fetch($url)), $urls); foreach ($fibers as $f) $f->start(); while ($any_running) { foreach ($fibers as $f) if (!$f->isTerminated()) $f->resume(); } } }`

75. Debug fiber execution: set breakpoints in fiber callbacks, use `var_dump($fiber->isTerminated())` to check status, trace suspend/resume calls with logging, use `debug_backtrace()` to see fiber call stack at suspend point.

76. Fiber-based promise implementation: `class Promise { private Fiber $fiber; public function __construct(Closure $executor) { $this->fiber = new Fiber($executor); } public function then(Closure $onResolve) { $this->fiber->start(); if ($this->fiber->isTerminated()) $onResolve($this->fiber->getReturn()); } }`

77. Best practices: always catch exceptions in fiber callbacks, clean up fiber references to allow GC, avoid blocking operations inside fibers (defeats purpose), use fibers for I/O coordination not CPU-bound work, test fiber code thoroughly.

78. Fiber-based WebSocket server (simplified): `class WsServer { public function handleClient(Fiber $client) { $client->start(); while (!$client->isTerminated()) { $message = Fiber::suspend('read'); $client->resume($message); } } }`

79. Fibers integrate with Laravel's queue system through custom queue handlers. Create `FiberQueueHandler` that uses fibers for concurrent job execution. Useful for processing multiple small jobs in parallel within single worker process.

80. Fiber-based circuit breaker: `class CircuitBreaker { public function call(Fiber $operation) { if ($this->isOpen()) { Fiber::suspend('circuit-open'); } $result = $this->try($operation); if ($this->fails > $this->threshold) $this->open(); return $result; } }`

### PHP 8.x Type System (Answers 81-100)

81. Union types allow multiple type options: `function handle(int|string $id): array|null { ... }`. Useful for APIs accepting multiple input types. Example: `function processId(int|string $id): void { $id = is_int($id) ? $id : (int)$id; }`. Reduces `mixed` type overuse.

82. Intersection types require all types simultaneously (PHP 8.1): `function process(Countable&ArrayAccess $data): void { ... }`. Useful for APIs requiring multiple interface implementation. Example: `function handle(Iterator&Serializable $data)` ensures both interfaces implemented.

83. DNF (Disjunctive Normal Form) types combine union and intersection (PHP 8.2): `function handle((A&B)|C|D $param): void { ... }`. Normalizes to: `(A&B)|C|D`. More flexible type constraints for complex scenarios. Example: `(Countable&Iterator)|Generator|array`.

84. `mixed` type allows any value (replaces old "no type hint" practice). Different from no type declaration: `mixed` enforces that type checking is intentional. Example: `function store(mixed $value)` vs `function store($value)` - former is clearer intent.

85. `never` return type (PHP 8.1) indicates function never returns (throws exception or loops forever): `function fail(): never { throw new Exception(); }`. Helps static analyzers detect unreachable code and enforce exhaustive conditionals in match expressions.

86. `false` type means function returns false only (not other falsy values): `function search(array $items, $needle): int|false { ... }`. Different from `?bool` which allows false or true. `true` type (PHP 8.2) is rarely used; usually paired with other types.

87. `public readonly string $email;` (PHP 8.1) - property can only be assigned once (in constructor), then becomes immutable. Useful for value objects: `readonly class User { public function __construct(public string $email) {} }`

88. `readonly class Point { public function __construct(public int $x, public int $y) {} }` (PHP 8.2) - all properties automatically readonly, enforce immutability at class level, useful for DTOs and value objects.

89. DTO with readonly and constructor promotion: `readonly class CreateUserRequest { public function __construct(public string $email, public string $password, public int $age) {} }`. Combines modern PHP 8 features for concise, immutable data transfer.

90. `true` type (PHP 8.2) is rarely useful alone; usually paired in unions: `function check(): true|false { ... }`. Distinguishes from `boolean` to enforce strict return. Example: `function validate(): true|Exception { return true; }`.

91. Nullable union with null in type: `string|int|null` vs `?(string|int)` - both work, former is preferred (explicit null in union). `?string` (shorthand nullable) still supported but less common with union types.

92. Generic-style function using union types: `function first(array $items): mixed { return reset($items); }` or with callback: `function map(array $items, callable $fn): array { return array_map($fn, $items); }`. PHP lacks true generics; union types provide type safety hints.

93. Covariance (return types can be more specific in subtypes) and contravariance (parameter types can be broader in subtypes). Example: covariant return: subclass method returns `Child` instead of `Parent`. Contravariant parameter: accepts `Parent` instead of `Child`.

94. Typed collection class simulating generics: `class Collection { private array $items = []; public function __construct(private string $itemType) {} public function add($item): void { if (get_class($item) !== $this->itemType) throw new TypeError(); $this->items[] = $item; } }`

95. `strict_types=1` enforces type coercion rules - values not matching parameter types trigger `TypeError`. Performance: minimal impact (type coercion happens regardless). Benefit: catches bugs early. Best practice: enable in all PHP files.

96. `function sum(...$numbers): int { return array_sum($numbers); } sum(1, 2, 3);` - variadic with type hints. Example: `function log(string $level, string ...$messages): void { foreach ($messages as $msg) echo "[$level] $msg\n"; }`

97. Type hint closures: `$callback = function(int $x): string { return (string)$x; };` or as parameter: `function execute(callable $fn): void { ... }`. For strict closure types, use `Closure` class: `function execute(Closure $fn): void { ... }`.

98. Value object with full type safety: `class Money { public function __construct(private int $cents, private string $currency) { if ($cents < 0) throw new ValueError(); } public function add(Money $other): Money { return new Money($this->cents + $other->cents, $this->currency); } }`

99. Static return type: `static` as return type means method returns instance of calling class (useful for immutable builders): `class QueryBuilder { public function where(string $col, $val): static { ... return new static(...); } }`

100. Type-safe builder pattern: `class UserBuilder { private string $email; public function email(string $e): static { $this->email = $e; return $this; } public function build(): User { return new User($this->email, ...); } }`

## Section 2: Laravel Request Lifecycle & Architecture (Answers 101-200)

*[Due to length constraints, I'll create a comprehensive answers file with all 1000 answers. Let me continue with a focused set for each section...]*

101. Laravel request lifecycle: (1) `index.php` receives request, (2) Bootstrap service providers, (3) HTTP Kernel handles request through middleware pipeline, (4) Route matching, (5) Controller resolution with DI, (6) Controller method execution, (7) Response rendering, (8) Send response, (9) Terminate phase (cleanup).

102. Bootstrap process: (1) Register config, (2) Register environment variables, (3) Register exception handler, (4) Register facades, (5) Register service providers (register method), (6) Boot service providers (boot method), (7) Load routes. All happens before HTTP kernel processes the request.

103. HTTP Kernel receives request, passes through middleware pipeline (global middleware first), matches routes, applies route middleware, resolves and executes controller, returns response to middleware chain in reverse order.

104. Service providers bootstrap the application - register bindings in service container, register routes, register event listeners, schedule commands. Called in order defined in `config/app.php`. Both `register()` and `boot()` methods called during request lifecycle.

105. Middleware execution order: global middleware (in `Middleware` array in Kernel), route middleware groups (web, api in $middlewareGroups), specific route middleware (applied via route definition). Middleware layers execute in order before controller, then in reverse after controller.

106. Route caching (`php artisan route:cache`) compiles routes into PHP array stored in `bootstrap/cache/routes-v7.php`. Dramatically speeds up routing for large applications (100+ routes). Must clear cache after adding routes. Doesn't affect middleware or route parameters.

107. Global middleware applies to every request. Route middleware applies only to specific routes or route groups. Use global middleware for concerns affecting all requests (logging, CORS). Use route middleware for specific concerns (authentication, authorization).

108. Terminate phase occurs after response sent to client. Middleware `terminate()` methods called, service provider `terminate()` methods called. Useful for cleanup, session saving, queued jobs that can run after response. Don't throw exceptions in terminate phase.

109. Laravel serves static files via web server (nginx, Apache) with static file handlers before reaching PHP application. For development, Laravel's `artisan serve` handles static files. In production, static files should be served by web server, not Laravel.

110. Router matches incoming request to registered routes. Uses trie-based matching for performance. Extracts route parameters, applies parameter binding (e.g., route model binding). Throws `RouteNotFoundException` if no match found. Cache routes for production.

111. Pipeline pattern in middleware: request passes through middleware stack as pipe, each middleware wraps the next, creating nested callback structure. Implemented via `Illuminate\Routing\Pipeline` class. Each middleware receives `$next` callback to continue to next middleware.

112. Controller dependency injection happens in service container. Reflection scans constructor, identifies type hints, resolves dependencies from container. Supports classes, interfaces, callables. Fails with clear error if dependency can't be resolved.

113. Route model binding occurs after route matching when route contains `{user}` placeholder with `User` model type. Container resolves the route parameter to model instance via `User::findOrFail($value)`. Can be customized in route model binding macros.

114. `Illuminate\Foundation\Http\Kernel` handles HTTP request lifecycle - loads middleware from arrays, passes request through pipeline, calls matched action, returns response. Manages both synchronous and terminable middleware.

115. CORS preflight requests (OPTIONS method) are handled by middleware or special routing. Browser sends OPTIONS before actual request to check if CORS allowed. Server must respond with appropriate `Access-Control-*` headers. Laravel has CORS middleware for handling.

116. Synchronous middleware executes before/after controller. Terminable middleware implements `Terminable` interface - has `terminate()` method called after response sent to client. Use for cleanup, session persistence, queueing jobs.

117. Exception thrown during lifecycle is caught by exception handler (`App\Exceptions\Handler`). Handler renders exception to response or reports to logging service. Can transform exceptions to HTTP responses with custom status codes.

118. Exception handler catches all exceptions, logs them (configurable per exception), renders them to responses (HTML or JSON based on content negotiation). Can register exception handlers for specific exception types to provide custom handling.

119. Response preparation phase sanitizes response headers, sets status code, ensures valid content type, prepares for transmission. Middleware can modify response before sending. Happens after controller execution.

120. Bootstrappers array in Kernel defines which service providers and components to bootstrap: `RegisterProviders`, `BootProviders`, `LoadConfiguration`, `HandleExceptions`, etc. Order matters - loaded in sequence during bootstrap.

121. File uploads handled in request lifecycle via multipart form data parsing. Laravel's `UploadedFile` class provides safe access to uploaded file. Stored temporarily in `storage/app` or `public/uploads`. Must validate file type, size, security before storing.

122. Session handling integrated in request lifecycle through middleware - `StartSession` middleware (in web middleware group) loads session data from storage, makes available via `Session` facade. Session data persisted in storage (database, cache, files) after request terminates.

123. Request facade provides fluent access to current request: `Request::input('name')`, `Request::file('avatar')`, `Request::header('Authorization')`. Bound to service container, can be resolved as dependency. Provides conveniences for accessing request data.

124. JSON request parsing happens in middleware or controller via `Request::json()` or `Request::input()`. Laravel automatically detects `Content-Type: application/json`, parses JSON body as array accessible via `Request::all()`.

125. Multipart form data handling via middleware - parses form fields and file uploads, makes available via `Request::all()` for fields and `Request::file()` for files. Laravel handles boundary parsing, MIME type detection automatically.

126. Route parameter resolution via middleware and router - extracts parameter from route pattern, attempts to match it to route model binding (if declared), or passes raw parameter to controller. Can be customized in route model binding.

127. Laravel doesn't enforce trailing slash - `/users` and `/users/` are different routes. Configure in `UrlGenerator::formatScheme()` or middleware to normalize URLs. Best practice: choose one style and be consistent.

128. `Request::capture()` creates new request instance from globals (`$_GET`, `$_POST`, `$_SERVER`, etc.). Manual instantiation: `$request = Request::createFromGlobals();`. Framework handles this automatically; rarely needed in application code.

129. `Illuminate\Routing\Pipeline` implements middleware pipeline pattern - wraps each middleware to create nested callback structure. Passes request through each middleware in sequence, collects response, passes back through middleware stack in reverse.

130. Request method spoofing via `_method` hidden field - allows forms to send PUT/PATCH/DELETE requests (browsers limited to GET/POST). Middleware intercepts `_method` field, changes request method accordingly. Enable with `Route::post(...)->name('...');` then use `method_field()` helper.

131-160: [Middleware Pipeline answers - covering custom middleware, middleware for authentication, CORS, rate limiting, logging, etc.]

131. Custom middleware logging request/response timing: `class LogTiming implements Middleware { public function handle($request, Closure $next) { $start = microtime(true); $response = $next($request); $duration = microtime(true) - $start; Log::info("Request took {$duration}s", ['path' => $request->path()]); return $response; } }`

132. Middleware executing after response: implement `TerminableMiddleware` interface with `terminate($request, $response)` method. Called after response sent to client - useful for cleanup, session saving, queued operations. Example: `public function terminate($request, $response) { Cache::put('last_request', $request->path()); }`

133. Middleware priority: defined in Kernel's `$middleware` array (global, executed in order) and `$middlewareGroups` (route-specific). Within groups, order matters. To control execution order within same group, define custom groups or adjust array order.

134. API key authentication middleware: `class ApiKeyAuth implements Middleware { public function handle($request, Closure $next) { $key = $request->header('X-API-Key'); if (!$this->validateKey($key)) abort(401); return $next($request); } }`

135. Pass data between middleware layers via request attributes: `$request->attributes->put('user_id', $userId);` in one middleware, retrieve in another with `$request->attributes->get('user_id')`. Shared across entire request lifecycle.

136. Rate limiting middleware: `class RateLimit implements Middleware { public function handle($request, Closure $next) { if (RateLimiter::tooManyAttempts($key = $request->ip(), $limit = 60)) abort(429); RateLimiter::hit($key); return $next($request); } }`

137. `handle()` executes before controller, receives request. `terminate()` executes after response sent, receives request and response. Use `handle()` for authentication, validation. Use `terminate()` for cleanup, logging, session persistence.

138. Webhook signature verification middleware: `class VerifyWebhookSignature implements Middleware { public function handle($request, Closure $next) { $signature = $request->header('X-Signature'); $expected = hash_hmac('sha256', $request->getContent(), $secret); if (!hash_equals($signature, $expected)) abort(403); return $next($request); } }`

139. Conditionally apply middleware: in routes file use `->middleware('name')` or `->middleware('name:param')`. In controller use `$this->middleware('name', ['only' => ['show']])` or `['except' => ['index']]`. In middleware use `if ($request->path() === '/skip') return $next($request);`

140. HTTPS enforcement middleware: `class ForceHttps implements Middleware { public function handle($request, Closure $next) { if (!$request->isSecure() && app()->environment('production')) return redirect()->secure($request->getRequestUri()); return $next($request); } }`

141. Middleware groups organize related middleware - `web` group includes session, CSRF, cookies. `api` group includes throttle, API key auth. Define in Kernel's `$middlewareGroups`. Apply entire group to route: `Route::get(...)->middleware('web');`

142. IP whitelisting middleware: `class IpWhitelist implements Middleware { public function handle($request, Closure $next) { $allowed = config('security.whitelist_ips'); if (!in_array($request->ip(), $allowed)) abort(403); return $next($request); } }`

143. Test middleware in isolation: `$this->middleware('name')->on('POST', '/api/users')->see('response content');`. Use `middleware()` method in test to apply, mock dependencies, assert middleware behavior without routing.

144. Data transformation middleware: `class TransformRequest implements Middleware { public function handle($request, Closure $next) { if ($request->input('name')) { $request->merge(['slug' => Str::slug($request->input('name'))]); } return $next($request); } }`

145. Middleware parameters: `Route::post('/users')->middleware('role:admin,editor');`. In middleware: `public function handle($request, Closure $next, ...$roles) { if (!in_array($request->user()?->role, $roles)) abort(403); return $next($request); }`

146. Role-based access control middleware: `class AuthorizeRole implements Middleware { public function handle($request, Closure $next, ...$roles) { if (!$request->user() || !$request->user()->hasAnyRole($roles)) abort(403); return $next($request); } }`

147. Middleware exception handling: use try/catch in `handle()` method, catch exceptions and respond appropriately or re-throw. In `terminate()`, don't throw exceptions as response already sent. Log exceptions: `try { ... } catch (Exception $e) { Log::error($e); abort(500); }`

148. CORS middleware: `class Cors implements Middleware { public function handle($request, Closure $next) { return $next($request)->header('Access-Control-Allow-Origin', '*')->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE'); } }`

149. Inspect middleware stack: middleware registered in Kernel's `$middleware` and `$middlewareGroups`. Access via `app('router')->getRoutes()` to see route-specific middleware. Use `artisan route:list` to debug routing and middleware.

150. Request ID generation middleware: `class RequestId implements Middleware { public function handle($request, Closure $next) { $id = Str::uuid(); $request->attributes->put('request_id', $id); return $next($request)->header('X-Request-Id', $id); } }`

151. Response modification middleware: `class ModifyResponse implements Middleware { public function handle($request, Closure $next) { $response = $next($request); return $response->header('X-Custom', 'value')->setContent($this->transform($response->getContent())); } }`

152. API versioning middleware: `class ApiVersion implements Middleware { public function handle($request, Closure $next) { $version = $request->header('Accept-Version', 'v1'); $request->attributes->put('api_version', $version); return $next($request); } }`

153. Global middleware executes on every request (security impact, performance overhead). Route middleware only on specific routes (more targeted, less overhead). Global for CORS, logging. Route-specific for authentication, authorization.

154. CSP (Content Security Policy) headers middleware: `class SetCSP implements Middleware { public function handle($request, Closure $next) { $csp = "default-src 'self'; script-src 'self' 'unsafe-inline'"; return $next($request)->header('Content-Security-Policy', $csp); } }`

155. Middleware working with both web and API routes: check content negotiation or route prefix. Example: `class Auth implements Middleware { public function handle($request, Closure $next) { if ($request->expectsJson()) return $this->handleJson($request, $next); return $this->handleWeb($request, $next); } }`

156. Webhook signature verification middleware (already covered in 138): verify webhook came from expected source using HMAC signature, prevent replay attacks, validate timestamp freshness.

157. Middleware dependency injection: constructor receives dependencies from service container. Example: `class CheckPermission implements Middleware { public function __construct(private PermissionService $permissions) {} public function handle(...) { ... } }`

158. Security headers middleware: implement multiple security headers in single middleware - X-Frame-Options (clickjacking), X-Content-Type-Options (MIME sniffing), Strict-Transport-Security (HTTPS), X-XSS-Protection (legacy XSS filter).

159. Debug middleware execution order: add logging in each middleware's `handle()` method showing middleware name and order. Use `artisan route:list` to see route-specific middleware. Trace request through middleware stack in logs.

160. Request sanitization middleware: `class Sanitize implements Middleware { public function handle($request, Closure $next) { $clean = array_map(fn($v) => is_string($v) ? strip_tags($v) : $v, $request->all()); $request->replace($clean); return $next($request); } }`

## Section 4: Service Container & Dependency Injection (Questions 161-250)

161. Laravel service container is IoC (Inversion of Control) container managing dependencies. Register bindings in service providers via `$this->app->bind()`, `$this->app->singleton()`, `$this->app->factory()`. Automatic resolution via type hints when service needed.

162. Singleton binding: `$this->app->singleton(Logger::class, fn() => new Logger());` - creates once, reuses across application. Useful for stateful services (logger, cache, database). Memory-efficient, thread-safe if no mutable state.

163. Factory binding: `$this->app->bind(Request::class, fn() => Request::capture());` - creates new instance every time. Useful for stateless services, request-specific objects, fresh state needed.

164. Service provider structure: `register()` method binds services (no access to booted services), `boot()` method runs after all providers registered (can use other services). Override both in custom providers to control timing.

165. Dependency injection in constructors: type-hint dependencies, container auto-resolves. Example: `class UserController { public function __construct(private UserRepository $repo) {} }` - container finds UserRepository binding, injects instance.

166. Method injection: inject dependencies into individual methods. Example: `public function show(Request $request, UserRepository $repo)` - container resolves both before method execution. Less common than constructor injection.

167. Interface binding: `$this->app->bind(UserRepositoryInterface::class, UserEloquentRepository::class);` - bind interface to concrete implementation. Type-hint interface in constructor, container injects implementation.

168. Contextual binding: `$this->app->when(PaymentService::class)->needs(PaymentGateway::class)->give(StripeGateway::class);` - different classes get different implementations of same interface. Useful for multiple implementations.

169. Binding with parameters: `$this->app->bind('config', fn() => Config::load($this->basePath));` - closure receives container instance, can load config then return service. Use for complex initialization.

170. Abstract binding: `$this->app->bind('mailer', 'Illuminate\Mail\Mailer');` - bind abstract name to concrete class. Less common than interface/class binding; mostly for legacy code.

171. Resolving from container: `$service = $this->app->make(UserService::class);` or `app(UserService::class)`. In middleware/controllers, just type-hint and Laravel auto-resolves. Manual resolution rarely needed.

172. Container auto-wiring: container automatically resolves concrete classes without explicit binding. If class doesn't exist, throws `BindingResolutionException`. Override with explicit binding when auto-wiring insufficient.

173. Booted check in service provider: use `$this->app->booted()` to check if bootstrap complete, or `$this->app->booting()` to register callback before boot. Useful for conditional logic.

174. Defer service providers: implement `DeferrableProvider` interface, define `provides()` method returning services. Container lazy-loads provider only when service requested. Improves bootstrap time.

175. Service provider aliases: define `$aliases` in config/app.php for facades. `Auth::user()` resolves to `app('auth.provider')` behind scenes. Improves readability, enables swapping implementations.

176. Container facade: use `App::make()`, `App::bind()`, etc. Static calls to container. Convenient but less testable. Prefer dependency injection or `app()` helper in production code.

177. Container events: `$this->app->resolving()` fires when service being resolved. Use for wrapping services, adding behavior. Example: `$this->app->resolving(Logger::class, fn($logger) => $logger->setLevel('debug'));`

178. Multi-level dependency resolution: container resolves chains of dependencies. Example: `UserController->UserService->UserRepository->Connection` - all auto-resolved. If any missing, throws exception.

179. Callable resolution: container resolves callables with dependencies. Example: `$callback = fn(Logger $log, UserService $users) => { ... }; app()->call($callback);` - both parameters auto-injected.

180. Custom resolver for binding: `$this->app->resolving(CacheManager::class, function($instance, $app) { $instance->setDefaultDriver('redis'); return $instance; });` - manipulate service after creation before injection.

181. Binding with instance: `$this->app->instance('key', $value);` - register pre-created instance. Useful for environment-specific objects, mock objects in tests. Instance reused across application.

182. Tag bindings: `$this->app->tag([Service1::class, Service2::class], 'handlers');` then resolve all: `$handlers = $this->app->tagged('handlers');` - useful for plugins, event handlers, middleware.

183. Service container snapshots: call `$snapshot = $this->app->getSnapshot();` to save state, later `$this->app->restoreSnapshot($snapshot)` to reset. Useful for testing, isolated test environments.

184. Container binding resolution events: `afterResolving()` fires after service resolved, `beforeResolving()` fires before. Use for side effects, logging, validation after service created.

185. Service provider boot order: defined in `config/app.php` providers array. Boot in order, each provider's `boot()` depends on all previous providers registered. Change order if dependency issues.

186. Accessing container in service providers: use `$this->app` property or `app()` helper. Consistency: prefer `$this->app` in providers, `app()` in other code.

187. Container auto-discovery: Laravel discovers bindings from config, routes, middleware. Custom discovery: override `discover()` in service provider to register based on directory scanning.

188. Service container performance: resolve time <1ms for most services. Singleton bindings cached, factory bindings created fresh each time. Defer heavy providers for bootstrap speed. Profile with `dd(get_class_vars(app()));`

189. Container debugging: `dd($this->app->getBindings());` shows all registered services. Use debugbar, Laravel Telescope to trace dependency resolution in requests.

190. Typed properties with container: PHP 7.4+ supports typed properties: `private Logger $logger;` but for DI, type-hint constructor parameter instead: `public function __construct(Logger $logger)`. Constructor injection clearer than property promotion.

191. Fluent interface for binding: `$this->app->bind(Service::class)->withSharedInstance();` - chain methods for configuration. Limited support; most fluent chains use `chain()` method or multiple calls.

192. Container with closures: `$this->app->bind('key', fn($app) => new Service($app->make(Config::class)));` - closure receives container, can access other services. Lazy evaluation only when service needed.

193. Error handling in container: use try/catch when manually resolving: `try { $service = app(UnknownClass::class); } catch (BindingResolutionException $e) { handle_error($e); }` - resolve may fail if binding missing.

194. Container reset between requests: container not reset between requests in same process (e.g., queue workers). Use fresh container for each job: `app()->forgetInstance(Service::class);` to remove singleton.

195. Service provider registration vs boot: `register()` runs once at startup, safe to register all bindings. `boot()` runs after all providers registered, safe to use other services. Don't access other services in `register()`.

196. Container method resolution: use `app()->call($callable, $parameters)` - container injects type-hinted parameters. Works with classes, closures, arrays (class method). Powerful for callbacks, event handlers.

197. Container with array access: `$service = app()[ServiceClass::class];` or `app()->make(ServiceClass::class);` - container implements ArrayAccess interface. Syntactic sugar, same as `make()` method.

198. Service provider testing: create provider in test, register services, assert bindings exist. Example: `(new MyProvider($this->app))->register(); $this->assertInstanceOf(Service::class, app(Service::class));`

199. Swappable implementations: use interfaces for all public APIs. In production, bind real implementation; in tests, bind mock implementation. Container enables easy swapping for testing.

200. Container statistics: none built-in, but can count services: `count($this->app->getBindings());` - useful for debugging large applications. Monitor resolution performance with custom decorator.

## Section 5: HTTP Client & External APIs (Questions 201-280)

201. Laravel HTTP client: `Http::get('https://api.example.com/users')` - clean fluent interface for HTTP requests. Wrapper around Guzzle HTTP client. Automatic JSON handling, timeout management, error handling.

202. POST request with data: `Http::post('https://api.example.com/users', ['name' => 'John', 'email' => 'john@example.com'])` - sends as form data by default, add `->asJson()` to send JSON, `->asForm()` for form-encoded.

203. JSON response parsing: `$response = Http::get(...); $data = $response->json();` - automatically decodes JSON to associative array. Use `$response->json('key')` to access nested values, `$response->collect()` for collection.

204. Response status checking: `$response->successful()` (200-299), `$response->clientError()` (400-499), `$response->serverError()` (500-599), `$response->failed()` (!successful). Simplify error handling vs checking status code.

205. Custom headers: `Http::withHeaders(['Authorization' => 'Bearer token'])->get(...)` - add any headers needed. Common: Authorization, User-Agent, Accept, Content-Type. Headers persisted across requests in same instance.

206. Authentication: `Http::withBasicAuth('user', 'pass')->get(...)` for HTTP Basic Auth. `Http::withToken('token')->get(...)` for Bearer tokens. Credentials not logged by default for security.

207. Timeout handling: `Http::timeout(5)->get(...)` - request fails if takes >5 seconds. Default 30 seconds. Use for external APIs, prevent hanging requests. Set per request, not global.

208. Retry logic: `Http::retry(3, 100)->get(...)` - retries 3 times if fails, waits 100ms between retries. Use for flaky APIs. Won't retry on 4xx errors (client error), only 5xx and connection errors.

209. Request with query parameters: `Http::get('https://api.example.com/users', ['page' => 1, 'per_page' => 10])` - automatically URL-encodes and appends query string. Or chain: `Http::get(...)->query(['page' => 1])`.

210. File uploads: `Http::post('https://api.example.com/upload', ['file' => Http::attachPath('/path/to/file.txt')])` - sends as multipart/form-data. Use `Http::attachContent()` for string content, `Http::attachPath()` for file.

211. Request/response logging: enable with `SCOPED_DUMP=1 php artisan serve` or use middleware to log requests/responses. Log before sending, after receiving. Include method, path, status, timing.

212. Handling redirects: automatically followed (max 5 by default). Change with `Http::withOptions(['allow_redirects' => ['max' => 10]])`. Disable with `['allow_redirects' => false]`.

213. SSL verification: `Http::withoutVerifying()->get(...)` - skips certificate verification (DANGEROUS - only in development). Default verifies SSL certificates, prevents MITM attacks.

214. Response streaming: `Http::get('https://example.com/large-file.bin')->stream(function($chunk) { echo $chunk; });` - streams large responses without loading into memory. Ideal for file downloads.

215. Pooled requests: `Http::pool(fn($pool) => { $pool->get('url1'); $pool->post('url2', [...]);... })` - sends multiple requests concurrently. Uses Guzzle pooling internally, faster than sequential requests.

216. Async requests: HTTP client doesn't support true async, but pooling provides concurrency. For true async, use ReactPHP or Amphp libraries. Most Laravel apps use queue jobs for background API calls.

217. Response body: `$response->body()` returns raw response body (string). `$response->json()` decodes JSON, `$response->xml()` parses XML (if SoapClient available), `$response->collect()` returns collection.

218. Custom guzzle options: `Http::withOptions(['verify' => false, 'http_errors' => false])->get(...)` - pass any Guzzle option. Most common: `verify` (SSL), `http_errors` (throw on 4xx/5xx), `allow_redirects`.

219. Request macros: `Http::macro('customRequest', function($url) { return $this->withHeaders([...])->get($url); }); Http::customRequest('...');` - extend HTTP client with custom methods. Useful for API client wrappers.

220. Error response handling: `if ($response->failed()) { $error = $response->json('error'); log($error); abort($response->status(), 'API Error'); }` - properly handle API errors, don't assume success.

221. Rate limiting for external APIs: track requests per minute, pause if limit exceeded. Use Redis for distributed rate limiting. Example: `if (Cache::has('api-rate-limit')) { sleep(1); }` - crude rate limiting.

222. API key management: never hardcode API keys, use environment variables. `env('THIRD_PARTY_API_KEY')` loads from `.env` file. Better: use Laravel's config system: `config('services.stripe.key')`.

223. Webhook handling: create route to receive webhooks: `Route::post('/webhooks/stripe', WebhookController::class);`. Verify signature (hash_hmac), log webhook, queue job to process. Never synchronously process webhooks.

224. GraphQL queries via HTTP: `Http::post('https://api.example.com/graphql', ['query' => $gqlQuery, 'variables' => $vars])` - same as REST, but pass GraphQL query/variables in body. Parse response JSON as normal.

225. Request logging/debugging: use Laravel's HTTP client logging: `Http::withOptions(['debug' => true])` - shows verbose request/response details. Or middleware to intercept: `Log::info(sprintf('%s %s', $request->method, $request->url));`

226. Connection pooling for HTTP: not built-in, but Guzzle uses persistent connections by default. Use `Http::timeout(10)->pool()` for multiple concurrent requests to same host - more efficient than sequential.

227. Circuit breaker pattern for APIs: track failures, stop requests if exceeding threshold. Example: `if ($failureCount > 5) { return cached_fallback(); }` - prevent cascading failures when external API down.

228. API versioning in client: `Http::baseUrl('https://api.example.com/v1/')->get('users')` - set base URL in macro for cleaner code. Or pass version in header: `Http::withHeaders(['Accept' => 'application/vnd.api+json;version=1'])->get(...)`

229. Payload compression: add header `Content-Encoding: gzip` if API supports, Guzzle will compress POST body. Reduces bandwidth for large payloads, minimal CPU cost.

230. User-Agent header: set custom user-agent for tracking. `Http::withHeaders(['User-Agent' => 'MyApp/1.0'])->get(...)`. Default is Guzzle version. APIs may reject unknown user-agents.

231. Response caching: `$response = Cache::remember('api-users', 60, fn() => Http::get(...)->json());` - cache API responses. Use TTL for stale data tolerance, invalidate cache on updates.

232. Handling paginated API responses: fetch first page, check for `next_url` or `page` in response, recursively fetch next pages. Store in database for pagination. Example: loop until `next_url` null.

233. Mock HTTP responses in tests: use `Http::fake()` to mock responses before making requests. Example: `Http::fake(['*' => Http::response(['id' => 1])])`. Test without calling actual APIs.

234. Testing with fake pools: `Http::fake(fn($request) => Http::response(...))` - closure receives request, returns response. Useful for conditional mocking based on URL/method.

235. Abort on 4xx/5xx: `Http::throw()->get(...)` - throws HttpClientException on any error response. Cleaner than checking `->failed()`. Catch exception in try/catch to handle errors.

236. Collecting multiple responses: `$responses = Http::pool(fn($pool) { $pool->get('url1'); $pool->post('url2'); })->responses;` - returns array of responses. Useful for orchestrating multiple API calls.

237. Request sequencing: first request result used for second request: `$user = Http::get(...)->json(); $posts = Http::get("/posts?user_id={$user['id']}")->json();` - common in API workflows, can use await/async patterns.

238. Handling large response bodies: stream to file instead of loading into memory: `$response->toPsrResponse()->getBody()->pipeTo(new SplFileObject('large-response.json', 'w'));`

239. Request signing for security: sign requests with HMAC to prove authenticity. Example: `$signature = hash_hmac('sha256', $body, $secret); Http::withHeaders(['X-Signature' => $signature])->post(...)`

240. API pagination helpers: Laravel doesn't have built-in pagination helpers for APIs, but `Http::lazy()` (if available) or manual loop. Use cursor-based pagination for efficiency.

241. Multipart requests with arrays: `Http::post('url', collect($items)->mapWithKeys(fn($item, $i) => ["items[$i]" => $item]))` - sends as array query parameters in multipart form.

242. Accept header negotiation: `Http::withHeaders(['Accept' => 'application/json'])->get(...)` - tells server desired response format. Server respects or returns error. JSON assumed by default in Laravel.

243. DNS resolution caching: not controlled by HTTP client; OS/system caches DNS. Use `/etc/hosts` override for development. Guzzle respects system DNS settings.

244. Connection keep-alive: Guzzle uses keep-alive by default for persistent connections. Reuse HTTP client instance across requests in same process for better performance.

245. Etag/conditional requests: `Http::withHeaders(['If-None-Match' => $etag])->get(...)` - returns 304 Not Modified if resource unchanged. Cache ETag, use for efficient API calls.

246. Cookie handling: Guzzle auto-handles cookies in persistent client instance. For custom cookie jar: `Http::withOptions(['cookies' => new CookieJar()])->get(...)`

247. Request body encoding: `Http::asJson()->post(..., ['data'])` sends as JSON, `->asForm()` sends as form-encoded, `->asMultipart()` sends as multipart/form-data. Choose based on API requirements.

248. Proxy support: `Http::withOptions(['proxy' => 'tcp://127.0.0.1:8080'])->get(...)` - route through proxy. Useful for corporate networks, debugging. Guzzle supports SOCKS5, HTTP proxies.

249. Request data serialization: use `http_build_query()` for query params, `json_encode()` for JSON body. Laravel handles automatically in most cases, but useful for custom encoding.

250. API client class: create dedicated class for API interactions: `class StripeClient { public function charge($amount) { return Http::withToken(env('STRIPE_KEY'))->post('...'); } }` - encapsulates API calls, reusable across application.

## Section 6: Authentication & Authorization (Questions 251-350)

251. Laravel authentication: built-in guards (web, api), providers (eloquent, database), password reset, session management. Configured in `config/auth.php`. Guards handle request authentication, providers load users.

252. Guard web: session-based authentication for web apps. After login, session token stored in cookie, automatically validated on each request. Logout destroys session. Works with traditional form-based logins.

253. Guard api: token/bearer authentication for APIs. Client sends token in Authorization header: `Authorization: Bearer <token>`. Guard validates token, no session needed. Stateless, suitable for mobile/SPA.

254. Eloquent provider: loads users from database via Eloquent model. Define in `config/auth.php`: `'providers' => ['users' => ['driver' => 'eloquent', 'model' => App\Models\User::class]]`. Uses Eloquent's query builder for lookups.

255. Database provider: loads users from database using query builder. Older approach before Eloquent ubiquity. Requires manual queries. Use Eloquent provider in modern Laravel.

256. Password hashing: use `Hash::make($password)` for hashing, `Hash::check($password, $hash)` for validation. Uses bcrypt by default. Cost factor configurable: `Hash::make($password, ['rounds' => 12])` for stronger hashing.

257. Login middleware: `auth:web` checks if user authenticated via web guard. Redirects to login if not. Use in routes: `Route::get('/dashboard')->middleware('auth:web');` Throws 401 for APIs.

258. Route model binding with auth: `Route::get('/posts/{post}')->middleware('auth')` - auto-loads post, verifies auth. Combine with `Can authorize policy to check ownership.

259. Authorization policies: define `App\Policies\PostPolicy` class with methods returning true/false. Methods like `update()`, `delete()` called via `$this->authorize('update', $post);` in controller. Gate/policy pattern.

260. Gates: define authorization logic: `Gate::define('edit-post', fn($user, $post) => $user->id === $post->user_id);`. Call with `Gate::allows('edit-post', $post)` or `auth()->user()->can('edit-post', $post)`.

261. Admin check pattern: `if ($user->is_admin) { ... }` - simple for single role. For complex permissions, use policies or gates. Can gate or policy return `Response::deny()` for custom error messages.

262. Token-based authentication: generate tokens for users: `$token = $user->createToken('app-token')->plainTextToken;` (API tokens). Validate with `auth()->user()` in `auth:sanctum` guard. Store tokens in database.

263. Laravel Sanctum: built-in package for token-based auth. Issues tokens per device, tracks last used. `->createToken()` returns token object with plainTextToken. Client sends token in Authorization header.

264. Revoking tokens: `auth()->user()->tokens()->delete();` - delete all tokens for user. Or specific: `auth()->user()->tokens()->where('name', 'api')->delete();` - logout from specific device.

265. Token abilities (scopes): limit token permissions: `->createToken('app', ['read-posts', 'create-posts'])`. Check ability: `$request->user()->tokenCan('read-posts')`. Useful for granular permissions per token.

266. CSRF protection: middleware automatically validates CSRF token in POST/PUT/DELETE requests. Token generated in forms via `@csrf` blade directive, validated by `VerifyCsrfToken` middleware. Disables for API routes.

267. Two-factor authentication: implement via packages (Laravel Fortify) or custom. Store 2FA secret in database, generate TOTP codes with `TOTP` class. Validate code before allowing login.

268. Session hijacking prevention: randomize session ID on login, invalidate old session. Laravel does automatically. Check IP address / user-agent for suspicious logins.

269. Password reset: `Hash::make()` new password, update database, invalidate tokens. Laravel includes `PasswordController` doing this. Reset link valid for configurable time, tokens stored in `password_resets` table.

270. Email verification: send verification email after signup, validate email before allowing access. Laravel includes `VerifyEmail` middleware. Mark email verified in database, check with `$user->hasVerifiedEmail()`.

271. Remember me functionality: set longer cookie expiry if "remember me" checked: `Auth::login($user, $remember = true);` - sets `remember_token` in cookie, valid longer than session. Validate on each request.

272. Authenticating with custom columns: override `retrieveByCredentials()` in provider to search by username instead of email. Example: `where('username', $credentials['username'])` in method.

273. Custom password reset tokens: override `ResetPassword` notification to generate custom token. Tokens stored in hash in database, validated before allowing reset. Default uses Illuminate\Auth\Passwords\TokenRepositoryInterface.

274. API token middleware: custom middleware validates API key: `if ($request->header('X-API-Key') !== env('API_KEY')) abort(401);` - simpler than Sanctum for public APIs.

275. Throttling authentication: prevent brute force by rate limiting login attempts. Use `ThrottleRequests` middleware: `Route::post('/login')->middleware('throttle:5,1')` - max 5 attempts per minute.

276. Multi-authentication: user can be authenticated via multiple guards simultaneously. Example: web + api. Use in controller: `auth('web')->user()` vs `auth('api')->user()`.

277. Authentication hooks: use events for authentication side effects. Example: `auth()->user()->dispatchEvent(new UserLoggedIn())` after login. Listen with event listeners for logging, cache invalidation.

278. Guest check: `auth()->guest()` returns true if not authenticated. Use in middleware to block authenticated users from login page: `Route::get('/login')->middleware('guest');`

279. Auth facade methods: `Auth::user()` current user, `Auth::id()` user ID, `Auth::login($user)` login, `Auth::logout()` logout, `Auth::attempt(['email' => $email, 'password' => $pass])` validate and login.

280. Custom authentication drivers: implement custom guard if needed. Create class implementing `GuardContract`, register in `config/auth.php`. Rarely needed; built-in guards cover most cases.

## Section 7: Database & Eloquent ORM (Questions 281-400)

281. Eloquent ORM: Laravel's ActiveRecord implementation. Models represent database tables, relationships, queries. Benefits: type safety, relationship loading, mutators/accessors, scopes, migrations.

282. Model creation: `class User extends Model { protected $table = 'users'; protected $fillable = ['name', 'email']; }` - `$table` specifies table name (auto-pluralized if omitted), `$fillable` defines mass-assignable attributes.

283. Timestamps: by default, `created_at` and `updated_at` timestamps auto-managed. Disable with `public $timestamps = false;`. Customize column names: `protected $dateFormat = 'Y-m-d H:i:s';`

284. Mass assignment: `User::create(['name' => 'John', 'email' => 'john@example.com'])` - creates record with specified attributes. Must define attributes in `$fillable` or `$guarded` arrays for security.

285. Hidden attributes: `protected $hidden = ['password', 'remember_token'];` - exclude from JSON serialization. Useful for sensitive fields. Override per instance: `$user->makeVisible('password')->toJson()`.

286. Casts: automatically convert attribute types: `protected $casts = ['email_verified_at' => 'datetime', 'is_admin' => 'boolean'];` - casting happens on read/write. Custom cast classes for complex conversions.

287. Has-many relationships: `class User extends Model { public function posts() { return $this->hasMany(Post::class); } }` - user has many posts. Access with `$user->posts()` returns query builder, `$user->posts` returns lazy collection.

288. Belongs-to relationships: `class Post extends Model { public function user() { return $this->belongsTo(User::class); } }` - inverse of has-many. Access with `$post->user()` or `$post->user` - singular.

289. Many-to-many relationships: `class User extends Model { public function roles() { return $this->belongsToMany(Role::class); } }` - join table stores relationship. Access with `$user->roles()`. Pivot table contains user_id, role_id.

290. Eager loading: `$users = User::with('posts')->get();` - loads posts for all users in single query. Prevents N+1 problem. Use `->with()` or `->load()` to load related models.

291. Lazy eager loading: `$posts->load('user')` - loads relationship after query. Useful when relationship needed after retrieval. Same efficiency as `->with()` if called before using relationship.

292. Nested eager loading: `User::with('posts.comments')->get();` - loads posts and comments for each post. Chain with dot notation. Limits nesting depth for query efficiency.

293. Eager loading with closures: `User::with(['posts' => fn($query) => $query->where('published', true)])->get()` - filter related models while loading. Useful for loading only published posts.

294. Constraints in eager loading: `User::with(['posts' => fn($query) => $query->limit(3)])->get()` - limits related models per user. Careful: applies constraint to all users, not per-user.

295. HasMany load count: `User::withCount('posts')->get()` - adds `posts_count` attribute without loading posts. Efficient for counting relationships. Use for displaying "X posts" without full load.

296. Polymorphic relationships: `class Comment extends Model { public function commentable() { return $this->morphTo(); } }` - comment belongs to post or video. Uses `commentable_type` and `commentable_id` columns.

297. Inverse polymorphic: `class Post extends Model { public function comments() { return $this->morphMany(Comment::class, 'commentable'); } }` - multiple models have comments. Useful for shared relationships.

298. Raw database queries: `DB::select('SELECT * FROM users WHERE id = ?', [$id]);` - executes raw SQL. Use only for complex queries Eloquent doesn't support. Always parameterize to prevent SQL injection.

299. Query scopes: define reusable query filters: `class User extends Model { public function scopeActive($query) { return $query->where('active', true); } }`. Call with `User::active()->get();`.

300. Dynamic scopes: `User::scope('status', 'active')->get()` - sometimes useful. Prefer explicit scope methods for clarity. Scopes improve code readability vs chaining where clauses.

301. Global scopes: `protected static function booted() { static::addGlobalScope('active', fn($query) => $query->where('active', true)); }` - automatically applies to all queries. Useful for soft deletes, multi-tenancy.

302. Removing global scopes: `User::withoutGlobalScopes()->get()` - bypasses all global scopes. `->withoutGlobalScope('active')` - removes specific scope. Use carefully to avoid logic errors.

303. Soft deletes: `SoftDeletes` trait marks records as deleted without removing. Adds `deleted_at` column. Query excludes soft-deleted by default. Use `->withTrashed()` to include, `->onlyTrashed()` for only deleted.

304. Force delete: `$user->forceDelete()` - permanently removes soft-deleted record. Use for GDPR compliance, final cleanup. Careful: unrecoverable after force delete.

305. Restore soft-deleted: `$user->restore()` - sets `deleted_at` to null, marks as active. Only works on soft-deleted models. Useful for undoing deletions.

306. Mutators (accessors): customize attribute getters/setters. Modern syntax (Laravel 8+): `#[Attribute] public function firstName(): Attribute { return new Attribute(get: fn($value) => ucfirst($value)); }`

307. Old mutator syntax: `public function getFirstNameAttribute($value) { return ucfirst($value); }` - called automatically when accessing `$user->first_name`. Still supported, modern syntax preferred.

308. Mutator setters: `#[Attribute] public function password(): Attribute { return new Attribute(set: fn($value) => Hash::make($value)); }` - automatically hashes password on assignment.

309. JSON column casting: `protected $casts = ['metadata' => 'json'];` - stores JSON in column, auto-decodes on read, auto-encodes on write. Access as array: `$user->metadata['key']`.

310. Collections: relationships return `Collection` objects, useful for querying results. Methods: `->pluck()`, `->map()`, `->filter()`, `->unique()`. Different from query builder, operates on loaded data.

311. Pagination: `User::paginate(15);` - returns paginator with links. Access with `$users->links()` in blade. Configure default per_page in model or override in controller.

312. Cursor pagination: `User::cursorPaginate(15);` - efficient for large datasets. Returns cursor object instead of page numbers. Use for infinite scroll, real-time feeds.

313. Query builder vs Eloquent: builder more flexible for complex queries, Eloquent adds convenience features (relationships, casts, scopes). Use Eloquent when possible, builder for complex SQL.

314. Model events: `created`, `updated`, `deleted`, `saved`, `retrieved` fire at lifecycle points. Listen with `static::creating(function($model) { ... })` in `boot()` method.

315. Observer pattern: extract event listeners to separate observer class. Register in service provider: `User::observe(UserObserver::class);` - cleaner than inline listeners.

316. Timestamps customization: `protected $dateFormat = 'Y-m-d'` - change format. Use `protected $dates = ['custom_date']` to auto-cast to Carbon instance. Prefer $casts in modern Laravel.

317. Model factory: `User::factory()->count(100)->create();` - generates fake records for testing. Define factory in `database/factories/`. Seeders use factories for data generation.

318. Guarding attributes: `protected $guarded = ['id', 'created_at'];` - inverse of fillable, block mass assignment. Use fillable for whitelist approach (more secure), guarded for blacklist.

319. Appended attributes: `protected $appends = ['full_name'];` - always include in serialization: `public function getFullNameAttribute() { return "{$this->first_name} {$this->last_name}"; }`

320. Model relationships caching: `$posts = $user->posts;` caches relationship in memory for request duration. Avoid calling same relationship multiple times. Laravel caches automatically.

321. Detaching relationships: `$user->roles()->detach()` - removes all relationships. `->detach([1, 2, 3])` - removes specific IDs. Use for many-to-many untying.

322. Attaching relationships: `$user->roles()->attach([1, 2])` - adds relationships to existing. `->sync([1, 2, 3])` - replaces all with these. Use for many-to-many assignment.

323. Pivot attributes: `$user->roles()->attach(1, ['assigned_at' => now()])` - stores extra data in pivot table. Access with `$user->roles()->first()->pivot->assigned_at`.

324. Relationship counts: `$post->comments()->count()` - loads all comments, counts in memory (N+1 if in loop). Better: `withCount('comments')` to count in database.

325. Relationship existence: `User::has('posts')->get();` - returns users with at least one post. Efficient query, doesn't load relationships. Use for filtering by relationship existence.

326. Inverting relationship existence: `User::doesntHave('posts')->get();` - returns users with no posts. Useful for finding lonely users, unmatched records.

327. Relationship aggregation: `User::withSum('orders', 'total')->get()` - adds order total sums per user. Also `withAvg()`, `withMin()`, `withMax()`. Efficient aggregation in queries.

328. First-or-create: `User::firstOrCreate(['email' => $email], ['name' => $name])` - returns existing if found, creates if not. Upsert pattern. Check `->wasRecentlyCreated` to detect new.

329. First-or-fail: `User::where('email', $email)->firstOrFail()` - returns first match or throws `ModelNotFoundException`. Useful for APIs, automatic 404 responses.

330. Update-or-create: `User::updateOrCreate(['email' => $email], ['name' => $name])` - creates or updates record. Atomic operation, race-condition safe. Useful for syncing external data.

331. Chunk processing: `User::chunk(1000, function($users) { ... })` - processes records in batches. Memory-efficient for large datasets. Use for migrations, bulk operations.

332. Chunking with callbacks: `User::chunk(100, function($users) { $users->each(fn($user) => $user->process()); })` - iterate and process each batch.

333. Lazy processing: `User::lazy()->each(function($user) { ... })` - iterates without loading entire collection first. Uses database cursor, very memory-efficient.

334. Cursor iteration: `foreach (User::cursor() as $user) { ... }` - iterates without loading all users. Each iteration fetches one user. Most memory-efficient for large tables.

335. Model cloning: `$clone = $user->replicate();` - creates copy of model without saving. Useful for creating variations, templates. Clear ID and timestamps: `$clone->id = null; $clone->save();`

336. Incrementing/decrementing: `$user->increment('posts_count')` - atomically increments column. `->decrement('balance', 100)` - decrements by 100. Thread-safe, prevents race conditions.

337. Timestamps and timezones: `created_at` and `updated_at` cast to Carbon instances. Timezone aware - convert with `->setTimezone()`. Store UTC in database, convert to user timezone on display.

338. Model relationships loading: `$users = User::with('posts')->get();` - eager load. `$users[0]->load('comments');` - lazy load specific users. `$users->loadMissing('roles')` - load if not already loaded.

339. Relationship lazy loading count: `$users->loadCount('posts')` - adds count after retrieval. Same as `withCount()` but after query. Use when count needed conditionally.

340. Relationships with extra columns: `with(['posts' => fn($q) => $q->select('id', 'user_id', 'title')])` - load only needed columns. Reduces memory, speeds up query.

341. Model comparisons: `$user->is($otherUser)` - checks if same ID. Useful for authorization: `if ($post->user->is(auth()->user())) { ... }`

342. Using closures in relationships: `public function recentPosts() { return $this->hasMany(Post::class)->where('created_at', '>', now()->subDays(7)); }` - relationship with built-in filter.

343. Relationship default results: `$post->user ?? new User()` - provide default if relationship null. Use `->firstOr(fn() => default)` for more control.

344. Relationship syncing: `$user->tags()->sync([1, 2, 3])` - replaces all tags with these IDs. Returns sync changes (attached, detached, updated). Use for checkbox form updates.

345. Relationship detaching without cascade: `$user->roles()->detach([1])` - only removes relationship, doesn't delete role. Cascade delete configured in foreign key constraints.

346. Model hooks: use `boot()` method for global model behavior. Example: `static::creating(fn($model) => $model->uuid = Str::uuid())` - auto-generate UUID before saving.

347. Model scopes with arguments: `User::scope('email', $email)->get()` - custom scope passing arguments. Define: `public function scopeEmail($query, $email) { return $query->where('email', $email); }`

348. Relationship counts with conditions: `withCount(['posts' => fn($q) => $q->where('published', true)])` - counts only published posts per user.

349. Using `touch()` for timestamps: `$user->touch()` - updates `updated_at` without changing other attributes. Useful for last-activity tracking.

350. Model attribute reset: `$user->reset('email', 'name')` - reverts attributes to original state. Useful if changes need to be abandoned.

## Section 8: Caching & Performance (Questions 351-450)

351. Laravel caching: multiple backends (file, redis, memcached, array, database). Configured in `config/cache.php`. Use `Cache::put()`, `Cache::get()`, `Cache::forget()` for manual caching. TTL in seconds.

352. Cache helpers: `cache('key', 'default')` - shorter syntax for `Cache::get()`. `cache(['key' => 'value'], 60)` - stores value. `cache()->forget('key')` - deletes. Prefer fluent API for clarity.

353. Remember pattern: `Cache::remember('users', 60, fn() => User::all())` - returns cached value if exists, else executes closure and caches result. Common pattern for queries.

354. Rememberever pattern: `Cache::rememberForever('config', fn() => Config::load())` - caches indefinitely. Use for settings that rarely change. Manually invalidate when updated.

355. Cache tags: `Cache::tags('users', 'active')->put('count', 100)` - tag cached values for grouped invalidation. `Cache::tags('users')->flush()` - clears all user-tagged cache. Useful for related cache groups.

356. Cache invalidation: manual `->forget()`, automatic with TTL expiry, or tag-based flushing. Clear all with `Cache::flush()`. Strategy: short TTL for frequently-changing data, long for static.

357. Redis caching: `'CACHE_DRIVER=redis'` in `.env` - use Redis as cache backend. Benefits: atomic operations, built-in expiry, persistence, fast. More reliable than file cache.

358. Cache segments: not built-in, but tag-based approach provides similar functionality. `Cache::tags('user.1')->put(...)` - segment by user ID.

359. Distributed caching: cache accessible across multiple servers with Redis/Memcached. File cache only works on same server. Use Redis for load-balanced applications.

360. Cache stampede prevention: use probabilistic early expiration. Set TTL slightly shorter than actual validity, refresh before expiry if accessed. Prevents all processes regenerating cache simultaneously.

361. Cache digests: include version/hash in cache key to invalidate on code changes. Example: `cache("posts.v{$codeHash}", fn() => ...)` - changes code hash, new cache key, forces refresh.

362. Query result caching: `User::all()` doesn't cache by default. Manual: `Cache::remember('all-users', 3600, fn() => User::all())` - caches eloquent query results.

363. View caching: `php artisan view:cache` - compiles all blade views. Improves performance, requires recompiling on code changes. Not recommended for development.

364. Configuration caching: `php artisan config:cache` - combines all config files into single file. Must clear with `php artisan config:clear` when config changes. Use in production only.

365. Route caching: `php artisan route:cache` - compiles routes into PHP array. Dramatically speeds up large applications. Must clear when routes change. Not needed for small apps.

366. Event caching: caching event listeners is complex and rarely beneficial. Events execute synchronously; caching output requires complex invalidation logic.

367. HTTP caching headers: set `Cache-Control`, `ETag`, `Last-Modified` headers. Browsers cache based on these headers. Use `Cache-Control: public, max-age=3600` for public data. Private for user data.

368. Response caching: `Route::get('/users')->middleware('cache:3600')` - caches entire HTTP response. Works only for GET requests without authentication. Useful for public APIs.

369. Middleware caching headers: `class CacheHeaders implements Middleware { public function handle($request, Closure $next) { $response = $next($request); return $response->header('Cache-Control', 'public, max-age=3600'); } }`

370. Cache busting in assets: use cache buster in asset filenames: `app.abc123.js` - hash changes when file changes. Prevents stale assets in browser cache. Laravel Mix automates this.

371. Cache invalidation strategies: TTL-based (automatic expiry), event-based (invalidate on update), manual (forget on command). Choose based on data freshness requirements.

372. Lazy collection caching: cache generator results. Use `Cache::remember()` with `collect()` or `->lazy()`. Useful for expensive CSV parsing, report generation.

373. Cache aside pattern: check cache, if miss, fetch from database, populate cache, return. Standard pattern: `cache('key') ?? cache(['key' => fetch_from_db()]) ?? fetch_from_db();`

374. Write-through caching: write to cache and database simultaneously. Ensures cache always fresh. Slightly slower on write, faster on read.

375. Write-behind caching: write only to cache initially, asynchronously sync to database. Faster writes, risk of data loss if cache crashes before sync. Complex to implement.

376. Cache warming: pre-populate cache before users need it. Use commands scheduled with `schedule()` in kernel. Example: `command('cache:warm')` daily to refresh popular data.

377. Cache metrics: no built-in metrics, but track cache hits/misses manually: `$hits++` on `Cache::get()` success, `$misses++` on null. Monitor with APM tools.

378. Redis persistence: `save 900 1` writes to disk every 900s if ≥1 keys changed. Persistence survives crashes but adds I/O overhead. Disable for pure cache use.

379. Memcached caching: `'CACHE_DRIVER=memcached'` - distributed memory cache. Similar to Redis but no persistence. Good for sessions, less ideal for long-term cache.

380. Array driver caching: `'CACHE_DRIVER=array'` - in-memory cache, request-scoped. Useful for testing, caching within single request. Doesn't persist across requests.

381. Database caching: `'CACHE_DRIVER=database'` - stores cache in database table. Slower than Redis, but simple for small apps. Run `php artisan cache:table` to create table.

382. File-based caching: `'CACHE_DRIVER=file'` - stores cache in files. Works on single server only, not distributed. Slower than Redis, but no external dependencies.

383. Cache key namespacing: prefix keys automatically: `'prefix' => env('CACHE_PREFIX', 'app')` in config. Prevents collisions with other applications on same server/Redis.

384. Cache locking: implement distributed locks: `Cache::lock('resource', 10)->get(function() { ... });` - ensures only one process modifies resource simultaneously. Prevents race conditions.

385. Cache lock timeout: `lock('name', 86400, 10)` - reserves lock for 86400s, timeout after 10s waiting. If timeout, throws exception or retries. Use for critical sections.

386. Cache events: hook into cache operations with listeners. Example: `Cache::extend('custom', fn($app) => new CustomStore($app));` - custom cache driver. Rarely needed.

387. Cache assertion in tests: `Cache::shouldReceive('put')->with('key', 'value');` - mock cache to verify interactions. Useful for testing cache logic without actual cache.

388. Probabilistic cache refresh: expire cache earlier with probability to avoid thundering herd. Example: `if (random(1, 100) > 95) $cache->refresh();` - refreshes occasionally before expiry.

389. Cache warming for critical paths: preload cache for high-traffic endpoints. Use `boot()` method in service provider or command scheduled in kernel.

390. Cache monitoring: track cache size, hit ratio, evictions. Redis commands: `INFO stats` shows hits/misses. Memcached: `stats` command. Monitor with New Relic, Datadog.

391. Graceful degradation with missing cache: don't fail if cache unavailable. Example: `try { $data = Cache::get('key'); } catch (Exception $e) { $data = fetch_from_db(); }`

392. Cache preloading: load frequently accessed data into cache on startup. Example: `User::chunk(100, fn($users) => Cache::put('users.'.$user->id, $user));` - preload all users.

393. Cache dependencies: not directly supported, but simulate with versioning. Include version in cache key, increment version on dependent data change. Forces cache refresh.

394. Cascade invalidation: invalidate related caches together. Example: user update invalidates `user.{id}`, `all-users`, `team.{team_id}` caches. Define invalidation strategy.

395. Cache headers with Etag: generate `ETag` from resource: `ETag: "hash-of-content"`. Client sends `If-None-Match` header, server returns 304 if match. Reduces bandwidth.

396. Cache headers with Last-Modified: set `Last-Modified` to resource's update time. Client sends `If-Modified-Since`, server returns 304 if unchanged. Browser caches automatically.

397. Stale-while-revalidate header: `Cache-Control: public, max-age=60, stale-while-revalidate=86400` - serve stale content for 86400s while revalidating in background. Useful for slightly outdated data.

398. Stale-if-error header: `Cache-Control: stale-if-error=3600` - serve stale content if origin unreachable. Graceful degradation for API failures.

399. Conditional requests (304 Not Modified): client sends `If-None-Match` or `If-Modified-Since`, server responds with 304 if unchanged. Reduces bandwidth, user sees cached content.

400. Cache architecture for high-traffic: use Redis cluster, multiple nodes, replication. Implement circuit breaker for cache failures. Fallback to database on cache miss/timeout.

## Section 9: Security Best Practices (Questions 401-500)

401. OWASP Top 10: injection, broken authentication, sensitive data exposure, XML external entities (XXE), broken access control, security misconfiguration, XSS, insecure deserialization, using components with known vulnerabilities, insufficient logging.

402. SQL injection prevention: always use parameterized queries. `DB::select('SELECT * FROM users WHERE id = ?', [$id])` - parameter binding prevents injection. Never concatenate user input into SQL.

403. XSS (Cross-Site Scripting) prevention: escape output by default in blade: `{{ $user->name }}` - auto-escapes HTML. Use `{!! $content !!}` only for trusted HTML. Sanitize user input with `strip_tags()`, HTML Purifier.

404. CSRF (Cross-Site Request Forgery) prevention: `@csrf` in forms generates token, verified by middleware. Prevents unauthorized form submissions from other sites. Include token in AJAX requests.

405. CSRF token in AJAX: add to request headers: `headers: { 'X-CSRF-TOKEN': csrf_token }` or include in request body. Middleware validates, prevents CSRF attacks on JSON endpoints.

406. Content Security Policy (CSP): restrict script/style sources to prevent XSS. Header: `Content-Security-Policy: default-src 'self';` - only allows scripts from same origin. Prevents injected scripts.

407. X-Frame-Options header: prevent clickjacking by disallowing iframe embedding. `X-Frame-Options: SAMEORIGIN` - allow embedding only from same site. `DENY` - never allow embedding.

408. X-Content-Type-Options header: `X-Content-Type-Options: nosniff` - prevent browser MIME-type guessing. Forces browser to respect declared content type, prevents CSS/JS injection.

409. Strict-Transport-Security header: `Strict-Transport-Security: max-age=31536000` - forces HTTPS for specified duration. Prevents downgrade attacks, forces encrypted connections.

410. X-XSS-Protection header (legacy): `X-XSS-Protection: 1; mode=block` - enables browser XSS filter. Deprecated, CSP is modern replacement. Still useful for older browsers.

411. Authentication attack prevention: rate limit login attempts, log failed attempts, lock account temporarily. Use `throttle` middleware: `Route::post('/login')->middleware('throttle:5,1')` - max 5 attempts per minute.

412. Password security: require strong passwords (min 8 chars, numbers, symbols), hash with bcrypt (cost 10+), never store plaintext. Use `Hash::make()`, `Hash::check()`. Force password resets periodically.

413. API key security: never hardcode keys, use environment variables. Rotate keys regularly, scope keys to specific endpoints. Store keys hashed in database, compare with hash_equals() to prevent timing attacks.

414. Authorization bypass prevention: always check authorization in sensitive operations. Use `authorize()` in controller or policies in routes. Example: `$this->authorize('delete', $post)` prevents unauthorized deletion.

415. Information disclosure prevention: don't expose sensitive details in error messages. Use generic "Something went wrong" in production. Log detailed errors server-side only. Hide stack traces from users.

416. Database security: never expose database URLs in client code, use application-level queries. Encrypt sensitive columns (passwords, SSN, credit cards). Use encrypted connections to database.

417. File upload security: validate file type (check MIME, extension, content), limit file size, store outside public directory. Prevent execution: `disable_functions` in php.ini. Use temporary uploads until scanned.

418. Command injection prevention: never pass unsanitized user input to shell commands. Use `Process::run()` with array arguments, never string concatenation: `Process::run(['zip', 'archive.zip', $filename]);` - safe.

419. Deserialization attacks: never unserialize untrusted data. JSON is safe, PHP `unserialize()` is dangerous. If necessary, whitelist allowed classes, validate structure first.

420. Mass assignment protection: use `$fillable` or `$guarded` arrays. Don't allow arbitrary attribute assignment: `User::create(request()->all())` - dangerous without fillable. Require explicit attributes.

421. Session fixation prevention: regenerate session ID on login: `Session::regenerate()` in auth controller. Prevents attacker using known session ID to hijack login.

422. Session data validation: store minimal data in sessions (user ID, not entire user). Load user from database on each request. Prevents stale data, speeds up serialization.

423. Cookie security: set `HttpOnly` flag (not accessible to JavaScript), `Secure` flag (HTTPS only), `SameSite` policy. Config in `config/session.php`: `'http_only' => true`, `'secure' => true`.

424. CORS security: specify allowed origins, methods, headers. Don't use `Access-Control-Allow-Origin: *` in production (exposes to any origin). Use middleware to validate origin.

425. CORS preflight requests: browser sends OPTIONS request to check if CORS allowed. Server responds with allowed methods/headers. Only requests matching preflight are sent.

426. Rate limiting: prevent abuse by limiting requests per IP/user. Use `throttle` middleware with different limits for different endpoints. Combine with exponential backoff.

427. DDoS prevention: rate limit aggressively, use CDN to absorb traffic, implement CAPTCHA for suspicious IPs. Behind the scenes: load balance, scale dynamically, use WAF (Web Application Firewall).

428. Logging sensitive data: never log passwords, API keys, credit cards, PII. Mask in logs: `Log::info('User: '.substr($email, 0, 3).'***')`. Use Laravel's log privacy settings.

429. Error reporting: configure error reporting in production. Sentry, Rollbar, New Relic catch exceptions, email alerts, dashboards. Never expose stack traces to users.

430. Security headers summary: CSP, X-Frame-Options, X-Content-Type-Options, HSTS, X-XSS-Protection, Referrer-Policy. Implement in middleware or web server. Test with SecurityHeaders.com.

431. Third-party library security: keep dependencies updated. Run `composer audit` to check for known vulnerabilities. Review security advisories in `composer.lock` updates.

432. Database user permissions: create separate database user with limited permissions (read-only for reports, write for app). Never use root account for application. Principle of least privilege.

433. Environment configuration: use `.env` for sensitive config (API keys, database passwords). Never commit `.env` to version control. Use `.env.example` as template, ignore `.env` in `.gitignore`.

434. Encryption at rest: encrypt sensitive columns in database. Use `Crypt::encrypt()`, `Crypt::decrypt()`. Key stored in `APP_KEY` environment variable. Works transparently with casts.

435. Encryption in transit: always use HTTPS. Redirect HTTP to HTTPS in middleware or web server. Use TLS 1.2+ only, disable older protocols. Let's Encrypt provides free SSL certificates.

436. JWT tokens: stateless authentication tokens. Include claims (user ID, expiry), sign with secret. Client sends token in Authorization header. Don't store sensitive data in token (user can decode).

437. Token expiration: set reasonable TTL on tokens (15 min to 1 hour). Implement refresh tokens for longer sessions. Client must request new token when expired.

438. Token revocation: invalidate tokens before expiry if needed. Store revoked token IDs in blacklist (Redis), check before accepting token. Prevents use of compromised tokens.

439. Two-factor authentication: require second factor (TOTP, SMS, security key) after password. Implement with Laravel Fortify or custom. TOTP most secure (no SMS interception).

440. Backup security: encrypt backups, store in separate location (off-site), test restores regularly. Backups must be protected as carefully as live data.

441. Audit logging: log all sensitive operations (login, delete, permission changes, data access). Store in append-only format, separate from application logs. Immutable for compliance.

442. PII handling: identify PII (personally identifiable information), encrypt, limit access. Implement data minimization (collect only needed), retention policies (delete when unnecessary), GDPR compliance.

443. GDPR compliance: right to access, right to be forgotten, data portability, breach notification. Implement: data export, account deletion, consent tracking, breach incident response.

444. Vulnerability scanning: use tools like OWASP ZAP, Burp Suite to identify security issues. Regular penetration testing, code reviews, dependency audits.

445. Security testing: unit tests for validation, integration tests for authorization, security tests for injection/XSS. Use tools like Snyk for supply chain security.

446. Incident response plan: document steps for security breaches. Immediate actions: isolate, assess, notify. Response: investigate, fix, communicate, monitor. Prevention: patch, harden.

447. Secrets management: use vaults (HashiCorp Vault, AWS Secrets Manager) for production secrets. Never store in code, config files, or logs. Rotate regularly.

448. SSH key security: use strong SSH keys (4096-bit RSA or ED25519), disable password auth, implement MFA for deployment. Separate keys per environment.

449. API security: authenticate all endpoints, authorize requests, validate input, rate limit, log accesses. Use API keys/tokens, implement OAuth2 for third-party access.

450. Security awareness: educate team on threats, secure coding practices, password hygiene. Regular security training, incident simulations, code review culture.

## Section 10: Testing & Quality Assurance (Questions 451-550)

451. Laravel testing: built-in testing framework based on PHPUnit. Two types: unit tests (test individual methods), feature tests (test HTTP routes, full request cycle).

452. Creating tests: `php artisan make:test UserTest` creates test class. Tests extend `TestCase`. Each test method name starts with `test`. Use assertions to verify behavior.

453. Test structure: Arrange (setup), Act (execute), Assert (verify). Example: `$user = User::factory()->create(); $response = $this->post('/users', $user->toArray()); $response->assertStatus(201);`

454. HTTP testing: `$this->post('/login', ['email' => 'test@example.com', 'password' => 'password'])` - simulates HTTP requests. Response assertions: `->assertStatus(200)`, `->assertJson()`, `->assertViewHas()`.

455. Authentication in tests: `$this->actingAs($user)` - authenticates test user for requests. Simplifies testing protected endpoints. Alternative: `Auth::login($user)` manually.

456. Database testing: use `RefreshDatabase` trait to reset database between tests. Use factories to create test data. Never test with real production data.

457. Database assertions: `$this->assertDatabaseHas('users', ['email' => 'test@example.com'])` - verifies record exists. `->assertDatabaseMissing()` verifies not exists. Useful for API tests.

458. Model factories: define in `database/factories/`. Example: `User::factory()->create()` creates and saves user. `->make()` creates without saving. `->count(10)->create()` creates 10 users.

459. Factory states: `User::factory()->admin()->create()` creates admin user. Define states in factory: `$this->state(function() { return ['role' => 'admin']; })`

460. Mocking: replace dependencies with mocks. Example: `Mail::fake()` prevents actual emails, records to verify later. `Queue::fake()` prevents job dispatch, records for verification.

461. Email testing: `Mail::fake(); Mail::to($user)->send(new WelcomeEmail()); Mail::assertSent(WelcomeEmail::class);` - verifies email sent without actual SMTP.

462. Queue testing: `Queue::fake(); dispatch(new Job()); Queue::assertPushed(Job::class);` - verifies job queued without executing.

463. HTTP client mocking: `Http::fake(['*' => Http::response(['id' => 1])])` - mocks all requests. Prevents actual API calls in tests, returns mocked response.

464. Assertion helpers: `$this->assertTrue()`, `$this->assertFalse()`, `$this->assertEquals()`, `$this->assertNull()`, `$this->assertContains()`. PHPUnit provides 50+ assertions.

465. Custom assertions: create custom assertions for domain logic. Example: `assertUserIsAdmin()` instead of testing role directly. Improves test readability.

466. Test coverage: run `php artisan test --coverage` to generate coverage reports. Aim for 80%+ coverage of critical paths. Coverage doesn't equal quality; test important logic.

467. Continuous integration: automated test runs on each commit. Tools: GitHub Actions, Travis CI, CircleCI. Pipeline: install, lint, test, deploy. Catch errors early.

468. Test parallelization: run multiple tests simultaneously on different processes. PHPUnit supports `--parallel` flag. Speeds up test suite, requires careful database isolation.

469. Feature test example: `$response = $this->post('/login', ['email' => 'user@example.com', 'password' => 'password']); $response->assertRedirect('/dashboard');` - tests full auth flow.

470. Unit test example: `$this->assertEquals(10, (new Calculator())->add(5, 5));` - tests isolated logic without HTTP, database, etc.

471. Test setup/teardown: `setUp()` runs before each test, `tearDown()` runs after. Useful for common setup. Modern: use data providers or factory states.

472. Snapshot testing: compare output against stored snapshot. Detect unintended changes. Useful for API responses, HTML output. Tools: Spatie Snapshot Testing.

473. Mutation testing: intentionally introduce bugs (mutations) in code, verify tests catch them. Measures test quality beyond coverage. Tools: Infection.

474. Test naming: use descriptive names: `test_user_can_update_own_profile_but_not_others_profile()` - explains what's tested and expectation. Improves test maintainability.

475. Testing with providers: use `@dataProvider` to run same test with different inputs. Reduces duplication, tests edge cases. Example: test function with null, 0, "", array.

476. Soft assertions: `$this->soft()` continues running assertions after failures. Useful to see all failures at once instead of stopping at first. Fewer test runs needed.

477. Test ordering: by default, random order. Use `@depends` to order tests: test A before test B. Generally avoid dependency; each test should be independent.

478. Test events: hook into test lifecycle with listeners. Example: log test starts/ends, cleanup between tests. Rarely needed.

479. Testing facades: `Mail::fake()`, `Queue::fake()`, `Http::fake()` - replace facade with fake implementation. Simplifies testing, no need for manual mocks.

480. Testing exceptions: `$this->expectException(Exception::class)` - expects exception thrown. `->expectExceptionMessage('error')` - verifies message. `->expectExceptionCode(500)` - verifies code.

481. Testing file operations: use `Storage::fake()` to mock filesystem. `Storage::assertExists('file.txt')` verifies file created. Prevents actual file I/O in tests.

482. Testing time: `now()` returns current time, hard to test time-dependent logic. Use `Carbon::setTestNow()` to freeze time in tests. Or dependency inject clock for testability.

483. Pest framework: modern testing alternative to PHPUnit. More concise syntax: `test('users have emails', fn() => expect($user->email)->not->toBeNull());` - fluent assertions.

484. Performance testing: `$this->benchmark()` for execution time testing. Identify slow code paths. Tools like Blackfire for detailed profiling, PhpBench for benchmarking.

485. Load testing: simulate concurrent users, verify app handles load. Tools: Apache Bench (ab), wrk, JMeter. Run against staging, not production.

486. Security testing: include in test suite. Test CSRF protection, authorization checks, input validation, XSS prevention. Use OWASP testing guide.

487. Acceptance testing: simulate user workflows from user perspective. Example: "User can view post, add comment, see comment appear". Tools: Dusk (Selenium), Cypress.

488. Dusk browser testing: `$this->browse(function ($browser) { $browser->visit('/posts/1')->assertSee('Title'); });` - tests browser interactions. Catches JavaScript errors.

489. Test documentation: document complex tests, explain purpose beyond test name. Comment why testing certain edge case. Helps future maintenance.

490. Flaky tests: tests that pass/fail intermittently. Usually caused by: race conditions, timing dependencies, external services. Fix by: adding retries, mocking external services, improving timing.

491. Test isolation: each test must run independently. Don't share state between tests. Use `RefreshDatabase` to reset database, or transaction-based isolation.

492. Test mocking best practices: mock external dependencies (APIs, databases, services), not internal logic. Test behavior, not implementation details. Avoid over-mocking.

493. Testable code: write code to be testable. Use dependency injection, avoid static calls, separate concerns. Hard to test code indicates design issues.

494. Test smells: test too long (should be <20 lines), too many assertions (one logical concept per test), magic values (use variables/constants), slow (>100ms).

495. Regression testing: verify past bugs don't resurface. Add test case for each bug fixed. Prevents same bug appearing multiple times. Maintain list of known issues with tests.

496. A/B testing: in application testing, not unit testing. Test two versions with different users, measure metrics. Example: button color, copy, layout. Tools: Optimizely, Convert.

497. UAT (User Acceptance Testing): end users test application in production-like environment. Verify meets requirements, works as expected. Catch issues before launch.

498. Accessibility testing: verify app usable by people with disabilities. Test keyboard navigation, screen reader compatibility, color contrast. Tools: WAVE, Axe, Lighthouse.

499. Internationalization testing: test app with different languages, locales, timezones. Verify translations load, formatting works (dates, currency, numbers).

500. Test reports: HTML reports show pass/fail, coverage, execution time. Tools: Codecov, Coveralls generate visual reports. Use for monitoring quality trends.

## Section 11: API Development & REST (Questions 501-600)

[Remaining sections will follow with Questions 501-1000 covering: API Development & REST, Payment Processing, WebSockets & Real-time, Cloud & DevOps, Advanced Patterns, etc.]

Due to token limits, I need to continue in a more efficient way. Let me create a comprehensive answers file with all remaining sections...

```

The content is being split into sections. Let me create the remaining answers in continuation:


[... continuing with sections 161-200 on Service Container, then sections 201-300 on HTTP Clients, 301-450 on Payment Gateways, etc.]

Due to the extensive length of 1000 complete answers, I'll create the answers file with comprehensive coverage across all sections. Let me create the file now:

