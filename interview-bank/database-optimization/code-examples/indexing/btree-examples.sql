-- B-tree Index Examples for Banking Applications
-- Demonstrates proper index creation and usage patterns

-- ============================================================================
-- Example 1: Simple B-tree Index on Email (Unique Constraint)
-- ============================================================================
-- Use case: User authentication, account lookup
CREATE UNIQUE INDEX idx_users_email ON users(email);

-- Query that uses this index:
SELECT * FROM users WHERE email = 'user@example.com';

-- ============================================================================
-- Example 2: B-tree Index on Transaction Date for Range Queries
-- ============================================================================
-- Use case: Transaction history queries, date range filtering
CREATE INDEX idx_transactions_date ON transactions(transaction_date);

-- Queries that benefit:
SELECT * FROM transactions 
WHERE transaction_date >= '2024-01-01' 
  AND transaction_date < '2024-02-01';

SELECT * FROM transactions 
WHERE transaction_date > NOW() - INTERVAL '30 days'
ORDER BY transaction_date DESC;

-- ============================================================================
-- Example 3: B-tree Index on Foreign Key for Join Performance
-- ============================================================================
-- Use case: Joining transactions to accounts
CREATE INDEX idx_transactions_account_id ON transactions(account_id);

-- Query that benefits:
SELECT a.account_number, t.amount, t.transaction_date
FROM accounts a
JOIN transactions t ON a.id = t.account_id
WHERE a.id = 12345;

-- ============================================================================
-- Example 4: B-tree Index with INCLUDE (Covering Index)
-- ============================================================================
-- PostgreSQL 11+ only
-- Use case: Avoid table lookups entirely
CREATE INDEX idx_accounts_number_covering 
ON accounts(account_number) 
INCLUDE (balance, status, created_at);

-- Query that benefits (index-only scan):
SELECT account_number, balance, status, created_at
FROM accounts
WHERE account_number = 'ACC-123456';

-- ============================================================================
-- Example 5: Analyzing B-tree Index Usage
-- ============================================================================

-- Check if index is being used:
EXPLAIN ANALYZE
SELECT * FROM transactions 
WHERE transaction_date >= '2024-01-01';

-- View index statistics (PostgreSQL):
SELECT 
    schemaname,
    tablename,
    indexname,
    idx_scan as scans,
    idx_tup_read as tuples_read,
    idx_tup_fetch as tuples_fetched
FROM pg_stat_user_indexes
WHERE indexname = 'idx_transactions_date';

-- ============================================================================
-- Example 6: Index Size and Bloat Analysis
-- ============================================================================

-- Check index size (PostgreSQL):
SELECT 
    indexname,
    pg_size_pretty(pg_relation_size(indexname::regclass)) as index_size
FROM pg_indexes
WHERE tablename = 'transactions';

-- Check index bloat:
SELECT 
    schemaname,
    tablename,
    indexname,
    pg_size_pretty(pg_relation_size(indexname::regclass)) as index_size,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch,
    CASE 
        WHEN idx_scan = 0 THEN 'NEVER USED'
        WHEN idx_scan < 10 THEN 'RARELY USED'
        ELSE 'ACTIVE'
    END as usage_status
FROM pg_stat_user_indexes
ORDER BY pg_relation_size(indexname::regclass) DESC;

-- ============================================================================
-- Example 7: Rebuilding Fragmented B-tree Indexes
-- ============================================================================

-- PostgreSQL - rebuild index:
REINDEX INDEX CONCURRENTLY idx_transactions_date;

-- MySQL - rebuild index:
-- ALTER TABLE transactions DROP INDEX idx_transactions_date;
-- ALTER TABLE transactions ADD INDEX idx_transactions_date(transaction_date);

-- Or optimize the whole table:
-- OPTIMIZE TABLE transactions;

-- ============================================================================
-- Example 8: Partial B-tree Index for Active Records
-- ============================================================================
-- Use case: Most queries only care about active accounts
CREATE INDEX idx_accounts_active_number 
ON accounts(account_number) 
WHERE status = 'active';

-- Query that uses this smaller, more efficient index:
SELECT * FROM accounts 
WHERE account_number = 'ACC-123456' 
  AND status = 'active';

-- ============================================================================
-- Example 9: B-tree Index for Sorting (ORDER BY optimization)
-- ============================================================================
-- Use case: Recent transactions first
CREATE INDEX idx_transactions_date_desc 
ON transactions(transaction_date DESC);

-- Query that benefits:
SELECT * FROM transactions
WHERE account_id = 12345
ORDER BY transaction_date DESC
LIMIT 10;

-- ============================================================================
-- Example 10: Composite B-tree Index (covered in composite-indexes.sql)
-- ============================================================================
-- Preview: Multi-column index following leftmost prefix rule
CREATE INDEX idx_transactions_account_date 
ON transactions(account_id, transaction_date);

-- This index can serve:
-- WHERE account_id = X
-- WHERE account_id = X AND transaction_date >= Y
-- But NOT: WHERE transaction_date >= Y (without account_id)

-- ============================================================================
-- Example 11: Monitoring Index Usage in Production
-- ============================================================================

-- Find unused indexes (PostgreSQL):
SELECT 
    schemaname,
    tablename,
    indexname,
    idx_scan,
    pg_size_pretty(pg_relation_size(indexname::regclass)) as index_size
FROM pg_stat_user_indexes
WHERE idx_scan = 0
  AND indexname NOT LIKE 'pg_toast%'
ORDER BY pg_relation_size(indexname::regclass) DESC;

-- Find indexes with low usage:
SELECT 
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch,
    pg_size_pretty(pg_relation_size(indexname::regclass)) as index_size,
    CASE 
        WHEN idx_scan = 0 THEN 0
        ELSE ROUND((idx_tup_fetch::numeric / idx_tup_read::numeric) * 100, 2)
    END as fetch_ratio
FROM pg_stat_user_indexes
WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
ORDER BY idx_scan ASC;

-- ============================================================================
-- Example 12: Index Creation Best Practices
-- ============================================================================

-- Create index CONCURRENTLY to avoid blocking writes (PostgreSQL):
CREATE INDEX CONCURRENTLY idx_transactions_amount 
ON transactions(amount);

-- For very large tables, consider:
-- 1. Create during low-traffic hours
-- 2. Monitor progress with pg_stat_progress_create_index
-- 3. Use CONCURRENTLY option to avoid table locks

-- Check index creation progress (PostgreSQL 12+):
SELECT 
    phase,
    round(100.0 * blocks_done / nullif(blocks_total, 0), 1) AS "% complete",
    blocks_total,
    blocks_done,
    tuples_total,
    tuples_done
FROM pg_stat_progress_create_index;

-- ============================================================================
-- Example 13: Index Selectivity Analysis
-- ============================================================================

-- Calculate cardinality and selectivity:
SELECT 
    COUNT(*) as total_rows,
    COUNT(DISTINCT account_id) as distinct_accounts,
    ROUND((COUNT(DISTINCT account_id)::numeric / COUNT(*)::numeric) * 100, 2) as selectivity_percentage,
    CASE 
        WHEN COUNT(DISTINCT account_id)::numeric / COUNT(*)::numeric > 0.05 
        THEN 'Good candidate for indexing'
        ELSE 'Low selectivity - index may not help'
    END as recommendation
FROM transactions;

-- ============================================================================
-- Example 14: Index-Only Scan Verification
-- ============================================================================

-- Check if query uses index-only scan:
EXPLAIN (ANALYZE, BUFFERS)
SELECT account_number, balance 
FROM accounts
WHERE account_number = 'ACC-123456';

-- Look for "Index Only Scan" in the output
-- Also check "Heap Fetches: 0" for true index-only scan

-- ============================================================================
-- Example 15: B-tree Index Maintenance Schedule
-- ============================================================================

-- Regular maintenance script (run during low-traffic periods):
-- Reindex critical indexes
REINDEX INDEX CONCURRENTLY idx_transactions_date;
REINDEX INDEX CONCURRENTLY idx_transactions_account_id;
REINDEX INDEX CONCURRENTLY idx_accounts_number_covering;

-- Update statistics for optimizer:
ANALYZE transactions;
ANALYZE accounts;

-- Vacuum to reclaim space and update visibility map:
VACUUM ANALYZE transactions;
VACUUM ANALYZE accounts;
