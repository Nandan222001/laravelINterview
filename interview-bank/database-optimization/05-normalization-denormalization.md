# Section 5: Database Normalization & Denormalization (Questions 451-550)

## Overview

This section covers normalization theory, denormalization strategies, materialized views, and schema design trade-offs for banking applications. Understanding when to normalize vs denormalize is critical for balancing data integrity with query performance.

---

## Normalization Forms (Questions 451-480)

451. Explain the First Normal Form (1NF) with examples.
452. Design a banking schema in 1NF for accounts and transactions.
453. What is Second Normal Form (2NF) and how does it build on 1NF?
454. Convert a non-2NF schema to 2NF with practical examples.
455. Explain Third Normal Form (3NF) and transitive dependencies.
456. Design a 3NF schema for a banking customer and account system.
457. What is Boyce-Codd Normal Form (BCNF)?
458. Explain the difference between 3NF and BCNF with examples.
459. What is Fourth Normal Form (4NF) and multi-valued dependencies?
460. Design a schema addressing 4NF for customer contact information.
461. Explain Fifth Normal Form (5NF) and join dependencies.
462. What is the practical limit of normalization for most applications?
463. Write SQL to normalize a denormalized transaction table.
464. Explain the benefits of normalization for data integrity.
465. What are the write performance benefits of normalization?
466. How does normalization reduce data redundancy?
467. Explain the relationship between normalization and referential integrity.
468. Design a fully normalized schema for a banking loan system.
469. What is the impact of normalization on database size?
470. How do you migrate from a denormalized to normalized schema?
471. Explain the concept of atomic values in 1NF.
472. What are the challenges of maintaining highly normalized schemas?
473. Design a normalized schema for a multi-currency banking system.
474. How does normalization affect backup and recovery?
475. Explain the relationship between normalization and indexing strategy.
476. What is the academic vs practical approach to normalization?
477. Design a normalized schema for banking audit trails.
478. How do you balance normalization with query performance?
479. Explain the impact of normalization on ORM relationships.
480. What are the best practices for normalizing existing systems?

### Normalization Example: Banking Schema

**❌ Denormalized (0NF) - Poor Design:**
```sql
CREATE TABLE transactions (
    transaction_id BIGINT PRIMARY KEY,
    account_number VARCHAR(20),
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    customer_address TEXT,
    account_type VARCHAR(50),
    account_balance DECIMAL(15,2),
    transaction_date TIMESTAMP,
    amount DECIMAL(15,2),
    transaction_type VARCHAR(50),
    merchant_name VARCHAR(100),
    merchant_category VARCHAR(50),
    -- Problems:
    -- 1. Data redundancy (customer info repeated for each transaction)
    -- 2. Update anomalies (changing email requires updating all rows)
    -- 3. Insert anomalies (can't add customer without transaction)
    -- 4. Delete anomalies (deleting last transaction loses customer data)
    -- 5. Large table size (redundant data)
);
```

**✅ 3NF Normalized Schema - Best Practice:**
```sql
-- 1NF: All columns contain atomic values
CREATE TABLE customers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2NF: No partial dependencies (all non-key attributes depend on entire key)
CREATE TABLE customer_addresses (
    id BIGSERIAL PRIMARY KEY,
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(50) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(50) NOT NULL DEFAULT 'USA',
    address_type VARCHAR(20) NOT NULL, -- 'billing', 'mailing'
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE accounts (
    id BIGSERIAL PRIMARY KEY,
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    account_number VARCHAR(20) UNIQUE NOT NULL,
    account_type_id INT NOT NULL REFERENCES account_types(id),
    balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    currency_code CHAR(3) NOT NULL DEFAULT 'USD',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_account_number (account_number)
);

-- 3NF: No transitive dependencies
CREATE TABLE account_types (
    id SERIAL PRIMARY KEY,
    type_code VARCHAR(20) UNIQUE NOT NULL,
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    minimum_balance DECIMAL(15,2) DEFAULT 0,
    interest_rate DECIMAL(5,4) DEFAULT 0
);

CREATE TABLE merchants (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL REFERENCES merchant_categories(id),
    merchant_code VARCHAR(50) UNIQUE,
    INDEX idx_name (name)
);

CREATE TABLE merchant_categories (
    id SERIAL PRIMARY KEY,
    category_code VARCHAR(20) UNIQUE NOT NULL,
    category_name VARCHAR(50) NOT NULL,
    description TEXT
);

CREATE TABLE transactions (
    id BIGSERIAL PRIMARY KEY,
    account_id BIGINT NOT NULL REFERENCES accounts(id),
    transaction_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(15,2) NOT NULL,
    transaction_type_id INT NOT NULL REFERENCES transaction_types(id),
    merchant_id BIGINT REFERENCES merchants(id),
    description TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reference_number VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_account_date (account_id, transaction_date),
    INDEX idx_merchant (merchant_id),
    INDEX idx_status (status) WHERE status = 'pending'
);

CREATE TABLE transaction_types (
    id SERIAL PRIMARY KEY,
    type_code VARCHAR(20) UNIQUE NOT NULL,
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    debit_credit VARCHAR(10) NOT NULL -- 'debit' or 'credit'
);
```

**Benefits of Normalization:**
```
Storage Reduction:
- Denormalized: 450 bytes per transaction × 100M = 45 GB
- Normalized: 120 bytes per transaction × 100M = 12 GB
- Savings: 73.3% reduction (33 GB saved)

Data Integrity:
- Customer email update: 1 row vs 100,000+ rows
- Referential integrity enforced via foreign keys
- No update/delete/insert anomalies

Write Performance:
- Smaller indexes (account_id vs account_number + customer_name + ...)
- Faster INSERT (less data per row)
- Faster UPDATE (update once, not thousands of times)
```

**Trade-off (Read Performance):**
```sql
-- Normalized requires JOINs for full data
SELECT 
    t.id,
    t.amount,
    t.transaction_date,
    tt.type_name,
    a.account_number,
    c.name as customer_name,
    c.email,
    m.name as merchant_name,
    mc.category_name
FROM transactions t
JOIN accounts a ON t.account_id = a.id
JOIN customers c ON a.customer_id = c.id
JOIN transaction_types tt ON t.transaction_type_id = tt.id
LEFT JOIN merchants m ON t.merchant_id = m.id
LEFT JOIN merchant_categories mc ON m.category_id = mc.id;

-- 5 JOINs required (but with proper indexes, still fast)
```

---

## Denormalization Strategies (Questions 481-510)

481. Explain when and why to denormalize database schemas.
482. Design a denormalized schema for a high-traffic transaction reporting system.
483. What are the trade-offs between normalization and denormalization?
484. Write a materialized view to denormalize account balance data.
485. Explain the concept of computed columns for denormalization.
486. How do you maintain consistency in denormalized data?
487. Design a denormalization strategy for a banking dashboard.
488. What is the impact of denormalization on write operations?
489. Explain read-heavy vs write-heavy workload denormalization strategies.
490. Write triggers to maintain denormalized columns.
491. How do you denormalize for reporting without affecting OLTP?
492. What is the role of materialized views in denormalization?
493. Design a denormalized schema for customer 360-degree view.
494. Explain the concept of summary tables and rollup tables.
495. Write SQL to maintain a denormalized account balance column.
496. How do you handle denormalization in microservices architectures?
497. What is the impact of denormalization on data integrity?
498. Design a denormalization strategy using event sourcing.
499. Explain the use of redundant foreign keys for query optimization.
500. Write a strategy to synchronize denormalized data.
501. How do you test denormalized schema consistency?
502. What is the relationship between denormalization and caching?
503. Design a hybrid normalized-denormalized schema.
504. Explain selective denormalization for hot paths.
505. Write a migration script for strategic denormalization.
506. How do you monitor denormalization effectiveness?
507. What is the impact of denormalization on database backups?
508. Design a denormalized schema for time-series banking data.
509. Explain the JSON/JSONB approach to denormalization.
510. Write best practices for denormalization in banking systems.

### Strategic Denormalization Example

**Scenario:** Banking dashboard showing account summaries (read-heavy, 1M+ requests/day)

**❌ Fully Normalized (Slow for Aggregations):**
```sql
-- Every dashboard request requires complex aggregation
SELECT 
    a.id,
    a.account_number,
    a.balance as current_balance,
    c.name as customer_name,
    COUNT(t.id) as total_transactions,
    COUNT(CASE WHEN t.transaction_date >= CURRENT_DATE - 30 THEN 1 END) as transactions_last_30_days,
    SUM(CASE WHEN t.transaction_date >= CURRENT_DATE - 30 THEN t.amount ELSE 0 END) as amount_last_30_days,
    MAX(t.transaction_date) as last_transaction_date
FROM accounts a
JOIN customers c ON a.customer_id = c.id
LEFT JOIN transactions t ON a.id = t.account_id
WHERE a.status = 'active'
GROUP BY a.id, a.account_number, a.balance, c.name;

-- Execution time: 2.5 seconds for 1M accounts
-- Problem: Cannot scale to 1M+ requests/day
```

**✅ Strategically Denormalized:**
```sql
-- Add denormalized summary columns to accounts table
ALTER TABLE accounts ADD COLUMN customer_name VARCHAR(100);
ALTER TABLE accounts ADD COLUMN total_transactions INT DEFAULT 0;
ALTER TABLE accounts ADD COLUMN transactions_last_30_days INT DEFAULT 0;
ALTER TABLE accounts ADD COLUMN amount_last_30_days DECIMAL(15,2) DEFAULT 0;
ALTER TABLE accounts ADD COLUMN last_transaction_date TIMESTAMP;

-- Create trigger to maintain denormalized data
CREATE OR REPLACE FUNCTION update_account_summary()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        -- Update account summary when new transaction inserted
        UPDATE accounts
        SET 
            total_transactions = total_transactions + 1,
            transactions_last_30_days = (
                SELECT COUNT(*) FROM transactions 
                WHERE account_id = NEW.account_id 
                AND transaction_date >= CURRENT_DATE - 30
            ),
            amount_last_30_days = (
                SELECT COALESCE(SUM(amount), 0) FROM transactions 
                WHERE account_id = NEW.account_id 
                AND transaction_date >= CURRENT_DATE - 30
            ),
            last_transaction_date = GREATEST(last_transaction_date, NEW.transaction_date)
        WHERE id = NEW.account_id;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_update_account_summary
AFTER INSERT ON transactions
FOR EACH ROW
EXECUTE FUNCTION update_account_summary();

-- Also update customer_name when customer changes
CREATE OR REPLACE FUNCTION sync_customer_name()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'UPDATE' AND OLD.name != NEW.name THEN
        UPDATE accounts SET customer_name = NEW.name WHERE customer_id = NEW.id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_sync_customer_name
AFTER UPDATE ON customers
FOR EACH ROW
EXECUTE FUNCTION sync_customer_name();

-- Simple query now (no JOINs or aggregations!)
SELECT 
    id,
    account_number,
    balance,
    customer_name,
    total_transactions,
    transactions_last_30_days,
    amount_last_30_days,
    last_transaction_date
FROM accounts
WHERE status = 'active';

-- Execution time: 0.05 seconds (50x improvement!)
```

**Performance Impact:**
```
Read Performance:
- Before: 2.5 seconds per query
- After: 0.05 seconds per query
- Improvement: 98% reduction (50x faster)

Write Performance:
- Additional overhead: ~2ms per INSERT (trigger execution)
- Trade-off: Acceptable for read-heavy workload

Scalability:
- Can handle 1M+ dashboard requests/day
- Database CPU usage reduced by 95%
```

### Alternative: Materialized View Approach

```sql
-- Create materialized view for dashboard
CREATE MATERIALIZED VIEW account_summaries AS
SELECT 
    a.id,
    a.account_number,
    a.balance,
    c.name as customer_name,
    COUNT(t.id) as total_transactions,
    COUNT(CASE WHEN t.transaction_date >= CURRENT_DATE - 30 THEN 1 END) as transactions_last_30_days,
    SUM(CASE WHEN t.transaction_date >= CURRENT_DATE - 30 THEN t.amount ELSE 0 END) as amount_last_30_days,
    MAX(t.transaction_date) as last_transaction_date
FROM accounts a
JOIN customers c ON a.customer_id = c.id
LEFT JOIN transactions t ON a.id = t.account_id
WHERE a.status = 'active'
GROUP BY a.id, a.account_number, a.balance, c.name;

-- Create indexes on materialized view
CREATE UNIQUE INDEX idx_account_summaries_id ON account_summaries(id);
CREATE INDEX idx_account_summaries_customer ON account_summaries(customer_name);

-- Refresh strategy (during low-traffic hours)
REFRESH MATERIALIZED VIEW CONCURRENTLY account_summaries;

-- Query is now simple and fast
SELECT * FROM account_summaries;
-- Execution time: 0.03 seconds
```

**Materialized View Benefits:**
- No trigger overhead on writes
- Can refresh on schedule (e.g., every 5 minutes)
- CONCURRENTLY option allows reads during refresh
- Easier to maintain than denormalized columns

**Trade-off:**
- Data can be stale (up to refresh interval)
- Refresh time proportional to data size
- Additional storage for the view

---

## Materialized Views (Questions 511-530)

511. Explain materialized views and how they differ from regular views.
512. Write SQL to create a materialized view for monthly account summaries.
513. How do you refresh materialized views efficiently?
514. What is the difference between full and incremental refresh?
515. Design a materialized view strategy for a banking analytics system.
516. Explain the CONCURRENTLY option in PostgreSQL materialized view refresh.
517. Write a scheduled job to refresh materialized views.
518. How do you index materialized views for optimal performance?
519. What is the storage overhead of materialized views?
520. Design a materialized view for customer transaction aggregates.
521. Explain the staleness vs performance trade-off in materialized views.
522. Write a query to monitor materialized view freshness.
523. How do you handle materialized views in replicated environments?
524. What is the impact of materialized views on source table writes?
525. Design a materialized view refresh strategy during low-traffic periods.
526. Explain the use of materialized views for complex joins.
527. Write a materialized view for real-time dashboard requirements.
528. How do you version materialized views during schema changes?
529. What is the relationship between materialized views and caching layers?
530. Design a comprehensive materialized view strategy for banking reports.

### Materialized View Refresh Strategies

```sql
-- Daily summary materialized view
CREATE MATERIALIZED VIEW daily_transaction_summary AS
SELECT 
    DATE(transaction_date) as date,
    account_id,
    COUNT(*) as transaction_count,
    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_credits,
    SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_debits,
    SUM(amount) as net_amount
FROM transactions
GROUP BY DATE(transaction_date), account_id;

CREATE UNIQUE INDEX idx_daily_summary ON daily_transaction_summary(date, account_id);

-- Incremental refresh function (PostgreSQL doesn't support natively)
CREATE OR REPLACE FUNCTION refresh_daily_summary_incremental()
RETURNS void AS $$
BEGIN
    -- Delete today's data
    DELETE FROM daily_transaction_summary 
    WHERE date >= CURRENT_DATE;
    
    -- Re-insert today's data
    INSERT INTO daily_transaction_summary
    SELECT 
        DATE(transaction_date) as date,
        account_id,
        COUNT(*) as transaction_count,
        SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_credits,
        SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_debits,
        SUM(amount) as net_amount
    FROM transactions
    WHERE DATE(transaction_date) >= CURRENT_DATE
    GROUP BY DATE(transaction_date), account_id;
END;
$$ LANGUAGE plpgsql;

-- Schedule refresh every 5 minutes during business hours
SELECT cron.schedule(
    'refresh-daily-summary',
    '*/5 9-17 * * 1-5',  -- Every 5 min, 9am-5pm, Mon-Fri
    'SELECT refresh_daily_summary_incremental()'
);
```

---

## Schema Design Trade-offs (Questions 531-550)

531. Compare star schema vs snowflake schema for banking analytics.
532. Design an optimal schema for high-throughput transaction processing.
533. Explain the CAP theorem in relation to schema design.
534. What is the impact of schema design on horizontal scaling?
535. Design a schema that balances normalization and performance.
536. Explain the concept of schema-on-read vs schema-on-write.
537. Write a schema migration strategy from normalized to hybrid design.
538. How do you design schemas for multi-tenancy?
539. What is the impact of schema design on query complexity?
540. Design a schema for both OLTP and OLAP workloads.
541. Explain the role of dimensional modeling in banking systems.
542. Write guidelines for schema design in microservices.
543. How do you design schemas for global distribution?
544. What is the impact of schema design on disaster recovery?
545. Design a schema for event sourcing and CQRS patterns.
546. Explain the time-series data schema design considerations.
547. Write a schema optimization plan for a legacy banking system.
548. How do you measure the effectiveness of schema design choices?
549. What are the emerging trends in database schema design?
550. Design a future-proof schema for digital banking platforms.

### Star Schema for Analytics

```sql
-- Fact table (denormalized for query performance)
CREATE TABLE fact_transactions (
    transaction_key BIGSERIAL PRIMARY KEY,
    date_key INT NOT NULL,
    account_key INT NOT NULL,
    customer_key INT NOT NULL,
    merchant_key INT NOT NULL,
    transaction_type_key INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    fee DECIMAL(10,2) DEFAULT 0,
    transaction_count INT DEFAULT 1,
    INDEX idx_date (date_key),
    INDEX idx_account (account_key),
    INDEX idx_customer (customer_key)
);

-- Dimension tables
CREATE TABLE dim_date (
    date_key INT PRIMARY KEY,
    date DATE NOT NULL,
    year INT NOT NULL,
    quarter INT NOT NULL,
    month INT NOT NULL,
    day INT NOT NULL,
    day_of_week INT NOT NULL,
    is_weekend BOOLEAN,
    is_holiday BOOLEAN
);

CREATE TABLE dim_account (
    account_key INT PRIMARY KEY,
    account_id BIGINT NOT NULL,
    account_number VARCHAR(20),
    account_type VARCHAR(50),
    status VARCHAR(20),
    opened_date DATE
);

-- Benefits:
-- - Query performance: Simple JOINs, no complex aggregations
-- - BI tool friendly: Easy to understand star schema
-- - Scalable: Can partition fact table by date_key

-- Example analytics query (very fast)
SELECT 
    d.year,
    d.quarter,
    dt.type_name,
    SUM(f.amount) as total_amount,
    COUNT(*) as transaction_count
FROM fact_transactions f
JOIN dim_date d ON f.date_key = d.date_key
JOIN dim_transaction_type dt ON f.transaction_type_key = dt.type_key
WHERE d.year = 2024
GROUP BY d.year, d.quarter, dt.type_name;
```

---

## Summary

This section covered 100 questions on normalization and denormalization strategies:

- **73% storage reduction** through normalization
- **98% read performance improvement** through strategic denormalization
- **50x faster** dashboard queries with materialized views
- Balanced approach for OLTP vs OLAP workloads

Schema design contributes **2-5% to overall response time reduction** through optimal data organization.
