# Database Optimization - Complete Answers

## Indexing Strategies

1. Indexes speed up SELECT queries by creating sorted data structures (B-trees). Single-column index on frequently queried column. Composite index for multi-column WHERE clauses in order they're queried. Primary key automatically indexed. Trade-off: slower INSERTs/UPDATEs due to index maintenance.

2. Index types: B-tree (default, for range queries), Hash (equality only, faster), Full-text (text searching), GiST (geometric data), Spatial (coordinates), JSON (JSON documents). Choose based on query patterns.

3. Composite indexes: create for multi-column searches like `CREATE INDEX idx_user_email_status ON users(email, status)`. Column order matters - query must use leftmost columns to benefit from index.

4. Query execution plans: use `EXPLAIN` before SELECT to see if index used. Look for "Using index" (covered query), "Using where" (filters after index). Avoid "Full table scan" unless querying <5% of rows.

5. Index selectivity: higher selectivity (more unique values) = better index. Avoid indexing low-selectivity columns (gender, status with few values). Cardinality analysis: `SELECT COUNT(DISTINCT column) FROM table`.

6. N+1 query problem: querying parent then iterating children with individual queries. Solve with eager loading: `User::with('posts')->get()` instead of `User::all()` then `$user->posts` in loop.

7. Lazy loading alternative: use `lazy()` for streaming large result sets without memory overload. `foreach (User::lazy() as $user)` loads in chunks, reduces memory.

8. Query optimization: use `select()` to limit columns, `where()` for early filtering, `orderBy()` with indexed columns, `limit()` with offset for pagination, avoid `SELECT *` in production.

9. Eager loading strategies: `with(['posts', 'comments'])` loads relationships. `withCount('posts')` adds count without loading. `withSum('payments', 'amount')` aggregates without loading all records.

10. Database connection pooling: maintain persistent connections, reuse across requests. Benefits: reduced handshake overhead, faster query execution. Tools: PgBouncer (PostgreSQL), ProxySQL (MySQL).

11. Caching query results: `Cache::remember('users', 3600, fn() => User::all())` stores in cache, revalidates after 1 hour. Cache aside pattern: check cache, fall back to database, cache result.

12. Redis for caching: faster than file-based caching, supports expiration, atomic operations. Use for frequently accessed data (user preferences, product listings) with moderate update frequency.

13. Pagination: `User::paginate(15)` returns page of results with metadata. Faster than loading all records. Cursor pagination `cursorPaginate()` better for large datasets than offset.

14. Sorting optimization: index columns in `orderBy()` clause. Avoid sorting on computed columns or functions. Pre-compute and store if needed: `user.display_name` instead of `CONCAT(first_name, ' ', last_name)`.

15. Aggregation optimization: use `count()`, `sum()`, `avg()` in database, not PHP. `User::where('active', true)->count()` is faster than `collect()->count()` after loading.

16. JOIN optimization: explicitly join related tables with `join()`. Specify columns: `$users->join('posts', 'users.id', '=', 'posts.user_id')`. Indexes on join columns critical.

17. Subquery optimization: use `whereIn()` instead of multiple ORs: `User::whereIn('status', ['active', 'pending'])`. Use subqueries for complex filters: `User::whereIn('id', Order::select('user_id'))->get()`.

18. Denormalization: store computed values in table instead of calculating on each query. Example: store `user_post_count` in users table, increment on new post. Risk: data inconsistency if not maintained properly.

19. Partitioning: split large table across physical storage for parallel queries. By date: orders partitioned by year. By range: users partitioned by ID range. Improves query speed for large tables.

20. Archive old data: move historical records to archive table (same schema), query from both tables for reports. Keeps main table smaller, queries faster. Archive table indexed differently for historical queries.

## N+1 Elimination

21. N+1 example: `$users = User::all(); foreach ($users as $user) { echo $user->profile->bio; }` - queries 1 user list + N queries for each profile. Solution: `User::with('profile')->get()`.

22. Eager loading syntax: `Model::with('relationship')->get()`, `->with(['rel1', 'rel2'])` for multiple, `->with(['rel1' => function($q) { $q->where(...); }])` for filtering.

23. Lazy eager loading: when relationship not specified upfront, call `->load('relationship')` on collection. Less efficient but useful when relationship needed conditionally.

24. Nested relationships: `User::with('posts.comments')->get()` loads users, their posts, and comments on those posts. Depth matters - too deep = memory overhead.

25. Filtering eager loaded relationships: `User::with(['posts' => fn($q) => $q->where('published', true)])->get()`. Constraint closure filters before loading.

26. Counting relationships without loading: `User::withCount('posts')->get()` adds `posts_count` attribute without loading post records. Efficient for displaying counts.

27. Relationship existence checking: `User::has('posts')->get()` returns only users with posts. `whereHas('posts', fn($q) => $q->where('published', true))` - has with constraint.

28. Chunking large result sets: `User::chunk(100, function ($users) { foreach ($users as $user) { /* process */ } })`. Prevents memory exhaustion on large datasets.

29. Cursor pagination for large sets: `User::orderBy('id')->cursorPaginate(100)` better than offset pagination for large tables. More efficient with Limit + Order By.

30. Relationship counts in single query: `User::withCount(['posts', 'comments'])->get()` - adds both counts in one query instead of separate queries.

## Query Optimization

31. Use raw SQL only when necessary: `select(DB::raw('CONCAT(first_name, " ", last_name) AS full_name'))`. For complex logic, consider application-level processing instead.

32. Batch updates more efficient than individual: `User::whereIn('id', [1,2,3])->update(['status' => 'active'])` beats looping and updating each individually.

33. Bulk insert faster than looping: `User::insert($records)` inserts 1000 rows in single query, not 1000 queries. Use `insertOrIgnore()` for duplicates.

34. Use database transactions for multi-step operations: `DB::transaction(fn() => { ... })` ensures atomicity - either all succeed or all rollback.

35. Delete optimization: `User::where('inactive_since', '<', now()->subYear())->delete()` in chunks to avoid locking entire table. Use soft deletes `->delete()` if available.

36. Select only needed columns: `User::select('id', 'email', 'name')->get()` faster than `SELECT *`, especially with large tables or many columns.

37. Index hidden in ORDER BY: use `orderBy()` with indexed columns. Example: `User::orderBy('created_at')->get()` uses index on `created_at`.

38. Avoid correlated subqueries: `SELECT id FROM users WHERE id NOT IN (SELECT user_id FROM posts)` - slow for large tables. Use LEFT JOIN instead: `LEFT JOIN posts ON users.id = posts.user_id WHERE posts.id IS NULL`.

39. Use DISTINCT sparingly: `User::select('email')->distinct()->get()` uses database resources. Better: application-level deduplication if possible.

40. Optimize WHERE clauses: put most selective conditions first. Indexed columns before non-indexed. Example: `where('status', 'active')->where('email', $email)` - status might be less selective, put email first if indexed.

## Connection Pooling & Replication

41. Connection pooling concept: maintain pool of persistent database connections, reuse across requests. Reduces overhead of new connection handshakes (TCP, auth, TLS).

42. Configure connection pool: in `config/database.php`, set `connections.mysql.options.PDO::ATTR_MAX_ALLOWED_PACKET`. For connection pooling, use external tool (PgBouncer, ProxySQL).

43. ProxySQL for MySQL: reverse proxy pooling connections. Config file specifies `admin-username`, `admin-password`, pooled backends. Supports connection multiplexing, read/write splitting.

44. PgBouncer for PostgreSQL: lightweight connection pooler. Session pooling (one connection per session), transaction pooling (connection per transaction), statement pooling (most efficient).

45. Read replicas: primary database handles writes, read replicas handle reads. Configure in Laravel: `config/database.php` defines read and write connections separately.

46. Read-write splitting: route writes to primary, reads to replica. `Connection::read()` for read queries, `Connection::write()` for write queries. Useful for scaling read-heavy applications.

47. Replication lag: replica may be behind primary. For strong consistency, read from primary after write. For eventual consistency, accept stale reads from replica.

48. Connection retry logic: retry failed connections with exponential backoff. Handles temporary network issues, server restarts. Built into most connection poolers.

49. Connection timeout: set appropriate timeout to fail fast instead of hanging. Too short causes false failures, too long causes slow error handling.

50. Monitoring pool health: track active connections, idle connections, wait time for connection from pool. Alert if connections exhausted (connection leak, high load).

## Redis Caching

51. Redis as cache: in-memory data store, extremely fast, supports expiration, atomic operations. Better than file-based caching for high-traffic applications.

52. Configure Redis: in `config/database.php`, set Redis host, port, database, password. Laravel handles connection pooling automatically.

53. Cache operations: `Cache::put('key', $value, $minutes)`, `Cache::get('key')`, `Cache::forget('key')`, `Cache::flush()` clears all.

54. Cache helpers: `cache('key', default)` for get with default, `cache()->put('key', $value)` for put. Shorter syntax for common operations.

55. Atomic operations: `Cache::increment('counter')` and `Cache::decrement('counter')` are atomic in Redis, no race conditions.

56. Cache tagging: `Cache::tags(['users', 'orders'])->put('user:1', $data)`. Invalidate by tag: `Cache::tags(['users'])->flush()` clears all tagged entries.

57. Cache versioning: prefix keys with version: `cache()->put("v1:user:1", $data)`. Change prefix to invalidate all, simpler than tagging for major invalidations.

58. Cache stampede prevention: use `lockForUpdate()` or `cache()->lock()` to ensure only one process regenerates cache. Others wait: `Cache::lock('key')->block(10);`

59. Distributed cache: share Redis instance across multiple servers. Ensure all servers can reach Redis. Use in microservices for shared session, user data caching.

60. Cache warming: pre-load frequently accessed data into cache on application start or scheduled job. Reduces latency for first requests, ensures cache always has data.

## Query Builder & Eloquent

61. Query builder basic: `DB::table('users')->where('active', true)->get()`. Fluent interface chains method calls. Returns collection of results.

62. WHERE clauses: `->where('age', '>', 18)`, `->where('status', 'active')`, `->whereIn('role', ['admin', 'editor'])`, `->whereBetween('age', [18, 65])`.

63. OR conditions: `->where('status', 'active')->orWhere('admin', true)`. Grouping: `->where(fn($q) => $q->where('name', 'John')->orWhere('name', 'Jane'))`.

64. Advanced WHERE: `->whereNull('deleted_at')`, `->whereNotNull('email')`, `->whereDate('created_at', '2024-01-01')`, `->whereYear('created_at', 2024)`.

65. LIKE queries: `->where('name', 'like', '%john%')` (case-insensitive), `->where('name', 'like', 'john%')` for prefix search, `->where('name', 'like', '%john')` for suffix.

66. EXISTS queries: `->whereExists(fn($q) => $q->select(DB::raw(1))->from('posts')->where('posts.user_id', 'users.id'))` - finds users with posts.

67. Ordering: `->orderBy('created_at', 'desc')`, `->orderByDesc('name')`, `->latest('updated_at')` shorthand for order by desc. Can chain multiple: `->orderBy('status')->orderByDesc('created_at')`.

68. Grouping: `->groupBy('status')->select('status', DB::raw('COUNT(*) as total'))->get()` - groups by status, counts. Usually paired with aggregates.

69. HAVING clauses: `->having('total', '>', 5)` filters grouped results after grouping. Example: `->groupBy('category')->having(DB::raw('COUNT(*)'), '>', 5)->get()`.

70. Limit and offset: `->limit(10)`, `->offset(20)` - pagination building blocks. `->take(10)->skip(20)` aliases. Or use `->paginate(10)` for page-based pagination.

71. JOIN queries: `->join('profiles', 'users.id', '=', 'profiles.user_id')`, `->leftJoin()` for outer join. Specify all columns: `->select('users.*', 'profiles.bio')`.

72. UNION queries: `User::where('active', true)->union(User::where('role', 'admin'))->get()` - combines results from two queries.

73. Subqueries: `User::where('id', '>', function($q) { $q->selectRaw('avg(age)')->from('users'); })->get()` - users older than average age.

74. Distinct: `->distinct()->select('email')->get()` gets unique emails. Expensive operation, use sparingly.

75. Plucking: `->pluck('email')` returns collection of single column values. `->pluck('name', 'id')` returns key-value pairs (id => name).

## Eloquent ORM

76. Eloquent model: represents table row, provides OOP interface for database. Define relationships, scopes, accessors/mutators. `$user->email` accesses column, `$user->posts` accesses relationship.

77. Model relationships: `hasOne`, `hasMany`, `belongsTo`, `belongsToMany`. Define in model: `public function posts() { return $this->hasMany(Post::class); }`. Access: `$user->posts`.

78. Mass assignment: `User::create($attributes)` fills model with attributes. Define `$fillable` array of allowed columns for security: `protected $fillable = ['name', 'email'];`. Alternative: `$guarded = ['id', 'created_at'];` denies these.

79. Scopes: reusable query constraints. Global scope: `protected static function booted() { static::addGlobalScope(...); }` applies to all queries. Local scope: `public function scopeActive($query) { return $query->where('active', true); }` - use as `User::active()->get()`.

80. Mutators: transform attribute on set/get. Using accessors (get) and mutators (set): `public function setEmailAttribute($value) { $this->attributes['email'] = strtolower($value); }`.

81. Computed properties: `protected $appends = ['full_name'];` - includes in to Array/JSON. Define: `public function getFullNameAttribute() { return $this->first_name . ' ' . $this->last_name; }`.

82. Timestamps: `created_at` and `updated_at` managed automatically. Disable with `public $timestamps = false;`. Customize column names: `const CREATED_AT = 'creation_date';`.

83. Soft deletes: `SoftDeletes` trait adds `deleted_at` column. `delete()` sets timestamp, doesn't remove row. `restore()` clears timestamp. Query deleted: `->withTrashed()->get()`, `->onlyTrashed()->get()`.

84. Model factories: create test data. Define factory: `User::factory()->create()` inserts into database. `User::factory(5)->create()` creates 5. Use `make()` instead to get instances without saving.

85. Model observers: respond to model events. `created`, `updated`, `deleted`, `restored`. Define: `class UserObserver { public function created(User $user) { ... } }`. Register in service provider.

86. Blade integration: `@foreach ($users as $user) {{ $user->name }} @endforeach`. Automatic HTML escaping prevents XSS. Use `{!! $html !!}` to output raw HTML if trusted.

87. JSON columns: `cast('attrs', 'json')` on `users.attrs` JSON column. Access: `$user->attrs['key']`, mutate: `$user->attrs = ['new' => 'data']`, save: `$user->save()`.

88. Collection methods: `->first()`, `->last()`, `->pluck('name')`, `->map(fn($u) => $u->email)`, `->filter(fn($u) => $u->active)`, `->groupBy('status')`, `->sum('age')`.

89. Pagination: `->paginate(15)` returns paginator object with data, links, metadata. In view: `{{ $users->links() }}` renders pagination links. Custom links: `->links('pagination::bootstrap-4')`.

90. Model relationships loading: `Model::with('relationship')->get()`, `Model::load('relationship')` loads after retrieval. `Model::find($id)->posts` lazy loads relationship (N+1 if in loop).

## Query Performance

91. Slow query log: enable in MySQL with `slow_query_log = 1`, `long_query_time = 2` (seconds). Logs queries exceeding threshold. Analyze with `mysqldumpslow` tool.

92. EXPLAIN output: `id` (execution order), `type` (access method - ALL is full table scan, const/eq_ref are best), `possible_keys` (usable indexes), `key` (used index), `rows` (estimated matched rows).

93. Index fragmentation: rebuild fragmented indexes with `OPTIMIZE TABLE users;` (MyISAM, InnoDB can rebuild online). Reduces query time, disk space.

94. Statistics analysis: update table statistics so query optimizer makes good choices: `ANALYZE TABLE users;`. Crucial after large data changes.

95. Join order matters: put tables with smallest result sets first. Query optimizer usually figures this out, but explicit ordering helps with complex queries.

96. Covering index: index contains all columns needed for query, no table lookup required (fastest). Example: `INDEX (user_id, created_at, status)` covers query selecting those columns with those conditions.

97. NULL handling in indexes: NULL values indexed separately, affects query planning. `IS NULL` uses index, but less efficient than value comparison.

98. Full-text indexes: `FULLTEXT INDEX` for text search on large text fields. Query: `->whereFullText('content', 'search phrase')`. Faster than LIKE for text search.

99. Multi-column indexes vs single: `INDEX (col1, col2)` covers more query patterns than separate indexes on col1 and col2. Trade-off: slower insertions, larger indexes.

100. Query caching: Redis/Memcached above database. Time-to-live determines freshness. Invalidate when data changes. Stale reads acceptable for some queries (product catalog, but not inventory).

