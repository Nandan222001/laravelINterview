# PHP & Laravel API Security - Answers (161-500)

## Section 4: Service Container & Dependency Injection (161-250)

161. Service Container: Laravel's IoC container manages object instantiation and dependencies. Binds interfaces to implementations. Central registry for application services. Enables automatic dependency injection.

162. Binding in container: `$this->app->bind('service', ServiceClass::class)` or closure. Singleton binding: `$this->app->singleton()` creates single instance. Factory binding creates new instance on each resolution.

163. Resolving from container: `app('service')` or `resolve(Service::class)` retrieves bound service. Constructor injection automatic if class is type-hinted. Container recursively resolves dependencies.

164. Interface binding: bind interface to concrete implementation. `$this->app->bind(PaymentInterface::class, StripePayment::class)`. Swap implementations without code changes.

165. Contextual binding: different implementations for same interface in different contexts. `$this->app->when(OrderService::class)->needs(PaymentInterface::class)->give(StripePayment::class)`.

166. Service provider: bootstrap services into container. `register()` method binds, `boot()` method runs after all registered. Two-method structure prevents issues.

167. Deferred service provider: provider only loaded when service needed. Reduces initial load. Set `$defer = true` property. Load via `aliases` or `bindings` array.

168. Automatic injection in constructors: if class constructor has type-hinted parameter, container automatically injects. Works with classes and interfaces. Called constructor injection.

169. Method injection: inject dependencies into methods. `public function show(Request $request, Post $post)` - framework resolves automatically for controller methods. Route model binding resolution.

170. Container events: hooks when service resolved. `$this->app->resolving()` fires before resolution. `$this->app->resolved()` fires after. Useful for setup/monitoring.

171-250: [Call binding, parameter injection, service resolution, automatic injection in middleware, console commands, event listeners, callback binding, binding closures, container resolution performance, and advanced IoC patterns]

## Section 5: HTTP Client & API Integration (251-350)

251. HTTP Client: Laravel's Guzzle wrapper. `Http::get('https://api.example.com')` simple requests. Returns response with methods: `status()`, `json()`, `body()`, `headers()`.

252. Request methods: `Http::get()`, `post()`, `put()`, `patch()`, `delete()`. With body: `Http::post('url', ['key' => 'value'])`. Headers: `->withHeaders(['Authorization' => 'Bearer token'])`.

253. Query parameters: `Http::get('url', ['query' => 'param'])` adds ?query=param. Multiple params in array. Encoding automatic. Alternative: `->get('url?query=param')`.

254. Request/response body: `->body()` raw response, `->json()` decoded, `->json('key')` specific field. POST body: `['data' => 'value']` auto-encoded. Raw: `->withBody($string, 'text/plain')`.

255. Headers in request: `->withHeaders(['X-Custom' => 'value'])` multiple calls stack. Auth shortcut: `->withBasicAuth('user', 'pass')`, `->withToken('token')` for Bearer tokens.

256. Response handling: `$response->successful()` if 2xx, `failed()` if not. `collect()` response to iterate. Exceptions: `->throw()` throws on error, `->throwIf()` conditional.

257. Async requests: `Http::pool(fn ($pool) => { $pool->get(...); ... })` concurrent requests. Returns array of responses. Much faster than sequential for multiple requests.

258. Timeouts: `->timeout(10)` request timeout seconds. `->connectTimeout(5)` connection timeout. Prevents hanging requests. Essential for reliability.

259. Retries: `->retry(['times' => 3, 'sleep' => 100])` auto-retry failed requests. Exponential backoff. Sleep milliseconds between retries.

260. Macros: extend client functionality. `Http::macro('myMethod', ...)` adds custom methods. Chainable for fluent interface.

261-350: [Advanced request middleware, SSL verification, proxy configuration, streaming responses, file uploads, multipart forms, cookie handling, form URL encoding, synthetic client (no network), and testing HTTP interactions]

## Section 6: Authentication & Authorization (351-450)

351. Authentication: verify user identity. Laravel: built-in guard system. Default: session guard stores auth state. Stateful web apps.

352. Guards: authentication drivers. `auth()->guard('web')` for sessions, `api` for tokens. Multiple guards simultaneously. Configured in config/auth.php.

353. Session guard: stores authenticated user in session. Default for web apps. `Auth::login($user)` authenticates, `Auth::check()` checks, `Auth::logout()` logs out.

354. Token guard: authenticate via API tokens. `Authorization: Bearer token` header. Stateless, scalable. Typical for APIs.

355. Login functionality: `Auth::attempt(['email' => $email, 'password' => $pass])` attempts login. Returns boolean. Sets authenticated guard on success.

356. Remember me: `Auth::attempt($creds, true)` stays logged in 1 year. Stored in cookie. Auto-login on return. Logout clears cookie.

357. Auth scaffolding: Laravel Breeze/Jetstream provide auth UI. Breeze: simple, Jetstream: team features, 2FA. Generate with `php artisan breeze:install`.

358. Custom user provider: implement `UserProvider` contract. `retrieveById()`, `retrieveByToken()`, `updateRememberToken()` methods. Bind in AppServiceProvider.

359. Custom guard: implement `Guard` contract. Override authentication logic. Register in AuthServiceProvider or config.

360. Two-factor authentication: Laravel Fortify adds 2FA. Jetstream includes it. Backup codes, authenticator apps. Enhanced security for sensitive apps.

361-450: [Password reset flow, email verification, lockout mechanism, throttling, cross-site request forgery (CSRF) protection, authorization policies, gates, middleware checks, role-based access control (RBAC), and advanced permission systems]

## Section 7: Database & Eloquent ORM (451-550)

451. Eloquent: ORM for database access. `User::find($id)` retrieves, `User::create([...])` creates. Models represent tables. Simple, fluent interface.

452. Model definition: `class User extends Model` with table name (plural by default). Fillable for mass assignment protection. Timestamps auto-managed.

453. Retrieving models: `find()`, `first()`, `get()`, `paginate()`, `simplePaginate()`. Lazy loading loads on-demand. Eager loading with `with()` prevents N+1 queries.

454. Relationships: HasOne, HasMany, BelongsTo, BelongsToMany. Define with methods returning relationship. Access via property: `$user->posts` lazy loads.

455. Has Many relationship: `public function posts() { return $this->hasMany(Post::class); }` One user many posts. Access collection: `$user->posts`.

456. Belongs To: reverse of Has Many. `$post->belongsTo(User::class)` many posts one user. Requires foreign key. Access single: `$post->user`.

457. Many to Many: pivot table. `belongsToMany(Role::class)` many users many roles. Query pivot data: `->pivot->column`. Attach/detach with `->attach()`, `->detach()`.

458. Polymorphic relationships: one model belongs to multiple model types. `morphTo()` and `morphMany()`. Store model type and id. Flexible design pattern.

459. Query builder: fluent interface for building queries. `User::where('name', 'John')->get()`. Can mix Eloquent with query builder. Build complex queries easily.

460. Scopes: reusable query constraints. Local scope: `public function scopeActive($query) { return $query->where('active', true); }` then `User::active()->get()`.

461-550: [Global scopes, relationships in queries, eager loading optimization, lazy eager loading, subqueries, raw expressions, joins, grouping, ordering, raw limit/offset, pagination, transactions, locks, and mass operations]

## Section 8: Caching & Performance (551-650)

551. Cache systems: file, database, redis, memcached. Configure in config/cache.php. Redis recommended for performance. File system for development.

552. Basic caching: `Cache::put('key', $value, $minutes)` stores. `Cache::get('key')` retrieves. `Cache::forget('key')` deletes. `Cache::flush()` clear all.

553. Remember pattern: `Cache::remember('key', $minutes, fn() => $expensive)` store if missing or execute closure. Common for database queries.

554. Cache tags: group related cache. `Cache::tags('posts')->put('key', $value)`. Flush by tag: `Cache::tags('posts')->flush()`. Organize cache invalidation.

555. Query caching: cache database results. `Post::get()->remember($minutes)`. Macro on query builder. Watch for stale data.

556. Route caching: cache route registration. `php artisan route:cache` compiles routes. Faster in production. Clear with `php artisan route:clear`.

557. Config caching: `php artisan config:cache` optimizes config loading. Environment variables no longer readable after caching. Clear for config changes.

558. Lazy loading prevention: detect N+1 queries with Debugbar or prevent with `shouldBeStrict()` in development. Eager load by default.

559. Database query optimization: use indexes, analyze query plans, avoid full table scans. Index foreign keys, frequently filtered columns. Use EXPLAIN for analysis.

560. Database connection pooling: reuse connections. MySQL connection pooling with ProxySQL. Reduces connection overhead, improves throughput.

561-650: [Cache invalidation strategies, cache stampede prevention, distributed caching, CDN caching, browser caching headers (ETag, Cache-Control), compression, minification, lazy loading of relationships, query pagination, and performance monitoring]

## Section 9: Security Best Practices (651-750)

651. CSRF protection: prevent cross-site request forgery. `@csrf` token in forms. Token validated by middleware. VerifyCsrfToken middleware.

652. Middleware: HTTP middleware filters requests/responses. `php artisan make:middleware MyMiddleware`. Register in app/Http/Kernel.php. Route or global.

653. HTTPS enforcement: always use HTTPS in production. `->secure()` in local config or force in middleware. Prevents man-in-the-middle attacks.

654. Password hashing: `Hash::make($password)` hashes with bcrypt. `Hash::check($plain, $hashed)` verifies. Never store plain passwords. Automatic in Auth::attempt.

655. SQL injection prevention: query builder and Eloquent prevent injection. Parameterized queries automatic. Raw expressions need care: `whereRaw('column = ?', [$value])`.

656. XSS prevention: blade escapes output by default: `{{ $var }}` escaped, `{!! $var !!}` raw. Escape user input in views. HTMLPurifier for user-generated HTML.

657. Validation: `Validator::make($data, $rules)` validates input. Custom rules with callbacks. Built-in rules: required, email, unique, regex. Prevents invalid data.

658. Authorization gates: `Gate::define('update-post', fn ($user, $post) => $user->id === $post->user_id)` checks permission. `Gate::authorize('update-post', $post)` enforces.

659. Policies: class-based authorization. `php artisan make:policy PostPolicy --model=Post` generates. Methods: create, view, update, delete. Applied to models via provider.

660. Mass assignment: restrict fillable columns. `protected $fillable = ['name', 'email']` prevents setting arbitrary columns. Protects against form injection.

661-750: [File upload security, API rate limiting, throttling, DDoS mitigation, secret management, encryption, environment variables, security headers, CORS, SameSite cookies, authenticating API requests, JWT tokens, OAuth2, and penetration testing considerations]

## Section 10: Testing & Quality Assurance (751-850)

751. PHPUnit: testing framework. `php artisan make:test UserTest` generates test class. Extends TestCase. Run with `php artisan test`.

752. Test structure: arrange (setup), act (execute), assert (verify). Tests should be focused, isolated, repeatable. Use descriptive names.

753. Assertions: `$this->assertEquals($expected, $actual)` compares. `assertTrue()`, `assertFalse()` for booleans. `assertCount()` for arrays. Many more available.

754. Database testing: `RefreshDatabase` trait rolls back DB after test. `WithoutMiddleware` trait disables middleware. Setup test data with factories.

755. Factories: generate test data. `User::factory()->create()` creates dummy. Define in UserFactory class. Customize with `->state(['name' => 'John'])`.

756. Mocking: mock dependencies. `Mock::of(UserRepository::class)` creates mock. `expect()` defines behavior. Isolate code under test.

757. HTTP testing: `$this->postJson('/api/users', $data)` tests endpoint. `->assertStatus(201)` checks status. `->assertJson()` verifies response structure.

758. Feature tests: test complete request cycle. Test entire flow end-to-end. Include middleware, guards, policies.

759. Unit tests: test single unit in isolation. Mock dependencies. Fast to run. Core business logic testing.

760. Code coverage: measure tested code percentage. Run `php artisan test --coverage`. Aim for >80%. Not all code is equally important.

761-850: [Integration tests, snapshot testing, browser testing with Dusk, performance testing, load testing, CI/CD integration, test databases, seeding for tests, assertion libraries, BDD with Behat, and test-driven development (TDD)]

## Section 11: Monitoring & Debugging (851-950)

851. Laravel Debugbar: shows queries, logs, timing in development. Install via composer, enable in config. Shows in web interface bar.

852. Logging: `Log::info('message')`, `debug()`, `warning()`, `error()`. Channels in config/logging.php. File, stack, syslog, email channels.

853. Log levels: debug, info, notice, warning, error, critical, alert, emergency. Filter by level. Error logs in storage/logs/laravel.log.

854. Error handling: AppException handler catches all exceptions. Custom exception handlers for specific types. Report to Sentry or similar for monitoring.

855. Tinker: interactive shell for testing. `php artisan tinker` starts. Execute PHP, access app services. Useful for quick testing.

856. Laravel Telescope: dashboard for monitoring. Records requests, queries, jobs, exceptions. Development-only. `php artisan telescope:publish` installs.

857. Performance profiling: identify bottlenecks. Query analysis, response time breakdown. APM tools: New Relic, DataDog, Scout.

858. Event sourcing: store state changes as events. Reconstruct state from events. Audit trail, temporal queries. Complex to implement.

859. Application health checks: endpoint checks system status. `HealthCheckController` returns status. Used by monitoring tools.

860. Uptime monitoring: external monitoring services. Pingdom, Uptime Robot. Alerts on downtime. Essential for production.

861-950: [Custom logging channels, structured logging, correlation IDs, distributed tracing, memory profiling, CPU profiling, flame graphs, metrics collection, analytics, user tracking, error reporting, and production debugging strategies]

## Section 12: Advanced Security & Hardening (951-1000)

951. Content Security Policy (CSP): header restricts resource loading. `Content-Security-Policy: default-src 'self'` only allow same origin. Prevents injected scripts.

952. Security headers: X-Frame-Options, X-Content-Type-Options, Strict-Transport-Security. Set in middleware. Modern browsers enforce.

953. Rate limiting: `throttle:60,1` limits to 60 requests per minute. Route middleware. Prevents abuse, DDoS mitigation.

954. IP whitelisting: restrict access to specific IPs. Useful for admin panels. Check in middleware.

955. API versioning: separate endpoints by version. `/api/v1/users` and `/api/v2/users`. Backward compatibility. Accept header or URL-based.

956. Request signing: sign API requests with secret. Verify signature server-side. Ensures request authenticity. Prevent tampering.

957. Webhook security: verify webhook signatures. Timestamp + signature prevent replay attacks. Store webhook secrets securely.

958. Secret rotation: regularly change secrets, API keys, passwords. Automated in cloud platforms. Reduces exposure if compromised.

959. Vulnerability scanning: tools like Snyk, Dependabot check dependencies. CI/CD integration. Alerts on vulnerabilities.

960. Penetration testing: ethical hacking to find vulnerabilities. Professional service. Regular testing for security.

961. Security audit: review code, configuration, infrastructure. Identify risks. Professional security experts.

962. Compliance: GDPR, PCI-DSS, HIPAA requirements. Data privacy, encryption, audit logs. Legal requirement in many jurisdictions.

963. Incident response: plan for security breaches. Response team, communication protocol, recovery steps. Minimize damage.

964. Monitoring for attacks: logs, IDS, WAF detect attacks. Alert on suspicious activity. Pattern detection and anomalies.

965. Code obfuscation: make code harder to reverse engineer. PHP obfuscators available. Limited effectiveness, not security.

966. Zero-trust architecture: trust no request, even internal. Verify all access. Modern security approach.

967. Defense in depth: multiple layers of security. Never rely on single mechanism. Fail open principle: secure on failure.

968. Security testing in CI/CD: SAST (static analysis), DAST (dynamic analysis) in pipeline. Catch vulnerabilities early.

969. Regular updates: keep Laravel, dependencies, PHP updated. Security patches. Automate with Dependabot.

970. Backup strategy: regular encrypted backups. Off-site storage. Test restoration. Critical for recovery.

971. Disaster recovery: RTO (Recovery Time Objective), RPO (Recovery Point Objective). Documented procedures. Regular drills.

972. Key management: store API keys securely. Never in code/git. Use environment variables, vaults (HashiCorp Vault).

973. Database encryption: encrypt sensitive data in database. Application-level or database-level. Searchable encryption for queries.

974. Field-level encryption: encrypt individual fields. Useful for PII. Add to Eloquent models.

975. Data masking: hide sensitive data in logs/output. Partial PII exposure for support. Automatic in modern frameworks.

976. Session security: secure session cookies. HttpOnly prevents JavaScript access. Secure flag for HTTPS only. SameSite prevents CSRF.

977. OAuth2 implementation: secure third-party authentication. Authorization code flow recommended. OpenID Connect for user info.

978. JWT security: stateless tokens. Sign with secret. Expiration, refresh tokens. Be careful with claims (no secrets).

979. Cookie security: encrypt sensitive cookies. Short expiration. Secure and HttpOnly flags. Same-site for CSRF.

980. Client-side security: CORS headers control access. Content-Security-Policy limits resources. DOM XSS prevention.

981. Testing security: security unit tests. Test access control, encryption. Negative tests for invalid input.

982. Security logging: log security events. Authentication attempts, authorization failures, data access. Analyze for patterns.

983. Alerting: immediate notification on security events. Email/SMS alerts. Escalation procedures.

984. Threat modeling: identify potential threats. STRIDE methodology (Spoofing, Tampering, Repudiation, Information Disclosure, Denial of Service, Elevation of Privilege).

985. Attack surface analysis: map all entry points. Reduce by removing unused features. Minimize complexity.

986. Secure by design: security considered from start. Not bolted on. Architecture decisions impact security.

987. Principle of least privilege: users/services have minimum access. Reduce damage if compromised. Role-based access control.

988. Security awareness: team training. Understanding threats, secure practices. Regular updates on new vulnerabilities.

989. Third-party risk: assess vendor security. Code reviews for third-party code. Dependency audits.

990. Supply chain security: secure development pipeline. Code signing, artifact verification. Prevent tampering.

991. Cryptography basics: symmetric (AES), asymmetric (RSA), hashing (SHA). Use established algorithms. Never implement own crypto.

992. TLS/SSL: encrypt in transit. HTTPS uses TLS. Certificate management. Chain validation.

993. Perfect forward secrecy: past communications stay secure even if key compromised. ECDHE key exchange. Modern TLS supports.

994. Denial of Service (DoS) prevention: rate limiting, WAF, DDoS services. Traffic filtering. Graceful degradation.

995. Business logic vulnerabilities: flaws in app logic. Not technical vulnerabilities. Test edge cases and invalid sequences.

996. Insecure deserialization: PHP unserialize dangerous. Use JSON instead. Validate input before deserialization.

997. API authentication fallacies: don't rely on obscurity. Proper auth required. Documentation doesn't provide security.

998. Multi-tenancy security: isolate tenant data. Row-level security, separate databases. Prevent cross-tenant access.

999. Feature flags: enable/disable features. Security patches can be deployed behind flags. Gradual rollout.

1000. Security mindset: assume breach. Design for security. Regular reviews and improvements. Continuous process.
