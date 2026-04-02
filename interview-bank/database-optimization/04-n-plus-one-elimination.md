# Section 4: N+1 Query Elimination (Questions 351-450)

## Overview

This section covers comprehensive strategies for eliminating N+1 query problems in Laravel applications. N+1 queries are one of the most common performance bottlenecks in banking applications, often causing 80-95% degradation in API response times.

---

## N+1 Problem Understanding (Questions 351-370)

351. Explain the N+1 query problem with a practical example.
352. Write code demonstrating an N+1 problem in Laravel Eloquent.
353. How does the N+1 problem impact database performance?
354. What is the performance difference between N+1 queries and a single JOIN?
355. Explain how ORMs like Eloquent contribute to N+1 problems.
356. Write a Laravel query that avoids N+1 when loading user posts and comments.
357. How do you detect N+1 queries in production?
358. What tools can identify N+1 problems during development?
359. Explain the memory vs query count trade-off in N+1 solutions.
360. Write a benchmark comparing N+1 queries vs eager loading.
361. How does the N+1 problem affect API response times?
362. What is the impact of N+1 on database connection pools?
363. Explain N+1 in the context of GraphQL APIs.
364. Write code to demonstrate the N+1 problem with nested relationships.
365. How do you educate developers about avoiding N+1 queries?
366. What are the common scenarios where N+1 occurs?
367. Explain the relationship between N+1 and database connection exhaustion.
368. Write monitoring queries to detect N+1 patterns in slow query logs.
369. How does N+1 impact horizontal scaling of applications?
370. What are the worst-case scenarios of N+1 in banking systems?

### N+1 Problem Demonstration

**The Problem:**
```php
// Banking API: Get accounts with recent transactions
// ❌ N+1 PROBLEM - BAD PRACTICE

class AccountController extends Controller
{
    public function index()
    {
        // Query 1: Fetch all accounts
        $accounts = Account::where('status', 'active')->get();  // 1 query
        
        $result = [];
        foreach ($accounts as $account) {
            // Query 2, 3, 4, ... N+1: One query PER account
            $recentTransactions = $account->transactions()
                ->where('transaction_date', '>=', now()->subDays(30))
                ->get();
            
            $result[] = [
                'account_number' => $account->account_number,
                'balance' => $account->balance,
                'recent_transactions' => $recentTransactions,
                'transaction_count' => $recentTransactions->count(),
            ];
        }
        
        return response()->json($result);
    }
}
```

**Query Log:**
```sql
-- Query 1
SELECT * FROM accounts WHERE status = 'active';  -- Returns 1,000 accounts

-- Query 2
SELECT * FROM transactions 
WHERE account_id = 1 AND transaction_date >= '2024-01-01';

-- Query 3
SELECT * FROM transactions 
WHERE account_id = 2 AND transaction_date >= '2024-01-01';

-- Query 4
SELECT * FROM transactions 
WHERE account_id = 3 AND transaction_date >= '2024-01-01';

-- ... repeats 997 more times ...

-- Query 1001
SELECT * FROM transactions 
WHERE account_id = 1000 AND transaction_date >= '2024-01-01';
```

**Performance Impact:**
```
Total Queries: 1,001
Database Time: 15,234 ms (15.2 seconds)
API Response Time: 16,890 ms (16.9 seconds)
Connection Pool: 1,001 connections acquired
```

---

## Eager Loading Strategies (Questions 371-400)

371. Explain eager loading and how it solves the N+1 problem.
372. Write a Laravel query using with() for eager loading relationships.
373. What is the difference between eager loading and lazy loading?
374. How do you eager load nested relationships in Eloquent?
375. Explain the load() method vs with() method in Laravel.
376. Write a query to eager load multiple relationships at different depths.
377. What is constraint eager loading in Laravel?
378. How do you conditionally eager load relationships?
379. Explain the performance implications of over-eager loading.
380. Write a query to eager load relationships with specific columns.
381. What is lazy eager loading in Laravel?
382. How do you prevent lazy loading in production using Model::preventLazyLoading()?
383. Explain the withCount() method and its use cases.
384. Write a query to eager load relationship counts efficiently.
385. What is the difference between with() and load() in terms of query timing?
386. How do you eager load polymorphic relationships?
387. Explain morph eager loading with morphTo() and morphMany().
388. Write a query to eager load relationships with pivot data.
389. What is the impact of eager loading on memory consumption?
390. How do you balance eager loading vs memory usage for large datasets?
391. Explain the withAggregate() method in Laravel.
392. Write a query using withMin(), withMax(), withSum() for aggregates.
393. How do you eager load relationships with where clauses?
394. What is the difference between whereHas() and whereRelation()?
395. Explain eager loading with global scopes.
396. Write a query to eager load soft-deleted relationships.
397. How do you handle circular relationship eager loading?
398. What are the best practices for eager loading in REST APIs?
399. Explain the performance monitoring of eager loaded queries.
400. Write a custom eager loading strategy for complex scenarios.

### Solution: Eager Loading

```php
// ✅ SOLUTION - BEST PRACTICE

class AccountController extends Controller
{
    public function index()
    {
        // Single query with JOIN
        $accounts = Account::where('status', 'active')
            ->with(['transactions' => function ($query) {
                $query->where('transaction_date', '>=', now()->subDays(30))
                      ->select('id', 'account_id', 'amount', 'transaction_date', 'description');
            }])
            ->withCount(['transactions as recent_transaction_count' => function ($query) {
                $query->where('transaction_date', '>=', now()->subDays(30));
            }])
            ->get(['id', 'account_number', 'balance']);
        
        $result = $accounts->map(function ($account) {
            return [
                'account_number' => $account->account_number,
                'balance' => $account->balance,
                'recent_transactions' => $account->transactions,
                'transaction_count' => $account->recent_transaction_count,
            ];
        });
        
        return response()->json($result);
    }
}
```

**Query Log (Optimized):**
```sql
-- Query 1: Fetch accounts
SELECT id, account_number, balance 
FROM accounts 
WHERE status = 'active';  -- Returns 1,000 accounts

-- Query 2: Eager load transactions (single query!)
SELECT id, account_id, amount, transaction_date, description
FROM transactions
WHERE account_id IN (1, 2, 3, ..., 1000)
  AND transaction_date >= '2024-01-01';

-- Query 3: Count transactions (single query!)
SELECT account_id, COUNT(*) as recent_transaction_count
FROM transactions
WHERE account_id IN (1, 2, 3, ..., 1000)
  AND transaction_date >= '2024-01-01'
GROUP BY account_id;
```

**Performance Impact (Optimized):**
```
Total Queries: 3 (reduced from 1,001)
Database Time: 234 ms (reduced from 15,234 ms)
API Response Time: 456 ms (reduced from 16,890 ms)
Connection Pool: 3 connections (reduced from 1,001)

IMPROVEMENT: 97.3% reduction in response time (16,890ms → 456ms)
```

### Advanced Eager Loading Patterns

**Nested Relationships:**
```php
// Eager load 3 levels deep
$accounts = Account::with([
    'transactions.merchant',
    'transactions.categories',
    'customer.profile',
    'customer.addresses'
])->get();

// Generated queries: 5 total instead of N×M×P
```

**Conditional Eager Loading:**
```php
$accounts = Account::with([
    'transactions' => function ($query) {
        $query->where('amount', '>', 1000)
              ->where('status', 'completed')
              ->orderBy('transaction_date', 'desc')
              ->limit(10);
    }
])->get();
```

**Polymorphic Eager Loading:**
```php
// Morphable notifications
$notifications = Notification::with('notifiable')
    ->get();

// Optimize morphTo eager loading
$notifications = Notification::with('notifiable')
    ->get()
    ->load('notifiable.account', 'notifiable.customer');
```

---

## DataLoader Pattern (Questions 401-420)

401. Explain the DataLoader pattern and its origin in GraphQL.
402. Write a basic DataLoader implementation in PHP.
403. How does DataLoader batch and cache requests?
404. What is the difference between DataLoader and eager loading?
405. Implement a DataLoader for loading users by ID in batches.
406. Explain the batching window in DataLoader.
407. How does DataLoader handle errors in batched requests?
408. Write a DataLoader for a many-to-many relationship.
409. What is the cache strategy in DataLoader?
410. How do you implement DataLoader in a Laravel application?
411. Explain the per-request caching in DataLoader.
412. Write a DataLoader with custom cache key function.
413. How does DataLoader prevent N+1 in GraphQL resolvers?
414. What are the limitations of the DataLoader pattern?
415. Implement a DataLoader with Redis caching.
416. Explain the difference between DataLoader and query batching.
417. How do you test DataLoader implementations?
418. Write a DataLoader for polymorphic relationships.
419. What is the performance comparison between DataLoader and eager loading?
420. How do you monitor DataLoader effectiveness in production?

### DataLoader Implementation

```php
<?php

namespace App\DataLoaders;

class TransactionDataLoader
{
    private array $queue = [];
    private array $cache = [];
    private bool $dispatched = false;
    
    public function load(int $accountId): Promise
    {
        // Check cache first
        if (isset($this->cache[$accountId])) {
            return new FulfilledPromise($this->cache[$accountId]);
        }
        
        // Add to queue
        if (!isset($this->queue[$accountId])) {
            $this->queue[$accountId] = new Deferred();
        }
        
        // Schedule batch execution
        if (!$this->dispatched) {
            $this->dispatched = true;
            $this->scheduleBatch();
        }
        
        return $this->queue[$accountId]->promise();
    }
    
    private function scheduleBatch(): void
    {
        // Execute on next tick (after current execution completes)
        defer(function () {
            $this->executeBatch();
        });
    }
    
    private function executeBatch(): void
    {
        $accountIds = array_keys($this->queue);
        
        if (empty($accountIds)) {
            return;
        }
        
        // Single batched query
        $transactions = DB::table('transactions')
            ->whereIn('account_id', $accountIds)
            ->where('transaction_date', '>=', now()->subDays(30))
            ->get()
            ->groupBy('account_id');
        
        // Fulfill all promises
        foreach ($accountIds as $accountId) {
            $result = $transactions->get($accountId, collect());
            $this->cache[$accountId] = $result;
            $this->queue[$accountId]->resolve($result);
        }
        
        // Reset for next batch
        $this->queue = [];
        $this->dispatched = false;
    }
}

// Usage in GraphQL resolver
class AccountResolver
{
    public function transactions(Account $account, TransactionDataLoader $loader)
    {
        return $loader->load($account->id);
    }
}
```

**Performance:**
```
Without DataLoader: 1,000 queries for 1,000 accounts
With DataLoader: 1 batched query for all accounts
Improvement: 99.9% query reduction
```

---

## Query Batching Techniques (Questions 421-450)

421. Explain query batching and how it differs from eager loading.
422. Write code to batch multiple SELECT queries into one.
423. How does Laravel's lazy collections help with N+1?
424. What is the chunk() method and when should you use it?
425. Explain the lazyById() method for memory-efficient iteration.
426. Write code to batch insert operations in Laravel.
427. How do you batch update queries efficiently?
428. What is the upsert() method in Laravel and its performance benefits?
429. Explain the performance trade-offs of batching.
430. Write code to batch relationship loading manually.
431. How do you implement custom batch loading in repositories?
432. What is the optimal batch size for different scenarios?
433. Explain the relationship between batch size and memory usage.
434. Write code to batch API calls that trigger database queries.
435. How do you handle errors in batched operations?
436. What is the cursor() method in Laravel and its use cases?
437. Explain lazy collection chunking vs eager collection chunking.
438. Write code to batch process transactions with rollback handling.
439. How do you implement batch loading in microservices?
440. What are the monitoring strategies for batched queries?
441. Explain the impact of batching on transaction isolation.
442. Write code to batch load with custom query optimization.
443. How do you batch queries across different database connections?
444. What is the relationship between batching and connection pooling?
445. Explain batching strategies for real-time systems.
446. Write code to implement adaptive batch sizing.
447. How do you benchmark batching performance improvements?
448. What are the pitfalls of excessive batching?
449. Explain batching in the context of event-driven architectures.
450. Write a comprehensive N+1 elimination strategy for a banking API.

### Batching Techniques

**Chunk Processing:**
```php
// ❌ BAD: Loads all 10M records into memory
$transactions = Transaction::all();
foreach ($transactions as $transaction) {
    $this->processTransaction($transaction);
}
// Memory: 8 GB

// ✅ GOOD: Process in chunks of 1000
Transaction::chunk(1000, function ($transactions) {
    foreach ($transactions as $transaction) {
        $this->processTransaction($transaction);
    }
});
// Memory: 80 MB (100x reduction)
```

**Lazy Collections:**
```php
// ✅ BEST: Stream one at a time
Transaction::lazy()->each(function ($transaction) {
    $this->processTransaction($transaction);
});
// Memory: Constant (8 KB per record)

// Or use lazyById for better performance
Transaction::lazyById(1000)->each(function ($transaction) {
    $this->processTransaction($transaction);
});
```

**Batch Inserts:**
```php
// ❌ BAD: 10,000 individual INSERT statements
foreach ($records as $record) {
    Transaction::create($record);  // 10,000 queries
}
// Time: 45 seconds

// ✅ GOOD: Single INSERT with multiple rows
Transaction::insert($records);  // 1 query
// Time: 1.2 seconds (97% improvement)

// ✅ BEST: Upsert for insert or update
Transaction::upsert(
    $records,
    ['transaction_id'],  // Unique columns
    ['amount', 'status'] // Columns to update on conflict
);
// Time: 1.5 seconds + handles duplicates
```

**Batch Updates:**
```php
// ❌ BAD: Update one by one
foreach ($accountIds as $accountId) {
    Account::where('id', $accountId)->update(['status' => 'active']);
}
// Time: 15 seconds for 5,000 accounts

// ✅ GOOD: Single UPDATE with WHERE IN
Account::whereIn('id', $accountIds)->update(['status' => 'active']);
// Time: 0.3 seconds (98% improvement)

// ✅ ADVANCED: Batch update with different values
DB::transaction(function () use ($updates) {
    foreach (array_chunk($updates, 500) as $batch) {
        $cases = [];
        $ids = [];
        
        foreach ($batch as $id => $amount) {
            $cases[] = "WHEN {$id} THEN {$amount}";
            $ids[] = $id;
        }
        
        $caseSql = implode(' ', $cases);
        $idsSql = implode(',', $ids);
        
        DB::update("
            UPDATE accounts 
            SET balance = CASE id {$caseSql} END 
            WHERE id IN ({$idsSql})
        ");
    }
});
// Time: 2.1 seconds for 50,000 records
```

### Complete N+1 Elimination Example

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use Illuminate\Http\JsonResponse;

class BankingDashboardController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $accounts = Account::query()
            // Eager load all relationships at once
            ->with([
                'customer:id,name,email',
                'transactions' => fn($q) => $q
                    ->where('transaction_date', '>=', now()->subDays(30))
                    ->select('id', 'account_id', 'amount', 'transaction_date', 'type')
                    ->latest()
                    ->limit(10),
                'transactions.merchant:id,name',
            ])
            // Eager load aggregate counts
            ->withCount([
                'transactions as total_transactions',
                'transactions as pending_transactions' => fn($q) => 
                    $q->where('status', 'pending'),
            ])
            // Eager load aggregate sums
            ->withSum([
                'transactions as total_amount' => fn($q) => 
                    $q->where('transaction_date', '>=', now()->subDays(30))
            ], 'amount')
            ->where('status', 'active')
            ->get();
        
        return response()->json([
            'accounts' => $accounts,
            'queries_executed' => DB::getQueryLog()->count(),
        ]);
    }
}

// Enable query logging in AppServiceProvider
DB::enableQueryLog();
```

**Result:**
```
Queries executed: 4 (instead of 1,000+)
Response time: 234 ms (instead of 15,000+ ms)
Improvement: 98.4% faster
```

---

## Performance Metrics

### N+1 Elimination Impact:

| Scenario | Before (N+1) | After (Eager Loading) | Improvement |
|----------|--------------|----------------------|-------------|
| 100 accounts | 101 queries, 1.2s | 2 queries, 0.05s | 95.8% |
| 1,000 accounts | 1,001 queries, 15.2s | 3 queries, 0.45s | 97.0% |
| 10,000 accounts | 10,001 queries, timeout | 4 queries, 2.3s | 99.9% |

### Banking Application Best Practices:

1. **Always eager load in API endpoints**: Use `with()` for all returned relationships
2. **Use withCount() for aggregates**: Avoid lazy counting in loops
3. **Enable preventLazyLoading() in development**: Catch N+1 during development
4. **Monitor query counts in production**: Alert when endpoints execute >10 queries
5. **Batch process background jobs**: Use `chunk()` or `lazy()` for large datasets

---

## Summary

This section covered 100 questions on N+1 query elimination with practical Laravel examples demonstrating:

- **97.3% response time reduction** through eager loading
- **99.9% query reduction** with DataLoader pattern
- **98.4% improvement** with batch operations
- **100x memory reduction** with lazy collections

N+1 elimination alone can contribute **4-8% to overall response time reduction** in banking APIs.
