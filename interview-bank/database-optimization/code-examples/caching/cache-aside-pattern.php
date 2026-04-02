<?php

/**
 * Cache-Aside Pattern Examples for Banking Applications
 * Also known as Lazy Loading pattern
 */

namespace App\Examples\Caching;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;

class CacheAsidePattern
{
    /**
     * Example 1: Basic Cache-Aside Pattern
     */
    public function basicCacheAside(string $userId): User
    {
        $cacheKey = "user:{$userId}";
        $ttl = 3600; // 1 hour
        
        // Try to get from cache first
        $user = Cache::get($cacheKey);
        
        if ($user === null) {
            // Cache miss - load from database
            $user = User::find($userId);
            
            if ($user) {
                // Store in cache for future requests
                Cache::put($cacheKey, $user, $ttl);
            }
        }
        
        return $user;
    }

    /**
     * Example 2: Cache-Aside with Laravel's remember() Method
     */
    public function cacheAsideWithRemember(string $userId): User
    {
        return Cache::remember("user:{$userId}", 3600, function () use ($userId) {
            return User::find($userId);
        });
    }

    /**
     * Example 3: Cache-Aside for Account Balance
     */
    public function getAccountBalance(string $accountId): float
    {
        $cacheKey = "account:balance:{$accountId}";
        
        return Cache::remember($cacheKey, 300, function () use ($accountId) {
            $account = Account::findOrFail($accountId);
            return $account->balance;
        });
    }

    /**
     * Example 4: Cache-Aside with Manual Invalidation
     */
    public function updateAccountBalance(string $accountId, float $newBalance): void
    {
        // Update database
        $account = Account::findOrFail($accountId);
        $account->balance = $newBalance;
        $account->save();
        
        // Invalidate cache
        Cache::forget("account:balance:{$accountId}");
        Cache::forget("account:{$accountId}");
        
        // Optionally, warm the cache immediately
        Cache::put("account:balance:{$accountId}", $newBalance, 300);
    }

    /**
     * Example 5: Cache-Aside for Transaction History
     */
    public function getRecentTransactions(string $accountId, int $limit = 10): array
    {
        $cacheKey = "account:{$accountId}:transactions:recent:{$limit}";
        
        return Cache::remember($cacheKey, 600, function () use ($accountId, $limit) {
            return Transaction::where('account_id', $accountId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Example 6: Cache-Aside with Cache Stampede Prevention
     * Uses locking to prevent multiple processes from regenerating cache simultaneously
     */
    public function cacheAsideWithLock(string $accountId): Account
    {
        $cacheKey = "account:{$accountId}";
        $lockKey = "lock:{$cacheKey}";
        
        // Try to get from cache
        $account = Cache::get($cacheKey);
        
        if ($account === null) {
            // Acquire lock to prevent stampede
            $lock = Cache::lock($lockKey, 10); // 10 second lock
            
            try {
                if ($lock->get()) {
                    // Double-check cache (another process might have filled it)
                    $account = Cache::get($cacheKey);
                    
                    if ($account === null) {
                        // Load from database
                        $account = Account::with('user')->findOrFail($accountId);
                        
                        // Store in cache
                        Cache::put($cacheKey, $account, 600);
                    }
                }
            } finally {
                $lock->release();
            }
        }
        
        return $account;
    }

    /**
     * Example 7: Cache-Aside with Fallback Strategy
     */
    public function cacheAsideWithFallback(string $userId): ?User
    {
        $cacheKey = "user:{$userId}";
        
        try {
            // Try primary cache (Redis)
            $user = Cache::store('redis')->get($cacheKey);
            
            if ($user === null) {
                // Try database
                $user = User::find($userId);
                
                if ($user) {
                    // Repopulate cache
                    Cache::store('redis')->put($cacheKey, $user, 3600);
                }
            }
            
            return $user;
        } catch (\Exception $e) {
            // If cache fails, go directly to database
            \Log::warning("Cache failure, falling back to database", [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            
            return User::find($userId);
        }
    }

    /**
     * Example 8: Multi-Layer Cache-Aside
     */
    public function multiLayerCacheAside(string $accountId): Account
    {
        $cacheKey = "account:{$accountId}";
        
        // L1: Application memory cache (within request)
        static $memoryCache = [];
        
        if (isset($memoryCache[$cacheKey])) {
            return $memoryCache[$cacheKey];
        }
        
        // L2: Redis cache
        $account = Cache::store('redis')->get($cacheKey);
        
        if ($account === null) {
            // L3: Database
            $account = Account::with('user')->findOrFail($accountId);
            
            // Populate Redis
            Cache::store('redis')->put($cacheKey, $account, 600);
        }
        
        // Populate memory cache
        $memoryCache[$cacheKey] = $account;
        
        return $account;
    }

    /**
     * Example 9: Cache-Aside with Probabilistic Early Expiration
     * Prevents stampede by randomly refreshing before TTL expires
     */
    public function cacheAsideWithEarlyExpiration(string $accountId): Account
    {
        $cacheKey = "account:{$accountId}";
        $ttl = 600; // 10 minutes
        $delta = 60; // 1 minute early refresh window
        
        $cached = Cache::get($cacheKey . ':data');
        $expiresAt = Cache::get($cacheKey . ':expires');
        
        $now = time();
        
        // Probabilistic early expiration
        if ($cached && $expiresAt) {
            $timeUntilExpiry = $expiresAt - $now;
            $probability = $delta / $timeUntilExpiry;
            
            if ($probability >= 1 || mt_rand() / mt_getrandmax() < $probability) {
                // Refresh cache early
                $cached = null;
            }
        }
        
        if ($cached === null) {
            $account = Account::findOrFail($accountId);
            
            Cache::put($cacheKey . ':data', $account, $ttl);
            Cache::put($cacheKey . ':expires', $now + $ttl, $ttl);
            
            return $account;
        }
        
        return $cached;
    }

    /**
     * Example 10: Cache-Aside for Aggregated Data
     */
    public function getAccountSummary(string $accountId): array
    {
        $cacheKey = "account:{$accountId}:summary";
        
        return Cache::remember($cacheKey, 1800, function () use ($accountId) {
            $account = Account::findOrFail($accountId);
            
            return [
                'balance' => $account->balance,
                'transaction_count' => $account->transactions()->count(),
                'last_transaction' => $account->transactions()
                    ->latest()
                    ->first(),
                'monthly_spending' => $account->transactions()
                    ->where('type', 'debit')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount'),
            ];
        });
    }

    /**
     * Example 11: Cache-Aside with Partial Data
     * Cache different parts with different TTLs
     */
    public function getUserDashboardData(string $userId): array
    {
        // Frequently changing data - short TTL
        $balance = Cache::remember(
            "user:{$userId}:balance",
            60, // 1 minute
            fn() => Account::where('user_id', $userId)->sum('balance')
        );
        
        // Rarely changing data - long TTL
        $userInfo = Cache::remember(
            "user:{$userId}:info",
            3600, // 1 hour
            fn() => User::find($userId)
        );
        
        // Medium frequency - medium TTL
        $recentActivity = Cache::remember(
            "user:{$userId}:recent_activity",
            300, // 5 minutes
            fn() => Transaction::whereHas('account', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->latest()->limit(5)->get()
        );
        
        return [
            'user' => $userInfo,
            'total_balance' => $balance,
            'recent_activity' => $recentActivity,
        ];
    }

    /**
     * Example 12: Cache-Aside with Tags (for Group Invalidation)
     */
    public function cacheAsideWithTags(string $accountId): Account
    {
        $account = Cache::tags(['accounts', "user:{$this->getUserId($accountId)}"])
            ->remember("account:{$accountId}", 600, function () use ($accountId) {
                return Account::with('user')->findOrFail($accountId);
            });
        
        return $account;
    }

    /**
     * Invalidate all caches for a user
     */
    public function invalidateUserCaches(string $userId): void
    {
        Cache::tags(["user:{$userId}"])->flush();
    }

    /**
     * Example 13: Cache-Aside with Compression
     */
    public function cacheAsideWithCompression(string $accountId): array
    {
        $cacheKey = "account:{$accountId}:transactions:all";
        
        $compressed = Cache::get($cacheKey);
        
        if ($compressed === null) {
            $transactions = Transaction::where('account_id', $accountId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
            
            // Compress before storing
            $compressed = gzcompress(json_encode($transactions));
            Cache::put($cacheKey, $compressed, 3600);
            
            return $transactions;
        }
        
        // Decompress
        return json_decode(gzuncompress($compressed), true);
    }

    /**
     * Example 14: Cache-Aside with Monitoring
     */
    public function cacheAsideWithMonitoring(string $key, callable $callback, int $ttl = 600)
    {
        $startTime = microtime(true);
        $hit = true;
        
        $value = Cache::get($key);
        
        if ($value === null) {
            $hit = false;
            $value = $callback();
            Cache::put($key, $value, $ttl);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Log metrics
        \Log::info('Cache operation', [
            'key' => $key,
            'hit' => $hit,
            'duration_ms' => round($duration * 1000, 2),
        ]);
        
        // Send to monitoring system (e.g., Prometheus, CloudWatch)
        if ($hit) {
            $this->incrementCacheHitCounter();
        } else {
            $this->incrementCacheMissCounter();
        }
        
        return $value;
    }

    /**
     * Example 15: Cache-Aside for Rate Limiting Data
     */
    public function checkRateLimit(string $userId, string $action): bool
    {
        $cacheKey = "rate_limit:{$userId}:{$action}";
        $limit = 100; // 100 requests
        $window = 3600; // per hour
        
        $count = Cache::get($cacheKey, 0);
        
        if ($count >= $limit) {
            return false; // Rate limit exceeded
        }
        
        // Increment counter
        Cache::increment($cacheKey);
        
        // Set expiration on first increment
        if ($count === 0) {
            Cache::expire($cacheKey, $window);
        }
        
        return true;
    }

    /**
     * Example 16: Cache Warming Strategy
     */
    public function warmCache(): void
    {
        // Pre-populate cache with frequently accessed data
        $popularAccounts = Account::where('status', 'active')
            ->orderBy('last_transaction_at', 'desc')
            ->limit(1000)
            ->get();
        
        foreach ($popularAccounts as $account) {
            $cacheKey = "account:{$account->id}";
            Cache::put($cacheKey, $account, 600);
        }
        
        \Log::info("Cache warmed with {$popularAccounts->count()} accounts");
    }

    /**
     * Example 17: Cache-Aside with Null Object Pattern
     */
    public function cacheAsideWithNullHandling(string $accountId): ?Account
    {
        $cacheKey = "account:{$accountId}";
        $nullKey = "account:{$accountId}:null";
        
        // Check if we've cached that this doesn't exist
        if (Cache::has($nullKey)) {
            return null;
        }
        
        return Cache::remember($cacheKey, 600, function () use ($accountId, $nullKey) {
            $account = Account::find($accountId);
            
            if ($account === null) {
                // Cache the fact that it doesn't exist (short TTL)
                Cache::put($nullKey, true, 60);
            }
            
            return $account;
        });
    }

    /**
     * Example 18: Complete Banking API Endpoint with Cache-Aside
     */
    public function getAccountDetails(string $accountId): array
    {
        // Cache account data
        $account = Cache::remember(
            "account:{$accountId}",
            600,
            fn() => Account::with('user')->findOrFail($accountId)
        );
        
        // Cache balance separately (more frequent updates)
        $balance = Cache::remember(
            "account:{$accountId}:balance",
            60,
            fn() => Account::where('id', $accountId)->value('balance')
        );
        
        // Cache recent transactions
        $transactions = Cache::remember(
            "account:{$accountId}:transactions:recent",
            300,
            fn() => Transaction::where('account_id', $accountId)
                ->with('merchant')
                ->latest()
                ->limit(10)
                ->get()
        );
        
        // Cache monthly summary
        $monthlySummary = Cache::remember(
            "account:{$accountId}:summary:" . now()->format('Y-m'),
            1800,
            fn() => [
                'total_debits' => Transaction::where('account_id', $accountId)
                    ->where('type', 'debit')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount'),
                'total_credits' => Transaction::where('account_id', $accountId)
                    ->where('type', 'credit')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount'),
            ]
        );
        
        return [
            'account' => $account,
            'balance' => $balance,
            'transactions' => $transactions,
            'monthly_summary' => $monthlySummary,
        ];
    }

    // Helper methods
    private function getUserId(string $accountId): string
    {
        return Account::where('id', $accountId)->value('user_id');
    }

    private function incrementCacheHitCounter(): void
    {
        // Implementation depends on monitoring system
    }

    private function incrementCacheMissCounter(): void
    {
        // Implementation depends on monitoring system
    }
}
