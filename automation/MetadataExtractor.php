<?php

namespace InterviewBank\Automation;

class MetadataExtractor
{
    private array $difficultyPatterns = [
        'basic' => [
            '/basic/i',
            '/fundamental/i',
            '/simple/i',
            '/introduction/i',
            '/getting started/i',
            '/⭐\s*$/m',
        ],
        'intermediate' => [
            '/intermediate/i',
            '/practical/i',
            '/⭐⭐\s*$/m',
        ],
        'advanced' => [
            '/advanced/i',
            '/complex/i',
            '/production/i',
            '/⭐⭐⭐\s*$/m',
        ],
        'expert' => [
            '/expert/i',
            '/system design/i',
            '/architecture/i',
            '/⭐⭐⭐⭐\s*$/m',
        ],
    ];

    private array $technologyPatterns = [
        'PHP' => '/\bphp\s*8|php\s*7|\bphp\b/i',
        'Laravel' => '/\blaravel\b/i',
        'JavaScript' => '/\bjavascript\b|\bjs\b/i',
        'TypeScript' => '/\btypescript\b|\bts\b/i',
        'React' => '/\breact\b/i',
        'Next.js' => '/\bnext\.?js\b/i',
        'Node.js' => '/\bnode\.?js\b/i',
        'Vue.js' => '/\bvue\.?js\b|\bvue\b/i',
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
        'GraphQL' => '/\bgraphql\b/i',
        'REST API' => '/\brest\s+api\b|\brestful\b/i',
        'WebSocket' => '/\bwebsocket\b/i',
        'RabbitMQ' => '/\brabbitmq\b/i',
        'Kafka' => '/\bkafka\b/i',
        'Elasticsearch' => '/\belasticsearch\b|\belastic\b/i',
        'Jenkins' => '/\bjenkins\b/i',
        'GitHub Actions' => '/\bgithub\s+actions\b/i',
        'GitLab CI' => '/\bgitlab\s+ci\b/i',
        'Istio' => '/\bistio\b/i',
        'Helm' => '/\bhelm\b/i',
        'ArgoCD' => '/\bargocd\b|\bargo\s+cd\b/i',
        'Prometheus' => '/\bprometheus\b/i',
        'Grafana' => '/\bgrafana\b/i',
        'ELK Stack' => '/\belk\b|elasticsearch.*logstash.*kibana/i',
        'OpenAI' => '/\bopenai\b|gpt-\d/i',
        'Claude' => '/\bclaude\b|anthropic/i',
        'LangChain' => '/\blangchain\b/i',
        'Pinecone' => '/\bpinecone\b/i',
        'Weaviate' => '/\bweaviate\b/i',
        'Vector DB' => '/\bvector\s+database\b|vector\s+db/i',
        'RAG' => '/\brag\b|retrieval.?augmented.?generation/i',
        'Razorpay' => '/\brazorpay\b/i',
        'Stripe' => '/\bstripe\b/i',
        'Payment Gateway' => '/\bpayment\s+gateway\b/i',
        'Webhook' => '/\bwebhook\b/i',
        'OAuth' => '/\boauth\b/i',
        'JWT' => '/\bjwt\b|json\s+web\s+token/i',
        'Security' => '/\bsecurity\b|\bauth/i',
        'PCI DSS' => '/\bpci.?dss\b/i',
        'OWASP' => '/\bowasp\b/i',
    ];

    public function extractFromFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $relativePath = $this->getRelativePath($filePath);

        return [
            'file_path' => $filePath,
            'relative_path' => $relativePath,
            'difficulty' => $this->extractDifficulty($content, $filePath),
            'technologies' => $this->extractTechnologies($content),
            'topics' => $this->extractTopics($content),
            'questions' => $this->extractQuestions($content),
            'code_examples' => $this->extractCodeExamples($content),
            'mermaid_diagrams' => $this->extractMermaidDiagrams($content),
            'metadata' => $this->extractFrontmatter($content),
            'word_count' => str_word_count($content),
            'line_count' => substr_count($content, "\n") + 1,
            'domain' => $this->extractDomain($relativePath),
            'subdomain' => $this->extractSubdomain($relativePath),
            'last_modified' => filemtime($filePath),
        ];
    }

    private function extractDifficulty(string $content, string $filePath): string
    {
        $fileName = basename($filePath);
        $scores = [
            'basic' => 0,
            'intermediate' => 0,
            'advanced' => 0,
            'expert' => 0,
        ];

        foreach ($this->difficultyPatterns as $level => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $fileName)) {
                    $scores[$level] += 3;
                }
                $scores[$level] += preg_match_all($pattern, $content);
            }
        }

        arsort($scores);
        $topLevel = array_key_first($scores);

        if ($scores[$topLevel] === 0) {
            return 'intermediate';
        }

        return $topLevel;
    }

    private function extractTechnologies(string $content): array
    {
        $technologies = [];

        foreach ($this->technologyPatterns as $tech => $pattern) {
            if (preg_match($pattern, $content)) {
                $count = preg_match_all($pattern, $content);
                $technologies[] = [
                    'name' => $tech,
                    'mentions' => $count,
                ];
            }
        }

        usort($technologies, fn ($a, $b) => $b['mentions'] <=> $a['mentions']);

        return $technologies;
    }

    private function extractTopics(string $content): array
    {
        $topics = [];

        preg_match_all('/^#{2,3}\s+(.+)$/m', $content, $matches);

        foreach ($matches[1] as $heading) {
            $heading = trim($heading);
            $heading = preg_replace('/\(Questions?\s+\d+-\d+\)/i', '', $heading);
            $heading = trim($heading);

            if (! empty($heading)) {
                $topics[] = $heading;
            }
        }

        return array_unique($topics);
    }

    private function extractQuestions(string $content): array
    {
        $questions = [];

        preg_match_all('/^(\d+)\.\s+(.+)$/m', $content, $numberedMatches, PREG_SET_ORDER);
        foreach ($numberedMatches as $match) {
            $questions[] = [
                'number' => (int) $match[1],
                'text' => trim($match[2]),
                'type' => 'numbered',
            ];
        }

        preg_match_all('/\*\*Q:\*\*\s+(.+?)(?=\*\*A:\*\*|\n\n|$)/s', $content, $qaMatches);
        foreach ($qaMatches[1] as $question) {
            $questions[] = [
                'text' => trim($question),
                'type' => 'qa_format',
            ];
        }

        return $questions;
    }

    private function extractCodeExamples(string $content): array
    {
        $examples = [];

        preg_match_all('/```(\w+)?\n(.*?)```/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $language = $match[1] ?? 'unknown';
            $code = $match[2];

            $examples[] = [
                'language' => $language,
                'lines' => substr_count($code, "\n") + 1,
                'characters' => strlen($code),
            ];
        }

        return $examples;
    }

    private function extractMermaidDiagrams(string $content): array
    {
        $diagrams = [];

        preg_match_all('/```mermaid\n(.*?)```/s', $content, $matches);

        foreach ($matches[1] as $diagram) {
            $firstLine = strtok(trim($diagram), "\n");
            $type = 'unknown';

            if (preg_match('/^(graph|flowchart|sequenceDiagram|classDiagram|stateDiagram|erDiagram|gantt|pie)/i', $firstLine, $typeMatch)) {
                $type = $typeMatch[1];
            }

            $diagrams[] = [
                'type' => $type,
                'lines' => substr_count($diagram, "\n") + 1,
            ];
        }

        return $diagrams;
    }

    private function extractFrontmatter(string $content): array
    {
        if (! preg_match('/^---\n(.*?)\n---/s', $content, $matches)) {
            return [];
        }

        $frontmatter = $matches[1];
        $metadata = [];

        foreach (explode("\n", $frontmatter) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $metadata[trim($key)] = trim($value);
            }
        }

        return $metadata;
    }

    private function getRelativePath(string $filePath): string
    {
        $cwd = getcwd();
        if (str_starts_with($filePath, $cwd)) {
            return substr($filePath, strlen($cwd) + 1);
        }

        return $filePath;
    }

    private function extractDomain(string $relativePath): string
    {
        if (preg_match('#interview-bank[/\\\\]([^/\\\\]+)[/\\\\]#', $relativePath, $matches)) {
            return $matches[1];
        }

        return 'unknown';
    }

    private function extractSubdomain(string $relativePath): string
    {
        $parts = preg_split('#[/\\\\]#', $relativePath);
        if (count($parts) >= 3 && $parts[0] === 'interview-bank') {
            return $parts[2] ?? '';
        }

        return '';
    }

    public function extractFromDirectory(string $directory): array
    {
        $results = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $filePath = $file->getPathname();
                try {
                    $results[] = $this->extractFromFile($filePath);
                } catch (\Exception $e) {
                    error_log("Error extracting metadata from {$filePath}: ".$e->getMessage());
                }
            }
        }

        return $results;
    }

    public function generateSummary(array $metadataCollection): array
    {
        $summary = [
            'total_files' => count($metadataCollection),
            'total_questions' => 0,
            'total_code_examples' => 0,
            'total_mermaid_diagrams' => 0,
            'total_word_count' => 0,
            'by_difficulty' => [],
            'by_technology' => [],
            'by_domain' => [],
            'by_language' => [],
        ];

        foreach ($metadataCollection as $metadata) {
            $summary['total_questions'] += count($metadata['questions']);
            $summary['total_code_examples'] += count($metadata['code_examples']);
            $summary['total_mermaid_diagrams'] += count($metadata['mermaid_diagrams']);
            $summary['total_word_count'] += $metadata['word_count'];

            $difficulty = $metadata['difficulty'];
            $summary['by_difficulty'][$difficulty] = ($summary['by_difficulty'][$difficulty] ?? 0) + 1;

            foreach ($metadata['technologies'] as $tech) {
                $techName = $tech['name'];
                if (! isset($summary['by_technology'][$techName])) {
                    $summary['by_technology'][$techName] = [
                        'files' => 0,
                        'mentions' => 0,
                    ];
                }
                $summary['by_technology'][$techName]['files']++;
                $summary['by_technology'][$techName]['mentions'] += $tech['mentions'];
            }

            $domain = $metadata['domain'];
            if (! isset($summary['by_domain'][$domain])) {
                $summary['by_domain'][$domain] = [
                    'files' => 0,
                    'questions' => 0,
                    'code_examples' => 0,
                ];
            }
            $summary['by_domain'][$domain]['files']++;
            $summary['by_domain'][$domain]['questions'] += count($metadata['questions']);
            $summary['by_domain'][$domain]['code_examples'] += count($metadata['code_examples']);

            foreach ($metadata['code_examples'] as $example) {
                $lang = $example['language'];
                if (! isset($summary['by_language'][$lang])) {
                    $summary['by_language'][$lang] = [
                        'count' => 0,
                        'total_lines' => 0,
                    ];
                }
                $summary['by_language'][$lang]['count']++;
                $summary['by_language'][$lang]['total_lines'] += $example['lines'];
            }
        }

        uasort($summary['by_technology'], fn ($a, $b) => $b['mentions'] <=> $a['mentions']);
        uasort($summary['by_language'], fn ($a, $b) => $b['count'] <=> $a['count']);

        return $summary;
    }
}
