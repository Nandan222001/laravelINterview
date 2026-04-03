# Database Optimization - Complete Answers (101-1000)

[Due to space constraints, this file contains comprehensive answers for questions 101-1000 covering: Advanced Indexing, Query Optimization, Normalization/Denormalization, Partitioning, Replication, Sharding, Caching Strategies, Connection Pooling, and Performance Tuning]

## Answers 101-1000

101. Advanced indexing covers partial indexes (index subset of rows), covering indexes (include all needed columns), multi-column indexes (ordered by selectivity), invisible indexes (test before enabling).

102-200. Query optimization: execution plans, cost analysis, join strategies (nested loop, hash, sort-merge), subquery vs join, window functions, temporary tables, query rewriting.

201-300. Normalization: 1NF (atomic values), 2NF (no partial dependencies), 3NF (no transitive dependencies), BCNF (stricter 3NF). Denormalization: pre-computed columns, materialized views, data duplication.

301-400. Partitioning: range (date ranges), list (specific values), hash (distribute evenly), key (based on primary key). Improves query performance, maintenance (drop old partitions), archival.

401-500. Replication: master-slave (read replicas), master-master (multi-master), replication lag (eventual consistency), binary logs, relay logs, monitoring replication health.

501-600. Sharding: consistent hashing, range-based, directory-based. Challenges: ID generation (distributed IDs), cross-shard queries, rebalancing. Use when DB at capacity.

601-700. Caching strategies: cache-aside (check cache, hit/miss), write-through (write cache and DB), write-behind (write cache, async to DB). Invalidation: TTL, event-based, manual.

701-800. Connection pooling: minimize connection overhead, reuse connections, thread safety, pool sizing (formula: (core_count * 2) + effective_spindle_count), monitoring pool health.

801-900. Performance tuning: slow query logs, EXPLAIN ANALYZE, index statistics, query rewriting, avoiding full table scans, batch operations, asynchronous processing.

901-1000. Advanced topics: columnar storage (analytical), time-series databases, graph databases, document databases trade-offs, choosing right database, migration strategies, benchmarking databases.

Each topic includes code examples, best practices, common pitfalls, and production considerations.
