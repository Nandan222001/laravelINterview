-- Composite Index Examples for Banking Applications
-- Multi-column indexes following the leftmost prefix rule

-- ============================================================================
-- Example 1: Basic Composite Index (account_id, transaction_date)
-- ============================================================================
-- Use case: Filter by account, optionally sort/filter by date
CREATE INDEX idx_transactions_account_date 
ON transactions(account_id, transaction_date);

-- Queries that can use this index:
-- ✅ WHERE account_id = X
SELECT * FROM transactions WHERE account_id = 12345;

-- ✅ WHERE account_id = X AND transaction_date >= Y
SELECT * FROM transactions 
WHERE account_id = 12345 
  AND transaction_date >= '2024-01-01';

-- ✅ WHERE account_id = X ORDER BY transaction_date
SELECT * FROM transactions 
WHERE account_id = 12345 
ORDER BY transaction_date DESC 
LIMIT 10;

-- ❌ Cannot efficiently use this index (no account_id in WHERE):
-- SELECT * FROM transactions WHERE transaction_date >= '2024-01-01';

-- ============================================================================
-- Example 2: Three-Column Composite Index
-- ============================================================================
-- Use case: Filter by account, status, and date range
CREATE INDEX idx_transactions_account_status_date 
ON transactions(account_id, status, transaction_date);

-- Supported query patterns:
-- ✅ account_id
SELECT COUNT(*) FROM transactions WHERE account_id = 12345;

-- ✅ account_id + status
SELECT * FROM transactions 
WHERE account_id = 12345 AND status = 'completed';

-- ✅ account_id + status + transaction_date
SELECT * FROM transactions 
WHERE account_id = 12345 
  AND status = 'completed'
  AND transaction_date >= '2024-01-01';

-- ⚠️ Can use index but not optimal (skips middle column):
SELECT * FROM transactions 
WHERE account_id = 12345 
  AND transaction_date >= '2024-01-01';

-- ❌ Cannot use index efficiently:
-- SELECT * FROM transactions WHERE status = 'completed';
-- SELECT * FROM transactions WHERE transaction_date >= '2024-01-01';

-- ============================================================================
-- Example 3: Composite Index with DESC for Sorting
-- ============================================================================
-- Use case: Recent transactions first for a given account
CREATE INDEX idx_transactions_account_date_desc 
ON transactions(account_id ASC, transaction_date DESC);

-- Optimized query:
SELECT * FROM transactions 
WHERE account_id = 12345 
ORDER BY transaction_date DESC 
LIMIT 10;

-- ============================================================================
-- Example 4: Covering Composite Index (Index-Only Scan)
-- ============================================================================
-- Use case: Common query that needs account_id, date, amount, type
CREATE INDEX idx_transactions_covering 
ON transactions(account_id, transaction_date DESC) 
INCLUDE (amount, transaction_type, description);

-- Index-only scan query:
SELECT account_id, transaction_date, amount, transaction_type, description
FROM transactions 
WHERE account_id = 12345 
  AND transaction_date >= '2024-01-01'
ORDER BY transaction_date DESC;

-- ============================================================================
-- Example 5: Composite Index for Multi-Tenant Applications
-- ============================================================================
-- Use case: SaaS banking platform with tenant isolation
CREATE INDEX idx_accounts_tenant_status_created 
ON accounts(tenant_id, status, created_at);

-- Efficient query:
SELECT * FROM accounts 
WHERE tenant_id = 'bank-xyz' 
  AND status = 'active'
ORDER BY created_at DESC;

-- ============================================================================
-- Example 6: Composite Index Column Order Analysis
-- ============================================================================

-- BAD: Low selectivity column first
-- CREATE INDEX idx_transactions_status_account 
-- ON transactions(status, account_id);  -- ❌ status has low cardinality

-- GOOD: High selectivity column first
CREATE INDEX idx_transactions_account_status 
ON transactions(account_id, status);  -- ✅ account_id has high cardinality

-- Test query performance:
EXPLAIN ANALYZE
SELECT * FROM transactions 
WHERE account_id = 12345 AND status = 'completed';

-- ============================================================================
-- Example 7: Composite Index for Range + Equality
-- ============================================================================
-- Rule: Equality conditions first, range conditions last
CREATE INDEX idx_transactions_account_type_amount 
ON transactions(account_id, transaction_type, amount);

-- Optimized for:
SELECT * FROM transactions 
WHERE account_id = 12345 
  AND transaction_type = 'withdrawal'
  AND amount > 1000;

-- ============================================================================
-- Example 8: Multiple Indexes vs One Composite Index
-- ============================================================================

-- Option 1: Two separate indexes (uses index intersection if supported)
-- CREATE INDEX idx_transactions_account ON transactions(account_id);
-- CREATE INDEX idx_transactions_date ON transactions(transaction_date);

-- Option 2: One composite index (usually more efficient)
CREATE INDEX idx_transactions_account_date_composite 
ON transactions(account_id, transaction_date);

-- Benchmark comparison:
EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM transactions 
WHERE account_id = 12345 
  AND transaction_date >= '2024-01-01';

-- ============================================================================
-- Example 9: Composite Index for GROUP BY Optimization
-- ============================================================================
-- Use case: Aggregate transactions by account and type
CREATE INDEX idx_transactions_account_type_for_aggregation 
ON transactions(account_id, transaction_type);

-- Optimized GROUP BY:
SELECT account_id, transaction_type, COUNT(*), SUM(amount)
FROM transactions 
GROUP BY account_id, transaction_type;

-- ============================================================================
-- Example 10: Composite Unique Index
-- ============================================================================
-- Use case: Prevent duplicate transactions on same day for same account
CREATE UNIQUE INDEX idx_transactions_account_ref_unique 
ON transactions(account_id, external_reference_id);

-- This prevents:
-- INSERT INTO transactions (account_id, external_reference_id, ...) 
-- VALUES (12345, 'REF-123', ...);  -- First insert succeeds
-- INSERT INTO transactions (account_id, external_reference_id, ...) 
-- VALUES (12345, 'REF-123', ...);  -- Second insert fails

-- ============================================================================
-- Example 11: Composite Partial Index
-- ============================================================================
-- Use case: Index only pending transactions for a faster queue
CREATE INDEX idx_transactions_pending_account_date 
ON transactions(account_id, transaction_date)
WHERE status = 'pending';

-- Optimized for pending transaction queries:
SELECT * FROM transactions 
WHERE account_id = 12345 
  AND status = 'pending'
ORDER BY transaction_date;

-- ============================================================================
-- Example 12: Composite Index Selection Analysis
-- ============================================================================

-- Find which columns are frequently used together in WHERE clauses:
-- (Use slow query log or pg_stat_statements to identify patterns)

-- Example query to find common query patterns:
SELECT 
    query,
    calls,
    total_exec_time,
    mean_exec_time,
    rows
FROM pg_stat_statements
WHERE query LIKE '%transactions%'
  AND query LIKE '%WHERE%'
ORDER BY calls DESC
LIMIT 20;

-- ============================================================================
-- Example 13: Composite Index Size Estimation
-- ============================================================================

-- Estimate index size before creation:
SELECT 
    pg_size_pretty(
        pg_relation_size('transactions')::bigint * 
        (
            SELECT COUNT(DISTINCT (account_id, transaction_date))::numeric / COUNT(*)::numeric 
            FROM transactions
        )
    ) as estimated_index_size;

-- Check actual index size after creation:
SELECT 
    indexname,
    pg_size_pretty(pg_relation_size(indexname::regclass)) as index_size
FROM pg_indexes
WHERE tablename = 'transactions'
  AND indexname = 'idx_transactions_account_date';

-- ============================================================================
-- Example 14: Composite Index Selectivity Analysis
-- ============================================================================

-- Calculate selectivity of composite key:
SELECT 
    COUNT(*) as total_rows,
    COUNT(DISTINCT account_id) as distinct_accounts,
    COUNT(DISTINCT transaction_date) as distinct_dates,
    COUNT(DISTINCT (account_id, transaction_date)) as distinct_combinations,
    ROUND(
        (COUNT(DISTINCT (account_id, transaction_date))::numeric / COUNT(*)::numeric) * 100, 
        2
    ) as composite_selectivity_pct
FROM transactions;

-- ============================================================================
-- Example 15: Query Pattern Matching Test
-- ============================================================================

-- Test different query patterns to verify index usage:

-- Pattern 1: First column only (should use index)
EXPLAIN (ANALYZE, COSTS, BUFFERS)
SELECT * FROM transactions WHERE account_id = 12345;

-- Pattern 2: First two columns (should use index optimally)
EXPLAIN (ANALYZE, COSTS, BUFFERS)
SELECT * FROM transactions 
WHERE account_id = 12345 AND transaction_date >= '2024-01-01';

-- Pattern 3: Second column only (cannot use index efficiently)
EXPLAIN (ANALYZE, COSTS, BUFFERS)
SELECT * FROM transactions WHERE transaction_date >= '2024-01-01';

-- Pattern 4: First and third column, skipping second (partial index use)
EXPLAIN (ANALYZE, COSTS, BUFFERS)
SELECT * FROM transactions 
WHERE account_id = 12345 AND amount > 1000;

-- ============================================================================
-- Example 16: Composite Index Maintenance Script
-- ============================================================================

-- Analyze composite index effectiveness:
WITH index_usage AS (
    SELECT 
        schemaname,
        tablename,
        indexname,
        idx_scan,
        idx_tup_read,
        idx_tup_fetch,
        pg_size_pretty(pg_relation_size(indexname::regclass)) as index_size,
        pg_relation_size(indexname::regclass) as size_bytes
    FROM pg_stat_user_indexes
    WHERE indexname LIKE '%composite%' 
       OR indexname LIKE '%account_date%'
       OR indexname LIKE '%account_status%'
)
SELECT 
    *,
    CASE 
        WHEN idx_scan = 0 THEN 'NEVER USED - Consider dropping'
        WHEN idx_scan < 100 AND size_bytes > 10000000 THEN 'LOW USAGE - Review necessity'
        WHEN idx_scan > 10000 THEN 'HIGH USAGE - Keep and maintain'
        ELSE 'MODERATE USAGE - Monitor'
    END as recommendation
FROM index_usage
ORDER BY size_bytes DESC;

-- ============================================================================
-- Example 17: Composite Index for JSON Queries (PostgreSQL)
-- ============================================================================

-- Create GIN index for JSONB with composite approach:
CREATE INDEX idx_transactions_account_metadata 
ON transactions USING GIN (account_id, metadata);

-- Or expression index:
CREATE INDEX idx_transactions_account_json_amount 
ON transactions(account_id, (metadata->>'amount')::numeric);

-- Query optimization:
SELECT * FROM transactions 
WHERE account_id = 12345 
  AND (metadata->>'amount')::numeric > 100;

-- ============================================================================
-- Example 18: Best Practices Summary
-- ============================================================================

/*
Composite Index Design Rules:
1. Order matters: Most selective columns first (usually)
2. Equality before ranges: WHERE x = 1 AND y > 10 → index(x, y)
3. Sort columns last: ORDER BY should match index order
4. Leftmost prefix: Can use index for (a), (a,b), (a,b,c) but not (b) or (c)
5. Include frequently selected columns for covering indexes
6. Consider partial indexes for filtered queries
7. Monitor usage and drop unused indexes
8. Keep composite indexes to 3-4 columns max for maintainability
9. Test with EXPLAIN ANALYZE before deploying to production
10. Update statistics regularly with ANALYZE

Common Patterns for Banking:
- (account_id, transaction_date) - Account transaction history
- (account_id, status, created_at) - Account filtering and sorting
- (user_id, account_id, transaction_date) - User's account transactions
- (tenant_id, account_id) - Multi-tenant isolation
- (account_id, transaction_type, amount) - Transaction analysis
*/
