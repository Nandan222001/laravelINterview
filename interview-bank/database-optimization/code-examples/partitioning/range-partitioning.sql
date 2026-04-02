-- Range Partitioning Examples for Banking Applications
-- PostgreSQL 10+ Declarative Partitioning

-- ============================================================================
-- Example 1: Basic Range Partitioning by Date
-- ============================================================================

-- Create parent table (partitioned)
CREATE TABLE transactions (
    id BIGSERIAL,
    account_id BIGINT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    transaction_type VARCHAR(20) NOT NULL,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id, transaction_date)
) PARTITION BY RANGE (transaction_date);

-- Create partitions for each month
CREATE TABLE transactions_2024_01 PARTITION OF transactions
    FOR VALUES FROM ('2024-01-01') TO ('2024-02-01');

CREATE TABLE transactions_2024_02 PARTITION OF transactions
    FOR VALUES FROM ('2024-02-01') TO ('2024-03-01');

CREATE TABLE transactions_2024_03 PARTITION OF transactions
    FOR VALUES FROM ('2024-03-01') TO ('2024-04-01');

-- Create indexes on partitions (automatically inherited)
CREATE INDEX idx_transactions_account_date 
ON transactions(account_id, transaction_date);

-- Query automatically uses partition pruning
EXPLAIN ANALYZE
SELECT * FROM transactions
WHERE transaction_date >= '2024-02-01'
  AND transaction_date < '2024-03-01'
  AND account_id = 12345;

/* Output shows partition pruning:
Index Scan using transactions_2024_02_account_id_transaction_date_idx 
  on transactions_2024_02 transactions
    (cost=0.43..8.45 rows=1 width=100)
  Index Cond: ((account_id = 12345) 
               AND (transaction_date >= '2024-02-01') 
               AND (transaction_date < '2024-03-01'))

Note: Only transactions_2024_02 partition is scanned!
*/

-- ============================================================================
-- Example 2: Automated Partition Creation Script
-- ============================================================================

-- Function to create next month's partition
CREATE OR REPLACE FUNCTION create_next_transaction_partition()
RETURNS void AS $$
DECLARE
    next_month DATE;
    following_month DATE;
    partition_name TEXT;
BEGIN
    -- Calculate next month
    next_month := DATE_TRUNC('month', CURRENT_DATE + INTERVAL '1 month');
    following_month := next_month + INTERVAL '1 month';
    partition_name := 'transactions_' || TO_CHAR(next_month, 'YYYY_MM');
    
    -- Check if partition already exists
    IF NOT EXISTS (
        SELECT 1 FROM pg_class
        WHERE relname = partition_name
    ) THEN
        -- Create partition
        EXECUTE format(
            'CREATE TABLE %I PARTITION OF transactions FOR VALUES FROM (%L) TO (%L)',
            partition_name,
            next_month,
            following_month
        );
        
        RAISE NOTICE 'Created partition: %', partition_name;
    ELSE
        RAISE NOTICE 'Partition % already exists', partition_name;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Schedule this to run monthly (e.g., via cron or pg_cron)
-- SELECT create_next_transaction_partition();

-- ============================================================================
-- Example 3: Creating Multiple Future Partitions
-- ============================================================================

DO $$
DECLARE
    start_date DATE := '2024-01-01';
    end_date DATE := '2025-01-01';
    current_date DATE;
    next_date DATE;
    partition_name TEXT;
BEGIN
    current_date := start_date;
    
    WHILE current_date < end_date LOOP
        next_date := current_date + INTERVAL '1 month';
        partition_name := 'transactions_' || TO_CHAR(current_date, 'YYYY_MM');
        
        -- Create partition if it doesn't exist
        IF NOT EXISTS (
            SELECT 1 FROM pg_class WHERE relname = partition_name
        ) THEN
            EXECUTE format(
                'CREATE TABLE %I PARTITION OF transactions FOR VALUES FROM (%L) TO (%L)',
                partition_name,
                current_date,
                next_date
            );
            RAISE NOTICE 'Created partition: %', partition_name;
        END IF;
        
        current_date := next_date;
    END LOOP;
END $$;

-- ============================================================================
-- Example 4: Quarterly Partitioning for High-Volume Data
-- ============================================================================

CREATE TABLE transaction_history (
    id BIGSERIAL,
    account_id BIGINT NOT NULL,
    amount DECIMAL(15, 2),
    transaction_date DATE NOT NULL,
    archived_at TIMESTAMP,
    PRIMARY KEY (id, transaction_date)
) PARTITION BY RANGE (transaction_date);

-- Q1 2024
CREATE TABLE transaction_history_2024_q1 PARTITION OF transaction_history
    FOR VALUES FROM ('2024-01-01') TO ('2024-04-01');

-- Q2 2024
CREATE TABLE transaction_history_2024_q2 PARTITION OF transaction_history
    FOR VALUES FROM ('2024-04-01') TO ('2024-07-01');

-- Q3 2024
CREATE TABLE transaction_history_2024_q3 PARTITION OF transaction_history
    FOR VALUES FROM ('2024-07-01') TO ('2024-10-01');

-- Q4 2024
CREATE TABLE transaction_history_2024_q4 PARTITION OF transaction_history
    FOR VALUES FROM ('2024-10-01') TO ('2025-01-01');

-- ============================================================================
-- Example 5: Yearly Partitioning for Long-Term Storage
-- ============================================================================

CREATE TABLE audit_logs (
    id BIGSERIAL,
    user_id BIGINT,
    action VARCHAR(100),
    details JSONB,
    created_at TIMESTAMP NOT NULL,
    PRIMARY KEY (id, created_at)
) PARTITION BY RANGE (created_at);

CREATE TABLE audit_logs_2022 PARTITION OF audit_logs
    FOR VALUES FROM ('2022-01-01') TO ('2023-01-01');

CREATE TABLE audit_logs_2023 PARTITION OF audit_logs
    FOR VALUES FROM ('2023-01-01') TO ('2024-01-01');

CREATE TABLE audit_logs_2024 PARTITION OF audit_logs
    FOR VALUES FROM ('2024-01-01') TO ('2025-01-01');

-- ============================================================================
-- Example 6: Detaching and Archiving Old Partitions
-- ============================================================================

-- Detach old partition (makes it independent table)
ALTER TABLE transactions 
DETACH PARTITION transactions_2022_01;

-- Now you can:
-- 1. Export it to cold storage
COPY transactions_2022_01 TO '/archive/transactions_2022_01.csv' CSV HEADER;

-- 2. Compress it
-- pg_dump transactions_2022_01 | gzip > transactions_2022_01.sql.gz

-- 3. Drop it if no longer needed
DROP TABLE transactions_2022_01;

-- Or keep it as regular table for archive queries

-- ============================================================================
-- Example 7: Attaching Existing Table as Partition
-- ============================================================================

-- Create table with data
CREATE TABLE transactions_2024_04 (LIKE transactions INCLUDING ALL);

-- Add check constraint matching partition bounds
ALTER TABLE transactions_2024_04 
ADD CONSTRAINT transactions_2024_04_check 
CHECK (transaction_date >= '2024-04-01' AND transaction_date < '2024-05-01');

-- Attach as partition
ALTER TABLE transactions 
ATTACH PARTITION transactions_2024_04 
FOR VALUES FROM ('2024-04-01') TO ('2024-05-01');

-- ============================================================================
-- Example 8: DEFAULT Partition for Out-of-Range Data
-- ============================================================================

-- Create DEFAULT partition to catch unpartitioned data
CREATE TABLE transactions_default PARTITION OF transactions DEFAULT;

-- Later, query which data is in DEFAULT partition
SELECT 
    MIN(transaction_date) as earliest,
    MAX(transaction_date) as latest,
    COUNT(*) as count
FROM transactions_default;

-- Create proper partition and move data
CREATE TABLE transactions_2025_01 PARTITION OF transactions
    FOR VALUES FROM ('2025-01-01') TO ('2025-02-01');

-- Data automatically moves from DEFAULT to new partition on insert

-- ============================================================================
-- Example 9: Sub-Partitioning (Range + List)
-- ============================================================================

CREATE TABLE transactions_multi (
    id BIGSERIAL,
    account_id BIGINT NOT NULL,
    region VARCHAR(10) NOT NULL,
    amount DECIMAL(15, 2),
    transaction_date DATE NOT NULL,
    PRIMARY KEY (id, transaction_date, region)
) PARTITION BY RANGE (transaction_date);

-- Create year partition
CREATE TABLE transactions_multi_2024 PARTITION OF transactions_multi
    FOR VALUES FROM ('2024-01-01') TO ('2025-01-01')
    PARTITION BY LIST (region);

-- Create region sub-partitions
CREATE TABLE transactions_multi_2024_us PARTITION OF transactions_multi_2024
    FOR VALUES IN ('US');

CREATE TABLE transactions_multi_2024_eu PARTITION OF transactions_multi_2024
    FOR VALUES IN ('EU');

CREATE TABLE transactions_multi_2024_asia PARTITION OF transactions_multi_2024
    FOR VALUES IN ('ASIA');

-- ============================================================================
-- Example 10: Monitoring Partition Sizes
-- ============================================================================

-- View all partitions and their sizes
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size,
    pg_total_relation_size(schemaname||'.'||tablename) AS size_bytes
FROM pg_tables
WHERE tablename LIKE 'transactions_%'
ORDER BY size_bytes DESC;

-- View partition information
SELECT 
    nmsp_parent.nspname AS parent_schema,
    parent.relname AS parent,
    nmsp_child.nspname AS child_schema,
    child.relname AS child,
    pg_size_pretty(pg_total_relation_size(child.oid)) AS size
FROM pg_inherits
JOIN pg_class parent ON pg_inherits.inhparent = parent.oid
JOIN pg_class child ON pg_inherits.inhrelid = child.oid
JOIN pg_namespace nmsp_parent ON nmsp_parent.oid = parent.relnamespace
JOIN pg_namespace nmsp_child ON nmsp_child.oid = child.relnamespace
WHERE parent.relname = 'transactions'
ORDER BY child.relname;

-- ============================================================================
-- Example 11: Partition Pruning Verification
-- ============================================================================

-- Enable partition pruning (should be on by default)
SET enable_partition_pruning = on;

-- Check if pruning is working
EXPLAIN (ANALYZE, VERBOSE)
SELECT * FROM transactions
WHERE transaction_date = '2024-02-15'
  AND account_id = 12345;

-- Should only scan transactions_2024_02 partition
-- If multiple partitions are scanned, check:
-- 1. Partition key is in WHERE clause
-- 2. Partition key uses compatible operators (=, <, >, BETWEEN)
-- 3. No type mismatches or implicit casts

-- ============================================================================
-- Example 12: Partition-wise Joins (PostgreSQL 11+)
-- ============================================================================

-- Enable partition-wise join
SET enable_partitionwise_join = on;

-- Both tables must be partitioned identically
CREATE TABLE account_balances (
    account_id BIGINT NOT NULL,
    balance DECIMAL(15, 2),
    balance_date DATE NOT NULL,
    PRIMARY KEY (account_id, balance_date)
) PARTITION BY RANGE (balance_date);

-- Create matching partitions
CREATE TABLE account_balances_2024_01 PARTITION OF account_balances
    FOR VALUES FROM ('2024-01-01') TO ('2024-02-01');

CREATE TABLE account_balances_2024_02 PARTITION OF account_balances
    FOR VALUES FROM ('2024-02-01') TO ('2024-03-01');

-- Join will be executed per-partition
EXPLAIN ANALYZE
SELECT 
    t.account_id,
    t.amount,
    ab.balance
FROM transactions t
JOIN account_balances ab ON ab.account_id = t.account_id 
    AND ab.balance_date = t.transaction_date
WHERE t.transaction_date >= '2024-02-01'
  AND t.transaction_date < '2024-03-01';

-- ============================================================================
-- Example 13: Maintenance Operations on Partitioned Tables
-- ============================================================================

-- Vacuum specific partition
VACUUM ANALYZE transactions_2024_02;

-- Reindex specific partition
REINDEX TABLE transactions_2024_02;

-- Vacuum all partitions
DO $$
DECLARE
    partition_name TEXT;
BEGIN
    FOR partition_name IN 
        SELECT tablename 
        FROM pg_tables 
        WHERE tablename LIKE 'transactions_2024_%'
    LOOP
        EXECUTE 'VACUUM ANALYZE ' || partition_name;
        RAISE NOTICE 'Vacuumed: %', partition_name;
    END LOOP;
END $$;

-- ============================================================================
-- Example 14: Migration from Non-Partitioned to Partitioned Table
-- ============================================================================

-- Step 1: Rename existing table
ALTER TABLE transactions RENAME TO transactions_old;

-- Step 2: Create partitioned table
CREATE TABLE transactions (
    id BIGINT,
    account_id BIGINT NOT NULL,
    amount DECIMAL(15, 2),
    transaction_date DATE NOT NULL,
    PRIMARY KEY (id, transaction_date)
) PARTITION BY RANGE (transaction_date);

-- Step 3: Create partitions
-- (use scripts from previous examples)

-- Step 4: Migrate data in batches
DO $$
DECLARE
    batch_size INT := 100000;
    min_id BIGINT;
    max_id BIGINT;
    current_id BIGINT;
BEGIN
    SELECT MIN(id), MAX(id) INTO min_id, max_id FROM transactions_old;
    current_id := min_id;
    
    WHILE current_id <= max_id LOOP
        INSERT INTO transactions
        SELECT * FROM transactions_old
        WHERE id >= current_id AND id < current_id + batch_size;
        
        current_id := current_id + batch_size;
        COMMIT; -- If using procedure instead of DO block
        RAISE NOTICE 'Migrated up to ID: %', current_id;
    END LOOP;
END $$;

-- Step 5: Verify counts match
SELECT 
    (SELECT COUNT(*) FROM transactions) as new_count,
    (SELECT COUNT(*) FROM transactions_old) as old_count;

-- Step 6: Drop old table when verified
-- DROP TABLE transactions_old;

-- ============================================================================
-- Example 15: Complete Partition Management Strategy
-- ============================================================================

-- Create management functions
CREATE OR REPLACE FUNCTION maintain_transaction_partitions()
RETURNS void AS $$
BEGIN
    -- Create next 3 months of partitions
    PERFORM create_next_transaction_partition();
    PERFORM create_next_transaction_partition();
    PERFORM create_next_transaction_partition();
    
    -- Archive partitions older than 2 years
    PERFORM archive_old_partitions(CURRENT_DATE - INTERVAL '2 years');
    
    -- Vacuum recent partitions
    PERFORM vacuum_recent_partitions();
END;
$$ LANGUAGE plpgsql;

-- Schedule via pg_cron (if installed)
-- SELECT cron.schedule('maintain-partitions', '0 2 1 * *', 
--     'SELECT maintain_transaction_partitions()');

-- Or create a shell script for cron:
-- #!/bin/bash
-- psql -d banking_db -c "SELECT maintain_transaction_partitions();"
