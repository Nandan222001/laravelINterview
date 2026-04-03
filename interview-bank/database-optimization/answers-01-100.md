# Database Optimization - Answers (1-100)

## Section 1: Index Fundamentals & B-Tree Structures (1-100)

1. Database index: data structure that maps column values to row locations. Accelerates query lookup from O(n) to O(log n). Trades storage for query speed.

2. B-tree index: self-balancing tree structure. Most common in databases. O(log n) operations. Balanced means predictable performance.

3. Index leaf nodes: contain actual data or row references (pointers). Stored sorted by key. Sequential storage reduces disk I/O.

4. Internal B-tree nodes: only contain keys and child pointers. Guide traversal to leaf nodes. Non-leaf levels reduce data storage.

5. Index balance: maintaining balance ensures O(log n) operations. Splitting/merging on insert/delete. Database handles automatically.

6. Multi-level indexing: multiple levels of nodes. Root → intermediate → leaf. Depth log_n of total rows. Small depth even for billions of rows.

7. Clustered index: table rows physically ordered by index key. Leaf nodes contain actual row data. One per table. Primary key typically clustered.

8. Non-clustered index: separate structure pointing to data rows. Leaves contain row pointers, not data. Multiple per table. Created on frequently searched columns.

9. Index selectivity: percentage of unique values. High selectivity (many unique values) indexes more useful. Low selectivity (few unique, many duplicates) less beneficial.

10. Cardinality: number of unique values in column. High cardinality (many unique) good for indexing. Low cardinality (few unique) poor index candidate.

11. Partial index: indexes subset of rows with WHERE clause. `CREATE INDEX idx ON table(col) WHERE status = 'active'`. Reduces size for filtered data.

12. Functional index: index on computed column or function result. `CREATE INDEX idx ON table(UPPER(name))` indexes uppercase version. Enables case-insensitive searches.

13. Composite index (Multi-column): single index on multiple columns. `(user_id, created_at)` index. Order matters. Leftmost prefix rule applies.

14. Leftmost prefix rule: composite index usable if query filters leftmost columns. `(a, b, c)` index helps queries on `a`, `(a, b)`, `(a, b, c)` but not `b` or `c` alone.

15. Index intersection: using multiple indexes in single query. Databases combine results from different indexes. Less efficient than single well-designed index.

16. Hash index: uses hash function to locate data. O(1) average lookup. Cannot support range queries or ordering. Limited use cases.

17. Bitmap index: uses bitmaps for indexed values. Efficient for low-cardinality columns. Common in data warehousing. Less common in OLTP.

18. Full-text index: specialized index for text searching. `FULLTEXT (content)` in MySQL. `MATCH ... AGAINST` queries. Supports relevance ranking.

19. Spatial index: indexes geographic coordinates (latitude/longitude). R-tree structure. `ST_CONTAINS`, `ST_DISTANCE` spatial queries. GIS applications.

20. Expression index: index on SQL expression not just column. `(salary * 12)` annual salary searches. Functional equivalent.

21. Index hints: explicit instructions to query optimizer. `USE INDEX`, `FORCE INDEX`, `IGNORE INDEX`. Override optimizer when necessary.

22. Covering index: contains all columns needed by query (no lookup needed). Query satisfied entirely from index. Reduces table access.

23. Index-only scan: query satisfied entirely from index data. No table access needed. Fastest query type. Covering index enables this.

24. Index fragmentation: blocks separated physically after updates. Degrades performance. Rebuild/reorganize periodically. Monitor fragmentation percentage.

25. Index maintenance: REBUILD rebuilds from scratch, REORGANIZE compacts existing. Automatic in modern databases. Manual scheduling for large tables.

26. Index page splits: when index page fills, splits into two. Temporary performance hit. Excess fragmentation with heavy updates.

27. Index compression: compress index pages to reduce size. Trades CPU for storage. Good for large indexes, memory-constrained systems.

28. Reverse index: index key in reverse direction. Enables reverse scans efficiently. Some databases support reverse specification.

29. Descending index: explicitly descending sort order. `CREATE INDEX idx ON table(col DESC)`. Helps ORDER BY DESC without reverse scan.

30. Index statistics: cardinality, null count, min/max values. Query optimizer uses for plan selection. Outdated stats lead to poor plans.

31. Index statistics update: manual `ANALYZE TABLE` or automatic background. Frequency depends on data change rate. Critical after bulk inserts.

32. Adaptive indexes: statistics collected as queries execute. Used by modern databases to improve plans dynamically. Self-tuning approach.

33. Index scans vs seeks: Seek jumps to specific point (fast), Scan reads sequentially (slower). Seeks preferred. Scans necessary sometimes.

34. Filtered statistics: statistics on subset of rows matching condition. Improves optimization for filtered queries. More accurate than table-wide stats.

35. Index recommendations: DMVs/system views show missing/unused indexes. Track performance, remove unnecessary ones. Built-in to most databases.

36. Index bloat: unused/redundant indexes consume storage. Remove unused indexes (check query logs). Consolidate similar indexes. Maintenance task.

37. Sparse index: index only on subset of rows (null-skipping). Many DBs skip nulls by default. Reduces size for optional columns.

38. Unique index: enforces uniqueness constraint. Can be single or composite column. Performance: PRIMARY KEY is clustered unique index.

39. Check constraint: validates column values. `CHECK (age > 0)`. Enforced at insert/update. Protects data integrity.

40. Generated column index: index on computed column. Column value calculated from expression. Can be indexed like normal column.

41. Index visibility: temporarily hide index without dropping. Test performance impact. `ALTER INDEX INVISIBLE` in MySQL 8.0+.

42. JSON index: indexes JSON document paths. `$.field` syntax. Enables efficient JSON queries. Growing importance with document storage.

43. Column store index: index optimized for analytical queries. Columnar storage instead of row-based. OLAP workloads.

44. Index hints in optimizer: `/*+ INDEX(...) */` comments guide optimizer. Database-specific syntax. Last resort for stubborn optimization issues.

45. Index page fills: parameter controls how full pages get before split. Trade-off between page splits and space utilization. Database-specific tuning.

46. Index statistics histogram: distribution of values across ranges. More detailed than simple cardinality. Helps optimizer estimate query results.

47. Incremental statistics: collect stats on subset of data quickly. Useful for very large tables. Reduces maintenance window.

48. Histogram bins: divide data ranges into buckets. Collect frequency counts. Better selectivity estimates for range predicates.

49. Index recommendation for joins: multiple indexes needed for join conditions. Left table index on join column, right table index on its join column.

50. Index stripe: RAID-like striping of indexes. Distribute index across multiple disks. Parallelism for large scans.

51. Index access patterns: sequential scans vs random access. Sequential: start at leftmost, read forward. Random: lookup specific keys.

52. Tight loop optimization: query optimization avoiding unnecessary operations. Index used efficiently, minimal tuple reconstruction.

53. Index pushdown: apply filters directly in index, not after fetching data. Reduces data retrieved. Push predicate into storage engine.

54. Index loose scan: scan index without touching every row. Skip ranges that don't match. Optimization for certain query patterns.

55. Index merge: combine results from multiple indexes. Useful when no single index covers all conditions. Union/intersection of index results.

56. Index structure: tree vs hash. Tree allows range queries and sorting. Hash only equality. Choice depends on query patterns.

57. Index leaf page: contains data (clustered) or pointers (non-clustered). Sorted storage enables range scans. Doubly-linked for backward traversal.

58. Index root node: entry point for traversal. One root per index. Accessed first in search. All paths have same length (balance property).

59. Index page splits: overflow when inserting into full page. Database chooses split point to minimize future splits. Can degrade performance.

60. Index coalescing: merging sibling pages after deletions. Reclaim space. Automatic in most systems.

61. Space reuse: deleted space used by new inserts. Prevents infinite growth. Fragmentation still occurs over time.

62. Index width: number of bytes per index entry. Wider indexes use more storage and memory. Fewer entries per page.

63. Index memory footprint: larger indexes consume more RAM. Entire B-tree root in memory ideal. Upper levels fit in cache.

64. Buffer pool: in-memory cache for index/data pages. Larger pool reduces disk I/O. Set based on available RAM.

65. Pin vs unpin: keep frequently accessed pages in memory. Unpin when no longer needed. Manual control in some systems.

66. Page eviction: LRU (Least Recently Used) policy removes cold pages. Make room for hot pages. Tunable in database settings.

67. Database warm-up: pre-load hot data into buffer pool. Avoid cold start performance. Replay workload or explicit prefetch.

68. Prefetching: load anticipated pages before requested. Sequential scan optimization. Read-ahead reduces latency.

69. Clustering factor: how sorted table data is with respect to index. Low (ordered): few page fetches. High (scattered): many page fetches.

70. Index design methodology: analyze queries, identify hot paths, design indexes accordingly. Avoid over-indexing. Balance read/write performance.

71. Equality predicate: exact value match in index. Most efficient use of index. Enables seek operation.

72. Range predicate: between, >, <, >= predicates. Indexes support efficiently. Sequential access in index range.

73. Prefix predicate: LIKE 'abc%' can use B-tree index. Database seeks to 'abc', scans matching entries. 'abc%' indexed, '%abc' requires full scan.

74. NULL handling: most indexes skip nulls. Except in some operations. Sparse indexes omit null values (saves space).

75. Index nulls: trailing nulls in composite index. Can be skipped if not needed for query. Partial index alternative.

76. Index on virtual column: MySQL supports generated column indexes. Computed on-the-fly from expression. Space/performance trade-off.

77. Computed index: similar to virtual column index. Pre-computed values indexed. Options in different databases.

78. Index with bitmap: use bit flags for common conditions. `if (user_active & 0x01) ...` tests. Fast filtering.

79. Partial index pruning: optimizer skips irrelevant index branches. Query conditions eliminate sections. Reduces search space.

80. Index skip scan: use index where first columns not in predicate. Skip to relevant second-column values. Modern optimizer feature.

81. Index state: active (used by queries), unused (never referenced), candidate (new, monitoring). Track and remove unused.

82. Index age: how long since last used. Tools track this. Old unused indexes should be removed.

83. Index bloat percentage: unused space in index blocks. High bloat warrants rebuild. Fragmentation measurement.

84. Index rebuild cost: CPU, I/O intensive operation. Lock table during rebuild. Schedule during low-traffic windows.

85. Online index rebuild: index rebuilt without blocking queries. Higher cost, non-blocking. Modern databases support.

86. Index corruption: damaged index pages. `REPAIR TABLE` or rebuild. Rare but serious issue.

87. Index consistency: index matches table data. Automatic in transactions. Manual check with REPAIR/CHECK commands.

88. Phantom reads: concurrent inserts after range query. Index doesn't prevent if not serializable isolation. SERIALIZABLE isolation prevents.

89. Index blooming: filter added to index searches. Returns false positives sometimes. Reduces unnecessary data access.

90. Bit-level index: bitwise operations on indexed columns. `status & 0x04` checks flag. Compression through bit packing.

91. Index estimation: optimizer estimates rows using index statistics. If estimates wrong, poor plan. Update statistics.

92. Index scan direction: forward (ascending) or backward (descending). Index can scan either, but unsorted reversal slower.

93. Index interleaving: multiple indexes on overlapping columns. Trade storage for flexibility. Design for common queries.

94. Index overlap: redundant indexes on same/overlapping columns. Remove duplicates. Keep most useful ones.

95. Disk I/O impact: index reduces I/O by enabling seeks. Each miss requires page fetch. Cache efficiency crucial.

96. Seek time: time to locate first matching record. Depends on index depth and structure. O(log n) operations.

97. Scan time: time to retrieve all matching records. Sequential reading once position found. Depends on result set size.

98. I/O latency: time for disk read. Modern SSDs have lower latency than HDD. Index design assumes some latency.

99. Throughput optimization: bulk operations benefit from index batching. Batch inserts reduce per-insert overhead.

100. Index design tradeoffs: faster reads vs slower writes, storage space, maintenance cost. No perfect design, optimize for workload.
