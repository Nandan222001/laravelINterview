-- EXPLAIN ANALYZE Examples for Banking Applications
-- Learn to read and optimize query execution plans

-- ============================================================================
-- Example 1: Basic EXPLAIN ANALYZE
-- ============================================================================

-- Simple query without index
EXPLAIN ANALYZE
SELECT * FROM transactions
WHERE transaction_date >= '2024-01-01'
  AND transaction_date < '2024-02-01';

/* Example output interpretation:
Seq Scan on transactions  (cost=0.00..25000.00 rows=50000 width=100) 
                          (actual time=0.050..250.123 rows=45678 loops=1)
  Filter: (transaction_date >= '2024-01-01' AND transaction_date < '2024-02-01')
  Rows Removed by Filter: 454322
Planning Time: 0.234 ms
Execution Time: 275.456 ms

Interpretation:
- cost=0.00..25000.00: Estimated cost range (startup..total)
- rows=50000: Estimated rows to be returned
- actual time=0.050..250.123: Actual time in milliseconds
- rows=45678: Actual rows returned (close to estimate = good statistics)
- Rows Removed by Filter: Many rows scanned but filtered out (inefficient)
- Execution Time: Total query time

Problem: Sequential scan reading entire table
Solution: Add index on transaction_date
*/

-- ============================================================================
-- Example 2: After Adding Index
-- ============================================================================

CREATE INDEX idx_transactions_date ON transactions(transaction_date);

EXPLAIN ANALYZE
SELECT * FROM transactions
WHERE transaction_date >= '2024-01-01'
  AND transaction_date < '2024-02-01';

/* Expected improved output:
Index Scan using idx_transactions_date on transactions  
  (cost=0.43..5234.56 rows=50000 width=100) 
  (actual time=0.025..45.123 rows=45678 loops=1)
  Index Cond: (transaction_date >= '2024-01-01' AND transaction_date < '2024-02-01')
Planning Time: 0.123 ms
Execution Time: 52.345 ms

Improvements:
- Index Scan instead of Seq Scan
- Lower total cost (5234.56 vs 25000.00)
- Faster execution (52ms vs 275ms) - 81% improvement!
- No "Rows Removed by Filter" - index is efficient
*/

-- ============================================================================
-- Example 3: Index-Only Scan (Covering Index)
-- ============================================================================

CREATE INDEX idx_transactions_date_amount 
ON transactions(transaction_date) 
INCLUDE (amount);

EXPLAIN ANALYZE
SELECT transaction_date, amount
FROM transactions
WHERE transaction_date >= '2024-01-01'
  AND transaction_date < '2024-02-01';

/* Expected output:
Index Only Scan using idx_transactions_date_amount on transactions  
  (cost=0.43..3234.56 rows=50000 width=16) 
  (actual time=0.015..25.123 rows=45678 loops=1)
  Index Cond: (transaction_date >= '2024-01-01' AND transaction_date < '2024-02-01')
  Heap Fetches: 0
Planning Time: 0.089 ms
Execution Time: 28.456 ms

Key indicator: "Index Only Scan" with "Heap Fetches: 0"
- No table access needed, all data from index
- Even faster than regular index scan
- Best possible outcome for this query pattern
*/

-- ============================================================================
-- Example 4: Join Query Analysis
-- ============================================================================

EXPLAIN ANALYZE
SELECT 
    a.account_number,
    u.name,
    t.amount,
    t.transaction_date
FROM transactions t
JOIN accounts a ON t.account_id = a.id
JOIN users u ON a.user_id = u.id
WHERE t.transaction_date >= '2024-01-01'
  AND t.amount > 1000;

/* Example output showing different join strategies:

Hash Join  (cost=1234.56..45678.90 rows=1234 width=150) 
           (actual time=15.234..125.678 rows=1156 loops=1)
  Hash Cond: (a.user_id = u.id)
  ->  Hash Join  (cost=567.89..12345.67 rows=2345 width=120) 
                 (actual time=5.123..45.678 rows=2234 loops=1)
        Hash Cond: (t.account_id = a.id)
        ->  Index Scan using idx_transactions_date on transactions t  
            (cost=0.43..5678.90 rows=3000 width=80) 
            (actual time=0.025..15.234 rows=2800 loops=1)
              Index Cond: (transaction_date >= '2024-01-01')
              Filter: (amount > 1000)
              Rows Removed by Filter: 12000
        ->  Hash  (cost=345.67..345.67 rows=15000 width=40) 
                  (actual time=5.098..5.098 rows=15234 loops=1)
              Buckets: 16384  Batches: 1  Memory Usage: 1234kB
              ->  Seq Scan on accounts a  
                  (cost=0.00..345.67 rows=15000 width=40) 
                  (actual time=0.012..2.345 rows=15234 loops=1)
  ->  Hash  (cost=456.78..456.78 rows=25000 width=30) 
            (actual time=10.111..10.111 rows=25345 loops=1)
        Buckets: 32768  Batches: 1  Memory Usage: 2345kB
        ->  Seq Scan on users u  
            (cost=0.00..456.78 rows=25000 width=30) 
            (actual time=0.008..4.567 rows=25345 loops=1)

Interpretation:
- Two Hash Joins (efficient for larger datasets)
- Index scan on transactions (good - using our index)
- Sequential scans on accounts and users (acceptable if small tables)
- Memory usage shown for hash tables
- Join order: transactions -> accounts -> users (optimizer's choice)
*/

-- ============================================================================
-- Example 5: Nested Loop Join (Small Result Sets)
-- ============================================================================

EXPLAIN ANALYZE
SELECT 
    t.amount,
    t.transaction_date,
    a.account_number
FROM accounts a
JOIN transactions t ON t.account_id = a.id
WHERE a.account_number = 'ACC-123456'
ORDER BY t.transaction_date DESC
LIMIT 10;

/* Expected output:
Limit  (cost=0.86..123.45 rows=10 width=50) 
       (actual time=0.045..0.234 rows=10 loops=1)
  ->  Nested Loop  (cost=0.86..5678.90 rows=450 width=50) 
                   (actual time=0.044..0.230 rows=10 loops=1)
        ->  Index Scan using idx_accounts_number on accounts a  
            (cost=0.43..8.45 rows=1 width=20) 
            (actual time=0.015..0.016 rows=1 loops=1)
              Index Cond: (account_number = 'ACC-123456')
        ->  Index Scan Backward using idx_transactions_account_date on transactions t  
            (cost=0.43..5670.23 rows=450 width=30) 
            (actual time=0.028..0.210 rows=10 loops=1)
              Index Cond: (account_id = a.id)
Planning Time: 0.234 ms
Execution Time: 0.456 ms

Key observations:
- Nested Loop chosen (efficient for small outer table result)
- "Index Scan Backward" provides sorted order naturally
- LIMIT stops execution early (only 10 rows needed)
- Extremely fast (0.456ms) due to optimal plan
*/

-- ============================================================================
-- Example 6: Analyzing Subquery Performance
-- ============================================================================

-- BAD: Correlated subquery
EXPLAIN ANALYZE
SELECT 
    a.account_number,
    a.balance,
    (SELECT COUNT(*) 
     FROM transactions t 
     WHERE t.account_id = a.id 
       AND t.transaction_date > NOW() - INTERVAL '30 days'
    ) as recent_transaction_count
FROM accounts a
WHERE a.status = 'active';

/* Problem output:
Seq Scan on accounts a  (cost=0.00..1234567.89 rows=10000 width=50) 
                        (actual time=0.050..5678.901 rows=9876 loops=1)
  Filter: (status = 'active')
  SubPlan 1
    ->  Aggregate  (cost=120.45..120.46 rows=1 width=8) 
                   (actual time=0.567..0.567 rows=1 loops=9876)
          ->  Index Scan using idx_transactions_account on transactions t  
              (cost=0.43..119.67 rows=312 width=0) 
              (actual time=0.012..0.456 rows=250 loops=9876)
                Index Cond: (account_id = a.id)
                Filter: (transaction_date > NOW() - INTERVAL '30 days')
Planning Time: 1.234 ms
Execution Time: 6789.012 ms

Problem: SubPlan executed 9876 times (loops=9876)!
*/

-- GOOD: Rewritten with JOIN
EXPLAIN ANALYZE
SELECT 
    a.account_number,
    a.balance,
    COUNT(t.id) as recent_transaction_count
FROM accounts a
LEFT JOIN transactions t ON t.account_id = a.id 
    AND t.transaction_date > NOW() - INTERVAL '30 days'
WHERE a.status = 'active'
GROUP BY a.id, a.account_number, a.balance;

/* Improved output:
HashAggregate  (cost=12345.67..12456.78 rows=10000 width=58) 
               (actual time=234.567..245.678 rows=9876 loops=1)
  Group Key: a.id
  ->  Hash Right Join  (cost=456.78..11234.56 rows=123456 width=50) 
                       (actual time=12.345..189.012 rows=98765 loops=1)
        Hash Cond: (t.account_id = a.id)
        ->  Index Scan using idx_transactions_account_date on transactions t  
            (cost=0.43..8901.23 rows=234567 width=8) 
            (actual time=0.025..89.456 rows=250000 loops=1)
              Index Cond: (transaction_date > NOW() - INTERVAL '30 days')
        ->  Hash  (cost=345.67..345.67 rows=10000 width=50) 
                  (actual time=12.234..12.234 rows=9876 loops=1)
              Buckets: 16384  Batches: 1  Memory Usage: 890kB
              ->  Seq Scan on accounts a  
                  (cost=0.00..345.67 rows=10000 width=50) 
                  (actual time=0.012..6.789 rows=9876 loops=1)
                    Filter: (status = 'active')
Planning Time: 0.678 ms
Execution Time: 256.789 ms

Improvement: 6789ms -> 257ms (96% faster!)
*/

-- ============================================================================
-- Example 7: Analyzing Buffer Usage
-- ============================================================================

EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM transactions
WHERE account_id = 12345
ORDER BY transaction_date DESC
LIMIT 10;

/* Output with buffer information:
Limit  (cost=0.43..45.67 rows=10 width=100) 
       (actual time=0.045..0.234 rows=10 loops=1)
  Buffers: shared hit=15
  ->  Index Scan Backward using idx_transactions_account_date on transactions  
      (cost=0.43..5678.90 rows=1234 width=100) 
      (actual time=0.044..0.230 rows=10 loops=1)
        Index Cond: (account_id = 12345)
        Buffers: shared hit=15
Planning:
  Buffers: shared hit=8
Planning Time: 0.234 ms
Execution Time: 0.456 ms

Buffer analysis:
- shared hit=15: 15 pages found in shared buffer cache (fast!)
- shared read=0: No disk reads needed (all in cache)
- If you see "shared read=XXX", those are disk I/O operations (slow)

Good performance indicators:
- High "shared hit" relative to "shared read"
- Low buffer count overall
- No temp buffers (indicates no disk-based sorting)
*/

-- ============================================================================
-- Example 8: Analyzing Parallel Query Execution
-- ============================================================================

EXPLAIN ANALYZE
SELECT 
    DATE_TRUNC('day', transaction_date) as day,
    COUNT(*) as transaction_count,
    SUM(amount) as total_amount
FROM transactions
WHERE transaction_date >= '2024-01-01'
GROUP BY DATE_TRUNC('day', transaction_date)
ORDER BY day;

/* Parallel execution output:
Finalize GroupAggregate  (cost=123456.78..124567.89 rows=365 width=40) 
                         (actual time=123.456..145.678 rows=365 loops=1)
  Group Key: (date_trunc('day', transaction_date))
  ->  Gather Merge  (cost=123456.78..123890.12 rows=730 width=40) 
                    (actual time=123.234..145.012 rows=1095 loops=1)
        Workers Planned: 2
        Workers Launched: 2
        ->  Partial GroupAggregate  (cost=122456.75..122567.86 rows=365 width=40) 
                                    (actual time=118.234..119.567 rows=365 loops=3)
              Group Key: (date_trunc('day', transaction_date))
              ->  Sort  (cost=122456.75..122478.90 rows=8862 width=16) 
                        (actual time=118.012..118.234 rows=5678 loops=3)
                    Sort Key: (date_trunc('day', transaction_date))
                    Sort Method: quicksort  Memory: 1234kB
                    Worker 0:  Sort Method: quicksort  Memory: 1156kB
                    Worker 1:  Sort Method: quicksort  Memory: 1198kB
                    ->  Parallel Index Scan using idx_transactions_date on transactions  
                        (cost=0.43..21234.56 rows=8862 width=16) 
                        (actual time=0.050..89.456 rows=5678 loops=3)
                          Index Cond: (transaction_date >= '2024-01-01')
Planning Time: 1.234 ms
Execution Time: 156.789 ms

Parallel execution indicators:
- "Gather Merge" node consolidates parallel worker results
- "Workers Planned: 2" and "Workers Launched: 2" (successful parallelization)
- "loops=3" means operation ran 3 times (1 leader + 2 workers)
- Each worker processed ~1/3 of data independently
*/

-- ============================================================================
-- Example 9: Analyzing ORDER BY Performance
-- ============================================================================

-- Without matching index - requires sort
EXPLAIN ANALYZE
SELECT * FROM transactions
WHERE account_id = 12345
ORDER BY amount DESC
LIMIT 10;

/* Output showing sort operation:
Limit  (cost=5678.90..5678.95 rows=10 width=100) 
       (actual time=234.567..234.589 rows=10 loops=1)
  ->  Sort  (cost=5678.90..5789.01 rows=44043 width=100) 
            (actual time=234.566..234.578 rows=10 loops=1)
        Sort Key: amount DESC
        Sort Method: top-N heapsort  Memory: 25kB
        ->  Index Scan using idx_transactions_account on transactions  
            (cost=0.43..4567.89 rows=44043 width=100) 
            (actual time=0.025..189.456 rows=44123 loops=1)
              Index Cond: (account_id = 12345)
Planning Time: 0.234 ms
Execution Time: 234.789 ms

Problem: Sort operation needed
*/

-- With matching composite index - no sort needed
CREATE INDEX idx_transactions_account_amount 
ON transactions(account_id, amount DESC);

EXPLAIN ANALYZE
SELECT * FROM transactions
WHERE account_id = 12345
ORDER BY amount DESC
LIMIT 10;

/* Improved output:
Limit  (cost=0.43..45.67 rows=10 width=100) 
       (actual time=0.045..0.234 rows=10 loops=1)
  ->  Index Scan using idx_transactions_account_amount on transactions  
      (cost=0.43..5678.90 rows=44043 width=100) 
      (actual time=0.044..0.230 rows=10 loops=1)
        Index Cond: (account_id = 12345)
Planning Time: 0.123 ms
Execution Time: 0.456 ms

Improvement: 234ms -> 0.4ms (99.8% faster!)
No sort operation - index provides natural order
*/

-- ============================================================================
-- Example 10: Full Query Optimization Process
-- ============================================================================

-- Step 1: Identify slow query
SELECT 
    a.account_number,
    u.name,
    u.email,
    COUNT(t.id) as transaction_count,
    SUM(t.amount) as total_amount
FROM users u
JOIN accounts a ON a.user_id = u.id
JOIN transactions t ON t.account_id = a.id
WHERE t.transaction_date >= '2024-01-01'
  AND u.status = 'active'
GROUP BY a.account_number, u.name, u.email
HAVING SUM(t.amount) > 10000
ORDER BY total_amount DESC;

-- Step 2: Analyze execution plan
EXPLAIN (ANALYZE, BUFFERS, VERBOSE)
[same query];

-- Step 3: Identify bottlenecks from output
-- Common issues to look for:
-- - Sequential scans on large tables
-- - High "Rows Removed by Filter" counts
-- - Expensive sort operations
-- - Many disk reads (high "shared read" in buffers)
-- - Nested loops on large result sets

-- Step 4: Add necessary indexes
CREATE INDEX idx_transactions_date_account 
ON transactions(transaction_date, account_id);

CREATE INDEX idx_accounts_user 
ON accounts(user_id) INCLUDE (account_number);

CREATE INDEX idx_users_status 
ON users(status) INCLUDE (name, email);

-- Step 5: Re-analyze and compare
EXPLAIN (ANALYZE, BUFFERS)
[same query];

-- Step 6: Verify improvement in production monitoring
