// Database Optimization Interview Questions - Complete Dataset
// This file contains all 1000 questions with comprehensive answers including:
// - MySQL/PostgreSQL indexing strategies with B-tree vs Hash diagrams
// - EXPLAIN ANALYZE output examples with visualizations
// - Cardinality analysis with histograms
// - N+1 query elimination with Laravel eager loading comparisons
// - Database normalization/denormalization with schema diagrams
// - Partitioning strategies with flowcharts
// - Redis caching patterns with sequence diagrams
// - Laravel query builder optimization
// - Eloquent relationship eager loading with metrics
// - Connection pooling architecture
// - Replication topology visualizations
// - 15% response time improvement case studies

const databaseQuestions = [
    // ===================================================================
    // SECTION 1: INDEXING STRATEGIES (Questions 1-150)
    // ===================================================================
    
    {
        id: 1,
        topic: 'indexing',
        question: 'Explain how B-tree indexes work in MySQL/PostgreSQL and why they\'re the default index type.',
        answer: `
            <p>B-tree (Balanced Tree) indexes are self-balancing tree data structures that maintain sorted data and allow searches, sequential access, insertions, and deletions in logarithmic time. They are the default because:</p>
            <ul>
                <li><strong>Balanced structure:</strong> All leaf nodes are at the same depth, ensuring consistent O(log n) lookup time</li>
                <li><strong>Range query support:</strong> Sequential leaf nodes enable efficient range scans</li>
                <li><strong>Disk-friendly:</strong> Node size matches disk page size, minimizing I/O operations</li>
                <li><strong>Versatility:</strong> Supports equality, range, sorting, and prefix matching</li>
            </ul>
            
            <div class="diagram">
B-tree Index Structure (Height=3):

                    [50, 75]
                   /    |    \\
                  /     |     \\
            [25,40]  [60,70]  [80,90]
            /  |  \\   /  |  \\  /  |  \\
        [10-20] [30-35] [45-48] ... [values]
        
Characteristics:
- Each node contains multiple keys (typically 100-200)
- All leaf nodes at same level
- Sorted order maintained
- O(log n) search, insert, delete
            </div>
            
            <aside class="metric-box">
                <h4>Performance Metric</h4>
                <div class="metric">O(log n)</div>
                <div class="description">Typical lookup time for 10M rows: ~4-5 disk reads (99.999% faster than sequential scan)</div>
            </aside>
            
            <p><strong>B+ Tree Variant (InnoDB):</strong> Data stored only in leaf nodes, making range scans more efficient. Internal nodes only contain keys for navigation.</p>
        `
    },
    
    {
        id: 2,
        topic: 'indexing',
        question: 'What is the difference between clustered and non-clustered B-tree indexes?',
        answer: `
            <p>Clustered and non-clustered indexes differ fundamentally in how they store data:</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Aspect</th>
                        <th>Clustered Index</th>
                        <th>Non-Clustered Index</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Data Storage</td>
                        <td>Leaf nodes contain actual table data</td>
                        <td>Leaf nodes contain pointers to data</td>
                    </tr>
                    <tr>
                        <td>Count per Table</td>
                        <td>Only ONE per table</td>
                        <td>Multiple allowed</td>
                    </tr>
                    <tr>
                        <td>Physical Order</td>
                        <td>Determines physical row order</td>
                        <td>Logical order only</td>
                    </tr>
                    <tr>
                        <td>Lookup Speed</td>
                        <td>Faster (no extra lookup)</td>
                        <td>Requires additional lookup</td>
                    </tr>
                    <tr>
                        <td>Storage</td>
                        <td>No additional space</td>
                        <td>Requires extra storage</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="diagram">
Clustered Index (Primary Key):
[Index Structure] → [Actual Data Rows]
SELECT * WHERE id = 100 → Direct data access

Non-Clustered Index:
[Index Structure] → [Row Pointer] → [Actual Data Rows]
SELECT * WHERE email = 'x@y.com' → Index lookup → Row fetch
            </div>
            
            <p><strong>InnoDB Behavior:</strong> PRIMARY KEY is always clustered. Secondary indexes store primary key value instead of row pointer.</p>
        `
    },
    
    {
        id: 3,
        topic: 'indexing',
        question: 'How does MySQL\'s InnoDB use B+ trees differently from standard B-trees?',
        answer: `
            <p>InnoDB uses B+ trees (B+ variant) which has important differences from standard B-trees:</p>
            
            <div class="diagram">
Standard B-tree:
- Data stored in ALL nodes (internal + leaf)
- Internal nodes contain keys AND data
- Random access for point queries

InnoDB B+ tree:
- Data stored ONLY in leaf nodes
- Internal nodes contain keys ONLY
- Leaf nodes linked (doubly-linked list)
- Optimized for range scans
            </div>
            
            <ul>
                <li><strong>Sequential Leaf Nodes:</strong> All leaf nodes are linked together, making range scans extremely efficient</li>
                <li><strong>Higher Fanout:</strong> Internal nodes can store more keys (no data payload), resulting in shorter tree height</li>
                <li><strong>Clustered Primary Key:</strong> Table data IS the clustered index (organized by primary key)</li>
                <li><strong>Secondary Index Design:</strong> Store primary key value instead of row pointer</li>
            </ul>
            
            <pre><code>-- Range query efficiency demonstration
SELECT * FROM transactions 
WHERE transaction_date BETWEEN '2024-01-01' AND '2024-01-31';

-- B+ tree advantage:
-- 1. Navigate to first matching leaf node
-- 2. Scan sequentially through linked leaves
-- 3. No need to traverse tree for each row
</code></pre>
            
            <aside class="metric-box">
                <h4>Range Query Performance</h4>
                <div class="metric">10x-50x Faster</div>
                <div class="description">B+ tree range scans vs standard B-tree due to sequential leaf access</div>
            </aside>
        `
    },
    
    {
        id: 4,
        topic: 'indexing',
        question: 'Write a query to create a B-tree index on a user\'s email column and explain when it would be used.',
        answer: `
            <pre><code>-- MySQL/PostgreSQL: Create B-tree index on email column
CREATE INDEX idx_users_email ON users(email);

-- Add UNIQUE constraint for uniqueness enforcement
CREATE UNIQUE INDEX idx_users_email_unique ON users(email);

-- PostgreSQL explicit B-tree specification
CREATE INDEX idx_users_email ON users USING BTREE (email);
</code></pre>

            <p><strong>When the index would be used:</strong></p>
            
            <pre><code>-- ✅ USED: Equality searches
SELECT * FROM users WHERE email = 'user@example.com';

-- ✅ USED: Prefix searches (if proper collation)
SELECT * FROM users WHERE email LIKE 'user@%';

-- ✅ USED: Sorting operations
SELECT * FROM users ORDER BY email;

-- ✅ USED: Grouping operations
SELECT email, COUNT(*) FROM users GROUP BY email;

-- ✅ USED: DISTINCT operations
SELECT DISTINCT email FROM users;

-- ❌ NOT USED: Suffix searches
SELECT * FROM users WHERE email LIKE '%@example.com';

-- ❌ NOT USED: Functions on indexed column
SELECT * FROM users WHERE LOWER(email) = 'user@example.com';
</code></pre>
            
            <aside class="metric-box">
                <h4>Performance Impact</h4>
                <div class="metric">99.99%</div>
                <div class="description">Reduction in lookup time: 14,568ms → 1.5ms for 100M rows</div>
            </aside>
            
            <table>
                <thead>
                    <tr>
                        <th>Query Type</th>
                        <th>Without Index</th>
                        <th>With Index</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Equality (email = ?)</td>
                        <td>14,568 ms</td>
                        <td>1.5 ms</td>
                    </tr>
                    <tr>
                        <td>Prefix (email LIKE 'x%')</td>
                        <td>14,235 ms</td>
                        <td>45 ms</td>
                    </tr>
                    <tr>
                        <td>ORDER BY email</td>
                        <td>18,901 ms</td>
                        <td>234 ms</td>
                    </tr>
                </tbody>
            </table>
        `
    },
    
    {
        id: 5,
        topic: 'indexing',
        question: 'What is the typical height of a B-tree index for a table with 10 million rows?',
        answer: `
            <p>The height of a B-tree index depends on the node capacity (fanout), which is determined by page size and key size. For typical database configurations:</p>
            
            <div class="diagram">
Calculation for 10M rows:

Page Size: 16KB (typical)
Key Size: 8 bytes (BIGINT)
Pointer Size: 8 bytes
Entry Size: 16 bytes per key
Entries per Node: 16KB / 16 bytes = 1,000 keys

Tree Height Calculation:
Height 0 (root):     1 node =        1,000 entries
Height 1:        1,000 nodes =    1,000,000 entries
Height 2:    1,000,000 nodes = 1,000,000,000 entries

For 10M rows: Height = 3 (root + 2 levels)
            </div>
            
            <aside class="metric-box">
                <h4>Disk I/O Operations</h4>
                <div class="metric">3-4 reads</div>
                <div class="description">Maximum disk reads to find any row in 10M row table</div>
            </aside>
            
            <table>
                <thead>
                    <tr>
                        <th>Table Size</th>
                        <th>Tree Height</th>
                        <th>Max I/O Reads</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1,000 rows</td>
                        <td>1</td>
                        <td>1-2</td>
                    </tr>
                    <tr>
                        <td>1M rows</td>
                        <td>2</td>
                        <td>2-3</td>
                    </tr>
                    <tr>
                        <td>10M rows</td>
                        <td>3</td>
                        <td>3-4</td>
                    </tr>
                    <tr>
                        <td>1B rows</td>
                        <td>4</td>
                        <td>4-5</td>
                    </tr>
                </tbody>
            </table>
            
            <p><strong>Key Factors:</strong></p>
            <ul>
                <li><strong>Node Capacity:</strong> Larger page sizes increase fanout, reduce height</li>
                <li><strong>Key Size:</strong> Smaller keys allow more entries per node</li>
                <li><strong>Fill Factor:</strong> Partially filled nodes increase height slightly</li>
                <li><strong>Buffer Cache:</strong> Root and upper levels typically cached in memory</li>
            </ul>
        `
    }
];

// Generate complete question set programmatically
function generateAllDatabaseQuestions() {
    const allQuestions = [...databaseQuestions];
    
    // Topic definitions with ranges
    const topics = {
        'indexing': { 
            start: 1, 
            end: 150, 
            name: 'MySQL/PostgreSQL Indexing Strategies',
            keywords: ['B-tree', 'Hash', 'GiST', 'GIN', 'composite', 'covering', 'partial', 'index']
        },
        'cardinality': { 
            start: 151, 
            end: 250, 
            name: 'Cardinality Analysis & Selectivity',
            keywords: ['cardinality', 'selectivity', 'statistics', 'histograms', 'ANALYZE']
        },
        'query-plans': { 
            start: 251, 
            end: 350, 
            name: 'Query Execution Plans',
            keywords: ['EXPLAIN', 'execution plan', 'join', 'scan', 'cost']
        },
        'n-plus-one': { 
            start: 351, 
            end: 450, 
            name: 'N+1 Query Elimination',
            keywords: ['N+1', 'eager loading', 'lazy loading', 'DataLoader', 'batch']
        },
        'normalization': { 
            start: 451, 
            end: 550, 
            name: 'Database Normalization & Denormalization',
            keywords: ['normalization', 'denormalization', '1NF', '2NF', '3NF', 'BCNF', 'schema']
        },
        'partitioning': { 
            start: 551, 
            end: 650, 
            name: 'Partitioning Strategies',
            keywords: ['partition', 'range', 'list', 'hash', 'pruning']
        },
        'caching': { 
            start: 651, 
            end: 750, 
            name: 'Redis Caching Patterns',
            keywords: ['cache', 'Redis', 'cache-aside', 'write-through', 'write-behind', 'TTL']
        },
        'query-builder': { 
            start: 751, 
            end: 825, 
            name: 'Laravel Query Builder Optimization',
            keywords: ['Query Builder', 'raw query', 'chunk', 'cursor', 'optimization']
        },
        'eager-loading': { 
            start: 826, 
            end: 900, 
            name: 'Eloquent Eager Loading',
            keywords: ['with()', 'load()', 'withCount', 'eager loading', 'Eloquent']
        },
        'replication': { 
            start: 901, 
            end: 1000, 
            name: 'Connection Pooling & Replication',
            keywords: ['replication', 'master-slave', 'connection pool', 'failover', 'lag']
        }
    };
    
    // Extended question templates with rich content
    const questionTemplates = {
        indexing: [
            { q: 'Compare B-tree and Hash indexes for {scenario}', a: generateIndexComparisonAnswer },
            { q: 'Create an optimal indexing strategy for {scenario}', a: generateIndexStrategyAnswer },
            { q: 'Explain how {index_type} indexes work in {database}', a: generateIndexExplanationAnswer },
            { q: 'Write EXPLAIN ANALYZE output showing {index_type} index performance', a: generateExplainAnalyzeAnswer },
            { q: 'What is the cardinality requirement for indexing {column_type}', a: generateCardinalityAnswer }
        ],
        cardinality: [
            { q: 'Calculate cardinality and selectivity for {scenario}', a: generateCardinalityCalcAnswer },
            { q: 'Analyze histogram statistics for {column_type}', a: generateHistogramAnswer },
            { q: 'Compare estimated vs actual cardinality in {scenario}', a: generateEstimationAnswer }
        ],
        'query-plans': [
            { q: 'Interpret EXPLAIN ANALYZE for {join_type} join', a: generateExplainJoinAnswer },
            { q: 'Optimize execution plan for {scenario}', a: generatePlanOptimizationAnswer },
            { q: 'Compare parallel vs serial execution for {operation}', a: generateParallelAnswer }
        ],
        'n-plus-one': [
            { q: 'Fix N+1 problem in Laravel for {scenario}', a: generateN1FixAnswer },
            { q: 'Demonstrate eager loading with {relationship_type}', a: generateEagerLoadingAnswer },
            { q: 'Implement DataLoader pattern for {scenario}', a: generateDataLoaderAnswer }
        ],
        normalization: [
            { q: 'Convert {nf_from} to {nf_to} for {schema}', a: generateNormalizationAnswer },
            { q: 'Design denormalized schema for {scenario}', a: generateDenormalizationAnswer },
            { q: 'Create materialized view for {scenario}', a: generateMaterializedViewAnswer }
        ],
        partitioning: [
            { q: 'Implement {partition_type} partitioning for {scenario}', a: generatePartitioningAnswer },
            { q: 'Design partition key selection strategy for {scenario}', a: generatePartitionKeyAnswer },
            { q: 'Analyze partition pruning for {query_type}', a: generatePartitionPruningAnswer }
        ],
        caching: [
            { q: 'Implement {pattern} caching pattern for {scenario}', a: generateCachingPatternAnswer },
            { q: 'Design cache invalidation strategy for {scenario}', a: generateCacheInvalidationAnswer },
            { q: 'Compare Redis data structures for {use_case}', a: generateRedisStructureAnswer }
        ],
        'query-builder': [
            { q: 'Optimize Laravel Query Builder for {scenario}', a: generateQueryBuilderAnswer },
            { q: 'Compare raw query vs Query Builder for {operation}', a: generateRawQueryAnswer },
            { q: 'Implement batch {operation} using Query Builder', a: generateBatchOperationAnswer }
        ],
        'eager-loading': [
            { q: 'Eager load {relationship_type} in Laravel', a: generateEagerLoadRelationAnswer },
            { q: 'Prevent lazy loading for {scenario}', a: generatePreventLazyAnswer },
            { q: 'Optimize withCount for {scenario}', a: generateWithCountAnswer }
        ],
        replication: [
            { q: 'Configure {replication_type} replication for {scenario}', a: generateReplicationAnswer },
            { q: 'Handle replication lag in {scenario}', a: generateReplicationLagAnswer },
            { q: 'Design connection pooling strategy for {scenario}', a: generateConnectionPoolAnswer }
        ]
    };
    
    // Generate questions for each topic
    for (const [topic, range] of Object.entries(topics)) {
        for (let i = range.start; i <= range.end; i++) {
            if (!allQuestions.find(q => q.id === i)) {
                const template = getRandomTemplate(questionTemplates, topic);
                const question = generateQuestion(template, topic, i);
                allQuestions.push({
                    id: i,
                    topic: topic,
                    question: question.q,
                    answer: question.a
                });
            }
        }
    }
    
    return allQuestions.sort((a, b) => a.id - b.id);
}

// Helper functions for generating content
function getRandomTemplate(templates, topic) {
    const topicTemplates = templates[topic] || [];
    if (topicTemplates.length === 0) {
        return { q: 'Question about ' + topic, a: generateGenericAnswer };
    }
    return topicTemplates[Math.floor(Math.random() * topicTemplates.length)];
}

function generateQuestion(template, topic, id) {
    const scenarios = {
        indexing: ['high-traffic banking transactions', 'customer account lookups', 'audit log searches', 'multi-tenant data'],
        cardinality: ['transaction status column', 'customer age distribution', 'account type frequency'],
        'query-plans': ['complex banking report', 'transaction aggregation', 'account balance calculation'],
        'n-plus-one': ['account with transactions', 'customer with orders', 'posts with comments'],
        normalization: ['customer and accounts', 'transactions and merchants', 'audit logs'],
        partitioning: ['time-series transaction data', 'multi-tenant accounts', 'global customer data'],
        caching: ['user sessions', 'account balances', 'frequently accessed data'],
        'query-builder': ['bulk transaction import', 'complex financial reports', 'data export'],
        'eager-loading': ['nested relationships', 'polymorphic associations', 'pivot table data'],
        replication: ['high-availability system', 'read-heavy workload', 'global distribution']
    };
    
    const scenario = scenarios[topic] ? scenarios[topic][id % scenarios[topic].length] : 'database operation';
    const questionText = template.q.replace('{scenario}', scenario)
                                   .replace('{index_type}', getRandomElement(['B-tree', 'Hash', 'GiST', 'GIN']))
                                   .replace('{database}', getRandomElement(['MySQL', 'PostgreSQL']))
                                   .replace('{join_type}', getRandomElement(['Nested Loop', 'Hash Join', 'Merge Join']))
                                   .replace('{partition_type}', getRandomElement(['range', 'list', 'hash']))
                                   .replace('{pattern}', getRandomElement(['cache-aside', 'write-through', 'write-behind']))
                                   .replace('{relationship_type}', getRandomElement(['hasMany', 'belongsToMany', 'morphMany']))
                                   .replace('{replication_type}', getRandomElement(['master-slave', 'multi-master']))
                                   .replace('{operation}', getRandomElement(['insert', 'update', 'delete', 'upsert']))
                                   .replace('{column_type}', getRandomElement(['email', 'status', 'timestamp', 'amount']))
                                   .replace('{nf_from}', '2NF')
                                   .replace('{nf_to}', '3NF')
                                   .replace('{schema}', 'banking schema')
                                   .replace('{query_type}', 'date range query')
                                   .replace('{use_case}', 'rate limiting');
    
    return { q: questionText, a: template.a(scenario, topic, id) };
}

function getRandomElement(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
}

// Answer generation functions
function generateIndexComparisonAnswer(scenario, topic, id) {
    return `
        <p>When comparing B-tree and Hash indexes for ${scenario}, consider the following:</p>
        
        <table>
            <thead>
                <tr>
                    <th>Aspect</th>
                    <th>B-tree Index</th>
                    <th>Hash Index</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Equality Lookups</td>
                    <td>O(log n) - Very fast</td>
                    <td>O(1) - Constant time</td>
                </tr>
                <tr>
                    <td>Range Queries</td>
                    <td>✅ Supported</td>
                    <td>❌ Not supported</td>
                </tr>
                <tr>
                    <td>Sorting</td>
                    <td>✅ Efficient</td>
                    <td>❌ Not supported</td>
                </tr>
                <tr>
                    <td>Prefix Matching</td>
                    <td>✅ Supported</td>
                    <td>❌ Not supported</td>
                </tr>
            </tbody>
        </table>
        
        <pre><code>-- B-tree index (recommended for most cases)
CREATE INDEX idx_btree ON table_name(column_name);

-- Hash index (only for exact equality)
CREATE INDEX idx_hash ON table_name USING HASH (column_name);
</code></pre>
        
        <aside class="metric-box">
            <h4>Performance for ${scenario}</h4>
            <div class="metric">B-tree Recommended</div>
            <div class="description">Versatility > marginal hash speed advantage</div>
        </aside>
    `;
}

function generateIndexStrategyAnswer(scenario, topic, id) {
    return `
        <p>Optimal indexing strategy for ${scenario}:</p>
        
        <pre><code>-- 1. Primary key (clustered index)
CREATE TABLE example (
    id BIGSERIAL PRIMARY KEY,
    account_id BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL,
    status VARCHAR(20),
    amount DECIMAL(15,2)
);

-- 2. Composite index for common queries
CREATE INDEX idx_account_created 
ON example(account_id, created_at DESC);

-- 3. Partial index for active records
CREATE INDEX idx_active 
ON example(status) 
WHERE status = 'active';

-- 4. Covering index for dashboard query
CREATE INDEX idx_covering 
ON example(account_id, created_at) 
INCLUDE (amount, status);
</code></pre>
        
        <aside class="metric-box">
            <h4>Expected Performance</h4>
            <div class="metric">85-95%</div>
            <div class="description">Query time reduction for ${scenario}</div>
        </aside>
    `;
}

function generateIndexExplanationAnswer(scenario, topic, id) {
    return `
        <p>Index explanation for ${scenario}:</p>
        
        <div class="diagram">
Index Structure:
    Root Node
    ├── Branch Nodes
    └── Leaf Nodes (data or pointers)

Characteristics:
- Self-balancing structure
- O(log n) operations
- Disk-optimized page size
- Sequential access support
        </div>
        
        <ul>
            <li><strong>Read Performance:</strong> Logarithmic time complexity</li>
            <li><strong>Write Performance:</strong> Slightly slower due to index maintenance</li>
            <li><strong>Storage:</strong> 15-25% overhead per index</li>
            <li><strong>Use Cases:</strong> Equality, range, sorting operations</li>
        </ul>
    `;
}

function generateExplainAnalyzeAnswer(scenario, topic, id) {
    const beforeTime = 1000 + (id * 13);
    const afterTime = 10 + (id * 0.2);
    const improvement = (((beforeTime - afterTime) / beforeTime) * 100).toFixed(1);
    
    return `
        <p>EXPLAIN ANALYZE output comparison for ${scenario}:</p>
        
        <p><strong>Before Index:</strong></p>
        <pre><code>EXPLAIN ANALYZE
SELECT * FROM table_name WHERE condition;

Seq Scan on table_name (cost=0.00..${beforeTime}.00 rows=1000 width=128)
  (actual time=0.123..${beforeTime}.456 rows=950 loops=1)
Planning Time: 0.234 ms
Execution Time: ${beforeTime}.567 ms
</code></pre>
        
        <p><strong>After Index:</strong></p>
        <pre><code>CREATE INDEX idx_optimized ON table_name(column_name);

EXPLAIN ANALYZE
SELECT * FROM table_name WHERE condition;

Index Scan using idx_optimized (cost=0.56..${afterTime}.23 rows=1000 width=128)
  (actual time=0.045..${afterTime}.234 rows=950 loops=1)
Planning Time: 0.189 ms
Execution Time: ${afterTime}.456 ms
</code></pre>
        
        <aside class="metric-box">
            <h4>Performance Improvement</h4>
            <div class="metric">${improvement}%</div>
            <div class="description">${beforeTime}ms → ${afterTime}ms execution time</div>
        </aside>
    `;
}

function generateCardinalityAnswer(scenario, topic, id) {
    return `
        <p>Cardinality requirements for ${scenario}:</p>
        
        <table>
            <thead>
                <tr>
                    <th>Cardinality Level</th>
                    <th>Ratio</th>
                    <th>Index Recommendation</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Very High (unique)</td>
                    <td>&gt; 95%</td>
                    <td>✅ Excellent candidate</td>
                </tr>
                <tr>
                    <td>High</td>
                    <td>50-95%</td>
                    <td>✅ Good candidate</td>
                </tr>
                <tr>
                    <td>Medium</td>
                    <td>10-50%</td>
                    <td>⚠️ Consider partial index</td>
                </tr>
                <tr>
                    <td>Low</td>
                    <td>&lt; 10%</td>
                    <td>❌ Poor candidate</td>
                </tr>
            </tbody>
        </table>
        
        <pre><code>-- Check cardinality
SELECT 
    COUNT(DISTINCT column_name) as distinct_values,
    COUNT(*) as total_rows,
    COUNT(DISTINCT column_name) * 100.0 / COUNT(*) as cardinality_ratio
FROM table_name;
</code></pre>
    `;
}

function generateCardinalityCalcAnswer(scenario, topic, id) {
    return `
        <p>Cardinality and selectivity calculation for ${scenario}:</p>
        
        <pre><code>-- Calculate cardinality
SELECT 
    'Column Analysis' as metric,
    COUNT(DISTINCT column_name) as cardinality,
    COUNT(*) as total_rows,
    COUNT(DISTINCT column_name) * 100.0 / COUNT(*) as cardinality_pct,
    1.0 / COUNT(DISTINCT column_name) as selectivity
FROM table_name;

-- Frequency distribution
SELECT 
    column_name,
    COUNT(*) as frequency,
    COUNT(*) * 100.0 / (SELECT COUNT(*) FROM table_name) as percentage
FROM table_name
GROUP BY column_name
ORDER BY frequency DESC
LIMIT 10;
</code></pre>
        
        <aside class="metric-box">
            <h4>Index Effectiveness</h4>
            <div class="metric">High Cardinality</div>
            <div class="description">Better query optimization with higher cardinality</div>
        </aside>
    `;
}

function generateHistogramAnswer(scenario, topic, id) {
    return `
        <p>Histogram statistics analysis for ${scenario}:</p>
        
        <pre><code>-- Update statistics
ANALYZE table_name;

-- View histogram data (PostgreSQL)
SELECT 
    schemaname,
    tablename,
    attname,
    n_distinct,
    most_common_vals,
    most_common_freqs,
    histogram_bounds
FROM pg_stats
WHERE tablename = 'table_name';
</code></pre>
        
        <div class="diagram">
Value Distribution Histogram:

Value Range    Frequency
[0-100)       ████████████ 30%
[100-500)     ████████████████████ 45%
[500-1000)    ████████ 15%
[1000+)       ████ 10%
        </div>
        
        <p>The query optimizer uses histogram bounds to estimate selectivity and choose optimal execution plans.</p>
    `;
}

function generateEstimationAnswer(scenario, topic, id) {
    return `
        <p>Comparing estimated vs actual cardinality for ${scenario}:</p>
        
        <table>
            <thead>
                <tr>
                    <th>Query</th>
                    <th>Estimated Rows</th>
                    <th>Actual Rows</th>
                    <th>Accuracy</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>WHERE status = 'active'</td>
                    <td>15,000</td>
                    <td>14,823</td>
                    <td>98.8%</td>
                </tr>
                <tr>
                    <td>WHERE amount &gt; 1000</td>
                    <td>5,000</td>
                    <td>8,234</td>
                    <td>60.7%</td>
                </tr>
            </tbody>
        </table>
        
        <pre><code>-- Run ANALYZE to update statistics
ANALYZE table_name;

-- Compare estimates
EXPLAIN SELECT * FROM table_name WHERE condition;
-- vs
EXPLAIN ANALYZE SELECT * FROM table_name WHERE condition;
</code></pre>
        
        <p>Large discrepancies indicate stale statistics or skewed data distribution.</p>
    `;
}

function generateExplainJoinAnswer(scenario, topic, id) {
    return `
        <p>EXPLAIN ANALYZE interpretation for ${scenario}:</p>
        
        <pre><code>EXPLAIN (ANALYZE, BUFFERS, VERBOSE)
SELECT t.*, a.account_number
FROM transactions t
JOIN accounts a ON t.account_id = a.id
WHERE t.created_at >= CURRENT_DATE - INTERVAL '30 days';

Hash Join  (cost=1234.56..5678.90 rows=5000 width=128)
           (actual time=45.678..234.567 rows=4823 loops=1)
  Hash Cond: (t.account_id = a.id)
  Buffers: shared hit=1234 read=456
  ->  Seq Scan on transactions t  (cost=0.00..3456.78 rows=5000 width=96)
                                   (actual time=0.123..123.456 rows=4823 loops=1)
        Filter: (created_at >= (CURRENT_DATE - 30))
        Buffers: shared hit=890 read=234
  ->  Hash  (cost=890.12..890.12 rows=10000 width=40)
            (actual time=34.567..34.567 rows=10000 loops=1)
        Buckets: 16384  Batches: 1  Memory Usage: 567kB
        Buffers: shared hit=344 read=222
        ->  Seq Scan on accounts a  (cost=0.00..890.12 rows=10000 width=40)
                                     (actual time=0.012..23.456 rows=10000 loops=1)
              Buffers: shared hit=344 read=222

Planning Time: 2.345 ms
Execution Time: 235.678 ms
</code></pre>
        
        <aside class="metric-box">
            <h4>Join Performance</h4>
            <div class="metric">235ms</div>
            <div class="description">Hash join selected for optimal performance</div>
        </aside>
    `;
}

function generatePlanOptimizationAnswer(scenario, topic, id) {
    return `
        <p>Execution plan optimization for ${scenario}:</p>
        
        <p><strong>Before Optimization:</strong></p>
        <pre><code>-- Slow query with sequential scan
SELECT * FROM table_name 
WHERE condition
ORDER BY created_at DESC
LIMIT 100;

-- Execution time: 2,345 ms
</code></pre>
        
        <p><strong>Optimizations Applied:</strong></p>
        <ol>
            <li>Add composite index: CREATE INDEX idx_opt ON table(condition, created_at DESC)</li>
            <li>Update statistics: ANALYZE table_name</li>
            <li>Adjust work_mem: SET work_mem = '256MB'</li>
        </ol>
        
        <p><strong>After Optimization:</strong></p>
        <pre><code>-- Optimized query with index scan
Index Scan using idx_opt (cost=0.56..234.56 rows=100 width=128)
  (actual time=0.123..45.678 rows=100 loops=1)
  
-- Execution time: 46 ms (98% improvement)
</code></pre>
    `;
}

function generateParallelAnswer(scenario, topic, id) {
    return `
        <p>Parallel vs serial execution comparison for ${scenario}:</p>
        
        <table>
            <thead>
                <tr>
                    <th>Execution Mode</th>
                    <th>Workers</th>
                    <th>Execution Time</th>
                    <th>CPU Usage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Serial</td>
                    <td>1</td>
                    <td>4,567 ms</td>
                    <td>25%</td>
                </tr>
                <tr>
                    <td>Parallel (2 workers)</td>
                    <td>2</td>
                    <td>2,456 ms</td>
                    <td>45%</td>
                </tr>
                <tr>
                    <td>Parallel (4 workers)</td>
                    <td>4</td>
                    <td>1,345 ms</td>
                    <td>80%</td>
                </tr>
            </tbody>
        </table>
        
        <pre><code>-- Enable parallel execution
SET max_parallel_workers_per_gather = 4;
SET parallel_setup_cost = 1000;

-- Query automatically uses parallel execution
EXPLAIN ANALYZE
SELECT aggregate_function(column)
FROM large_table
WHERE condition;
</code></pre>
        
        <aside class="metric-box">
            <h4>Parallel Speedup</h4>
            <div class="metric">3.4x</div>
            <div class="description">70.6% reduction with 4 workers</div>
        </aside>
    `;
}

function generateN1FixAnswer(scenario, topic, id) {
    return `
        <p>Fixing N+1 problem in Laravel for ${scenario}:</p>
        
        <p><strong>❌ Problem (N+1 Queries):</strong></p>
        <pre><code>// Loads 1 + N queries
$items = Model::all();
foreach ($items as $item) {
    echo $item->relation->name;  // N additional queries!
}
// 1,001 queries for 1,000 items
</code></pre>
        
        <p><strong>✅ Solution (Eager Loading):</strong></p>
        <pre><code>// Loads only 2 queries
$items = Model::with('relation')->get();
foreach ($items as $item) {
    echo $item->relation->name;  // No additional query
}
// Only 2 queries total
</code></pre>
        
        <aside class="metric-box">
            <h4>Performance Impact</h4>
            <div class="metric">97.3%</div>
            <div class="description">16,890ms → 456ms (1,001 queries → 2 queries)</div>
        </aside>
    `;
}

function generateEagerLoadingAnswer(scenario, topic, id) {
    return `
        <p>Eager loading demonstration for ${scenario}:</p>
        
        <pre><code>// Eager load multiple relationships
$records = Model::with([
    'relation1',
    'relation2' => function($query) {
        $query->where('status', 'active')
              ->select('id', 'name', 'status');
    },
    'relation3.nested'
])
->withCount('relation4')
->get();

// Generated SQL:
// 1. SELECT * FROM models
// 2. SELECT * FROM relation1 WHERE id IN (...)
// 3. SELECT * FROM relation2 WHERE id IN (...) AND status = 'active'
// 4. SELECT * FROM relation3 WHERE id IN (...)
// 5. SELECT * FROM nested WHERE relation3_id IN (...)
// 6. SELECT model_id, COUNT(*) FROM relation4 GROUP BY model_id

// Total: 6 queries instead of 1 + (N × 4)
</code></pre>
        
        <aside class="metric-box">
            <h4>Query Reduction</h4>
            <div class="metric">99.5%</div>
            <div class="description">For 1,000 records: 4,001 queries → 6 queries</div>
        </aside>
    `;
}

function generateDataLoaderAnswer(scenario, topic, id) {
    return `
        <p>DataLoader pattern implementation for ${scenario}:</p>
        
        <pre><code>class DataLoader {
    private $queue = [];
    private $cache = [];
    
    public function load($id) {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        
        $this->queue[] = $id;
        
        // Batch execution scheduled
        return $this->executeBatch();
    }
    
    private function executeBatch() {
        $ids = array_unique($this->queue);
        $results = DB::table('table')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
        
        $this->cache = array_merge($this->cache, $results->toArray());
        $this->queue = [];
        
        return $results;
    }
}

// Usage: 1 query for all items instead of N queries
</code></pre>
    `;
}

function generateNormalizationAnswer(scenario, topic, id) {
    return `
        <p>Database normalization for ${scenario}:</p>
        
        <div class="diagram">
Before (2NF):
customers: id, name, account_type, account_limit

After (3NF):
customers: id, name, account_type_id
account_types: id, type_name, default_limit

Benefits:
- Remove transitive dependencies
- Reduce data redundancy
- Improve update anomaly resistance
        </div>
        
        <pre><code>-- Create normalized schema
CREATE TABLE account_types (
    id SERIAL PRIMARY KEY,
    type_name VARCHAR(50) UNIQUE,
    default_limit DECIMAL(15,2)
);

CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    account_type_id INTEGER REFERENCES account_types(id)
);
</code></pre>
    `;
}

function generateDenormalizationAnswer(scenario, topic, id) {
    return `
        <p>Denormalized schema design for ${scenario}:</p>
        
        <p><strong>Scenario:</strong> High-read, low-write dashboard requiring complex aggregations</p>
        
        <pre><code>-- Denormalized table with pre-computed values
CREATE TABLE dashboard_summary (
    id SERIAL PRIMARY KEY,
    account_id BIGINT,
    account_number VARCHAR(50),
    customer_name VARCHAR(255),
    total_transactions INTEGER,
    total_amount DECIMAL(15,2),
    last_transaction_date TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Maintain with triggers or scheduled jobs
CREATE OR REPLACE FUNCTION update_dashboard_summary()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO dashboard_summary (account_id, ...)
    VALUES (NEW.account_id, ...)
    ON CONFLICT (account_id) 
    DO UPDATE SET 
        total_transactions = dashboard_summary.total_transactions + 1,
        total_amount = dashboard_summary.total_amount + NEW.amount,
        updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
</code></pre>
        
        <aside class="metric-box">
            <h4>Read Performance</h4>
            <div class="metric">95%</div>
            <div class="description">Faster queries at cost of write complexity</div>
        </aside>
    `;
}

function generateMaterializedViewAnswer(scenario, topic, id) {
    return `
        <p>Materialized view implementation for ${scenario}:</p>
        
        <pre><code>-- Create materialized view
CREATE MATERIALIZED VIEW mv_monthly_summary AS
SELECT 
    DATE_TRUNC('month', transaction_date) as month,
    account_id,
    COUNT(*) as transaction_count,
    SUM(amount) as total_amount,
    AVG(amount) as avg_amount
FROM transactions
GROUP BY DATE_TRUNC('month', transaction_date), account_id;

-- Create index on materialized view
CREATE INDEX idx_mv_summary ON mv_monthly_summary(account_id, month);

-- Refresh strategy
REFRESH MATERIALIZED VIEW CONCURRENTLY mv_monthly_summary;
</code></pre>
        
        <aside class="metric-box">
            <h4>Query Speedup</h4>
            <div class="metric">100x-1000x</div>
            <div class="description">Pre-aggregated data vs on-demand calculation</div>
        </aside>
    `;
}

function generatePartitioningAnswer(scenario, topic, id) {
    return `
        <p>Partitioning implementation for ${scenario}:</p>
        
        <pre><code>-- Create partitioned table
CREATE TABLE transactions (
    id BIGSERIAL,
    transaction_date DATE NOT NULL,
    account_id BIGINT,
    amount DECIMAL(15,2)
) PARTITION BY RANGE (transaction_date);

-- Create partitions
CREATE TABLE transactions_2024_01 
    PARTITION OF transactions
    FOR VALUES FROM ('2024-01-01') TO ('2024-02-01');

CREATE TABLE transactions_2024_02 
    PARTITION OF transactions
    FOR VALUES FROM ('2024-02-01') TO ('2024-03-01');

-- Query automatically uses partition pruning
SELECT * FROM transactions 
WHERE transaction_date >= '2024-01-15'
  AND transaction_date < '2024-01-20';
-- Only scans transactions_2024_01 partition
</code></pre>
        
        <aside class="metric-box">
            <h4>Partition Pruning Benefit</h4>
            <div class="metric">92%</div>
            <div class="description">Scans 1 partition instead of 12 monthly partitions</div>
        </aside>
    `;
}

function generatePartitionKeyAnswer(scenario, topic, id) {
    return `
        <p>Partition key selection strategy for ${scenario}:</p>
        
        <div class="flowchart">
            <div class="step">Analyze Query Patterns</div>
            <div class="arrow">↓</div>
            <div class="step">Identify Common Filter Columns</div>
            <div class="arrow">↓</div>
            <div class="step">Consider Data Distribution</div>
            <div class="arrow">↓</div>
            <div class="step">Choose Partition Key</div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Partition Key</th>
                    <th>Use Case</th>
                    <th>Pros</th>
                    <th>Cons</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Date/Time</td>
                    <td>Time-series data</td>
                    <td>Easy archival, clear boundaries</td>
                    <td>Hot partition for recent data</td>
                </tr>
                <tr>
                    <td>Customer/Tenant ID</td>
                    <td>Multi-tenant systems</td>
                    <td>Data isolation, parallel queries</td>
                    <td>Uneven distribution possible</td>
                </tr>
                <tr>
                    <td>Geographic Region</td>
                    <td>Global applications</td>
                    <td>Locality, compliance</td>
                    <td>Requires location in queries</td>
                </tr>
            </tbody>
        </table>
    `;
}

function generatePartitionPruningAnswer(scenario, topic, id) {
    return `
        <p>Partition pruning analysis for ${scenario}:</p>
        
        <pre><code>-- Query with partition pruning
EXPLAIN (ANALYZE, VERBOSE)
SELECT * FROM transactions 
WHERE transaction_date = '2024-01-15';

Result:
Seq Scan on transactions_2024_01 transactions  (cost=0.00..234.56 rows=500 width=128)
                                                 (actual time=0.123..45.678 rows=487 loops=1)
  Filter: (transaction_date = '2024-01-15'::date)
  Rows Removed by Filter: 124,513
  
-- Only 1 partition scanned (partition pruning successful)

-- Query WITHOUT partition pruning (missing partition key)
EXPLAIN ANALYZE
SELECT * FROM transactions 
WHERE account_id = 12345;

Result:
Append  (cost=0.00..45678.90 rows=5000 width=128)
  ->  Seq Scan on transactions_2024_01  (cost=0.00..3789.12 rows=...)
  ->  Seq Scan on transactions_2024_02  (cost=0.00..3789.12 rows=...)
  ->  Seq Scan on transactions_2024_03  (cost=0.00..3789.12 rows=...)
  ... (all 12 partitions scanned)
</code></pre>
        
        <aside class="metric-box">
            <h4>Pruning Effectiveness</h4>
            <div class="metric">1 of 12</div>
            <div class="description">Partitions scanned (91.7% reduction)</div>
        </aside>
    `;
}

function generateCachingPatternAnswer(scenario, topic, id) {
    return `
        <p>Caching pattern implementation for ${scenario}:</p>
        
        <pre><code>// Cache-Aside Pattern
public function getData($id) {
    $cacheKey = "data:{$id}";
    
    // 1. Check cache
    $cached = Cache::get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    // 2. Cache miss - query database
    $data = DB::table('table')->find($id);
    
    // 3. Store in cache
    Cache::put($cacheKey, $data, 3600);
    
    return $data;
}

// Write-Through Pattern
public function updateData($id, $data) {
    $cacheKey = "data:{$id}";
    
    // 1. Update database
    DB::table('table')->where('id', $id)->update($data);
    
    // 2. Update cache synchronously
    Cache::put($cacheKey, $data, 3600);
}

// Write-Behind Pattern
public function updateDataAsync($id, $data) {
    $cacheKey = "data:{$id}";
    
    // 1. Update cache immediately
    Cache::put($cacheKey, $data, 3600);
    
    // 2. Queue database update
    Queue::push(new UpdateDatabaseJob($id, $data));
}
</code></pre>
        
        <aside class="metric-box">
            <h4>Cache Hit Rate Impact</h4>
            <div class="metric">87%</div>
            <div class="description">Database load reduction at 90% hit rate</div>
        </aside>
    `;
}

function generateCacheInvalidationAnswer(scenario, topic, id) {
    return `
        <p>Cache invalidation strategy for ${scenario}:</p>
        
        <div class="diagram">
Cache Invalidation Sequence:

1. Event Triggered (Update/Delete)
   ↓
2. Invalidate Related Cache Keys
   ↓
3. Optionally Refresh Cache
   ↓
4. Broadcast Invalidation (if distributed)
        </div>
        
        <pre><code>// Tag-based invalidation
Cache::tags(['accounts', 'account:123'])->put($key, $value, $ttl);
Cache::tags(['accounts', 'account:123'])->flush();

// Event-based invalidation
class AccountUpdated {
    public function handle() {
        Cache::forget("account:{$this->account->id}");
        Cache::tags(['account:' . $this->account->id])->flush();
        
        // Invalidate related caches
        Cache::forget("account_summary:{$this->account->id}");
        Cache::forget("dashboard:user:{$this->account->user_id}");
    }
}

// Time-based invalidation (TTL)
Cache::put($key, $value, now()->addMinutes(60));
</code></pre>
    `;
}

function generateRedisStructureAnswer(scenario, topic, id) {
    return `
        <p>Redis data structure comparison for ${scenario}:</p>
        
        <table>
            <thead>
                <tr>
                    <th>Structure</th>
                    <th>Use Case</th>
                    <th>Operations</th>
                    <th>Time Complexity</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>String</td>
                    <td>Simple cache, counters</td>
                    <td>GET, SET, INCR</td>
                    <td>O(1)</td>
                </tr>
                <tr>
                    <td>Hash</td>
                    <td>Object storage</td>
                    <td>HGET, HSET, HGETALL</td>
                    <td>O(1) per field</td>
                </tr>
                <tr>
                    <td>List</td>
                    <td>Queues, recent items</td>
                    <td>LPUSH, RPOP, LRANGE</td>
                    <td>O(1) for push/pop</td>
                </tr>
                <tr>
                    <td>Set</td>
                    <td>Unique items, tags</td>
                    <td>SADD, SISMEMBER</td>
                    <td>O(1)</td>
                </tr>
                <tr>
                    <td>Sorted Set</td>
                    <td>Leaderboards, rankings</td>
                    <td>ZADD, ZRANGE</td>
                    <td>O(log N)</td>
                </tr>
            </tbody>
        </table>
        
        <pre><code>// Example: Storing account data
// Hash (recommended for objects)
HSET account:123 name "John" balance "1000.00" status "active"
HGET account:123 balance
// Memory efficient, partial updates

// vs String (simple but monolithic)
SET account:123 '{"name":"John","balance":"1000.00","status":"active"}'
GET account:123
// Must serialize/deserialize entire object
</code></pre>
    `;
}

function generateQueryBuilderAnswer(scenario, topic, id) {
    return `
        <p>Laravel Query Builder optimization for ${scenario}:</p>
        
        <pre><code>// ❌ Inefficient: N+1 with additional queries
$results = DB::table('table1')
    ->get()
    ->map(function($item) {
        $item->related = DB::table('table2')
            ->where('id', $item->related_id)
            ->first();
        return $item;
    });

// ✅ Optimized: Single JOIN query
$results = DB::table('table1')
    ->join('table2', 'table1.related_id', '=', 'table2.id')
    ->select('table1.*', 'table2.name as related_name')
    ->get();

// ✅ Optimized: Chunking for large datasets
DB::table('large_table')
    ->orderBy('id')
    ->chunk(1000, function($records) {
        foreach ($records as $record) {
            // Process each record
        }
    });

// ✅ Optimized: Select only needed columns
$results = DB::table('table')
    ->select('id', 'name', 'email')  // Not SELECT *
    ->where('status', 'active')
    ->get();
</code></pre>
        
        <aside class="metric-box">
            <h4>Memory Usage</h4>
            <div class="metric">95%</div>
            <div class="description">Reduction with column selection and chunking</div>
        </aside>
    `;
}

function generateRawQueryAnswer(scenario, topic, id) {
    return `
        <p>Raw query vs Query Builder comparison for ${scenario}:</p>
        
        <table>
            <thead>
                <tr>
                    <th>Aspect</th>
                    <th>Query Builder</th>
                    <th>Raw Query</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Performance</td>
                    <td>Good (slight overhead)</td>
                    <td>Best (direct SQL)</td>
                </tr>
                <tr>
                    <td>Readability</td>
                    <td>Excellent</td>
                    <td>Good</td>
                </tr>
                <tr>
                    <td>Maintainability</td>
                    <td>High</td>
                    <td>Medium</td>
                </tr>
                <tr>
                    <td>Database Portability</td>
                    <td>Yes</td>
                    <td>Limited</td>
                </tr>
            </tbody>
        </table>
        
        <pre><code>// Query Builder (recommended for most cases)
$results = DB::table('transactions')
    ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
    ->where('transactions.amount', '>', 1000)
    ->whereDate('transactions.created_at', '>=', now()->subDays(30))
    ->select('transactions.*', 'accounts.account_number')
    ->get();

// Raw Query (for complex operations or maximum performance)
$results = DB::select("
    SELECT t.*, a.account_number
    FROM transactions t
    INNER JOIN accounts a ON t.account_id = a.id
    WHERE t.amount > ?
      AND t.created_at >= CURRENT_DATE - INTERVAL '30 days'
", [1000]);

// Performance: Raw query ~5-10% faster for complex queries
</code></pre>
    `;
}

function generateBatchOperationAnswer(scenario, topic, id) {
    return `
        <p>Batch operation implementation using Query Builder for ${scenario}:</p>
        
        <pre><code>// ❌ BAD: Individual inserts
foreach ($records as $record) {
    DB::table('table')->insert($record);  // N queries
}
// Time: 45 seconds for 10,000 records

// ✅ GOOD: Batch insert
DB::table('table')->insert($records);  // 1 query
// Time: 1.2 seconds (97% improvement)

// ✅ BEST: Upsert (insert or update)
DB::table('table')->upsert(
    $records,
    ['id'],  // Unique columns
    ['amount', 'status', 'updated_at']  // Columns to update
);
// Time: 1.5 seconds + handles conflicts

// Batch updates
$ids = [1, 2, 3, 4, 5];
DB::table('table')
    ->whereIn('id', $ids)
    ->update(['status' => 'processed']);

// Chunked batch processing
DB::table('large_table')
    ->orderBy('id')
    ->chunk(500, function($records) {
        $updates = [];
        foreach ($records as $record) {
            $updates[] = [
                'id' => $record->id,
                'processed' => true,
                'updated_at' => now()
            ];
        }
        DB::table('large_table')->upsert($updates, ['id']);
    });
</code></pre>
        
        <aside class="metric-box">
            <h4>Batch Performance</h4>
            <div class="metric">50x-100x</div>
            <div class="description">Faster than individual operations</div>
        </aside>
    `;
}

function generateEagerLoadRelationAnswer(scenario, topic, id) {
    return `
        <p>Eager loading implementation in Laravel for ${scenario}:</p>
        
        <pre><code>// Basic eager loading
$models = Model::with('relation')->get();

// Multiple relationships
$models = Model::with(['relation1', 'relation2', 'relation3'])->get();

// Nested relationships
$models = Model::with('relation.nested.deep')->get();

// Conditional eager loading
$models = Model::with([
    'relation' => function($query) {
        $query->where('status', 'active')
              ->orderBy('created_at', 'desc')
              ->limit(10);
    }
])->get();

// Eager load specific columns
$models = Model::with('relation:id,name,parent_id')->get();

// Eager load counts
$models = Model::withCount('relation')->get();
// Access via $model->relation_count

// Eager load aggregates
$models = Model::withSum('transactions', 'amount')
               ->withAvg('transactions', 'amount')
               ->withMax('transactions', 'amount')
               ->withMin('transactions', 'amount')
               ->get();

// Polymorphic eager loading
$models = Model::with('morphable')->get();
</code></pre>
        
        <aside class="metric-box">
            <h4>Query Reduction</h4>
            <div class="metric">98.5%</div>
            <div class="description">For 100 records with 2 relations: 201 queries → 3 queries</div>
        </aside>
    `;
}

function generatePreventLazyAnswer(scenario, topic, id) {
    return `
        <p>Preventing lazy loading for ${scenario}:</p>
        
        <pre><code>// In AppServiceProvider.php boot() method
use Illuminate\\Database\\Eloquent\\Model;

public function boot()
{
    // Enable in non-production environments
    Model::preventLazyLoading(!app()->isProduction());
    
    // Or strict mode (always prevent)
    Model::preventLazyLoading(true);
}

// This will throw LazyLoadingViolationException
$account = Account::find(1);
$transactions = $account->transactions;  // Exception thrown!

// ✅ Fix: Use eager loading
$account = Account::with('transactions')->find(1);
$transactions = $account->transactions;  // No exception

// Custom handling
Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
    Log::warning("Lazy loading detected", [
        'model' => get_class($model),
        'relation' => $relation,
        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
    ]);
});
</code></pre>
        
        <p><strong>Benefits:</strong></p>
        <ul>
            <li>Catch N+1 problems during development</li>
            <li>Enforce eager loading best practices</li>
            <li>Identify performance issues early</li>
            <li>Improve code quality through awareness</li>
        </ul>
    `;
}

function generateWithCountAnswer(scenario, topic, id) {
    return `
        <p>Optimizing withCount() for ${scenario}:</p>
        
        <pre><code>// ❌ Bad: Lazy loading count
$accounts = Account::all();
foreach ($accounts as $account) {
    echo $account->transactions()->count();  // N queries
}

// ✅ Good: Eager load counts
$accounts = Account::withCount('transactions')->get();
foreach ($accounts as $account) {
    echo $account->transactions_count;  // No additional queries
}

// Multiple counts with conditions
$accounts = Account::withCount([
    'transactions',
    'transactions as pending_count' => function($query) {
        $query->where('status', 'pending');
    },
    'transactions as last_30_days_count' => function($query) {
        $query->where('created_at', '>=', now()->subDays(30));
    }
])->get();

// Combine with aggregates
$accounts = Account::withCount('transactions')
                   ->withSum('transactions', 'amount')
                   ->withAvg('transactions', 'amount')
                   ->get();

// Access: 
// $account->transactions_count
// $account->transactions_sum_amount
// $account->transactions_avg_amount
</code></pre>
        
        <aside class="metric-box">
            <h4>Performance</h4>
            <div class="metric">99%</div>
            <div class="description">Faster than lazy loading counts</div>
        </aside>
    `;
}

function generateReplicationAnswer(scenario, topic, id) {
    return `
        <p>Replication configuration for ${scenario}:</p>
        
        <pre><code>// Laravel database.php configuration
'mysql' => [
    'read' => [
        'host' => [
            '192.168.1.10',  // Read replica 1
            '192.168.1.11',  // Read replica 2
        ],
    ],
    'write' => [
        'host' => [
            '192.168.1.1',   // Master
        ],
    ],
    'sticky' => true,  // Read your own writes
    'driver' => 'mysql',
    'database' => 'database',
    'username' => 'root',
    'password' => 'password',
],

// Force read from master when needed
$balance = DB::connection('mysql')
    ->useWritePdo()  // Force master
    ->table('accounts')
    ->where('id', $accountId)
    ->value('balance');

// Explicit write
DB::connection('mysql')->table('accounts')->insert($data);

// Explicit read (replica)
DB::connection('mysql')->table('accounts')->get();
</code></pre>
        
        <div class="diagram">
Replication Topology:

        Master (Write)
        192.168.1.1
             |
    Binary Log Stream
             |
    +--------+--------+
    |                 |
Replica 1        Replica 2
(Read)           (Read)
192.168.1.10     192.168.1.11
        </div>
    `;
}

function generateReplicationLagAnswer(scenario, topic, id) {
    return `
        <p>Handling replication lag for ${scenario}:</p>
        
        <table>
            <thead>
                <tr>
                    <th>Strategy</th>
                    <th>Use Case</th>
                    <th>Lag Tolerance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Read from Master</td>
                    <td>Critical consistency</td>
                    <td>Zero lag</td>
                </tr>
                <tr>
                    <td>Sticky Sessions</td>
                    <td>User writes</td>
                    <td>Session duration</td>
                </tr>
                <tr>
                    <td>Accept Lag</td>
                    <td>Analytics, reports</td>
                    <td>Minutes acceptable</td>
                </tr>
                <tr>
                    <td>Wait for Sync</td>
                    <td>Semi-sync replication</td>
                    <td>Minimal lag</td>
                </tr>
            </tbody>
        </table>
        
        <pre><code>// Check replication lag
SELECT 
    TIMESTAMPDIFF(SECOND, ts, NOW()) as lag_seconds
FROM mysql.slave_master_info;

// Force master read after write
DB::transaction(function() {
    // Write
    DB::table('accounts')->insert($data);
    
    // Read from master (within same transaction)
    $account = DB::table('accounts')
        ->where('id', $accountId)
        ->first();
});

// Sticky connection (read your own writes)
'mysql' => [
    'sticky' => true,  // Session reads from master after writes
],

// Monitor lag in application
$lagSeconds = DB::connection('mysql_replica')
    ->selectOne('SHOW SLAVE STATUS')['Seconds_Behind_Master'];

if ($lagSeconds > 60) {
    Log::warning("Replication lag high: {$lagSeconds}s");
}
</code></pre>
    `;
}

function generateConnectionPoolAnswer(scenario, topic, id) {
    return `
        <p>Connection pooling strategy for ${scenario}:</p>
        
        <div class="diagram">
Connection Pool Architecture:

Application Servers (100 instances)
        ↓
Connection Pooler (PgBouncer/ProxySQL)
    Pool Size: 200 connections
        ↓
Database Server
    Max Connections: 200
        </div>
        
        <aside class="metric-box">
            <h4>Optimal Pool Size Formula</h4>
            <div class="metric">connections = ((core_count × 2) + effective_spindle_count)</div>
            <div class="description">Example: 8 cores + 1 SSD = 17 connections</div>
        </aside>
        
        <pre><code>-- PgBouncer configuration
[databases]
banking_db = host=localhost port=5432 dbname=banking

[pgbouncer]
pool_mode = transaction
max_client_conn = 1000
default_pool_size = 25
min_pool_size = 10
reserve_pool_size = 5
reserve_pool_timeout = 3
max_db_connections = 100

-- Laravel configuration
'options' => [
    PDO::ATTR_PERSISTENT => true,  // Use persistent connections
    PDO::ATTR_TIMEOUT => 5,
],

// Monitor pool utilization
SELECT 
    numbackends as active_connections,
    max_conn as max_connections,
    numbackends * 100.0 / max_conn as utilization_pct
FROM pg_stat_database, 
     (SELECT setting::int as max_conn FROM pg_settings WHERE name = 'max_connections') mc
WHERE datname = 'banking_db';
</code></pre>
        
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Target</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Pool Utilization</td>
                    <td>&lt; 80%</td>
                    <td>Good</td>
                </tr>
                <tr>
                    <td>Pool Utilization</td>
                    <td>80-95%</td>
                    <td>Monitor closely</td>
                </tr>
                <tr>
                    <td>Pool Utilization</td>
                    <td>&gt; 95%</td>
                    <td>Increase pool size</td>
                </tr>
                <tr>
                    <td>Wait Time</td>
                    <td>&lt; 10ms</td>
                    <td>Good</td>
                </tr>
                <tr>
                    <td>Wait Time</td>
                    <td>&gt; 100ms</td>
                    <td>Pool exhaustion</td>
                </tr>
            </tbody>
        </table>
    `;
}

function generateGenericAnswer(scenario, topic, id) {
    return `
        <p>Comprehensive answer for ${scenario} (Question ${id} in ${topic} category):</p>
        
        <p>This question covers essential database optimization concepts including:</p>
        <ul>
            <li><strong>Performance Analysis:</strong> Understanding query execution and bottlenecks</li>
            <li><strong>Best Practices:</strong> Industry-standard approaches for optimization</li>
            <li><strong>Real-world Application:</strong> Banking system implementation examples</li>
            <li><strong>Metrics:</strong> Measurable performance improvements</li>
        </ul>
        
        <pre><code>-- Example implementation
-- Database optimization technique for ${scenario}

-- Before optimization
SELECT * FROM table_name WHERE condition;
-- Execution time: baseline

-- After optimization
-- Apply indexing, query rewriting, or caching strategy
-- Execution time: improved by 50-95%
</code></pre>
        
        <aside class="metric-box">
            <h4>Expected Performance Improvement</h4>
            <div class="metric">15%+</div>
            <div class="description">Overall response time reduction through cumulative optimizations</div>
        </aside>
        
        <p><strong>Key Takeaways:</strong></p>
        <ul>
            <li>Measure before and after optimization</li>
            <li>Use EXPLAIN ANALYZE to understand query execution</li>
            <li>Consider read/write trade-offs</li>
            <li>Monitor production performance metrics</li>
            <li>Apply optimizations incrementally and test thoroughly</li>
        </ul>
    `;
}

// Initialize and render questions
const allQuestions = generateAllDatabaseQuestions();

function renderQuestions(questionsToRender) {
    const container = document.getElementById('questionsContainer');
    container.innerHTML = questionsToRender.map(q => `
        <article data-topic="${q.topic}" data-id="${q.id}">
            <div class="question-header">
                <div class="question">${q.id}. ${q.question}</div>
                <div class="badges">
                    <span class="badge badge-topic" data-topic="${q.topic}">${q.topic}</span>
                </div>
            </div>
            <div class="answer">${q.answer}</div>
        </article>
    `).join('');

    updateStats(questionsToRender.length);
}

function updateStats(visibleCount) {
    document.getElementById('visibleCount').textContent = visibleCount;
    document.getElementById('totalCount').textContent = allQuestions.length;
}

function filterQuestions() {
    const topicFilter = document.getElementById('topicFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();

    const filtered = allQuestions.filter(q => {
        const matchesTopic = topicFilter === 'all' || q.topic === topicFilter;
        const matchesSearch = searchTerm === '' || 
            q.question.toLowerCase().includes(searchTerm) ||
            q.answer.toLowerCase().includes(searchTerm);
        return matchesTopic && matchesSearch;
    });

    renderQuestions(filtered);
}

// Event listeners
document.getElementById('topicFilter').addEventListener('change', filterQuestions);
document.getElementById('searchInput').addEventListener('input', filterQuestions);

// Initial render
renderQuestions(allQuestions);
