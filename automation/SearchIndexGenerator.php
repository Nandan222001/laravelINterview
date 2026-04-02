<?php

namespace InterviewBank\Automation;

class SearchIndexGenerator
{
    public function generateSearchIndex(array $metadataCollection): array
    {
        $index = [
            'generated_at' => date('c'),
            'version' => '1.0',
            'total_documents' => count($metadataCollection),
            'documents' => [],
            'domains' => [],
            'technologies' => [],
            'topics' => [],
            'difficulty_levels' => []
        ];

        foreach ($metadataCollection as $metadata) {
            $doc = $this->createDocumentEntry($metadata);
            $index['documents'][] = $doc;

            $this->updateDomainIndex($index['domains'], $metadata);
            $this->updateTechnologyIndex($index['technologies'], $metadata);
            $this->updateTopicIndex($index['topics'], $metadata);
            $this->updateDifficultyIndex($index['difficulty_levels'], $metadata);
        }

        $index['statistics'] = $this->generateStatistics($index);

        return $index;
    }

    private function createDocumentEntry(array $metadata): array
    {
        return [
            'id' => $this->generateDocumentId($metadata['file_path']),
            'title' => $this->extractTitle($metadata),
            'path' => $metadata['relative_path'],
            'domain' => $metadata['domain'],
            'subdomain' => $metadata['subdomain'],
            'difficulty' => $metadata['difficulty'],
            'technologies' => array_map(fn($t) => $t['name'], $metadata['technologies']),
            'topics' => $metadata['topics'],
            'question_count' => count($metadata['questions']),
            'code_example_count' => count($metadata['code_examples']),
            'diagram_count' => count($metadata['mermaid_diagrams']),
            'word_count' => $metadata['word_count'],
            'last_modified' => date('c', $metadata['last_modified']),
            'questions' => $this->extractQuestionSummaries($metadata['questions']),
            'searchable_content' => $this->generateSearchableContent($metadata)
        ];
    }

    private function generateDocumentId(string $filePath): string
    {
        return md5($filePath);
    }

    private function extractTitle(array $metadata): string
    {
        $fileName = basename($metadata['file_path'], '.md');
        
        $fileName = preg_replace('/^\d+-/', '', $fileName);
        $fileName = preg_replace('/^(questions?|index|readme)/i', '', $fileName);
        
        $title = ucwords(str_replace(['-', '_'], ' ', $fileName));
        
        if (empty(trim($title)) && !empty($metadata['topics'])) {
            $title = $metadata['topics'][0];
        }
        
        if (empty(trim($title))) {
            $title = basename($metadata['file_path']);
        }
        
        return trim($title);
    }

    private function extractQuestionSummaries(array $questions): array
    {
        return array_slice(array_map(function($q) {
            $text = $q['text'] ?? '';
            if (strlen($text) > 150) {
                $text = substr($text, 0, 147) . '...';
            }
            return [
                'number' => $q['number'] ?? null,
                'text' => $text,
                'type' => $q['type'] ?? 'unknown'
            ];
        }, $questions), 0, 100);
    }

    private function generateSearchableContent(array $metadata): string
    {
        $content = [];
        
        $content[] = $this->extractTitle($metadata);
        $content[] = $metadata['domain'];
        $content[] = $metadata['subdomain'];
        
        foreach ($metadata['topics'] as $topic) {
            $content[] = $topic;
        }
        
        foreach ($metadata['technologies'] as $tech) {
            $content[] = $tech['name'];
        }
        
        foreach ($metadata['questions'] as $question) {
            $content[] = $question['text'] ?? '';
        }
        
        return strtolower(implode(' ', $content));
    }

    private function updateDomainIndex(array &$domains, array $metadata): void
    {
        $domain = $metadata['domain'];
        
        if (!isset($domains[$domain])) {
            $domains[$domain] = [
                'name' => $domain,
                'display_name' => $this->formatDomainName($domain),
                'document_count' => 0,
                'question_count' => 0,
                'technologies' => [],
                'difficulty_distribution' => [
                    'basic' => 0,
                    'intermediate' => 0,
                    'advanced' => 0,
                    'expert' => 0
                ]
            ];
        }
        
        $domains[$domain]['document_count']++;
        $domains[$domain]['question_count'] += count($metadata['questions']);
        $domains[$domain]['difficulty_distribution'][$metadata['difficulty']]++;
        
        foreach ($metadata['technologies'] as $tech) {
            $techName = $tech['name'];
            if (!in_array($techName, $domains[$domain]['technologies'])) {
                $domains[$domain]['technologies'][] = $techName;
            }
        }
    }

    private function updateTechnologyIndex(array &$technologies, array $metadata): void
    {
        foreach ($metadata['technologies'] as $tech) {
            $techName = $tech['name'];
            
            if (!isset($technologies[$techName])) {
                $technologies[$techName] = [
                    'name' => $techName,
                    'document_count' => 0,
                    'total_mentions' => 0,
                    'domains' => [],
                    'related_technologies' => []
                ];
            }
            
            $technologies[$techName]['document_count']++;
            $technologies[$techName]['total_mentions'] += $tech['mentions'];
            
            $domain = $metadata['domain'];
            if (!isset($technologies[$techName]['domains'][$domain])) {
                $technologies[$techName]['domains'][$domain] = 0;
            }
            $technologies[$techName]['domains'][$domain]++;
            
            foreach ($metadata['technologies'] as $relatedTech) {
                if ($relatedTech['name'] !== $techName) {
                    $technologies[$techName]['related_technologies'][$relatedTech['name']] = 
                        ($technologies[$techName]['related_technologies'][$relatedTech['name']] ?? 0) + 1;
                }
            }
        }
    }

    private function updateTopicIndex(array &$topics, array $metadata): void
    {
        foreach ($metadata['topics'] as $topic) {
            $topicKey = strtolower($topic);
            
            if (!isset($topics[$topicKey])) {
                $topics[$topicKey] = [
                    'name' => $topic,
                    'document_count' => 0,
                    'domains' => []
                ];
            }
            
            $topics[$topicKey]['document_count']++;
            
            $domain = $metadata['domain'];
            if (!in_array($domain, $topics[$topicKey]['domains'])) {
                $topics[$topicKey]['domains'][] = $domain;
            }
        }
    }

    private function updateDifficultyIndex(array &$difficultyLevels, array $metadata): void
    {
        $difficulty = $metadata['difficulty'];
        
        if (!isset($difficultyLevels[$difficulty])) {
            $difficultyLevels[$difficulty] = [
                'level' => $difficulty,
                'document_count' => 0,
                'question_count' => 0,
                'domains' => []
            ];
        }
        
        $difficultyLevels[$difficulty]['document_count']++;
        $difficultyLevels[$difficulty]['question_count'] += count($metadata['questions']);
        
        $domain = $metadata['domain'];
        if (!isset($difficultyLevels[$difficulty]['domains'][$domain])) {
            $difficultyLevels[$difficulty]['domains'][$domain] = 0;
        }
        $difficultyLevels[$difficulty]['domains'][$domain]++;
    }

    private function generateStatistics(array $index): array
    {
        return [
            'total_questions' => array_sum(array_map(fn($d) => $d['question_count'], $index['documents'])),
            'total_code_examples' => array_sum(array_map(fn($d) => $d['code_example_count'], $index['documents'])),
            'total_diagrams' => array_sum(array_map(fn($d) => $d['diagram_count'], $index['documents'])),
            'domain_count' => count($index['domains']),
            'technology_count' => count($index['technologies']),
            'topic_count' => count($index['topics'])
        ];
    }

    private function formatDomainName(string $domain): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $domain));
    }

    public function generateJsonIndex(array $metadataCollection): string
    {
        $index = $this->generateSearchIndex($metadataCollection);
        return json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function generateCompactJsonIndex(array $metadataCollection): string
    {
        $index = $this->generateSearchIndex($metadataCollection);
        
        foreach ($index['documents'] as &$doc) {
            unset($doc['searchable_content']);
            $doc['questions'] = array_slice($doc['questions'], 0, 10);
        }
        
        return json_encode($index, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function generateLunrIndex(array $metadataCollection): string
    {
        $documents = [];
        
        foreach ($metadataCollection as $metadata) {
            $documents[] = [
                'id' => $this->generateDocumentId($metadata['file_path']),
                'title' => $this->extractTitle($metadata),
                'domain' => $metadata['domain'],
                'difficulty' => $metadata['difficulty'],
                'technologies' => implode(' ', array_map(fn($t) => $t['name'], $metadata['technologies'])),
                'topics' => implode(' ', $metadata['topics']),
                'content' => $this->generateSearchableContent($metadata)
            ];
        }
        
        return json_encode([
            'documents' => $documents,
            'config' => [
                'fields' => [
                    ['name' => 'title', 'boost' => 10],
                    ['name' => 'domain', 'boost' => 5],
                    ['name' => 'topics', 'boost' => 8],
                    ['name' => 'technologies', 'boost' => 7],
                    ['name' => 'content', 'boost' => 1]
                ]
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function generateElasticsearchMapping(): string
    {
        $mapping = [
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'title' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => [
                            'keyword' => ['type' => 'keyword']
                        ]
                    ],
                    'path' => ['type' => 'keyword'],
                    'domain' => ['type' => 'keyword'],
                    'subdomain' => ['type' => 'keyword'],
                    'difficulty' => ['type' => 'keyword'],
                    'technologies' => ['type' => 'keyword'],
                    'topics' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => [
                            'keyword' => ['type' => 'keyword']
                        ]
                    ],
                    'question_count' => ['type' => 'integer'],
                    'code_example_count' => ['type' => 'integer'],
                    'diagram_count' => ['type' => 'integer'],
                    'word_count' => ['type' => 'integer'],
                    'last_modified' => ['type' => 'date'],
                    'questions' => [
                        'type' => 'nested',
                        'properties' => [
                            'number' => ['type' => 'integer'],
                            'text' => ['type' => 'text', 'analyzer' => 'standard'],
                            'type' => ['type' => 'keyword']
                        ]
                    ],
                    'searchable_content' => [
                        'type' => 'text',
                        'analyzer' => 'standard'
                    ]
                ]
            ],
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 1,
                'analysis' => [
                    'analyzer' => [
                        'custom_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'stop', 'snowball']
                        ]
                    ]
                ]
            ]
        ];
        
        return json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function generateSearchInterface(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Bank Search</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
               background: #f5f7fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                  color: white; padding: 40px 20px; text-align: center; }
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .search-box { background: white; padding: 30px; border-radius: 8px; 
                      box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .search-input { width: 100%; padding: 15px; font-size: 16px; border: 2px solid #e0e0e0; 
                        border-radius: 6px; transition: border-color 0.3s; }
        .search-input:focus { outline: none; border-color: #667eea; }
        .filters { display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; margin-bottom: 5px; font-size: 14px; 
                              color: #555; font-weight: 500; }
        .filter-select { width: 100%; padding: 10px; border: 1px solid #ddd; 
                         border-radius: 4px; font-size: 14px; }
        .results { display: grid; gap: 20px; }
        .result-card { background: white; padding: 20px; border-radius: 8px; 
                       box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .result-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
        .result-title { font-size: 20px; color: #2c3e50; margin-bottom: 10px; }
        .result-meta { display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 4px; 
                 font-size: 12px; font-weight: 600; }
        .badge-domain { background: #e3f2fd; color: #1976d2; }
        .badge-difficulty { background: #fff3e0; color: #e65100; }
        .badge-tech { background: #f3e5f5; color: #7b1fa2; }
        .result-stats { color: #777; font-size: 14px; }
        .no-results { text-align: center; padding: 60px 20px; }
        .no-results img { width: 200px; opacity: 0.5; }
        .no-results p { margin-top: 20px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔍 Interview Bank Search</h1>
        <p>Search across 3500+ interview questions and code examples</p>
    </div>
    
    <div class="container">
        <div class="search-box">
            <input type="text" class="search-input" id="searchInput" 
                   placeholder="Search by topic, technology, keyword...">
            
            <div class="filters">
                <div class="filter-group">
                    <label for="domainFilter">Domain</label>
                    <select class="filter-select" id="domainFilter">
                        <option value="">All Domains</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="difficultyFilter">Difficulty</label>
                    <select class="filter-select" id="difficultyFilter">
                        <option value="">All Levels</option>
                        <option value="basic">⭐ Basic</option>
                        <option value="intermediate">⭐⭐ Intermediate</option>
                        <option value="advanced">⭐⭐⭐ Advanced</option>
                        <option value="expert">⭐⭐⭐⭐ Expert</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="technologyFilter">Technology</label>
                    <select class="filter-select" id="technologyFilter">
                        <option value="">All Technologies</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="results" id="results">
            <div class="no-results">
                <p>Enter a search query to find relevant questions and topics</p>
            </div>
        </div>
    </div>
    
    <script>
        let searchIndex = null;
        
        async function loadSearchIndex() {
            try {
                const response = await fetch('search-index.json');
                searchIndex = await response.json();
                populateFilters();
            } catch (error) {
                console.error('Failed to load search index:', error);
            }
        }
        
        function populateFilters() {
            const domainFilter = document.getElementById('domainFilter');
            const technologyFilter = document.getElementById('technologyFilter');
            
            Object.keys(searchIndex.domains).forEach(domain => {
                const option = document.createElement('option');
                option.value = domain;
                option.textContent = searchIndex.domains[domain].display_name;
                domainFilter.appendChild(option);
            });
            
            Object.keys(searchIndex.technologies).forEach(tech => {
                const option = document.createElement('option');
                option.value = tech;
                option.textContent = tech;
                technologyFilter.appendChild(option);
            });
        }
        
        function performSearch() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const domain = document.getElementById('domainFilter').value;
            const difficulty = document.getElementById('difficultyFilter').value;
            const technology = document.getElementById('technologyFilter').value;
            
            if (!searchIndex) return;
            
            let results = searchIndex.documents.filter(doc => {
                let matches = true;
                
                if (query && !doc.searchable_content.includes(query)) {
                    matches = false;
                }
                
                if (domain && doc.domain !== domain) {
                    matches = false;
                }
                
                if (difficulty && doc.difficulty !== difficulty) {
                    matches = false;
                }
                
                if (technology && !doc.technologies.includes(technology)) {
                    matches = false;
                }
                
                return matches;
            });
            
            displayResults(results);
        }
        
        function displayResults(results) {
            const container = document.getElementById('results');
            
            if (results.length === 0) {
                container.innerHTML = '<div class="no-results"><p>No results found</p></div>';
                return;
            }
            
            container.innerHTML = results.slice(0, 50).map(doc => `
                <div class="result-card">
                    <div class="result-title">\${doc.title}</div>
                    <div class="result-meta">
                        <span class="badge badge-domain">\${doc.domain}</span>
                        <span class="badge badge-difficulty">\${doc.difficulty}</span>
                        \${doc.technologies.slice(0, 3).map(tech => 
                            `<span class="badge badge-tech">\${tech}</span>`
                        ).join('')}
                    </div>
                    <div class="result-stats">
                        \${doc.question_count} questions · 
                        \${doc.code_example_count} code examples · 
                        \${doc.diagram_count} diagrams
                    </div>
                </div>
            `).join('');
        }
        
        document.getElementById('searchInput').addEventListener('input', performSearch);
        document.getElementById('domainFilter').addEventListener('change', performSearch);
        document.getElementById('difficultyFilter').addEventListener('change', performSearch);
        document.getElementById('technologyFilter').addEventListener('change', performSearch);
        
        loadSearchIndex();
    </script>
</body>
</html>
HTML;
    }
}
