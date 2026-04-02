# Section 2: Cardinality Analysis & Selectivity (Questions 151-250)

## Overview

This section covers cardinality analysis, selectivity calculations, and query optimizer statistics for high-performance database systems. Understanding these concepts is crucial for creating effective indexes and optimizing query execution plans in banking applications.

---

## Cardinality Fundamentals (Questions 151-170)

151. Define cardinality in the context of database indexes.
152. Explain the difference between high-cardinality and low-cardinality columns.
153. Write a query to calculate the cardinality of a column in MySQL.
154. How does cardinality affect index selection by the query optimizer?
155. What is the cardinality of a primary key column?
156. Explain why low-cardinality columns make poor index candidates.
157. Write a query to find the cardinality ratio of all indexed columns.
158. How does PostgreSQL store cardinality statistics?
159. What is the relationship between cardinality and index size?
160. Create a script to identify low-cardinality indexes that should be removed.
161. Explain the concept of distinct values vs cardinality.
162. How do you calculate the cardinality of a composite index?
163. Write a query to compare cardinality across different time periods.
164. What is the impact of data skew on cardinality estimates?
165. Explain how NULL values affect cardinality calculations.
166. How does cardinality influence the choice between hash and B-tree indexes?
167. Write a query to monitor cardinality changes over time.
168. What is the threshold cardinality for creating an index?
169. Explain the relationship between cardinality and selectivity.
170. How do you update cardinality statistics in MySQL and PostgreSQL?

### Example: Analyzing Column Cardinality

```sql
-- PostgreSQL: Check cardinality of columns
SELECT 
    schemaname,
    tablename,
    attname AS column_name,
    n_distinct,
    CASE 
        WHEN n_distinct < 0 THEN ABS(n_distinct) * reltuples
        ELSE n_distinct
    END AS estimated_distinct_values,
    reltuples AS total_rows,
    CASE 
        WHEN reltuples > 0 THEN 
            (CASE WHEN n_distinct < 0 THEN ABS(n_distinct) ELSE n_distinct / reltuples END)
        ELSE 0
    END AS cardinality_ratio
FROM pg_stats
JOIN pg_class ON pg_stats.tablename = pg_class.relname
WHERE schemaname = 'public'
    AND tablename = 'transactions'
ORDER BY estimated_distinct_values DESC;
```

**Output:**
```
column_name          | n_distinct | estimated_distinct_values | cardinality_ratio
---------------------|------------|---------------------------|------------------
transaction_id       | -1.0       | 100,000,000              | 1.0 (High)
account_id           | 1250000    | 1,250,000                | 0.0125 (High)
transaction_date     | 3650       | 3,650                    | 0.0000365 (Medium)
transaction_type     | 12         | 12                       | 0.00000012 (Low)
status               | 5          | 5                        | 0.00000005 (Low)
```

**Indexing Decision:**
- ✅ High cardinality (>1%): Create indexes on transaction_id, account_id
- ⚠️ Medium cardinality (0.01%-1%): Conditional index on transaction_date
- ❌ Low cardinality (<0.01%): Avoid indexing status, transaction_type alone

---

## Selectivity Calculations (Questions 171-190)

171. Define selectivity and how it differs from cardinality.
172. Write a formula to calculate selectivity of a WHERE clause predicate.
173. Explain how the query optimizer uses selectivity estimates.
174. What is the selectivity of a primary key lookup?
175. Calculate the selectivity of a range query on transaction dates.
176. How does selectivity affect join order in query execution plans?
177. Write a query to estimate selectivity before creating an index.
178. Explain the relationship between selectivity and index efficiency.
179. What is considered good selectivity for an index to be useful?
180. How do multiple predicates combine to affect overall selectivity?
181. Write a script to analyze selectivity of frequently used WHERE clauses.
182. Explain how PostgreSQL calculates selectivity for LIKE queries.
183. What is the selectivity of a full table scan?
184. How does data distribution affect selectivity estimates?
185. Write a query to compare estimated vs actual selectivity.
186. Explain the concept of correlation between selectivity and physical ordering.
187. How do you improve selectivity through index design?
188. What is the impact of histogram statistics on selectivity?
189. Write a query to calculate selectivity for multi-column predicates.
190. Explain why inaccurate selectivity estimates lead to poor query plans.

### Selectivity Formula and Examples

**Selectivity Formula:**
```
Selectivity = (Number of rows matching predicate) / (Total number of rows)
```

**Example Calculations:**

```sql
-- Banking transactions table: 100M rows

-- Example 1: Primary key lookup
SELECT * FROM transactions WHERE transaction_id = 'TXN123456';
-- Rows returned: 1
-- Selectivity: 1 / 100,000,000 = 0.00000001 (0.000001%) - Excellent

-- Example 2: Date range query
SELECT * FROM transactions 
WHERE transaction_date BETWEEN '2024-01-01' AND '2024-01-31';
-- Rows returned: 2,850,000 (one month of data)
-- Selectivity: 2,850,000 / 100,000,000 = 0.0285 (2.85%) - Good

-- Example 3: Status filter
SELECT * FROM transactions WHERE status = 'pending';
-- Rows returned: 15,000,000
-- Selectivity: 15,000,000 / 100,000,000 = 0.15 (15%) - Moderate

-- Example 4: Transaction type
SELECT * FROM transactions WHERE type = 'withdrawal';
-- Rows returned: 35,000,000
-- Selectivity: 35,000,000 / 100,000,000 = 0.35 (35%) - Poor
```

**Index Recommendations:**
- **Selectivity < 5%**: Index is highly beneficial
- **Selectivity 5-15%**: Index may be beneficial depending on query frequency
- **Selectivity > 15%**: Index may not be used; consider partial index or composite index

---

## Statistics and Histograms (Questions 191-210)

191. Explain how PostgreSQL's ANALYZE command works.
192. Write a query to view column statistics in pg_stats.
193. What are histograms and how do they improve selectivity estimates?
194. How often should you run ANALYZE on high-traffic tables?
195. Explain the difference between default_statistics_target and column-level targets.
196. Write a script to automate statistics gathering for critical tables.
197. What is the format of histogram bounds in PostgreSQL?
198. How do you increase statistics sampling for better estimates?
199. Explain the most_common_values (MCV) list and its purpose.
200. Write a query to identify stale statistics.
201. How does MySQL's ANALYZE TABLE differ from PostgreSQL's ANALYZE?
202. What are extended statistics in PostgreSQL 10+?
203. Write a query to create multi-column statistics for correlated columns.
204. Explain how statistics affect index selection.
205. What is the default sample size for table statistics?
206. How do you monitor statistics freshness in production?
207. Write a query to view index statistics and usage counts.
208. Explain the impact of stale statistics on query performance.
209. How do you handle statistics for partitioned tables?
210. What is the relationship between table size and statistics accuracy?

### PostgreSQL Statistics Example

```sql
-- View detailed statistics for a column
SELECT 
    schemaname,
    tablename,
    attname,
    null_frac,
    avg_width,
    n_distinct,
    most_common_vals,
    most_common_freqs,
    histogram_bounds,
    correlation
FROM pg_stats
WHERE tablename = 'transactions'
    AND attname = 'amount';
```

**Sample Output:**
```
tablename:    transactions
attname:      amount
null_frac:    0.001 (0.1% NULL values)
avg_width:    8 (bytes)
n_distinct:   -0.75 (75% of rows have distinct values)
most_common_vals:  {100.00, 50.00, 25.00, 10.00, 5.00}
most_common_freqs: {0.05, 0.03, 0.02, 0.015, 0.01}
histogram_bounds:  {0.01, 15.50, 47.25, 125.00, 500.00, 2500.00, 50000.00}
correlation:  0.92 (high correlation with physical order)
```

**Interpretation:**
- 75% distinct values → High cardinality, good index candidate
- Top 5 values account for 12.5% of data → Consider these in optimization
- High correlation (0.92) → Sequential scans might be efficient for range queries
- Histogram shows distribution skew → Optimizer can make better decisions

### Updating Statistics

```sql
-- PostgreSQL: Analyze specific table
ANALYZE transactions;

-- Analyze with increased statistics target
ALTER TABLE transactions ALTER COLUMN account_id SET STATISTICS 1000;
ANALYZE transactions;

-- MySQL: Analyze table
ANALYZE TABLE transactions;

-- Automated statistics script
CREATE OR REPLACE FUNCTION update_critical_table_stats()
RETURNS void AS $$
BEGIN
    ANALYZE transactions;
    ANALYZE accounts;
    ANALYZE customers;
    ANALYZE audit_logs;
    RAISE NOTICE 'Statistics updated at %', NOW();
END;
$$ LANGUAGE plpgsql;

-- Schedule via cron or pg_cron
SELECT cron.schedule('update-stats', '0 2 * * *', 
    'SELECT update_critical_table_stats()');
```

---

## Query Optimizer Statistics (Questions 211-230)

211. Explain how the cost-based optimizer uses cardinality and selectivity.
212. Write a query to view the optimizer's cost estimates for a query plan.
213. What factors influence the optimizer's cost calculations?
214. How does the optimizer estimate the cost of sequential scans vs index scans?
215. Explain the concept of row estimates in query execution plans.
216. Write a query where the optimizer makes a suboptimal choice due to bad statistics.
217. How do you force the optimizer to use a specific index?
218. What is the enable_seqscan parameter and when should you disable it?
219. Explain the optimizer's join cost estimation algorithm.
220. Write a query to compare optimizer estimates with actual row counts.
221. How does work_mem affect optimizer decisions?
222. What is the random_page_cost vs seq_page_cost ratio?
223. Explain how the optimizer handles multi-column correlation.
224. Write a query to test optimizer behavior with different statistics targets.
225. How do you debug optimizer decisions that seem incorrect?
226. What is the effective_cache_size parameter and how does it affect planning?
227. Explain the concept of optimizer hints in MySQL.
228. Write a query using optimizer hints to control execution strategy.
229. How does the optimizer handle queries with multiple possible indexes?
230. What are the limitations of cost-based optimization?

### EXPLAIN ANALYZE: Optimizer Cost Estimation

```sql
-- Example: Comparing optimizer estimates vs actuals
EXPLAIN (ANALYZE, BUFFERS, VERBOSE)
SELECT t.*, a.account_number
FROM transactions t
JOIN accounts a ON t.account_id = a.id
WHERE t.transaction_date >= CURRENT_DATE - INTERVAL '30 days'
    AND t.amount > 1000;
```

**Before Statistics Update:**
```
Hash Join  (cost=12589.50..456789.23 rows=5000 width=256)
           (actual time=234.567..8945.123 rows=125430 loops=1)
  Hash Cond: (t.account_id = a.id)
  ->  Seq Scan on transactions t  (cost=0.00..425689.00 rows=5000 width=224)
                                   (actual time=0.123..8234.567 rows=125430 loops=1)
      Filter: ((transaction_date >= (CURRENT_DATE - 30)) AND (amount > 1000))
      Rows Removed by Filter: 98,574,570
  ->  Hash  (cost=10234.00..10234.00 rows=125000 width=32)
            (actual time=234.234..234.234 rows=125000 loops=1)
        ->  Seq Scan on accounts a  (cost=0.00..10234.00 rows=125000 width=32)
                                     (actual time=0.045..123.456 rows=125000 loops=1)

Planning Time: 2.345 ms
Execution Time: 8967.890 ms

NOTICE: Optimizer estimated 5,000 rows but actual was 125,430 rows (2,508% error!)
```

**After ANALYZE:**
```
Hash Join  (cost=12589.50..89456.78 rows=125000 width=256)
           (actual time=45.123..567.890 rows=125430 loops=1)
  Hash Cond: (t.account_id = a.id)
  ->  Bitmap Heap Scan on transactions t  (cost=2345.67..75689.12 rows=125000 width=224)
                                          (actual time=12.345..345.678 rows=125430 loops=1)
      Recheck Cond: ((transaction_date >= (CURRENT_DATE - 30)) AND (amount > 1000))
      Heap Blocks: exact=45678
      ->  Bitmap Index Scan on idx_transactions_date_amount  (cost=0.00..2314.42 rows=125000 width=0)
                                                              (actual time=8.234..8.234 rows=125430 loops=1)
            Index Cond: ((transaction_date >= (CURRENT_DATE - 30)) AND (amount > 1000))
  ->  Hash  (cost=10234.00..10234.00 rows=125000 width=32)
            (actual time=32.567..32.567 rows=125000 loops=1)
        ->  Seq Scan on accounts a  (cost=0.00..10234.00 rows=125000 width=32)
                                     (actual time=0.034..18.234 rows=125000 loops=1)

Planning Time: 1.234 ms
Execution Time: 589.456 ms

IMPROVEMENT: Execution time reduced by 93.4% (8968ms → 589ms)
```

---

## Cardinality Estimation Techniques (Questions 231-250)

231. Explain the HyperLogLog algorithm for cardinality estimation.
232. Write a query to estimate distinct counts for high-cardinality columns.
233. How does PostgreSQL estimate join cardinality?
234. What is the impact of correlated columns on cardinality estimates?
235. Explain bloom filters for cardinality estimation in joins.
236. Write a script to compare estimated vs actual cardinalities in production.
237. How do you improve cardinality estimates for skewed data?
238. What are the techniques for estimating cardinality in streaming data?
239. Explain the count-min sketch algorithm.
240. Write a query to identify queries with large estimation errors.
241. How does the optimizer estimate cardinality for subqueries?
242. What is the role of sampling in cardinality estimation?
243. Explain the Flajolet-Martin algorithm for distinct counting.
244. Write a query to monitor cardinality estimation accuracy.
245. How do you handle cardinality estimation for dynamic query patterns?
246. What are the challenges of cardinality estimation in multi-join queries?
247. Explain probabilistic counting techniques for large datasets.
248. Write a script to calibrate optimizer parameters based on actual cardinalities.
249. How does machine learning improve cardinality estimation?
250. What are the future trends in query optimization and cardinality estimation?

### HyperLogLog Example for Cardinality Estimation

```sql
-- PostgreSQL extension for HyperLogLog
CREATE EXTENSION IF NOT EXISTS hll;

-- Create HLL sketch for distinct account counting
CREATE TABLE account_sketches (
    date DATE,
    unique_accounts hll
);

-- Update sketch daily
INSERT INTO account_sketches (date, unique_accounts)
SELECT 
    CURRENT_DATE,
    hll_add_agg(hll_hash_bigint(account_id))
FROM transactions
WHERE transaction_date = CURRENT_DATE;

-- Estimate unique accounts with <2% error, using only ~1.5KB per sketch
SELECT 
    date,
    hll_cardinality(unique_accounts)::bigint AS estimated_unique_accounts
FROM account_sketches
WHERE date >= CURRENT_DATE - INTERVAL '7 days';
```

**Results:**
```
date       | estimated_unique_accounts | actual_count | error_rate
-----------|---------------------------|--------------|------------
2024-01-15 | 1,247,892                | 1,250,234    | 0.19%
2024-01-16 | 1,252,345                | 1,254,891    | 0.20%
2024-01-17 | 1,256,789                | 1,259,123    | 0.19%
```

**Benefits:**
- **Space efficiency**: 1.5KB sketch vs full distinct list
- **Merge capability**: Combine sketches across time periods
- **Accuracy**: <2% error with tunable precision
- **Performance**: O(1) cardinality estimation vs O(n) distinct count

### Extended Statistics for Correlated Columns

```sql
-- PostgreSQL 10+: Create extended statistics
CREATE STATISTICS stats_transactions_account_date (dependencies)
ON account_id, transaction_date
FROM transactions;

ANALYZE transactions;

-- Before extended statistics
EXPLAIN SELECT * FROM transactions
WHERE account_id = 12345 
    AND transaction_date = '2024-01-15';
-- Estimated rows: 95 (assuming independence)
-- Actual rows: 3 (columns are correlated)

-- After extended statistics
-- Estimated rows: 3 (recognizes correlation)
-- Accurate estimate leads to better plan selection
```

---

## Performance Impact Summary

### Impact of Accurate Statistics:

| Optimization | Before | After | Improvement |
|--------------|--------|-------|-------------|
| Query plan selection | Wrong index chosen | Optimal index used | 85-95% faster |
| Join order | Suboptimal | Optimal | 60-80% faster |
| Cardinality estimates | 100-1000% error | <5% error | 40-70% faster |
| Index vs scan decision | Incorrect choice | Correct choice | 70-90% faster |

### Best Practices for Banking Applications:

1. **Run ANALYZE regularly**: After bulk loads, daily for high-traffic tables
2. **Increase statistics_target**: 1000+ for critical query columns
3. **Use extended statistics**: For correlated columns (account_id + date)
4. **Monitor estimation errors**: Alert when estimates differ >50% from actuals
5. **Automate statistics updates**: Scheduled jobs during low-traffic periods

### Response Time Contribution:

Proper cardinality analysis and statistics contribute **5-15% response time reduction** through:
- Optimal index selection
- Correct join ordering
- Accurate cost estimation
- Better execution plans

---

## Summary

This section covered 100 questions on cardinality analysis and selectivity, demonstrating how accurate statistics enable the query optimizer to make optimal decisions. Key takeaways:

- Understand the difference between cardinality and selectivity
- Use ANALYZE to maintain accurate statistics
- Monitor and tune statistics for critical columns
- Apply probabilistic algorithms for efficient cardinality estimation
- Achieve measurable performance improvements through better optimizer decisions
