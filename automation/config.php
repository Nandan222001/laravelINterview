<?php

return [
    'base_dir' => dirname(__DIR__) . '/interview-bank',
    
    'output_dir' => __DIR__ . '/output',
    
    'validation' => [
        'strict_mode' => false,
        'check_php_syntax' => true,
        'check_json_syntax' => true,
        'check_yaml_syntax' => function_exists('yaml_parse'),
        'check_links' => true,
        'validate_mermaid' => true,
    ],
    
    'metadata' => [
        'extract_difficulty' => true,
        'extract_technologies' => true,
        'extract_topics' => true,
        'extract_questions' => true,
        'extract_code_examples' => true,
        'extract_mermaid_diagrams' => true,
    ],
    
    'difficulty_patterns' => [
        'basic' => [
            '/basic/i',
            '/fundamental/i',
            '/simple/i',
            '/introduction/i',
            '/⭐\s*$/m'
        ],
        'intermediate' => [
            '/intermediate/i',
            '/practical/i',
            '/⭐⭐\s*$/m'
        ],
        'advanced' => [
            '/advanced/i',
            '/complex/i',
            '/production/i',
            '/⭐⭐⭐\s*$/m'
        ],
        'expert' => [
            '/expert/i',
            '/system design/i',
            '/architecture/i',
            '/⭐⭐⭐⭐\s*$/m'
        ]
    ],
    
    'cross_reference' => [
        'min_similarity_score' => 5,
        'strong_threshold' => 20,
        'moderate_threshold' => 10,
        'max_results' => 1000,
    ],
    
    'search_index' => [
        'include_full_content' => true,
        'max_questions_per_doc' => 100,
        'generate_compact' => true,
        'generate_lunr' => true,
        'generate_elasticsearch' => true,
    ],
    
    'dashboard' => [
        'generate_markdown' => true,
        'generate_json' => true,
        'generate_html' => true,
        'top_technologies_count' => 20,
        'top_topics_count' => 15,
    ],
    
    'file_patterns' => [
        'include' => ['*.md'],
        'exclude' => [
            'node_modules/**',
            'vendor/**',
            '.git/**',
        ]
    ],
    
    'technology_patterns' => [
        'PHP' => '/\bphp\s*8|php\s*7|\bphp\b/i',
        'Laravel' => '/\blaravel\b/i',
        'JavaScript' => '/\bjavascript\b|\bjs\b/i',
        'TypeScript' => '/\btypescript\b|\bts\b/i',
        'React' => '/\breact\b/i',
        'Next.js' => '/\bnext\.?js\b/i',
        'Node.js' => '/\bnode\.?js\b/i',
        'Python' => '/\bpython\b/i',
        'Go' => '/\bgolang\b|\bgo\b/i',
        'Docker' => '/\bdocker\b/i',
        'Kubernetes' => '/\bkubernetes\b|\bk8s\b/i',
        'AWS' => '/\baws\b|amazon web services/i',
        'Lambda' => '/\blambda\b|aws lambda/i',
        'Terraform' => '/\bterraform\b/i',
        'Redis' => '/\bredis\b/i',
        'PostgreSQL' => '/\bpostgresql\b|\bpostgres\b|\bpgsql\b/i',
        'MySQL' => '/\bmysql\b/i',
        'MongoDB' => '/\bmongodb\b|\bmongo\b/i',
        'Razorpay' => '/\brazorpay\b/i',
        'Stripe' => '/\bstripe\b/i',
    ],
    
    'keyword_categories' => [
        'payment_webhooks' => ['webhook', 'razorpay', 'stripe', 'payment', 'signature verification'],
        'serverless' => ['lambda', 'serverless', 'aws lambda', 'function as a service'],
        'database_optimization' => ['query optimization', 'indexing', 'n\+1', 'caching', 'redis'],
        'authentication' => ['jwt', 'oauth', 'authentication', 'authorization', 'token'],
        'docker_kubernetes' => ['docker', 'kubernetes', 'k8s', 'container', 'orchestration'],
        'security' => ['security', 'pci dss', 'owasp', 'encryption', 'vulnerability'],
        'real_time' => ['websocket', 'real-?time', 'socket\.io', 'server-sent events'],
        'llm' => ['openai', 'claude', 'gpt', 'language model', 'llm'],
        'rag' => ['rag', 'retrieval.*augmented.*generation', 'semantic search'],
    ],
    
    'output_formats' => [
        'markdown' => true,
        'json' => true,
        'html' => true,
    ],
    
    'performance' => [
        'memory_limit' => '512M',
        'max_execution_time' => 300,
        'enable_caching' => false,
    ],
];
