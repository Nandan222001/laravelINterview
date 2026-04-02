<?php

namespace InterviewBank\Automation;

class CrossReferenceMapper
{
    private array $keywordPatterns = [
        'payment_webhooks' => ['webhook', 'razorpay', 'stripe', 'payment', 'signature verification'],
        'serverless' => ['lambda', 'serverless', 'aws lambda', 'function as a service'],
        'database_optimization' => ['query optimization', 'indexing', 'n\+1', 'caching', 'redis'],
        'authentication' => ['jwt', 'oauth', 'authentication', 'authorization', 'token'],
        'docker_kubernetes' => ['docker', 'kubernetes', 'k8s', 'container', 'orchestration'],
        'terraform' => ['terraform', 'infrastructure as code', 'iac', 'provisioning'],
        'cicd' => ['jenkins', 'github actions', 'gitlab ci', 'pipeline', 'deployment'],
        'monitoring' => ['prometheus', 'grafana', 'monitoring', 'observability', 'metrics'],
        'api_design' => ['rest api', 'graphql', 'api design', 'endpoint'],
        'security' => ['security', 'pci dss', 'owasp', 'encryption', 'vulnerability'],
        'real_time' => ['websocket', 'real-?time', 'socket\.io', 'server-sent events'],
        'vector_db' => ['vector database', 'pinecone', 'weaviate', 'embeddings', 'semantic search'],
        'llm' => ['openai', 'claude', 'gpt', 'language model', 'llm'],
        'rag' => ['rag', 'retrieval.*augmented.*generation', 'semantic search'],
        'react' => ['react', 'next\.?js', 'hooks', 'components'],
        'service_mesh' => ['istio', 'service mesh', 'mtls', 'traffic management']
    ];

    public function mapCrossReferences(array $metadataCollection): array
    {
        $references = [];
        
        foreach ($metadataCollection as $sourceIndex => $sourceMetadata) {
            $sourceFile = $sourceMetadata['relative_path'];
            $sourceDomain = $sourceMetadata['domain'];
            $sourceContent = $this->getContentForAnalysis($sourceMetadata);

            foreach ($metadataCollection as $targetIndex => $targetMetadata) {
                if ($sourceIndex === $targetIndex) continue;
                
                $targetFile = $targetMetadata['relative_path'];
                $targetDomain = $targetMetadata['domain'];
                
                if ($sourceDomain === $targetDomain) continue;

                $similarity = $this->calculateSimilarity($sourceMetadata, $targetMetadata);
                
                if ($similarity['score'] > 0) {
                    $references[] = [
                        'source_file' => $sourceFile,
                        'source_domain' => $sourceDomain,
                        'target_file' => $targetFile,
                        'target_domain' => $targetDomain,
                        'similarity_score' => $similarity['score'],
                        'common_topics' => $similarity['common_topics'],
                        'common_technologies' => $similarity['common_technologies'],
                        'relationship_type' => $this->determineRelationshipType($similarity),
                        'suggested_links' => $this->generateSuggestedLinks($sourceMetadata, $targetMetadata, $similarity)
                    ];
                }
            }
        }

        usort($references, fn($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);

        return $references;
    }

    private function getContentForAnalysis(array $metadata): string
    {
        $content = '';
        
        foreach ($metadata['topics'] as $topic) {
            $content .= strtolower($topic) . ' ';
        }
        
        foreach ($metadata['technologies'] as $tech) {
            $content .= strtolower($tech['name']) . ' ';
        }
        
        foreach ($metadata['questions'] as $question) {
            $content .= strtolower($question['text'] ?? '') . ' ';
        }
        
        return $content;
    }

    private function calculateSimilarity(array $source, array $target): array
    {
        $commonTechnologies = $this->findCommonTechnologies($source, $target);
        $commonTopics = $this->findCommonTopics($source, $target);
        $keywordMatches = $this->findKeywordMatches($source, $target);
        
        $techScore = count($commonTechnologies) * 2;
        $topicScore = count($commonTopics) * 3;
        $keywordScore = count($keywordMatches) * 5;
        
        $totalScore = $techScore + $topicScore + $keywordScore;
        
        return [
            'score' => $totalScore,
            'common_technologies' => $commonTechnologies,
            'common_topics' => $commonTopics,
            'keyword_matches' => $keywordMatches
        ];
    }

    private function findCommonTechnologies(array $source, array $target): array
    {
        $sourceTechs = array_map(fn($t) => $t['name'], $source['technologies']);
        $targetTechs = array_map(fn($t) => $t['name'], $target['technologies']);
        
        return array_values(array_intersect($sourceTechs, $targetTechs));
    }

    private function findCommonTopics(array $source, array $target): array
    {
        $sourceTopics = array_map('strtolower', $source['topics']);
        $targetTopics = array_map('strtolower', $target['topics']);
        
        $exactMatches = array_intersect($sourceTopics, $targetTopics);
        $partialMatches = [];
        
        foreach ($sourceTopics as $sTopic) {
            foreach ($targetTopics as $tTopic) {
                if ($sTopic !== $tTopic && $this->hasPartialMatch($sTopic, $tTopic)) {
                    $partialMatches[] = "$sTopic ↔ $tTopic";
                }
            }
        }
        
        return array_merge(array_values($exactMatches), $partialMatches);
    }

    private function hasPartialMatch(string $topic1, string $topic2): bool
    {
        $words1 = preg_split('/\s+/', strtolower($topic1));
        $words2 = preg_split('/\s+/', strtolower($topic2));
        
        $commonWords = array_intersect($words1, $words2);
        
        return count($commonWords) >= 2;
    }

    private function findKeywordMatches(array $source, array $target): array
    {
        $sourceContent = $this->getContentForAnalysis($source);
        $targetContent = $this->getContentForAnalysis($target);
        
        $matches = [];
        
        foreach ($this->keywordPatterns as $category => $patterns) {
            $sourceMatches = false;
            $targetMatches = false;
            
            foreach ($patterns as $pattern) {
                if (preg_match("/$pattern/i", $sourceContent)) {
                    $sourceMatches = true;
                }
                if (preg_match("/$pattern/i", $targetContent)) {
                    $targetMatches = true;
                }
            }
            
            if ($sourceMatches && $targetMatches) {
                $matches[] = str_replace('_', ' ', ucwords($category, '_'));
            }
        }
        
        return $matches;
    }

    private function determineRelationshipType(array $similarity): string
    {
        $score = $similarity['score'];
        
        if ($score >= 20) {
            return 'strong';
        } elseif ($score >= 10) {
            return 'moderate';
        } elseif ($score >= 5) {
            return 'weak';
        } else {
            return 'minimal';
        }
    }

    private function generateSuggestedLinks(array $source, array $target, array $similarity): array
    {
        $links = [];
        
        foreach ($similarity['keyword_matches'] as $match) {
            $links[] = [
                'reason' => "Both cover {$match}",
                'type' => 'concept_overlap'
            ];
        }
        
        foreach ($similarity['common_technologies'] as $tech) {
            $links[] = [
                'reason' => "Both use {$tech}",
                'type' => 'technology_overlap'
            ];
        }
        
        if (count($similarity['common_topics']) > 0) {
            $topicList = implode(', ', array_slice($similarity['common_topics'], 0, 3));
            $links[] = [
                'reason' => "Related topics: {$topicList}",
                'type' => 'topic_overlap'
            ];
        }
        
        return $links;
    }

    public function generateCrossReferenceReport(array $references): string
    {
        $report = "# Cross-Reference Mapping Report\n\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $report .= "This report identifies related questions and topics across different domains.\n\n";
        
        $report .= "## Summary\n\n";
        $report .= "- Total cross-references found: " . count($references) . "\n";
        
        $byRelationType = $this->groupByRelationType($references);
        foreach ($byRelationType as $type => $refs) {
            $report .= "- " . ucfirst($type) . " relationships: " . count($refs) . "\n";
        }
        $report .= "\n";
        
        $report .= "## Top Cross-References\n\n";
        $report .= $this->generateTopReferencesTable(array_slice($references, 0, 30));
        
        $report .= "## Cross-References by Domain Pair\n\n";
        $report .= $this->generateDomainPairSection($references);
        
        $report .= "## Specific Examples\n\n";
        $report .= $this->generateExamplesSection($references);
        
        return $report;
    }

    private function groupByRelationType(array $references): array
    {
        $grouped = [];
        foreach ($references as $ref) {
            $type = $ref['relationship_type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $ref;
        }
        return $grouped;
    }

    private function generateTopReferencesTable(array $references): string
    {
        $output = "| Source Domain | Target Domain | Score | Common Topics | Technologies |\n";
        $output .= "|---------------|---------------|-------|---------------|---------------|\n";
        
        foreach ($references as $ref) {
            $topics = implode(', ', array_slice($ref['common_topics'], 0, 2));
            $techs = implode(', ', array_slice($ref['common_technologies'], 0, 3));
            
            $output .= sprintf("| %s | %s | %d | %s | %s |\n",
                $this->formatDomain($ref['source_domain']),
                $this->formatDomain($ref['target_domain']),
                $ref['similarity_score'],
                $topics ?: 'N/A',
                $techs ?: 'N/A'
            );
        }
        $output .= "\n";
        
        return $output;
    }

    private function generateDomainPairSection(array $references): string
    {
        $pairs = [];
        
        foreach ($references as $ref) {
            $pairKey = $ref['source_domain'] . ' → ' . $ref['target_domain'];
            if (!isset($pairs[$pairKey])) {
                $pairs[$pairKey] = [];
            }
            $pairs[$pairKey][] = $ref;
        }
        
        arsort($pairs);
        
        $output = "";
        foreach (array_slice($pairs, 0, 10, true) as $pairKey => $refs) {
            [$source, $target] = explode(' → ', $pairKey);
            $output .= "### " . $this->formatDomain($source) . " ↔ " . $this->formatDomain($target) . "\n\n";
            $output .= "**" . count($refs) . " cross-references found**\n\n";
            
            $strongRefs = array_filter($refs, fn($r) => $r['relationship_type'] === 'strong');
            if (!empty($strongRefs)) {
                $output .= "Strong connections:\n";
                foreach (array_slice($strongRefs, 0, 5) as $ref) {
                    $output .= "- " . basename($ref['source_file']) . " ↔ " . basename($ref['target_file']) . "\n";
                    foreach (array_slice($ref['suggested_links'], 0, 2) as $link) {
                        $output .= "  - " . $link['reason'] . "\n";
                    }
                }
                $output .= "\n";
            }
        }
        
        return $output;
    }

    private function generateExamplesSection(array $references): string
    {
        $output = "### Example: Razorpay Webhooks (PHP/Laravel) → Lambda Processing (Serverless)\n\n";
        
        $webhookLambdaRefs = array_filter($references, function($ref) {
            return (str_contains($ref['source_domain'], 'php-laravel') || 
                    str_contains($ref['source_domain'], 'payment')) &&
                   (str_contains($ref['target_domain'], 'serverless') || 
                    str_contains($ref['target_domain'], 'lambda'));
        });
        
        if (!empty($webhookLambdaRefs)) {
            $topRef = reset($webhookLambdaRefs);
            $output .= "**Connection:** Both domains cover webhook processing patterns\n\n";
            $output .= "**Common Concepts:**\n";
            foreach ($topRef['suggested_links'] as $link) {
                $output .= "- " . $link['reason'] . "\n";
            }
            $output .= "\n";
        }
        
        $output .= "### Example: Kubernetes (DevOps) → Database Optimization\n\n";
        
        $k8sDbRefs = array_filter($references, function($ref) {
            return str_contains($ref['source_domain'], 'devops') &&
                   str_contains($ref['target_domain'], 'database');
        });
        
        if (!empty($k8sDbRefs)) {
            $topRef = reset($k8sDbRefs);
            $output .= "**Connection:** Infrastructure and database performance overlap\n\n";
            $output .= "**Common Concepts:**\n";
            foreach (array_slice($topRef['suggested_links'], 0, 3) as $link) {
                $output .= "- " . $link['reason'] . "\n";
            }
            $output .= "\n";
        }
        
        return $output;
    }

    private function formatDomain(string $domain): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $domain));
    }

    public function generateJsonMapping(array $references): string
    {
        $mapping = [
            'generated_at' => date('c'),
            'total_references' => count($references),
            'references' => array_map(function($ref) {
                return [
                    'source' => [
                        'file' => $ref['source_file'],
                        'domain' => $ref['source_domain']
                    ],
                    'target' => [
                        'file' => $ref['target_file'],
                        'domain' => $ref['target_domain']
                    ],
                    'similarity_score' => $ref['similarity_score'],
                    'relationship_type' => $ref['relationship_type'],
                    'common_topics' => $ref['common_topics'],
                    'common_technologies' => $ref['common_technologies'],
                    'suggested_links' => $ref['suggested_links']
                ];
            }, $references)
        ];
        
        return json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function generateNetworkGraph(array $references): string
    {
        $output = "```mermaid\n";
        $output .= "graph TD\n";
        
        $domains = [];
        foreach ($references as $ref) {
            $domains[$ref['source_domain']] = true;
            $domains[$ref['target_domain']] = true;
        }
        
        foreach (array_keys($domains) as $index => $domain) {
            $nodeId = 'D' . $index;
            $label = $this->formatDomain($domain);
            $output .= "    {$nodeId}[\"{$label}\"]\n";
        }
        
        $domainToId = array_flip(array_keys($domains));
        $domainToId = array_map(fn($i) => 'D' . $i, $domainToId);
        
        $addedConnections = [];
        foreach (array_slice($references, 0, 20) as $ref) {
            if ($ref['relationship_type'] !== 'strong' && $ref['relationship_type'] !== 'moderate') {
                continue;
            }
            
            $sourceId = $domainToId[$ref['source_domain']];
            $targetId = $domainToId[$ref['target_domain']];
            $connectionKey = min($sourceId, $targetId) . '-' . max($sourceId, $targetId);
            
            if (!isset($addedConnections[$connectionKey])) {
                $style = $ref['relationship_type'] === 'strong' ? '===' : '---';
                $output .= "    {$sourceId} {$style} {$targetId}\n";
                $addedConnections[$connectionKey] = true;
            }
        }
        
        $output .= "```\n";
        
        return $output;
    }
}
