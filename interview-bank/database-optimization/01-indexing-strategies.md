# Section 1: MySQL/PostgreSQL Indexing Strategies (Questions 1-150)

## Overview

This section covers advanced indexing strategies for MySQL and PostgreSQL databases, focusing on practical applications in high-traffic banking systems. Topics include B-tree, Hash, GiST, GIN, composite, covering, and partial indexes with EXPLAIN ANALYZE examples demonstrating performance improvements.

---

## B-tree Indexes (Questions 1-30)

1. Explain how B-tree indexes work in MySQL/PostgreSQL and why they're the default index type.
2. What is the difference between clustered and non-clustered B-tree indexes?
3. How does MySQL's InnoDB use B+ trees differently from standard B-trees?
4. Write a query to create a B-tree index on a user's email column and explain when it would be used.
5. What is the typical height of a B-tree index for a table with 10 million rows?
6. Explain how B-tree indexes handle range queries efficiently.
7. What are the write performance implications of B-tree indexes?
8. How does the fill factor affect B-tree index performance?
9. Create an index strategy for a banking transactions table with 100M+ records.
10. Explain the concept of index page splits and how they impact performance.
11. What is the difference between a unique B-tree index and a non-unique B-tree index?
12. How do B-tree indexes handle NULL values in MySQL vs PostgreSQL?
13. Write a query to analyze B-tree index fragmentation.
14. What is the optimal node size for a B-tree index in high-concurrency scenarios?
15. Explain how B-tree indexes support both equality and range predicates.
16. How does the cardinality of a column affect B-tree index effectiveness?
17. Create a B-tree index for a timestamp column used in time-series banking data.
18. What are the disk I/O patterns when traversing a B-tree index?
19. Explain the difference between a covering index and a regular B-tree index.
20. How do you determine if a B-tree index is being used in a query execution plan?
21. What is the impact of data distribution skew on B-tree index performance?
22. Write a maintenance script to rebuild fragmented B-tree indexes.
23. Explain how B-tree indexes handle updates to indexed columns.
24. What is the relationship between index selectivity and B-tree efficiency?
25. How do partial B-tree indexes work in PostgreSQL?
26. Create an indexing strategy for a multi-tenant banking application.
27. What are the memory requirements for B-tree index operations?
28. Explain how B-tree indexes support ORDER BY clauses.
29. How do you monitor B-tree index usage statistics in production?
30. What is the impact of VARCHAR vs CHAR columns on B-tree index size?

### EXPLAIN ANALYZE Example: B-tree Index Optimization

**Before (Sequential Scan):**
```sql
EXPLAIN ANALYZE 
SELECT * FROM transactions 
WHERE account_id = 12345;

-- Result: Seq Scan on transactions (cost=0.00..1847239.50 rows=95 width=128) 
-- (actual time=1245.234..14567.891 rows=95 loops=1)
-- Planning Time: 0.156 ms
-- Execution Time: 14568.234 ms
```

**After (B-tree Index):**
```sql
CREATE INDEX idx_transactions_account_id ON transactions(account_id);

EXPLAIN ANALYZE 
SELECT * FROM transactions 
WHERE account_id = 12345;

-- Result: Index Scan using idx_transactions_account_id (cost=0.56..325.45 rows=95 width=128)
-- (actual time=0.045..1.234 rows=95 loops=1)
-- Planning Time: 0.189 ms
-- Execution Time: 1.456 ms
```

**Performance Improvement: 99.99% reduction (14568ms → 1.5ms)**

---

## Hash Indexes (Questions 31-50)

31. Explain when hash indexes are more efficient than B-tree indexes.
32. What are the limitations of hash indexes in MySQL and PostgreSQL?
33. Write a query to create a hash index on a UUID column.
34. Why don't hash indexes support range queries?
35. How does the hash function affect index distribution and performance?
36. Compare hash index vs B-tree index for exact-match lookups on account numbers.
37. What happens during hash collisions in database hash indexes?
38. Explain why PostgreSQL hash indexes are now crash-safe (since version 10).
39. How do hash indexes handle growth and rehashing?
40. Create a hash index strategy for a customer lookup system using SSN.
41. What is the memory footprint comparison between hash and B-tree indexes?
42. Explain the write amplification factor for hash indexes.
43. How do hash indexes perform under high-concurrency workloads?
44. What are the recovery implications of hash indexes?
45. Write a query to compare performance between hash and B-tree indexes.
46. Explain the bucket structure in hash index implementation.
47. How do you determine if a hash index is appropriate for your use case?
48. What is the load factor in hash indexes and how does it affect performance?
49. Compare hash index performance in MySQL vs PostgreSQL.
50. Why are hash indexes rarely used in production systems?

---

## GiST Indexes (Questions 51-70)

51. What is a GiST (Generalized Search Tree) index in PostgreSQL?
52. Explain the difference between GiST and GIN indexes.
53. Write a query to create a GiST index for full-text search.
54. How do GiST indexes support spatial data types (PostGIS)?
55. What are the use cases for GiST indexes in banking applications?
56. Explain the concept of lossy storage in GiST indexes.
57. Create a GiST index for range overlap queries on account balance history.
58. How does GiST index compression work?
59. What is the penalty function in GiST index construction?
60. Write a query using a GiST index for finding nearby bank branches.
61. Explain the GiST index build process and its performance characteristics.
62. How do GiST indexes handle updates and maintenance?
63. What is the difference between GiST and SP-GiST indexes?
64. Create a GiST index for IP address range queries in audit logs.
65. Explain the page split algorithm in GiST indexes.
66. How do you tune GiST index performance for large datasets?
67. What are the memory requirements for GiST index operations?
68. Write a query to monitor GiST index bloat.
69. Explain how GiST indexes support custom operator classes.
70. How do GiST indexes perform in write-heavy workloads?

---

## GIN Indexes (Questions 71-90)

71. What is a GIN (Generalized Inverted Index) in PostgreSQL?
72. Explain when to use GIN vs GiST for full-text search.
73. Write a query to create a GIN index on a JSONB column.
74. How does GIN index posting lists and keys?
75. Create a GIN index strategy for searching transaction descriptions.
76. What is fastupdate mode in GIN indexes?
77. Explain the pending list in GIN indexes and its performance implications.
78. How do you tune gin_pending_list_limit for optimal performance?
79. Write a query to search JSONB data using a GIN index.
80. What are the storage overhead implications of GIN indexes?
81. Explain how GIN indexes support array containment queries.
82. Create a GIN index for multi-tag search in a document management system.
83. How does GIN index vacuuming differ from B-tree indexes?
84. What is the difference between gin_fuzzy_search_limit and regular limits?
85. Write a maintenance script for GIN index optimization.
86. Explain the trade-offs between GIN and trigram indexes for partial matching.
87. How do GIN indexes handle high-cardinality data?
88. Create a GIN index strategy for audit log analysis.
89. What are the CPU implications of GIN index scans?
90. Explain how to monitor GIN index effectiveness using pg_stat_user_indexes.

### EXPLAIN ANALYZE Example: GIN Index for JSONB

**Before (Sequential Scan):**
```sql
EXPLAIN ANALYZE
SELECT * FROM audit_logs
WHERE metadata @> '{"action": "transfer"}';

-- Result: Seq Scan on audit_logs (cost=0.00..324561.00 rows=5000 width=256)
-- (actual time=12.345..8934.567 rows=4823 loops=1)
-- Planning Time: 0.234 ms
-- Execution Time: 8935.123 ms
```

**After (GIN Index):**
```sql
CREATE INDEX idx_audit_logs_metadata ON audit_logs USING GIN (metadata);

EXPLAIN ANALYZE
SELECT * FROM audit_logs
WHERE metadata @> '{"action": "transfer"}';

-- Result: Bitmap Heap Scan on audit_logs (cost=58.23..1245.67 rows=5000 width=256)
-- (actual time=2.456..45.678 rows=4823 loops=1)
-- Planning Time: 0.345 ms
-- Execution Time: 46.234 ms
```

**Performance Improvement: 99.48% reduction (8935ms → 46ms)**

---

## Composite Indexes (Questions 91-110)

91. What is a composite index and when should you use one?
92. Explain the column order importance in composite indexes.
93. Write a composite index for a query filtering by account_id and transaction_date.
94. What is the leftmost prefix rule in composite indexes?
95. How do you determine the optimal column order for a composite index?
96. Create a composite index strategy for a banking transaction search system.
97. Explain index skip scan and when it's used.
98. What is the impact of adding columns to an existing composite index?
99. Write a query to find unused columns in composite indexes.
100. How do composite indexes affect index-only scans?
101. Explain the trade-off between multiple single-column indexes vs one composite index.
102. Create a composite index for queries with equality and range conditions.
103. What is the index intersection technique and when does the optimizer use it?
104. How do you handle composite indexes in multi-tenant applications?
105. Write a query to analyze composite index selectivity.
106. Explain covering composite indexes and their benefits.
107. How do NULL values affect composite index usage?
108. Create a composite index for a complex JOIN query on transaction tables.
109. What is the maximum number of columns recommended in a composite index?
110. Explain how composite indexes support multiple query patterns.

---

## Covering Indexes (Questions 111-130)

111. What is a covering index and how does it eliminate table lookups?
112. Write a covering index for a query that selects user_id, email, and created_at.
113. Explain the performance benefits of index-only scans.
114. How do you identify queries that would benefit from covering indexes?
115. Create a covering index strategy for a high-traffic API endpoint.
116. What are the storage trade-offs of covering indexes?
117. Explain the visibility map in PostgreSQL and its role in index-only scans.
118. Write a query to verify if an index-only scan is being used.
119. How do covering indexes affect write performance?
120. What is the difference between INCLUDE columns (PostgreSQL 11+) and regular index columns?
121. Create a covering index for a banking dashboard query.
122. Explain when to use partial covering indexes.
123. How do you balance covering index width vs maintenance overhead?
124. Write a script to identify potential covering index opportunities.
125. What is the maximum index size limit in MySQL/PostgreSQL?
126. Explain how covering indexes interact with table statistics.
127. Create a covering index for aggregate queries on transaction amounts.
128. How do you monitor covering index effectiveness?
129. What are the implications of covering indexes in read replicas?
130. Explain the relationship between covering indexes and buffer cache efficiency.

### EXPLAIN ANALYZE Example: Covering Index

**Before (Index + Table Lookup):**
```sql
EXPLAIN ANALYZE
SELECT account_id, transaction_date, amount 
FROM transactions
WHERE account_id = 12345
ORDER BY transaction_date DESC
LIMIT 10;

-- Result: Index Scan using idx_account_id (cost=0.56..425.67 rows=10 width=24)
-- (actual time=0.123..12.456 rows=10 loops=1)
-- Planning Time: 0.234 ms
-- Execution Time: 12.678 ms
```

**After (Covering Index - Index Only Scan):**
```sql
CREATE INDEX idx_transactions_covering 
ON transactions(account_id, transaction_date DESC) 
INCLUDE (amount);

EXPLAIN ANALYZE
SELECT account_id, transaction_date, amount 
FROM transactions
WHERE account_id = 12345
ORDER BY transaction_date DESC
LIMIT 10;

-- Result: Index Only Scan using idx_transactions_covering (cost=0.56..45.23 rows=10 width=24)
-- (actual time=0.089..0.456 rows=10 loops=1)
-- Heap Fetches: 0
-- Planning Time: 0.198 ms
-- Execution Time: 0.567 ms
```

**Performance Improvement: 95.5% reduction (12.7ms → 0.57ms)**

---

## Partial and Filtered Indexes (Questions 131-150)

131. What is a partial index in PostgreSQL and when should you use it?
132. Write a partial index for active accounts only (status = 'active').
133. Explain the storage savings of partial indexes.
134. How do partial indexes improve query performance in multi-tenant systems?
135. Create a partial index for recent transactions (last 90 days).
136. What are filtered indexes in SQL Server and how do they compare to PostgreSQL partial indexes?
137. Write a partial index for non-null values in a sparse column.
138. Explain the predicate matching rules for partial index usage.
139. How do you test if the query optimizer is using your partial index?
140. Create a partial index strategy for soft-deleted records.
141. What are the limitations of partial indexes?
142. Write a partial index for high-value transactions (amount > 10000).
143. Explain how partial indexes reduce index maintenance overhead.
144. How do partial indexes interact with table partitioning?
145. Create a partial index for pending payment status records.
146. What is the relationship between partial indexes and query selectivity?
147. Write a script to analyze partial index usage statistics.
148. Explain when not to use partial indexes.
149. How do you maintain partial indexes during schema changes?
150. Create a partial index for time-based data retention policies.

### EXPLAIN ANALYZE Example: Partial Index

**Before (Full Index):**
```sql
CREATE INDEX idx_transactions_status ON transactions(status);

EXPLAIN ANALYZE
SELECT * FROM transactions
WHERE status = 'pending';

-- Index size: 2.4 GB
-- Result: Index Scan using idx_transactions_status (cost=0.56..15678.90 rows=12500 width=128)
-- (actual time=0.234..234.567 rows=12450 loops=1)
-- Execution Time: 235.123 ms
```

**After (Partial Index):**
```sql
CREATE INDEX idx_transactions_pending 
ON transactions(status) 
WHERE status = 'pending';

EXPLAIN ANALYZE
SELECT * FROM transactions
WHERE status = 'pending';

-- Index size: 156 MB (93.5% reduction)
-- Result: Index Scan using idx_transactions_pending (cost=0.43..1234.56 rows=12500 width=128)
-- (actual time=0.123..23.456 rows=12450 loops=1)
-- Execution Time: 24.012 ms
```

**Performance Improvement: 89.8% reduction (235ms → 24ms), 93.5% storage reduction**

---

## Key Performance Metrics

### Response Time Improvements by Index Type:

- **B-tree indexes**: 50-99% improvement for equality and range queries
- **GIN indexes**: 95-99% improvement for JSONB and array queries
- **Covering indexes**: 85-95% improvement through index-only scans
- **Partial indexes**: 70-90% improvement + 80-95% storage reduction
- **Composite indexes**: 60-95% improvement for multi-column queries

### Banking Application Best Practices:

1. **Transaction Tables**: Use composite indexes on (account_id, transaction_date)
2. **Account Lookups**: B-tree on account_number, consider hash for exact matches
3. **Audit Logs**: GIN indexes for JSONB metadata, partial indexes for active records
4. **Customer Search**: Composite indexes on (tenant_id, email) with covering columns
5. **Reporting Queries**: Covering indexes to eliminate table lookups

---

## Summary

This section provided 150 comprehensive questions on indexing strategies with practical EXPLAIN ANALYZE examples demonstrating measurable performance improvements. Key takeaways:

- Choose the right index type for your query patterns
- Use EXPLAIN ANALYZE to measure before/after improvements
- Balance query performance with storage and write overhead
- Apply banking-specific indexing patterns for optimal results
- Achieve 15%+ response time reduction through strategic indexing
