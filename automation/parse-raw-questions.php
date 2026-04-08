<?php

/**
 * Raw Questions Parser
 * 
 * Parses raw question lists, extracts individual questions, detects categories,
 * assigns difficulty levels, and generates structured JSON output.
 * 
 * Usage:
 *   php parse-raw-questions.php <input-file> [output-file]
 * 
 * Example:
 *   php parse-raw-questions.php raw-questions.txt parsed-questions.json
 */

class RawQuestionsParser
{
    private array $config;
    private array $parsedQuestions = [];
    private array $statistics = [];
    
    // Category detection keywords
    private array $categoryKeywords = [
        'Laravel' => [
            'keywords' => ['laravel', 'eloquent', 'artisan', 'blade', 'middleware', 'route', 'controller', 'model', 'migration', 'composer', 'php artisan', 'service provider', 'facade'],
            'weight' => 1.0
        ],
        'PHP' => [
            'keywords' => ['php', 'namespace', 'trait', 'interface', 'psr-', 'composer', 'autoload', 'opcache', 'jit compiler', 'attribute', 'enum', 'fiber', 'readonly', 'named argument'],
            'weight' => 1.0
        ],
        'MySQL' => [
            'keywords' => ['mysql', 'innodb', 'myisam', 'b-tree', 'query optimization', 'index', 'join', 'transaction', 'acid', 'isolation level', 'replication', 'binlog'],
            'weight' => 1.0
        ],
        'PostgreSQL' => [
            'keywords' => ['postgresql', 'postgres', 'pgsql', 'mvcc', 'vacuum', 'pg_stat', 'jsonb', 'ctid', 'toast'],
            'weight' => 1.0
        ],
        'Database' => [
            'keywords' => ['database', 'sql', 'query', 'table', 'schema', 'normalization', 'denormalization', 'index', 'foreign key', 'primary key', 'constraint'],
            'weight' => 0.8
        ],
        'React' => [
            'keywords' => ['react', 'jsx', 'hook', 'usestate', 'useeffect', 'component', 'props', 'virtual dom', 'fiber', 'concurrent', 'suspense'],
            'weight' => 1.0
        ],
        'Next.js' => [
            'keywords' => ['next.js', 'nextjs', 'next', 'app router', 'server component', 'static generation', 'isr', 'getstaticprops', 'getserversideprops'],
            'weight' => 1.0
        ],
        'JavaScript' => [
            'keywords' => ['javascript', 'ecmascript', 'es6', 'es2015', 'async', 'await', 'promise', 'closure', 'prototype', 'event loop', 'webpack', 'babel'],
            'weight' => 0.9
        ],
        'TypeScript' => [
            'keywords' => ['typescript', 'type annotation', 'interface', 'generic', 'type guard', 'utility type', 'declaration'],
            'weight' => 1.0
        ],
        'Docker' => [
            'keywords' => ['docker', 'dockerfile', 'container', 'image', 'docker-compose', 'volume', 'network', 'registry'],
            'weight' => 1.0
        ],
        'Kubernetes' => [
            'keywords' => ['kubernetes', 'k8s', 'pod', 'deployment', 'service', 'ingress', 'namespace', 'configmap', 'secret', 'helm', 'kubectl', 'replica', 'statefulset'],
            'weight' => 1.0
        ],
        'DevOps' => [
            'keywords' => ['devops', 'ci/cd', 'pipeline', 'jenkins', 'gitlab', 'github actions', 'terraform', 'ansible', 'monitoring'],
            'weight' => 0.9
        ],
        'AWS' => [
            'keywords' => ['aws', 'amazon web services', 'ec2', 's3', 'lambda', 'cloudformation', 'eks', 'rds', 'elasticache', 'cloudwatch'],
            'weight' => 1.0
        ],
        'Security' => [
            'keywords' => ['security', 'authentication', 'authorization', 'jwt', 'oauth', 'csrf', 'xss', 'sql injection', 'owasp', 'pci dss', 'encryption', 'ssl', 'tls'],
            'weight' => 1.0
        ],
        'API' => [
            'keywords' => ['api', 'rest', 'restful', 'graphql', 'endpoint', 'http', 'json', 'xml', 'webhook', 'rate limit', 'throttle'],
            'weight' => 0.9
        ],
        'Redis' => [
            'keywords' => ['redis', 'cache', 'session', 'pub/sub', 'sorted set', 'hash', 'redis cluster', 'sentinel'],
            'weight' => 1.0
        ],
        'Testing' => [
            'keywords' => ['test', 'testing', 'unit test', 'integration test', 'phpunit', 'jest', 'mocha', 'cypress', 'selenium', 'mock', 'stub'],
            'weight' => 0.9
        ],
        'WordPress' => [
            'keywords' => ['wordpress', 'wp', 'plugin', 'theme', 'hook', 'filter', 'action', 'custom post type', 'taxonomy', 'gutenberg'],
            'weight' => 1.0
        ],
        'Git' => [
            'keywords' => ['git', 'github', 'gitlab', 'commit', 'branch', 'merge', 'rebase', 'pull request', 'version control'],
            'weight' => 0.8
        ],
        'Performance' => [
            'keywords' => ['performance', 'optimization', 'cache', 'lazy load', 'code splitting', 'profiling', 'benchmark', 'bottleneck'],
            'weight' => 0.8
        ],
        'Microservices' => [
            'keywords' => ['microservice', 'service mesh', 'istio', 'event driven', 'message queue', 'rabbitmq', 'kafka'],
            'weight' => 1.0
        ],
        'AI/ML' => [
            'keywords' => ['ai', 'machine learning', 'llm', 'gpt', 'openai', 'claude', 'transformer', 'embedding', 'vector', 'rag', 'semantic search', 'langchain'],
            'weight' => 1.0
        ],
        'Serverless' => [
            'keywords' => ['serverless', 'lambda', 'function as a service', 'faas', 'api gateway', 'event bridge'],
            'weight' => 1.0
        ],
        'Real-time' => [
            'keywords' => ['real-time', 'realtime', 'websocket', 'socket.io', 'sse', 'server-sent events', 'pusher', 'broadcasting'],
            'weight' => 1.0
        ],
    ];
    
    // Subcategory keywords
    private array $subcategoryKeywords = [
        'Authentication' => ['auth', 'authentication', 'login', 'password', 'credential', 'session', 'token'],
        'Authorization' => ['authorization', 'permission', 'role', 'rbac', 'access control', 'policy'],
        'Caching' => ['cache', 'caching', 'redis', 'memcached', 'cdn', 'varnish'],
        'Database Design' => ['schema', 'normalization', 'denormalization', 'relationship', 'foreign key', 'primary key'],
        'Query Optimization' => ['query optimization', 'index', 'explain', 'execution plan', 'n+1', 'eager loading'],
        'State Management' => ['state', 'redux', 'zustand', 'context', 'recoil', 'mobx'],
        'Routing' => ['route', 'routing', 'router', 'navigation'],
        'Deployment' => ['deploy', 'deployment', 'production', 'staging', 'release'],
        'Monitoring' => ['monitoring', 'logging', 'metrics', 'prometheus', 'grafana', 'elk'],
        'Scaling' => ['scaling', 'horizontal scaling', 'vertical scaling', 'load balancing', 'sharding'],
        'Webhooks' => ['webhook', 'callback', 'event notification'],
        'Payment' => ['payment', 'razorpay', 'stripe', 'paypal', 'transaction'],
        'Error Handling' => ['error', 'exception', 'error handling', 'try catch', 'logging'],
    ];
    
    // Difficulty patterns
    private array $difficultyPatterns = [
        'basic' => [
            'keywords' => ['what is', 'define', 'explain', 'difference between', 'list', 'basic', 'simple', 'introduction to'],
            'complexity_indicators' => ['single concept', 'definition'],
            'score_threshold' => 0
        ],
        'intermediate' => [
            'keywords' => ['how do you', 'implement', 'create', 'write', 'compare', 'use case', 'practical'],
            'complexity_indicators' => ['implementation', 'practical application'],
            'score_threshold' => 3
        ],
        'advanced' => [
            'keywords' => ['optimize', 'design', 'architecture', 'production', 'scale', 'performance', 'troubleshoot', 'debug'],
            'complexity_indicators' => ['multiple concepts', 'trade-offs', 'best practices'],
            'score_threshold' => 6
        ],
        'expert' => [
            'keywords' => ['system design', 'distributed', 'high availability', 'fault tolerance', 'enterprise', 'complex', 'migration'],
            'complexity_indicators' => ['system-level', 'architectural decisions', 'deep understanding'],
            'score_threshold' => 9
        ]
    ];

    public function __construct()
    {
        $this->config = $this->loadConfig();
        $this->initializeStatistics();
    }

    private function loadConfig(): array
    {
        $configFile = __DIR__ . '/config.php';
        return file_exists($configFile) ? require $configFile : [];
    }

    private function initializeStatistics(): void
    {
        $this->statistics = [
            'total_questions' => 0,
            'by_difficulty' => [
                'basic' => 0,
                'intermediate' => 0,
                'advanced' => 0,
                'expert' => 0
            ],
            'by_category' => [],
            'by_subcategory' => [],
            'parsing_errors' => 0,
            'warnings' => []
        ];
    }

    public function parseFile(string $inputFile): array
    {
        if (!file_exists($inputFile)) {
            throw new Exception("Input file not found: {$inputFile}");
        }

        $content = file_get_contents($inputFile);
        $this->parsedQuestions = [];
        
        // Detect file format and parse accordingly
        if ($this->isMarkdownFormat($content)) {
            $this->parseMarkdownFormat($content);
        } else {
            $this->parseSimpleFormat($content);
        }

        $this->updateStatistics();
        
        return $this->parsedQuestions;
    }

    private function isMarkdownFormat(string $content): bool
    {
        // Check if content has markdown headings or numbered lists
        return preg_match('/^#+\s+/m', $content) || 
               preg_match('/^\d+\.\s+/m', $content);
    }

    private function parseMarkdownFormat(string $content): void
    {
        $lines = explode("\n", $content);
        $currentSection = null;
        $questionNumber = 0;

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }

            // Detect section headers
            if (preg_match('/^#+\s+(.+)/', $line, $matches)) {
                $currentSection = trim($matches[1]);
                continue;
            }

            // Detect numbered questions
            if (preg_match('/^(\d+)\.\s+(.+)/', $line, $matches)) {
                $questionNumber = (int)$matches[1];
                $questionText = trim($matches[2]);
                
                $this->addQuestion($questionText, $questionNumber, $currentSection, $lineNum + 1);
            }
            // Detect bullet point questions
            elseif (preg_match('/^[-*]\s+(.+)/', $line, $matches)) {
                $questionText = trim($matches[1]);
                $questionNumber++;
                
                $this->addQuestion($questionText, $questionNumber, $currentSection, $lineNum + 1);
            }
        }
    }

    private function parseSimpleFormat(string $content): void
    {
        $lines = explode("\n", $content);
        $questionNumber = 0;

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }

            // Skip lines that look like headers or separators
            if (preg_match('/^[=#-]{3,}$/', $line)) {
                continue;
            }

            $questionNumber++;
            
            // Remove leading numbers if present
            $questionText = preg_replace('/^\d+\.\s*/', '', $line);
            $questionText = preg_replace('/^[-*]\s*/', '', $questionText);
            
            $this->addQuestion($questionText, $questionNumber, null, $lineNum + 1);
        }
    }

    private function addQuestion(string $questionText, int $number, ?string $section, int $lineNumber): void
    {
        // Clean up question text
        $questionText = trim($questionText);
        
        if (strlen($questionText) < 10) {
            $this->statistics['warnings'][] = "Question too short at line {$lineNumber}: {$questionText}";
            return;
        }

        // Detect categories
        $categories = $this->detectCategories($questionText);
        
        // Detect subcategories
        $subcategories = $this->detectSubcategories($questionText);
        
        // Determine difficulty
        $difficulty = $this->determineDifficulty($questionText);
        
        // Calculate complexity score
        $complexityScore = $this->calculateComplexityScore($questionText);

        $question = [
            'id' => $number,
            'text' => $questionText,
            'section' => $section,
            'category' => $categories['primary'] ?? 'General',
            'categories' => $categories['all'] ?? [],
            'subcategory' => $subcategories['primary'] ?? null,
            'subcategories' => $subcategories['all'] ?? [],
            'difficulty' => $difficulty,
            'metadata' => [
                'line_number' => $lineNumber,
                'word_count' => str_word_count($questionText),
                'character_count' => strlen($questionText),
                'complexity_score' => $complexityScore,
                'has_code_requirement' => $this->hasCodeRequirement($questionText),
                'question_type' => $this->detectQuestionType($questionText),
                'estimated_answer_length' => $this->estimateAnswerLength($questionText, $difficulty),
                'tags' => $this->extractTags($questionText),
            ],
            'parsed_at' => date('Y-m-d H:i:s'),
        ];

        $this->parsedQuestions[] = $question;
    }

    private function detectCategories(string $text): array
    {
        $textLower = strtolower($text);
        $scores = [];

        foreach ($this->categoryKeywords as $category => $config) {
            $score = 0;
            
            foreach ($config['keywords'] as $keyword) {
                $keywordLower = strtolower($keyword);
                
                // Exact word boundary match
                if (preg_match('/\b' . preg_quote($keywordLower, '/') . '\b/', $textLower)) {
                    $score += $config['weight'] * 2;
                }
                // Partial match
                elseif (stripos($textLower, $keywordLower) !== false) {
                    $score += $config['weight'];
                }
            }
            
            if ($score > 0) {
                $scores[$category] = $score;
            }
        }

        // Sort by score descending
        arsort($scores);
        
        return [
            'primary' => !empty($scores) ? array_key_first($scores) : null,
            'all' => array_keys($scores),
            'scores' => $scores
        ];
    }

    private function detectSubcategories(string $text): array
    {
        $textLower = strtolower($text);
        $matches = [];

        foreach ($this->subcategoryKeywords as $subcategory => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($textLower, $keyword) !== false) {
                    $matches[] = $subcategory;
                    break;
                }
            }
        }

        return [
            'primary' => $matches[0] ?? null,
            'all' => array_unique($matches)
        ];
    }

    private function determineDifficulty(string $text): string
    {
        $textLower = strtolower($text);
        $scores = [
            'basic' => 0,
            'intermediate' => 0,
            'advanced' => 0,
            'expert' => 0
        ];

        foreach ($this->difficultyPatterns as $level => $config) {
            // Check keywords
            foreach ($config['keywords'] as $keyword) {
                if (stripos($textLower, $keyword) !== false) {
                    $scores[$level] += 2;
                }
            }
        }

        // Additional heuristics
        $wordCount = str_word_count($text);
        
        if ($wordCount < 8) {
            $scores['basic'] += 2;
        } elseif ($wordCount < 15) {
            $scores['intermediate'] += 1;
        } elseif ($wordCount < 25) {
            $scores['advanced'] += 1;
        } else {
            $scores['expert'] += 1;
        }

        // Check for code requirements
        if ($this->hasCodeRequirement($text)) {
            $scores['intermediate'] += 2;
            $scores['advanced'] += 1;
        }

        // Check for design/architecture keywords
        if (preg_match('/\b(design|architect|system|distributed|scale)\b/i', $text)) {
            $scores['advanced'] += 2;
            $scores['expert'] += 3;
        }

        // Return highest scoring difficulty
        arsort($scores);
        $difficulty = array_key_first($scores);
        
        // Default to intermediate if all scores are equal
        if ($scores['basic'] === $scores['intermediate'] && 
            $scores['intermediate'] === $scores['advanced'] && 
            $scores['advanced'] === $scores['expert']) {
            return 'intermediate';
        }

        return $difficulty;
    }

    private function calculateComplexityScore(string $text): int
    {
        $score = 0;
        
        // Word count factor
        $wordCount = str_word_count($text);
        $score += min(5, floor($wordCount / 5));
        
        // Technical terms
        $technicalTerms = ['implement', 'optimize', 'design', 'architecture', 'performance', 
                           'security', 'scale', 'distribute', 'migrate', 'integrate'];
        foreach ($technicalTerms as $term) {
            if (stripos($text, $term) !== false) {
                $score += 2;
            }
        }
        
        // Code requirement
        if ($this->hasCodeRequirement($text)) {
            $score += 3;
        }
        
        // Multiple concepts (indicated by conjunctions)
        $conjunctions = preg_match_all('/\b(and|or|with|using|between)\b/i', $text);
        $score += min(3, $conjunctions);
        
        return min(10, $score); // Cap at 10
    }

    private function hasCodeRequirement(string $text): bool
    {
        $codeKeywords = [
            'write', 'implement', 'create', 'develop', 'code', 'function', 
            'class', 'method', 'script', 'program', 'example code'
        ];
        
        foreach ($codeKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function detectQuestionType(string $text): string
    {
        $textLower = strtolower($text);
        
        if (preg_match('/^(what|who|when|where|which)/', $textLower)) {
            return 'definition';
        } elseif (preg_match('/^(how|explain|describe)/', $textLower)) {
            return 'explanation';
        } elseif (preg_match('/^(write|create|implement|develop|code)/', $textLower)) {
            return 'implementation';
        } elseif (preg_match('/^(compare|difference|versus|vs)/', $textLower)) {
            return 'comparison';
        } elseif (preg_match('/^(why|reason)/', $textLower)) {
            return 'reasoning';
        } elseif (preg_match('/^(design|architect)/', $textLower)) {
            return 'design';
        } elseif (preg_match('/^(debug|troubleshoot|fix)/', $textLower)) {
            return 'troubleshooting';
        } elseif (preg_match('/^(list|name|enumerate)/', $textLower)) {
            return 'enumeration';
        } else {
            return 'general';
        }
    }

    private function estimateAnswerLength(string $text, string $difficulty): string
    {
        $type = $this->detectQuestionType($text);
        
        // Base estimates
        $estimates = [
            'basic' => 'short',
            'intermediate' => 'medium',
            'advanced' => 'long',
            'expert' => 'very_long'
        ];
        
        $baseEstimate = $estimates[$difficulty];
        
        // Adjust based on question type
        if ($type === 'implementation') {
            return 'long'; // Code examples make answers longer
        } elseif ($type === 'definition') {
            return 'short';
        } elseif ($type === 'design') {
            return 'very_long';
        }
        
        return $baseEstimate;
    }

    private function extractTags(string $text): array
    {
        $tags = [];
        $textLower = strtolower($text);
        
        // Technical tags
        $technicalTags = [
            'security', 'performance', 'optimization', 'best-practices',
            'production', 'debugging', 'testing', 'deployment', 'architecture',
            'scalability', 'reliability', 'monitoring', 'caching'
        ];
        
        foreach ($technicalTags as $tag) {
            if (stripos($textLower, $tag) !== false) {
                $tags[] = $tag;
            }
        }
        
        return array_unique($tags);
    }

    private function updateStatistics(): void
    {
        $this->statistics['total_questions'] = count($this->parsedQuestions);
        
        foreach ($this->parsedQuestions as $question) {
            // Count by difficulty
            $difficulty = $question['difficulty'];
            $this->statistics['by_difficulty'][$difficulty]++;
            
            // Count by category
            $category = $question['category'];
            if (!isset($this->statistics['by_category'][$category])) {
                $this->statistics['by_category'][$category] = 0;
            }
            $this->statistics['by_category'][$category]++;
            
            // Count by subcategory
            foreach ($question['subcategories'] as $subcategory) {
                if (!isset($this->statistics['by_subcategory'][$subcategory])) {
                    $this->statistics['by_subcategory'][$subcategory] = 0;
                }
                $this->statistics['by_subcategory'][$subcategory]++;
            }
        }
        
        // Sort categories by count
        arsort($this->statistics['by_category']);
        arsort($this->statistics['by_subcategory']);
    }

    public function generateOutput(array $options = []): array
    {
        $format = $options['format'] ?? 'full';
        
        switch ($format) {
            case 'compact':
                return $this->generateCompactOutput();
            case 'categorized':
                return $this->generateCategorizedOutput();
            case 'full':
            default:
                return [
                    'metadata' => [
                        'generated_at' => date('Y-m-d H:i:s'),
                        'parser_version' => '1.0.0',
                        'total_questions' => $this->statistics['total_questions'],
                    ],
                    'statistics' => $this->statistics,
                    'questions' => $this->parsedQuestions
                ];
        }
    }

    private function generateCompactOutput(): array
    {
        return [
            'total' => $this->statistics['total_questions'],
            'questions' => array_map(function($q) {
                return [
                    'id' => $q['id'],
                    'text' => $q['text'],
                    'category' => $q['category'],
                    'difficulty' => $q['difficulty']
                ];
            }, $this->parsedQuestions)
        ];
    }

    private function generateCategorizedOutput(): array
    {
        $categorized = [];
        
        foreach ($this->parsedQuestions as $question) {
            $category = $question['category'];
            if (!isset($categorized[$category])) {
                $categorized[$category] = [];
            }
            $categorized[$category][] = $question;
        }
        
        return [
            'metadata' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'total_categories' => count($categorized),
            ],
            'statistics' => $this->statistics,
            'categories' => $categorized
        ];
    }

    public function exportToJson(string $outputFile, array $options = []): void
    {
        $output = $this->generateOutput($options);
        
        $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $json = json_encode($output, $jsonOptions);
        
        if ($json === false) {
            throw new Exception("Failed to encode JSON: " . json_last_error_msg());
        }
        
        file_put_contents($outputFile, $json);
    }

    public function exportToMarkdown(string $outputFile): void
    {
        $md = "# Parsed Interview Questions\n\n";
        $md .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $md .= "## Statistics\n\n";
        $md .= "- **Total Questions:** {$this->statistics['total_questions']}\n";
        $md .= "- **Basic:** {$this->statistics['by_difficulty']['basic']}\n";
        $md .= "- **Intermediate:** {$this->statistics['by_difficulty']['intermediate']}\n";
        $md .= "- **Advanced:** {$this->statistics['by_difficulty']['advanced']}\n";
        $md .= "- **Expert:** {$this->statistics['by_difficulty']['expert']}\n\n";
        
        $md .= "## Questions by Category\n\n";
        
        $byCategory = [];
        foreach ($this->parsedQuestions as $question) {
            $category = $question['category'];
            if (!isset($byCategory[$category])) {
                $byCategory[$category] = [];
            }
            $byCategory[$category][] = $question;
        }
        
        foreach ($byCategory as $category => $questions) {
            $md .= "### {$category} (" . count($questions) . " questions)\n\n";
            
            foreach ($questions as $question) {
                $difficulty = strtoupper(substr($question['difficulty'], 0, 1));
                $md .= "{$question['id']}. [{$difficulty}] {$question['text']}\n";
            }
            
            $md .= "\n";
        }
        
        file_put_contents($outputFile, $md);
    }

    public function getStatistics(): array
    {
        return $this->statistics;
    }

    public function printStatistics(): void
    {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "PARSING STATISTICS\n";
        echo str_repeat('=', 70) . "\n\n";
        
        echo "Total Questions: {$this->statistics['total_questions']}\n\n";
        
        echo "By Difficulty:\n";
        foreach ($this->statistics['by_difficulty'] as $level => $count) {
            $percentage = $this->statistics['total_questions'] > 0 
                ? round(($count / $this->statistics['total_questions']) * 100, 1) 
                : 0;
            echo sprintf("  %-15s: %4d (%5.1f%%)\n", ucfirst($level), $count, $percentage);
        }
        
        echo "\nTop Categories:\n";
        $topCategories = array_slice($this->statistics['by_category'], 0, 10, true);
        foreach ($topCategories as $category => $count) {
            $percentage = $this->statistics['total_questions'] > 0 
                ? round(($count / $this->statistics['total_questions']) * 100, 1) 
                : 0;
            echo sprintf("  %-20s: %4d (%5.1f%%)\n", $category, $count, $percentage);
        }
        
        if (!empty($this->statistics['warnings'])) {
            echo "\nWarnings: " . count($this->statistics['warnings']) . "\n";
            foreach (array_slice($this->statistics['warnings'], 0, 5) as $warning) {
                echo "  - {$warning}\n";
            }
        }
        
        echo "\n" . str_repeat('=', 70) . "\n\n";
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $scriptName = basename(__FILE__);
    
    if ($argc < 2) {
        echo "Usage: php {$scriptName} <input-file> [output-file] [options]\n\n";
        echo "Arguments:\n";
        echo "  input-file   Path to raw questions file (text or markdown)\n";
        echo "  output-file  Path to output JSON file (optional, default: parsed-questions.json)\n\n";
        echo "Options:\n";
        echo "  --format=full|compact|categorized  Output format (default: full)\n";
        echo "  --markdown=<file>                  Also export to markdown\n";
        echo "  --stats-only                       Only show statistics\n\n";
        echo "Examples:\n";
        echo "  php {$scriptName} raw-questions.txt\n";
        echo "  php {$scriptName} raw-questions.txt output.json --format=compact\n";
        echo "  php {$scriptName} questions.md parsed.json --markdown=report.md\n";
        exit(1);
    }
    
    $inputFile = $argv[1];
    $outputFile = $argv[2] ?? 'parsed-questions.json';
    
    // Parse options
    $options = ['format' => 'full'];
    $markdownFile = null;
    $statsOnly = false;
    
    for ($i = 2; $i < $argc; $i++) {
        if (strpos($argv[$i], '--format=') === 0) {
            $options['format'] = substr($argv[$i], 9);
        } elseif (strpos($argv[$i], '--markdown=') === 0) {
            $markdownFile = substr($argv[$i], 11);
        } elseif ($argv[$i] === '--stats-only') {
            $statsOnly = true;
        }
    }
    
    try {
        echo "Parsing questions from: {$inputFile}\n";
        
        $parser = new RawQuestionsParser();
        $parser->parseFile($inputFile);
        
        $parser->printStatistics();
        
        if (!$statsOnly) {
            $parser->exportToJson($outputFile, $options);
            echo "JSON output saved to: {$outputFile}\n";
            
            if ($markdownFile) {
                $parser->exportToMarkdown($markdownFile);
                echo "Markdown report saved to: {$markdownFile}\n";
            }
        }
        
        echo "\nParsing completed successfully!\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
