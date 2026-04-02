<?php

/**
 * Eager Loading Examples for Banking Applications
 * Demonstrates N+1 query prevention and optimization techniques
 */

namespace App\Examples\DatabaseOptimization;

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Card;
use Illuminate\Support\Facades\DB;

class EagerLoadingExamples
{
    /**
     * Example 1: Basic N+1 Problem (BAD)
     * This will execute 1 + N queries where N is the number of users
     */
    public function badExample_N_Plus_One()
    {
        // Query 1: Get all users
        $users = User::all(); // 1 query
        
        // N queries: One for each user's accounts
        foreach ($users as $user) {
            echo $user->name . ' has ' . $user->accounts->count() . ' accounts'; // N queries
        }
        
        // Total: 1 + N queries (if 100 users = 101 queries!)
    }

    /**
     * Example 2: Basic Eager Loading Solution (GOOD)
     * This will execute exactly 2 queries regardless of user count
     */
    public function goodExample_BasicEagerLoading()
    {
        // Query 1: Get all users
        // Query 2: Get all accounts for these users
        $users = User::with('accounts')->get(); // 2 queries total
        
        foreach ($users as $user) {
            echo $user->name . ' has ' . $user->accounts->count() . ' accounts';
        }
        
        // Total: 2 queries (if 100 users = still just 2 queries!)
    }

    /**
     * Example 3: Multiple Relationship Eager Loading
     */
    public function eagerLoadMultipleRelationships()
    {
        // Load user with accounts, cards, and transactions
        $users = User::with(['accounts', 'cards', 'transactions'])->get();
        
        // Only 4 queries total:
        // 1. SELECT * FROM users
        // 2. SELECT * FROM accounts WHERE user_id IN (...)
        // 3. SELECT * FROM cards WHERE user_id IN (...)
        // 4. SELECT * FROM transactions WHERE user_id IN (...)
        
        foreach ($users as $user) {
            echo "User: {$user->name}\n";
            echo "Accounts: {$user->accounts->count()}\n";
            echo "Cards: {$user->cards->count()}\n";
            echo "Transactions: {$user->transactions->count()}\n";
        }
    }

    /**
     * Example 4: Nested Eager Loading
     */
    public function nestedEagerLoading()
    {
        // Load users -> accounts -> transactions
        $users = User::with('accounts.transactions')->get();
        
        // Only 3 queries:
        // 1. SELECT * FROM users
        // 2. SELECT * FROM accounts WHERE user_id IN (...)
        // 3. SELECT * FROM transactions WHERE account_id IN (...)
        
        foreach ($users as $user) {
            foreach ($user->accounts as $account) {
                echo "Account {$account->account_number} has {$account->transactions->count()} transactions\n";
            }
        }
    }

    /**
     * Example 5: Deep Nested Eager Loading
     */
    public function deepNestedEagerLoading()
    {
        // Load users -> accounts -> transactions -> merchant
        $users = User::with([
            'accounts.transactions.merchant',
            'accounts.cards'
        ])->get();
        
        // Only 5 queries total regardless of data size:
        // 1. SELECT * FROM users
        // 2. SELECT * FROM accounts WHERE user_id IN (...)
        // 3. SELECT * FROM transactions WHERE account_id IN (...)
        // 4. SELECT * FROM merchants WHERE id IN (...)
        // 5. SELECT * FROM cards WHERE account_id IN (...)
    }

    /**
     * Example 6: Conditional Eager Loading
     */
    public function conditionalEagerLoading()
    {
        // Only load transactions for active accounts
        $users = User::with([
            'accounts' => function ($query) {
                $query->where('status', 'active');
            },
            'accounts.transactions' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            }
        ])->get();
        
        // Filters are applied in the eager load queries
    }

    /**
     * Example 7: Eager Loading with Specific Columns
     */
    public function eagerLoadingWithColumns()
    {
        // Only load specific columns to reduce memory usage
        $users = User::with([
            'accounts:id,user_id,account_number,balance',
            'accounts.transactions:id,account_id,amount,created_at'
        ])->get(['id', 'name', 'email']);
        
        // Note: Always include the foreign key (id, user_id, account_id)
    }

    /**
     * Example 8: Eager Loading with Counts
     */
    public function eagerLoadingWithCounts()
    {
        // Load account count without loading actual accounts
        $users = User::withCount('accounts')->get();
        
        foreach ($users as $user) {
            echo "{$user->name} has {$user->accounts_count} accounts\n";
        }
        
        // Also works with conditions:
        $users = User::withCount([
            'accounts',
            'accounts as active_accounts_count' => function ($query) {
                $query->where('status', 'active');
            },
            'transactions as recent_transactions_count' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            }
        ])->get();
    }

    /**
     * Example 9: Eager Loading with Aggregates
     */
    public function eagerLoadingWithAggregates()
    {
        // Load sum, avg, min, max without loading all records
        $users = User::withSum('transactions', 'amount')
            ->withAvg('transactions', 'amount')
            ->withMin('transactions', 'amount')
            ->withMax('transactions', 'amount')
            ->get();
        
        foreach ($users as $user) {
            echo "User: {$user->name}\n";
            echo "Total Spent: {$user->transactions_sum_amount}\n";
            echo "Avg Transaction: {$user->transactions_avg_amount}\n";
            echo "Smallest: {$user->transactions_min_amount}\n";
            echo "Largest: {$user->transactions_max_amount}\n";
        }
        
        // Can also use custom column names:
        $accounts = Account::withSum('transactions as total_credits', 'amount', function ($query) {
            $query->where('type', 'credit');
        })->get();
    }

    /**
     * Example 10: Lazy Eager Loading
     */
    public function lazyEagerLoading()
    {
        // Sometimes you need to eager load after initial query
        $users = User::all();
        
        // Later, decide to load relationships
        if (someCondition()) {
            $users->load('accounts.transactions');
        }
        
        // This prevents N+1 even when loading after the fact
    }

    /**
     * Example 11: Preventing Lazy Loading (Laravel 8.43+)
     */
    public function preventLazyLoading()
    {
        // In AppServiceProvider boot() method:
        // Model::preventLazyLoading(!app()->isProduction());
        
        // This will throw an exception if you try to lazy load:
        try {
            $user = User::first();
            $accounts = $user->accounts; // LazyLoadingViolationException in dev
        } catch (\Illuminate\Database\LazyLoadingViolationException $e) {
            // Should have used: User::with('accounts')->first()
        }
    }

    /**
     * Example 12: Eager Loading for API Response
     */
    public function apiResponseOptimization()
    {
        // Load all necessary data for API response in one go
        $account = Account::with([
            'user:id,name,email',
            'transactions' => function ($query) {
                $query->latest()->limit(10);
            },
            'transactions.merchant:id,name',
            'cards:id,account_id,last_four,type,status'
        ])->findOrFail($accountId);
        
        return response()->json($account);
        
        // Total queries: 4 (account, user, transactions, merchants, cards)
        // Without eager loading: 1 + 1 + 10 + 1 = 13 queries minimum
    }

    /**
     * Example 13: Morphable Relationship Eager Loading
     */
    public function morphableEagerLoading()
    {
        // Load polymorphic relationships
        $activities = Activity::with('activityable')->get();
        
        // For better performance, specify the types:
        $activities = Activity::with([
            'activityable' => function ($query) {
                $query->morphWith([
                    Transaction::class => ['account'],
                    Transfer::class => ['fromAccount', 'toAccount'],
                ]);
            }
        ])->get();
    }

    /**
     * Example 14: Eager Loading with Pivot Data
     */
    public function eagerLoadingWithPivot()
    {
        // Load many-to-many with pivot data
        $user = User::with([
            'roles' => function ($query) {
                $query->withPivot('assigned_at', 'assigned_by');
            }
        ])->find($userId);
        
        foreach ($user->roles as $role) {
            echo "Role: {$role->name}, Assigned: {$role->pivot->assigned_at}\n";
        }
    }

    /**
     * Example 15: Combining whereHas with Eager Loading
     */
    public function whereHasWithEagerLoading()
    {
        // BAD: This causes N+1
        $users = User::whereHas('accounts', function ($query) {
            $query->where('balance', '>', 10000);
        })->get();
        
        foreach ($users as $user) {
            $highBalanceAccounts = $user->accounts; // N+1 problem!
        }
        
        // GOOD: Eager load the same relationship
        $users = User::whereHas('accounts', function ($query) {
            $query->where('balance', '>', 10000);
        })->with(['accounts' => function ($query) {
            $query->where('balance', '>', 10000);
        }])->get();
        
        // Now no additional queries needed
    }

    /**
     * Example 16: Chunk with Eager Loading
     */
    public function chunkWithEagerLoading()
    {
        // Process large datasets in chunks with eager loading
        User::with('accounts.transactions')
            ->chunk(1000, function ($users) {
                foreach ($users as $user) {
                    // Process user with accounts and transactions
                    $this->processUserData($user);
                }
            });
        
        // Memory efficient and still avoids N+1
    }

    /**
     * Example 17: Lazy Collection with Eager Loading
     */
    public function lazyCollectionWithEagerLoading()
    {
        // Use cursor() for memory efficiency
        foreach (User::with('accounts')->cursor() as $user) {
            // Process one user at a time
            // Eager loading still prevents N+1
            $this->processUser($user);
        }
    }

    /**
     * Example 18: Global Scopes and Eager Loading
     */
    public function globalScopesWithEagerLoading()
    {
        // Eager loading respects global scopes
        // If Account has a global scope for 'active' status:
        $users = User::with('accounts')->get();
        // Only active accounts are loaded
        
        // To load without global scopes:
        $users = User::with(['accounts' => function ($query) {
            $query->withoutGlobalScopes();
        }])->get();
    }

    /**
     * Example 19: Touch Parent Timestamps
     */
    public function touchParentWithEagerLoading()
    {
        // When using touch in model relationships
        // Eager loading doesn't affect touch behavior
        $account = Account::with('user')->find($accountId);
        $account->touch(); // Updates account's updated_at
        
        // If Account model has: protected $touches = ['user'];
        // It will also update user's updated_at
    }

    /**
     * Example 20: Complete Banking Dashboard Example
     */
    public function bankingDashboardOptimized()
    {
        $userId = auth()->id();
        
        // Single optimized query for entire dashboard
        $user = User::with([
            // Load accounts with specific columns
            'accounts:id,user_id,account_number,balance,type,status',
            
            // Recent transactions with merchant info
            'accounts.transactions' => function ($query) {
                $query->latest()
                    ->limit(5)
                    ->with('merchant:id,name,category');
            },
            
            // Active cards
            'accounts.cards' => function ($query) {
                $query->where('status', 'active')
                    ->select('id', 'account_id', 'last_four', 'type', 'expiry_date');
            },
            
            // Pending transfers
            'transfersFrom' => function ($query) {
                $query->where('status', 'pending')
                    ->with('toAccount:id,account_number');
            }
        ])
        // Add aggregate data
        ->withCount([
            'accounts as active_accounts_count' => function ($query) {
                $query->where('status', 'active');
            }
        ])
        ->withSum('accounts as total_balance', 'balance')
        ->findOrFail($userId);
        
        return view('dashboard', [
            'user' => $user,
            'accounts' => $user->accounts,
            'totalBalance' => $user->total_balance,
            'activeAccountsCount' => $user->active_accounts_count
        ]);
        
        // Total queries: ~6 (user, accounts, transactions, merchants, cards, transfers)
        // Without optimization: 100+ queries easily
    }

    /**
     * Example 21: Query Performance Comparison
     */
    public function performanceComparison()
    {
        // Enable query logging
        DB::enableQueryLog();
        
        // Lazy loading (BAD)
        $start = microtime(true);
        $users = User::limit(100)->get();
        foreach ($users as $user) {
            $accountCount = $user->accounts->count();
        }
        $lazyLoadTime = microtime(true) - $start;
        $lazyLoadQueries = count(DB::getQueryLog());
        
        DB::flushQueryLog();
        
        // Eager loading (GOOD)
        $start = microtime(true);
        $users = User::with('accounts')->limit(100)->get();
        foreach ($users as $user) {
            $accountCount = $user->accounts->count();
        }
        $eagerLoadTime = microtime(true) - $start;
        $eagerLoadQueries = count(DB::getQueryLog());
        
        echo "Lazy Loading: {$lazyLoadQueries} queries in {$lazyLoadTime}s\n";
        echo "Eager Loading: {$eagerLoadQueries} queries in {$eagerLoadTime}s\n";
        echo "Improvement: " . round(($lazyLoadTime - $eagerLoadTime) / $lazyLoadTime * 100, 2) . "%\n";
    }

    // Helper methods
    private function processUserData($user) { }
    private function processUser($user) { }
}

/**
 * Model Definitions for Reference
 */

// User.php
class UserModel extends \Illuminate\Database\Eloquent\Model
{
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
    
    public function cards()
    {
        return $this->hasManyThrough(Card::class, Account::class);
    }
    
    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, Account::class);
    }
}

// Account.php
class AccountModel extends \Illuminate\Database\Eloquent\Model
{
    protected $touches = ['user']; // Touch parent on update
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    
    public function cards()
    {
        return $this->hasMany(Card::class);
    }
}

// Transaction.php  
class TransactionModel extends \Illuminate\Database\Eloquent\Model
{
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
