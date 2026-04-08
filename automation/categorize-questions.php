<?php

/**
 * Question Categorizer
 * 
 * Automatically categorizes parsed questions into primary categories using keyword
 * analysis and pattern matching. Outputs categorized markdown files organized by category.
 * 
 * Categories:
 * - PHP Core
 * - Laravel Framework
 * - Database MySQL
 * - API Security
 * - OOP Design
 * - JavaScript/Frontend
 * - CMS WordPress
 * - CMS Magento/Drupal
 * - DevOps/Git
 * - Cloud Infrastructure
 * - CodeIgniter
 * - Testing/Debugging
 * - Coding Challenges
 * 
 * Usage:
 *   php categorize-questions.php <input-file> [output-dir]
 * 
 * Example:
 *   php categorize-questions.php parsed-questions.json categorized-output/
 */

class QuestionCategorizer
{
    private array $questions = [];
    private array $categorizedQuestions = [];
    private array $statistics = [];
    private string $outputDir;
    
    // Primary categories with comprehensive keyword patterns
    private array $categoryDefinitions = [
        'PHP Core' => [
            'keywords' => [
                'php', 'namespace', 'trait', 'interface', 'abstract', 'class',
                'psr-', 'composer', 'autoload', 'opcache', 'jit compiler',
                'attribute', 'enum', 'fiber', 'readonly', 'named argument',
                'variadic', 'generator', 'iterator', 'closure', 'anonymous function',
                'magic method', '__construct', '__destruct', '__call', '__get', '__set',
                'pdo', 'mysqli', 'session', 'cookie', 'header', 'superglobal',
                'exception handling', 'error handling', 'try catch', 'throw',
                'type declaration', 'type hint', 'union type', 'intersection type',
                'static', 'final', 'private', 'protected', 'public', 'visibility',
                'spl', 'standard php library', 'array_', 'string_', 'preg_',
                'serialize', 'unserialize', 'json_encode', 'json_decode',
                'php.ini', 'php-fpm', 'xdebug', 'reflection', 'reflectionclass'
            ],
            'weight' => 1.0,
            'exclusions' => ['laravel', 'eloquent', 'blade', 'artisan', 'wordpress', 'magento', 'drupal', 'codeigniter']
        ],
        'Laravel Framework' => [
            'keywords' => [
                'laravel', 'eloquent', 'artisan', 'blade', 'middleware', 'route',
                'controller', 'model', 'migration', 'service provider', 'facade',
                'request validation', 'form request', 'resource controller',
                'api resource', 'collection', 'accessor', 'mutator', 'scope',
                'relationship', 'hasmany', 'belongsto', 'polymorphic',
                'queue', 'job', 'dispatch', 'broadcast', 'event', 'listener',
                'notification', 'mail', 'mailable', 'mailgun', 'ses',
                'sanctum', 'passport', 'fortify', 'breeze', 'jetstream',
                'telescope', 'horizon', 'vapor', 'forge', 'envoyer',
                'livewire', 'inertia', 'nova', 'spark', 'cashier',
                'scout', 'socialite', 'dusk', 'pest', 'phpunit',
                'config cache', 'route cache', 'view cache', 'artisan command',
                'service container', 'dependency injection', 'ioc', 'binding'
            ],
            'weight' => 1.5,
            'exclusions' => []
        ],
        'Database MySQL' => [
            'keywords' => [
                'mysql', 'innodb', 'myisam', 'mariadb', 'sql',
                'b-tree', 'b+tree', 'query optimization', 'index', 'composite index',
                'join', 'inner join', 'left join', 'right join', 'cross join',
                'transaction', 'acid', 'isolation level', 'read committed',
                'repeatable read', 'serializable', 'deadlock',
                'replication', 'master-slave', 'binlog', 'binary log',
                'slow query', 'explain', 'execution plan', 'query cache',
                'stored procedure', 'trigger', 'view', 'materialized view',
                'normalization', 'denormalization', '1nf', '2nf', '3nf', 'bcnf',
                'foreign key', 'primary key', 'unique key', 'constraint',
                'partition', 'sharding', 'table lock', 'row lock',
                'buffer pool', 'redo log', 'undo log', 'checkpoint',
                'full-text search', 'spatial index', 'charset', 'collation',
                'mysql workbench', 'phpmyadmin', 'mysqldump', 'backup', 'restore'
            ],
            'weight' => 1.3,
            'exclusions' => ['postgresql', 'postgres', 'mongodb', 'redis', 'elasticsearch']
        ],
        'API Security' => [
            'keywords' => [
                'api', 'rest', 'restful', 'graphql', 'soap',
                'authentication', 'authorization', 'oauth', 'oauth2',
                'jwt', 'json web token', 'bearer token', 'access token', 'refresh token',
                'api key', 'api secret', 'api gateway',
                'csrf', 'xss', 'sql injection', 'injection attack',
                'cors', 'cross-origin', 'same-origin policy',
                'rate limit', 'throttle', 'api throttling',
                'encryption', 'hashing', 'bcrypt', 'argon2',
                'ssl', 'tls', 'https', 'certificate',
                'security header', 'content security policy', 'csp',
                'owasp', 'owasp top 10', 'pci dss', 'gdpr',
                'sanitization', 'validation', 'input validation',
                'session hijacking', 'session fixation', 'clickjacking',
                'two-factor', '2fa', 'mfa', 'multi-factor',
                'password reset', 'password policy', 'brute force',
                'idempotency', 'idempotent', 'webhook security',
                'api versioning', 'api documentation', 'swagger', 'openapi'
            ],
            'weight' => 1.4,
            'exclusions' => []
        ],
        'OOP Design' => [
            'keywords' => [
                'oop', 'object-oriented', 'design pattern', 'design principle',
                'solid', 'single responsibility', 'open-closed', 'liskov substitution',
                'interface segregation', 'dependency inversion',
                'singleton', 'factory', 'abstract factory', 'builder', 'prototype',
                'adapter', 'bridge', 'composite', 'decorator', 'facade pattern',
                'flyweight', 'proxy', 'chain of responsibility', 'command',
                'iterator pattern', 'mediator', 'memento', 'observer',
                'state pattern', 'strategy', 'template method', 'visitor',
                'repository pattern', 'service layer', 'dto', 'data transfer object',
                'value object', 'entity', 'aggregate', 'domain model',
                'dependency injection', 'inversion of control', 'ioc container',
                'polymorphism', 'inheritance', 'encapsulation', 'abstraction',
                'composition', 'aggregation', 'association',
                'uml', 'class diagram', 'sequence diagram',
                'dry', 'kiss', 'yagni', 'grasp', 'law of demeter'
            ],
            'weight' => 1.2,
            'exclusions' => []
        ],
        'JavaScript/Frontend' => [
            'keywords' => [
                'javascript', 'ecmascript', 'es6', 'es2015', 'es2020', 'es2021',
                'typescript', 'type annotation', 'generic', 'type guard',
                'react', 'reactjs', 'jsx', 'tsx', 'hook', 'usestate', 'useeffect',
                'usecontext', 'usereducer', 'usememo', 'usecallback', 'useref',
                'component', 'props', 'state', 'virtual dom', 'reconciliation',
                'fiber', 'concurrent mode', 'suspense', 'error boundary',
                'next.js', 'nextjs', 'app router', 'pages router', 'server component',
                'static generation', 'ssg', 'ssr', 'isr', 'incremental static regeneration',
                'getstaticprops', 'getserversideprops', 'getstaticpaths',
                'vue', 'vuejs', 'vue3', 'composition api', 'options api',
                'angular', 'angularjs', 'rxjs', 'observable',
                'webpack', 'vite', 'rollup', 'parcel', 'esbuild',
                'babel', 'transpile', 'polyfill',
                'dom', 'dom manipulation', 'event listener', 'event delegation',
                'async', 'await', 'promise', 'fetch', 'axios',
                'closure', 'scope', 'hoisting', 'this keyword', 'bind', 'call', 'apply',
                'prototype', 'prototype chain', 'class syntax',
                'event loop', 'call stack', 'callback queue', 'microtask',
                'module', 'import', 'export', 'esm', 'commonjs',
                'html', 'css', 'sass', 'scss', 'less', 'tailwind',
                'responsive', 'media query', 'flexbox', 'grid', 'css grid'
            ],
            'weight' => 1.3,
            'exclusions' => []
        ],
        'CMS WordPress' => [
            'keywords' => [
                'wordpress', 'wp', 'wp-admin', 'wp-content',
                'plugin', 'theme', 'child theme', 'functions.php',
                'hook', 'filter', 'action', 'add_action', 'add_filter',
                'custom post type', 'taxonomy', 'custom taxonomy',
                'gutenberg', 'block editor', 'classic editor',
                'widget', 'sidebar', 'menu', 'navigation menu',
                'shortcode', 'template tag', 'template hierarchy',
                'the_loop', 'wp_query', 'get_posts', 'query_posts',
                'wpdb', 'wp database', 'wp_options', 'wp_posts', 'wp_postmeta',
                'multisite', 'network', 'super admin',
                'rest api', 'wp-json', 'wp rest api',
                'woocommerce', 'easy digital downloads', 'edd',
                'acf', 'advanced custom fields', 'custom field',
                'yoast', 'seo', 'wp seo',
                'wp-cli', 'wordpress cli', 'wp command',
                'htaccess', 'permalink', 'pretty permalink',
                'wp_enqueue_script', 'wp_enqueue_style'
            ],
            'weight' => 1.5,
            'exclusions' => []
        ],
        'CMS Magento/Drupal' => [
            'keywords' => [
                'magento', 'magento 2', 'magento2',
                'module', 'extension', 'component',
                'di.xml', 'dependency injection xml',
                'layout xml', 'block', 'phtml',
                'observer', 'plugin', 'preference',
                'repository', 'service contract', 'api interface',
                'eav', 'entity attribute value',
                'catalog', 'product', 'category', 'sku',
                'checkout', 'cart', 'quote', 'order',
                'payment gateway', 'shipping method',
                'drupal', 'drupal 8', 'drupal 9', 'drupal 10',
                'node', 'entity', 'content type',
                'views', 'panels', 'ctools',
                'hook_', 'preprocess', 'template',
                'twig', 'theme layer',
                'field', 'field api', 'entity api',
                'configuration management', 'config sync',
                'drush', 'drupal console'
            ],
            'weight' => 1.5,
            'exclusions' => []
        ],
        'DevOps/Git' => [
            'keywords' => [
                'devops', 'devsecops', 'gitops',
                'ci/cd', 'continuous integration', 'continuous deployment', 'continuous delivery',
                'pipeline', 'build pipeline', 'deployment pipeline',
                'jenkins', 'jenkins pipeline', 'jenkinsfile',
                'gitlab', 'gitlab ci', 'gitlab runner', '.gitlab-ci.yml',
                'github actions', 'github workflow', 'github runner',
                'circleci', 'travis ci', 'bamboo', 'teamcity',
                'git', 'github', 'version control', 'source control',
                'commit', 'branch', 'merge', 'rebase', 'cherry-pick',
                'pull request', 'merge request', 'code review',
                'git flow', 'github flow', 'trunk-based development',
                'tag', 'release', 'semantic versioning', 'semver',
                'git hook', 'pre-commit', 'pre-push', 'post-commit',
                'infrastructure as code', 'iac',
                'terraform', 'terragrunt', 'cloudformation', 'pulumi',
                'ansible', 'chef', 'puppet', 'saltstack',
                'configuration management', 'provisioning',
                'monitoring', 'observability', 'logging',
                'prometheus', 'grafana', 'elk', 'elasticsearch', 'logstash', 'kibana',
                'datadog', 'new relic', 'splunk', 'cloudwatch',
                'artifact', 'artifact repository', 'nexus', 'artifactory',
                'sonarqube', 'code quality', 'static analysis',
                'blue-green deployment', 'canary deployment', 'rolling deployment',
                'feature flag', 'feature toggle', 'launchdarkly'
            ],
            'weight' => 1.2,
            'exclusions' => []
        ],
        'Cloud Infrastructure' => [
            'keywords' => [
                'aws', 'amazon web services', 'ec2', 'elastic compute cloud',
                's3', 'simple storage service', 'glacier',
                'lambda', 'serverless', 'function as a service', 'faas',
                'cloudformation', 'cdk', 'cloud development kit',
                'eks', 'elastic kubernetes service', 'ecs', 'elastic container service',
                'rds', 'relational database service', 'aurora',
                'elasticache', 'elasticache redis', 'memcached',
                'cloudwatch', 'cloudtrail', 'x-ray',
                'vpc', 'virtual private cloud', 'subnet', 'security group',
                'elb', 'elastic load balancer', 'alb', 'nlb', 'application load balancer',
                'route 53', 'dns', 'cloudfront', 'cdn',
                'sqs', 'simple queue service', 'sns', 'simple notification service',
                'kinesis', 'kinesis stream', 'firehose',
                'api gateway', 'rest api', 'websocket api',
                'cognito', 'iam', 'identity and access management',
                'secrets manager', 'parameter store', 'kms', 'key management service',
                'step functions', 'state machine', 'workflow',
                'gcp', 'google cloud platform', 'compute engine', 'gce',
                'app engine', 'cloud functions', 'cloud run',
                'cloud storage', 'gcs', 'big query',
                'azure', 'microsoft azure', 'azure functions',
                'blob storage', 'azure sql', 'cosmos db',
                'docker', 'dockerfile', 'container', 'image', 'docker-compose',
                'volume', 'network', 'registry', 'docker hub', 'ecr',
                'kubernetes', 'k8s', 'pod', 'deployment', 'service',
                'ingress', 'namespace', 'configmap', 'secret',
                'helm', 'helm chart', 'kubectl', 'kustomize',
                'replica', 'replicaset', 'statefulset', 'daemonset',
                'horizontal pod autoscaler', 'hpa', 'cluster autoscaler'
            ],
            'weight' => 1.4,
            'exclusions' => []
        ],
        'CodeIgniter' => [
            'keywords' => [
                'codeigniter', 'ci3', 'ci4', 'codeigniter 3', 'codeigniter 4',
                'mvc', 'model view controller',
                'ci_controller', 'ci_model', 'ci_loader',
                'database library', 'query builder', 'active record',
                'form validation', 'validation library',
                'session library', 'cookie helper',
                'email library', 'upload library',
                'routing', 'uri routing', 'routes.php',
                'helper', 'url helper', 'form helper', 'text helper',
                'autoload.php', 'config.php', 'database.php',
                'hooks', 'ci hooks', 'pre_controller', 'post_controller'
            ],
            'weight' => 1.5,
            'exclusions' => []
        ],
        'Testing/Debugging' => [
            'keywords' => [
                'test', 'testing', 'unit test', 'unit testing',
                'integration test', 'e2e test', 'end-to-end test',
                'functional test', 'acceptance test',
                'phpunit', 'pest', 'phpspec', 'codeception',
                'jest', 'mocha', 'chai', 'jasmine', 'karma',
                'cypress', 'selenium', 'webdriver', 'playwright', 'puppeteer',
                'test coverage', 'code coverage', 'coverage report',
                'mock', 'stub', 'spy', 'fake', 'dummy',
                'mocking', 'mockery', 'prophecy',
                'test-driven development', 'tdd', 'behavior-driven development', 'bdd',
                'given when then', 'arrange act assert', 'aaa pattern',
                'test case', 'test suite', 'test runner', 'test fixture',
                'assertion', 'assert', 'expect', 'should',
                'debug', 'debugging', 'debugger', 'breakpoint',
                'xdebug', 'kint', 'whoops', 'error handler',
                'profiling', 'profiler', 'performance profiling',
                'blackfire', 'new relic', 'tideways',
                'log', 'logging', 'logger', 'monolog', 'psr-3',
                'error tracking', 'sentry', 'bugsnag', 'rollbar',
                'smoke test', 'regression test', 'sanity test'
            ],
            'weight' => 1.1,
            'exclusions' => []
        ],
        'Coding Challenges' => [
            'keywords' => [
                'algorithm', 'data structure', 'time complexity', 'space complexity',
                'big o', 'o(n)', 'o(log n)', 'o(n^2)', 'complexity analysis',
                'array', 'linked list', 'doubly linked list', 'circular linked list',
                'stack', 'queue', 'priority queue', 'deque',
                'tree', 'binary tree', 'binary search tree', 'bst', 'avl tree',
                'heap', 'min heap', 'max heap', 'binary heap',
                'graph', 'directed graph', 'undirected graph', 'weighted graph',
                'hash table', 'hash map', 'dictionary', 'set',
                'sorting', 'bubble sort', 'insertion sort', 'selection sort',
                'merge sort', 'quick sort', 'heap sort', 'radix sort',
                'searching', 'binary search', 'linear search', 'depth-first search', 'dfs',
                'breadth-first search', 'bfs', 'dijkstra', 'bellman-ford',
                'dynamic programming', 'dp', 'memoization', 'tabulation',
                'greedy algorithm', 'divide and conquer', 'backtracking',
                'recursion', 'recursive', 'tail recursion',
                'palindrome', 'anagram', 'fibonacci', 'factorial',
                'string manipulation', 'substring', 'subsequence',
                'two pointers', 'sliding window', 'kadane',
                'leetcode', 'hackerrank', 'codewars', 'project euler',
                'coding interview', 'technical interview', 'whiteboard',
                'fizzbuzz', 'reverse string', 'reverse array'
            ],
            'weight' => 1.3,
            'exclusions' => []
        ]
    ];
    
    // Secondary category mapping for multi-category questions
    private array $categoryHierarchy = [
        'Laravel Framework' => ['PHP Core'],
        'CodeIgniter' => ['PHP Core'],
        'CMS WordPress' => ['PHP Core'],
        'CMS Magento/Drupal' => ['PHP Core'],
        'API Security' => ['PHP Core', 'Laravel Framework'],
        'Database MySQL' => ['PHP Core', 'Laravel Framework']
    ];

    public function __construct()
    {
        $this->initializeStatistics();
    }

    private function initializeStatistics(): void
    {
        $this->statistics = [
            'total_questions' => 0,
            'categorized' => 0,
            'uncategorized' => 0,
            'multi_category' => 0,
            'by_category' => [],
            'by_difficulty' => [],
            'processing_time' => 0,
            'warnings' => []
        ];
        
        foreach (array_keys($this->categoryDefinitions) as $category) {
            $this->statistics['by_category'][$category] = 0;
        }
    }

    public function loadQuestions(string $inputFile): void
    {
        if (!file_exists($inputFile)) {
            throw new Exception("Input file not found: {$inputFile}");
        }

        $content = file_get_contents($inputFile);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON file: " . json_last_error_msg());
        }

        if (isset($data['questions'])) {
            $this->questions = $data['questions'];
        } else {
            $this->questions = $data;
        }

        $this->statistics['total_questions'] = count($this->questions);
    }

    public function categorizeQuestions(): void
    {
        $startTime = microtime(true);

        foreach ($this->questions as $question) {
            $categorization = $this->categorizeQuestion($question);
            
            $primaryCategory = $categorization['primary'];
            $secondaryCategories = $categorization['secondary'] ?? [];
            
            if ($primaryCategory === 'Uncategorized') {
                $this->statistics['uncategorized']++;
                $this->statistics['warnings'][] = "Question #{$question['id']} could not be categorized: " . substr($question['text'], 0, 50) . '...';
            } else {
                $this->statistics['categorized']++;
            }
            
            if (count($secondaryCategories) > 0) {
                $this->statistics['multi_category']++;
            }

            if (!isset($this->categorizedQuestions[$primaryCategory])) {
                $this->categorizedQuestions[$primaryCategory] = [];
            }

            $categorizedQuestion = array_merge($question, [
                'primary_category' => $primaryCategory,
                'secondary_categories' => $secondaryCategories,
                'categorization_score' => $categorization['score'],
                'categorization_confidence' => $categorization['confidence']
            ]);

            $this->categorizedQuestions[$primaryCategory][] = $categorizedQuestion;
            $this->statistics['by_category'][$primaryCategory]++;
            
            $difficulty = $question['difficulty'] ?? 'unknown';
            if (!isset($this->statistics['by_difficulty'][$difficulty])) {
                $this->statistics['by_difficulty'][$difficulty] = 0;
            }
            $this->statistics['by_difficulty'][$difficulty]++;
        }

        $this->statistics['processing_time'] = round(microtime(true) - $startTime, 3);
    }

    private function categorizeQuestion(array $question): array
    {
        $text = strtolower($question['text']);
        $scores = [];

        foreach ($this->categoryDefinitions as $category => $definition) {
            $score = $this->calculateCategoryScore($text, $definition);
            
            if ($score > 0) {
                $scores[$category] = $score;
            }
        }

        if (empty($scores)) {
            return [
                'primary' => 'Uncategorized',
                'secondary' => [],
                'score' => 0,
                'confidence' => 'low'
            ];
        }

        arsort($scores);
        $primaryCategory = array_key_first($scores);
        $primaryScore = $scores[$primaryCategory];
        
        $secondaryCategories = [];
        $scoreThreshold = $primaryScore * 0.6;
        
        foreach ($scores as $category => $score) {
            if ($category !== $primaryCategory && $score >= $scoreThreshold) {
                $secondaryCategories[] = $category;
            }
        }

        $confidence = $this->determineConfidence($primaryScore, $scores);

        return [
            'primary' => $primaryCategory,
            'secondary' => $secondaryCategories,
            'score' => $primaryScore,
            'confidence' => $confidence,
            'all_scores' => $scores
        ];
    }

    private function calculateCategoryScore(string $text, array $definition): float
    {
        $score = 0.0;
        $keywords = $definition['keywords'];
        $weight = $definition['weight'];
        $exclusions = $definition['exclusions'] ?? [];

        foreach ($exclusions as $exclusion) {
            if (strpos($text, strtolower($exclusion)) !== false) {
                return 0.0;
            }
        }

        foreach ($keywords as $keyword) {
            $keywordLower = strtolower($keyword);
            
            if (preg_match('/\b' . preg_quote($keywordLower, '/') . '\b/', $text)) {
                $score += $weight * 3.0;
            } elseif (strpos($text, $keywordLower) !== false) {
                $score += $weight * 1.5;
            }
        }

        return $score;
    }

    private function determineConfidence(float $primaryScore, array $allScores): string
    {
        if ($primaryScore < 3) {
            return 'low';
        }

        if (count($allScores) === 1) {
            return 'very_high';
        }

        arsort($allScores);
        $scores = array_values($allScores);
        
        if (count($scores) > 1) {
            $ratio = $scores[0] / $scores[1];
            
            if ($ratio > 3.0) {
                return 'very_high';
            } elseif ($ratio > 2.0) {
                return 'high';
            } elseif ($ratio > 1.5) {
                return 'medium';
            }
        }

        return 'medium';
    }

    public function exportToMarkdown(string $outputDir): void
    {
        $this->outputDir = rtrim($outputDir, '/\\');
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        foreach ($this->categorizedQuestions as $category => $questions) {
            $filename = $this->sanitizeFilename($category);
            $filepath = $this->outputDir . '/' . $filename . '.md';
            
            $this->exportCategoryToMarkdown($category, $questions, $filepath);
        }

        $this->exportSummaryMarkdown();
        $this->exportMasterIndex();
    }

    private function exportCategoryToMarkdown(string $category, array $questions, string $filepath): void
    {
        $md = "# {$category} - Interview Questions\n\n";
        $md .= "**Total Questions:** " . count($questions) . "\n";
        $md .= "**Generated:** " . date('Y-m-d H:i:s') . "\n\n";
        
        $md .= "---\n\n";

        $questionsByDifficulty = $this->groupByDifficulty($questions);
        
        foreach (['basic', 'intermediate', 'advanced', 'expert'] as $difficulty) {
            if (!empty($questionsByDifficulty[$difficulty])) {
                $md .= "## " . ucfirst($difficulty) . " Level\n\n";
                
                foreach ($questionsByDifficulty[$difficulty] as $question) {
                    $md .= "{$question['id']}. {$question['text']}\n";
                    
                    if (!empty($question['secondary_categories'])) {
                        $md .= "   *Also relevant to: " . implode(', ', $question['secondary_categories']) . "*\n";
                    }
                    
                    if (!empty($question['metadata']['tags'])) {
                        $md .= "   **Tags:** " . implode(', ', $question['metadata']['tags']) . "\n";
                    }
                    
                    $md .= "\n";
                }
            }
        }

        file_put_contents($filepath, $md);
    }

    private function groupByDifficulty(array $questions): array
    {
        $grouped = [
            'basic' => [],
            'intermediate' => [],
            'advanced' => [],
            'expert' => []
        ];

        foreach ($questions as $question) {
            $difficulty = $question['difficulty'] ?? 'intermediate';
            if (isset($grouped[$difficulty])) {
                $grouped[$difficulty][] = $question;
            } else {
                $grouped['intermediate'][] = $question;
            }
        }

        return $grouped;
    }

    private function exportSummaryMarkdown(): void
    {
        $filepath = $this->outputDir . '/SUMMARY.md';
        
        $md = "# Question Categorization Summary\n\n";
        $md .= "**Generated:** " . date('Y-m-d H:i:s') . "\n";
        $md .= "**Processing Time:** {$this->statistics['processing_time']} seconds\n\n";
        
        $md .= "## Overall Statistics\n\n";
        $md .= "- **Total Questions:** {$this->statistics['total_questions']}\n";
        $md .= "- **Categorized:** {$this->statistics['categorized']}\n";
        $md .= "- **Uncategorized:** {$this->statistics['uncategorized']}\n";
        $md .= "- **Multi-category:** {$this->statistics['multi_category']}\n\n";
        
        $md .= "## Questions by Category\n\n";
        $md .= "| Category | Count | Percentage |\n";
        $md .= "|----------|-------|------------|\n";
        
        arsort($this->statistics['by_category']);
        
        foreach ($this->statistics['by_category'] as $category => $count) {
            if ($count > 0) {
                $percentage = round(($count / $this->statistics['total_questions']) * 100, 1);
                $md .= "| {$category} | {$count} | {$percentage}% |\n";
            }
        }
        
        $md .= "\n## Questions by Difficulty\n\n";
        $md .= "| Difficulty | Count | Percentage |\n";
        $md .= "|------------|-------|------------|\n";
        
        foreach ($this->statistics['by_difficulty'] as $difficulty => $count) {
            $percentage = round(($count / $this->statistics['total_questions']) * 100, 1);
            $md .= "| " . ucfirst($difficulty) . " | {$count} | {$percentage}% |\n";
        }
        
        if (!empty($this->statistics['warnings'])) {
            $md .= "\n## Warnings\n\n";
            foreach (array_slice($this->statistics['warnings'], 0, 20) as $warning) {
                $md .= "- {$warning}\n";
            }
            
            if (count($this->statistics['warnings']) > 20) {
                $remaining = count($this->statistics['warnings']) - 20;
                $md .= "\n*...and {$remaining} more warnings*\n";
            }
        }
        
        file_put_contents($filepath, $md);
    }

    private function exportMasterIndex(): void
    {
        $filepath = $this->outputDir . '/INDEX.md';
        
        $md = "# Categorized Questions - Master Index\n\n";
        $md .= "**Generated:** " . date('Y-m-d H:i:s') . "\n";
        $md .= "**Total Questions:** {$this->statistics['total_questions']}\n\n";
        
        $md .= "## Categories\n\n";
        
        arsort($this->statistics['by_category']);
        
        foreach ($this->statistics['by_category'] as $category => $count) {
            if ($count > 0) {
                $filename = $this->sanitizeFilename($category);
                $md .= "### [{$category}](./{$filename}.md) ({$count} questions)\n\n";
                
                $categoryQuestions = $this->categorizedQuestions[$category] ?? [];
                if (!empty($categoryQuestions)) {
                    $difficultyBreakdown = $this->groupByDifficulty($categoryQuestions);
                    
                    $md .= "**Difficulty Breakdown:**\n";
                    foreach (['basic', 'intermediate', 'advanced', 'expert'] as $level) {
                        $levelCount = count($difficultyBreakdown[$level] ?? []);
                        if ($levelCount > 0) {
                            $md .= "- " . ucfirst($level) . ": {$levelCount}\n";
                        }
                    }
                    $md .= "\n";
                }
            }
        }
        
        $md .= "## Quick Links\n\n";
        $md .= "- [Summary Report](./SUMMARY.md)\n";
        
        foreach ($this->statistics['by_category'] as $category => $count) {
            if ($count > 0) {
                $filename = $this->sanitizeFilename($category);
                $md .= "- [{$category}](./{$filename}.md)\n";
            }
        }
        
        file_put_contents($filepath, $md);
    }

    private function sanitizeFilename(string $name): string
    {
        $filename = strtolower($name);
        $filename = preg_replace('/[^a-z0-9]+/', '-', $filename);
        $filename = trim($filename, '-');
        return $filename;
    }

    public function exportToJson(string $filepath): void
    {
        $output = [
            'metadata' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'version' => '1.0.0',
                'total_questions' => $this->statistics['total_questions']
            ],
            'statistics' => $this->statistics,
            'categories' => $this->categorizedQuestions
        ];

        $json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($filepath, $json);
    }

    public function getStatistics(): array
    {
        return $this->statistics;
    }

    public function printStatistics(): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "QUESTION CATEGORIZATION STATISTICS\n";
        echo str_repeat('=', 80) . "\n\n";
        
        echo "Total Questions: {$this->statistics['total_questions']}\n";
        echo "Categorized: {$this->statistics['categorized']}\n";
        echo "Uncategorized: {$this->statistics['uncategorized']}\n";
        echo "Multi-category: {$this->statistics['multi_category']}\n";
        echo "Processing Time: {$this->statistics['processing_time']} seconds\n\n";
        
        echo "Questions by Category:\n";
        echo str_repeat('-', 80) . "\n";
        
        arsort($this->statistics['by_category']);
        
        foreach ($this->statistics['by_category'] as $category => $count) {
            if ($count > 0) {
                $percentage = round(($count / $this->statistics['total_questions']) * 100, 1);
                $bar = str_repeat('█', min(40, (int)($percentage * 0.4)));
                printf("  %-30s: %4d (%5.1f%%) %s\n", $category, $count, $percentage, $bar);
            }
        }
        
        echo "\n" . str_repeat('=', 80) . "\n\n";
    }
}

if (php_sapi_name() === 'cli') {
    $scriptName = basename(__FILE__);
    
    if ($argc < 2) {
        echo "Usage: php {$scriptName} <input-file> [output-dir] [options]\n\n";
        echo "Arguments:\n";
        echo "  input-file   Path to parsed questions JSON file\n";
        echo "  output-dir   Directory for categorized markdown files (default: categorized/)\n\n";
        echo "Options:\n";
        echo "  --json=<file>    Also export to JSON file\n";
        echo "  --stats-only     Only show statistics, don't export files\n\n";
        echo "Examples:\n";
        echo "  php {$scriptName} parsed-questions.json\n";
        echo "  php {$scriptName} parsed-questions.json output/categorized/\n";
        echo "  php {$scriptName} parsed.json categorized/ --json=categorized.json\n";
        exit(1);
    }
    
    $inputFile = $argv[1];
    $outputDir = $argv[2] ?? 'categorized';
    
    $jsonFile = null;
    $statsOnly = false;
    
    for ($i = 2; $i < $argc; $i++) {
        if (strpos($argv[$i], '--json=') === 0) {
            $jsonFile = substr($argv[$i], 7);
        } elseif ($argv[$i] === '--stats-only') {
            $statsOnly = true;
        }
    }
    
    try {
        echo "Loading questions from: {$inputFile}\n";
        
        $categorizer = new QuestionCategorizer();
        $categorizer->loadQuestions($inputFile);
        
        echo "Categorizing questions...\n";
        $categorizer->categorizeQuestions();
        
        $categorizer->printStatistics();
        
        if (!$statsOnly) {
            echo "Exporting categorized questions to: {$outputDir}/\n";
            $categorizer->exportToMarkdown($outputDir);
            
            if ($jsonFile) {
                echo "Exporting JSON to: {$jsonFile}\n";
                $categorizer->exportToJson($jsonFile);
            }
            
            echo "\nCategorization completed successfully!\n";
            echo "Check {$outputDir}/INDEX.md for the master index\n";
            echo "Check {$outputDir}/SUMMARY.md for detailed statistics\n";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
